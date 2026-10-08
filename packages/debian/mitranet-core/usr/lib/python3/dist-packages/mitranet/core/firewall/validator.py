"""
MitraNet Firewall Semantic & Security Validator.
Phase 3A: Validates IP addresses, CIDR ranges, port numbers, interface names,
character injection prevention, and anti-lockout management protection.
"""

import ipaddress
import re
from typing import List, Optional, Set
from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallZone,
    FirewallFamily,
    FirewallProtocol,
    FirewallAction,
    FirewallDirection,
    NatRule,
    NatType,
)
from mitranet.core.firewall.errors import (
    FirewallValidationError,
    FirewallSecurityViolationError,
)

# Safe interface name pattern (Linux kernel device name rules)
INTERFACE_PATTERN = re.compile(r"^[a-zA-Z0-9_.\-]+$")
# Safe identifier pattern
IDENTIFIER_PATTERN = re.compile(r"^[a-zA-Z0-9_\-]+$")
# Dangerous shell characters
DANGEROUS_CHARS = set(";;|&`$()\\\"'\n\r\t{}[]<>~*?")


class FirewallValidator:
    """Rigorous validator for firewall rules, tables, zones, and security invariants."""

    @classmethod
    def validate_injection_safety(cls, value: str, field_name: str) -> None:
        """Ensures string contains zero shell metacharacters or control bytes."""
        if not value:
            return
        if any(c in DANGEROUS_CHARS for c in value):
            raise FirewallValidationError(
                f"Field '{field_name}' contains illegal or dangerous shell characters: {value!r}"
            )

    @classmethod
    def validate_ip_or_cidr(cls, addr: str, family: FirewallFamily) -> None:
        """Validates IP host or network CIDR against the given family."""
        addr_clean = addr.strip()
        if addr_clean.lower() in ("any", "all", "0.0.0.0/0", "::/0"):
            return

        cls.validate_injection_safety(addr_clean, "address")

        try:
            # Check if network CIDR
            if "/" in addr_clean:
                net = ipaddress.ip_network(addr_clean, strict=False)
                if family == FirewallFamily.IPV4 and net.version != 4:
                    raise FirewallValidationError(f"Expected IPv4 CIDR, got IPv6: {addr_clean}")
                if family == FirewallFamily.IPV6 and net.version != 6:
                    raise FirewallValidationError(f"Expected IPv6 CIDR, got IPv4: {addr_clean}")
            else:
                ip = ipaddress.ip_address(addr_clean)
                if family == FirewallFamily.IPV4 and ip.version != 4:
                    raise FirewallValidationError(f"Expected IPv4 address, got IPv6: {addr_clean}")
                if family == FirewallFamily.IPV6 and ip.version != 6:
                    raise FirewallValidationError(f"Expected IPv6 address, got IPv4: {addr_clean}")
        except ValueError as e:
            raise FirewallValidationError(f"Invalid IP address or CIDR '{addr_clean}': {e}")

    @classmethod
    def validate_port_spec(cls, port_str: str) -> None:
        """Validates a single port integer (1-65535) or a port range (start-end)."""
        port_clean = port_str.strip()
        cls.validate_injection_safety(port_clean, "port")

        if "-" in port_clean:
            parts = port_clean.split("-", 1)
            try:
                p_start = int(parts[0])
                p_end = int(parts[1])
                if not (1 <= p_start <= 65535 and 1 <= p_end <= 65535):
                    raise ValueError("Port out of range 1-65535")
                if p_start > p_end:
                    raise ValueError("Range start cannot be greater than end")
            except ValueError as e:
                raise FirewallValidationError(f"Invalid port range '{port_clean}': {e}")
        else:
            try:
                p = int(port_clean)
                if not (1 <= p <= 65535):
                    raise ValueError("Port out of range 1-65535")
            except ValueError as e:
                raise FirewallValidationError(f"Invalid port number '{port_clean}': {e}")

    @classmethod
    def validate_interface(cls, iface: str, known_interfaces: Optional[Set[str]] = None) -> None:
        """Validates interface name format and known status."""
        iface_clean = iface.strip()
        if iface_clean.lower() in ("any", "all"):
            return

        cls.validate_injection_safety(iface_clean, "interface")

        if not INTERFACE_PATTERN.match(iface_clean):
            raise FirewallValidationError(f"Invalid network interface format: '{iface_clean}'")

        if known_interfaces is not None and iface_clean not in known_interfaces:
            raise FirewallValidationError(
                f"Interface '{iface_clean}' is not recognized in active system interface inventory"
            )

    @classmethod
    def validate_rule(
        cls,
        rule: FirewallRule,
        known_interfaces: Optional[Set[str]] = None,
        known_zones: Optional[Set[str]] = None,
    ) -> None:
        """Validates a single FirewallRule against semantic and safety invariants."""
        cls.validate_injection_safety(rule.id, "id")
        if not IDENTIFIER_PATTERN.match(rule.id):
            raise FirewallValidationError(f"Rule ID '{rule.id}' must be alphanumeric with underscores/hyphens")

        # Validate interfaces
        if rule.interface != "any":
            cls.validate_interface(rule.interface, known_interfaces)
        if rule.out_interface and rule.out_interface != "any":
            cls.validate_interface(rule.out_interface, known_interfaces)

        # Validate zone reference
        if rule.zone and known_zones is not None and rule.zone not in known_zones:
            raise FirewallValidationError(f"Rule '{rule.id}' references undefined zone '{rule.zone}'")

        # Validate IPs
        if rule.source != "any":
            cls.validate_ip_or_cidr(rule.source, rule.family)
        if rule.destination != "any":
            cls.validate_ip_or_cidr(rule.destination, rule.family)

        # Validate Ports
        for p in rule.source_ports:
            cls.validate_port_spec(p)
        for p in rule.destination_ports:
            cls.validate_port_spec(p)

        # Ports require TCP, UDP, or TCP_UDP
        if (rule.source_ports or rule.destination_ports) and rule.protocol not in (
            FirewallProtocol.TCP,
            FirewallProtocol.UDP,
            FirewallProtocol.TCP_UDP,
        ):
            raise FirewallValidationError(
                f"Rule '{rule.id}' specifies port filters but protocol '{rule.protocol.value}' does not support L4 ports"
            )

        # Validate log prefix
        if rule.log_prefix:
            cls.validate_injection_safety(rule.log_prefix, "log_prefix")

    @classmethod
    def validate_anti_lockout(cls, config: FirewallTableConfig) -> None:
        """
        Anti-lockout verification.
        Ensures management interfaces (default enp0s3, port 22/SSH) are not locked out
        by an unescaped blanket drop rule on input chain without prior accept.
        """
        if not config.policy.anti_lockout_enabled:
            return

        mgmt_ifaces = set(config.policy.management_interfaces)
        mgmt_ports = set(config.policy.management_ports)

        # Scan input rules in priority order
        # If there is a rule that explicitly drops all traffic on mgmt_iface or any iface
        # before any explicit accept rule for SSH / mgmt ports, raise security violation
        sorted_rules = sorted(
            [r for r in config.rules if r.enabled and r.direction == FirewallDirection.IN],
            key=lambda x: x.priority,
        )

        has_mgmt_accept = False
        for rule in sorted_rules:
            # Check if this rule explicitly accepts management port on mgmt interface
            is_mgmt_match = (
                rule.action == FirewallAction.ACCEPT
                and (rule.interface in mgmt_ifaces or rule.interface == "any")
                and (
                    rule.protocol in (FirewallProtocol.ANY, FirewallProtocol.TCP, FirewallProtocol.TCP_UDP)
                )
                and (
                    not rule.destination_ports
                    or any(p in mgmt_ports for p in rule.destination_ports)
                )
            )
            if is_mgmt_match:
                has_mgmt_accept = True
                break

            # Check if this rule drops management traffic
            is_blanket_drop = (
                rule.action in (FirewallAction.DROP, FirewallAction.REJECT)
                and (rule.interface in mgmt_ifaces or rule.interface == "any")
                and (rule.destination == "any" or rule.destination == "")
                and (not rule.destination_ports)
            )
            if is_blanket_drop and not has_mgmt_accept:
                raise FirewallSecurityViolationError(
                    f"Anti-Lockout Protection: Rule '{rule.id}' drops input traffic on management interface "
                    f"'{rule.interface}' without a preceding management accept rule."
                )

    @classmethod
    def validate_table_config(
        cls,
        config: FirewallTableConfig,
        known_interfaces: Optional[Set[str]] = None,
    ) -> List[str]:
        """
        Validates the entire firewall table configuration.
        Returns a list of warnings or non-fatal issues; raises on fatal errors.
        """
        cls.validate_injection_safety(config.name, "table name")
        if not IDENTIFIER_PATTERN.match(config.name):
            raise FirewallValidationError(f"Invalid table name: '{config.name}'")

        known_zones = set(config.zones.keys())

        # Validate zones
        for z_name, zone in config.zones.items():
            cls.validate_injection_safety(z_name, "zone name")
            for iface in zone.interfaces:
                cls.validate_interface(iface, known_interfaces)

        # Validate rules & detect duplicate IDs
        seen_ids = set()
        for rule in config.rules:
            if rule.id in seen_ids:
                raise FirewallValidationError(f"Duplicate firewall rule ID detected: '{rule.id}'")
            seen_ids.add(rule.id)
            cls.validate_rule(rule, known_interfaces=known_interfaces, known_zones=known_zones)

        # Validate NAT rules & detect duplicate IDs
        for nat_rule in config.nat_rules:
            if nat_rule.id in seen_ids:
                raise FirewallValidationError(f"Duplicate rule/NAT ID detected: '{nat_rule.id}'")
            seen_ids.add(nat_rule.id)
            cls.validate_nat_rule(nat_rule, known_interfaces=known_interfaces)

        # Validate Anti-Lockout
        cls.validate_anti_lockout(config)

        return []

    @classmethod
    def validate_nat_rule(cls, rule: NatRule, known_interfaces: Optional[Set[str]] = None) -> None:
        """Validates semantic and safety constraints for a single NAT rule."""
        cls.validate_injection_safety(rule.id, "NAT rule id")
        if not IDENTIFIER_PATTERN.match(rule.id):
            raise FirewallValidationError(f"Invalid characters in NAT rule ID: '{rule.id}'")

        if rule.interface != "any":
            cls.validate_interface(rule.interface, known_interfaces)

        # Validate IPs
        if rule.src_ip != "any":
            cls.validate_ip_or_cidr(rule.src_ip, FirewallFamily.INET)
        if rule.dst_ip != "any":
            cls.validate_ip_or_cidr(rule.dst_ip, FirewallFamily.INET)
        if rule.target_ip:
            cls.validate_ip_or_cidr(rule.target_ip, FirewallFamily.INET)

        # Validate Ports
        if rule.src_port and rule.src_port != "any":
            cls.validate_port_spec(rule.src_port)
        if rule.dst_port and rule.dst_port != "any":
            cls.validate_port_spec(rule.dst_port)
        if rule.target_port and rule.target_port != "any":
            cls.validate_port_spec(rule.target_port)

        # Specific requirements per type
        if rule.nat_type == NatType.PORT_FORWARD:
            if not rule.target_ip:
                raise FirewallValidationError(f"Port Forward rule '{rule.id}' requires target_ip")
