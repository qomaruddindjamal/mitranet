"""
MitraNet Interface Configuration Validator.
Enforces strict security checks, validates names against shell injection,
and checks MAC/MTU/IP format boundaries.
"""

import re
import ipaddress
from typing import Union, Optional, Any
from mitranet.core.network.exceptions import (
    NetworkValidationError,
    NetworkSecurityError,
    UnsupportedBondModeError,
)



# Strict Linux network device name regex (alphanumeric, dots, underscores, dashes, max 15 chars)
IFACE_NAME_REGEX = re.compile(r"^[a-zA-Z0-9_.-]{1,15}$")
MAC_REGEX = re.compile(r"^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$")


class InterfaceConfigValidator:
    """Security and semantic validation for network interface parameters."""

    @classmethod
    def validate_interface_name(cls, name: str) -> str:
        if not name or not isinstance(name, str):
            raise NetworkValidationError("Interface name must be a non-empty string.")
        name = name.strip()
        # Security: protect against shell metacharacters and command injection
        if any(c in name for c in [";", "&", "|", "`", "$", "(", ")", "<", ">", "\n", "\r", " "]):
            raise NetworkSecurityError(f"Malicious interface name rejected: '{name}'.")
        if not IFACE_NAME_REGEX.match(name):
            raise NetworkValidationError(f"Invalid Linux interface name format: '{name}'. Must be 1-15 alphanumeric/._- characters.")
        return name

    @classmethod
    def validate_mtu(cls, mtu: int) -> int:
        try:
            mtu_int = int(mtu)
        except (ValueError, TypeError):
            raise NetworkValidationError(f"MTU must be an integer, got: {mtu}")
        if mtu_int < 68 or mtu_int > 9216:
            raise NetworkValidationError(f"MTU {mtu_int} out of valid range (68 - 9216).")
        return mtu_int

    @classmethod
    def validate_mac_address(cls, mac: str) -> str:
        if not mac or not isinstance(mac, str):
            raise NetworkValidationError("MAC address must be a non-empty string.")
        mac = mac.strip()
        if not MAC_REGEX.match(mac):
            raise NetworkValidationError(f"Invalid MAC address format: '{mac}'. Expected format: XX:XX:XX:XX:XX:XX")
        return mac.lower()

    @classmethod
    def validate_ip_address(cls, cidr: str) -> Union[ipaddress.IPv4Interface, ipaddress.IPv6Interface]:
        if not cidr or not isinstance(cidr, str):
            raise NetworkValidationError("IP address must be a non-empty string in CIDR notation.")
        cidr = cidr.strip()
        if "/" not in cidr:
            raise NetworkValidationError(f"IP address '{cidr}' must include a prefix length in CIDR notation (e.g. /24 or /64).")
        try:
            if ":" in cidr:
                return ipaddress.IPv6Interface(cidr)
            else:
                return ipaddress.IPv4Interface(cidr)
        except ValueError as e:
            raise NetworkValidationError(f"Invalid IP/CIDR address format '{cidr}': {e}")


class RouteValidator:
    """Security, syntax, and semantic validator for routing entries (Phase 1C)."""

    @classmethod
    def _check_injection(cls, value: str, field_name: str) -> str:
        if not isinstance(value, str):
            raise NetworkValidationError(f"{field_name} must be a string.")
        val = value.strip()
        if any(c in val for c in [";", "&", "|", "`", "$", "(", ")", "<", ">", "\n", "\r", " "]):
            raise NetworkSecurityError(f"Malicious {field_name} rejected: '{val}'.")
        return val

    @classmethod
    def validate_destination(cls, destination: str) -> Union[ipaddress.IPv4Network, ipaddress.IPv6Network]:
        if not destination or not isinstance(destination, str):
            raise NetworkValidationError("Route destination must be a non-empty string.")
        dest = cls._check_injection(destination, "route destination")

        # Map special 'default' keyword
        if dest.lower() == "default":
            dest = "0.0.0.0/0"
        elif dest == "default6":
            dest = "::/0"

        # If bare IP provided without prefix length, treat as host route (/32 or /128)
        if "/" not in dest:
            dest = f"{dest}/128" if ":" in dest else f"{dest}/32"

        try:
            if ":" in dest:
                return ipaddress.IPv6Network(dest, strict=False)
            else:
                return ipaddress.IPv4Network(dest, strict=False)
        except ValueError as e:
            raise NetworkValidationError(f"Invalid route destination CIDR '{destination}': {e}")

    @classmethod
    def validate_gateway(cls, gateway: Optional[str], expected_family: Optional[str] = None) -> Optional[Union[ipaddress.IPv4Address, ipaddress.IPv6Address]]:
        if not gateway:
            return None
        gw_str = cls._check_injection(gateway, "gateway address")
        try:
            if ":" in gw_str:
                gw_ip = ipaddress.IPv6Address(gw_str)
                family = "inet6"
            else:
                gw_ip = ipaddress.IPv4Address(gw_str)
                family = "inet"
        except ValueError as e:
            raise NetworkValidationError(f"Invalid gateway IP address format '{gateway}': {e}")

        if expected_family and family != expected_family:
            raise NetworkValidationError(
                f"Gateway address family mismatch: gateway '{gateway}' is {family}, but route is {expected_family}."
            )
        return gw_ip

    @classmethod
    def validate_metric(cls, metric: Optional[int]) -> Optional[int]:
        if metric is None:
            return None
        try:
            m = int(metric)
        except (ValueError, TypeError):
            raise NetworkValidationError(f"Metric must be an integer, got: {metric}")
        if m < 0 or m > 4294967295:
            raise NetworkValidationError(f"Metric {m} out of valid range (0 - 4294967295).")
        return m

    @classmethod
    def validate_table(cls, table: Optional[Union[int, str]]) -> int:
        if table is None:
            return 254  # default main table
        if isinstance(table, str):
            table = table.strip()
            table = cls._check_injection(table, "routing table")
            if table.lower() == "main":
                return 254
            elif table.lower() == "local":
                return 255
            elif table.lower() == "default":
                return 253
        try:
            t = int(table)
        except (ValueError, TypeError):
            raise NetworkValidationError(f"Routing table must be an integer or known name, got: {table}")
        if t < 1 or t > 4294967295:
            raise NetworkValidationError(f"Routing table {t} out of valid range (1 - 4294967295).")
        return t


class VlanValidator:
    """Security and semantic validation for 802.1Q VLAN devices."""

    @classmethod
    def validate_vlan_id(cls, vlan_id: Any) -> int:
        try:
            vid = int(vlan_id)
        except (ValueError, TypeError):
            raise NetworkValidationError(f"VLAN ID must be an integer, got: {vlan_id}")
        if vid < 1 or vid > 4094:
            raise NetworkValidationError(f"VLAN ID {vid} out of valid range (1 - 4094).")
        return vid

    @classmethod
    def validate_vlan_name(cls, name: str) -> str:
        return InterfaceConfigValidator.validate_interface_name(name)

    @classmethod
    def validate_parent_interface(cls, parent: str) -> str:
        valid_name = InterfaceConfigValidator.validate_interface_name(parent)
        if valid_name == "lo":
            raise NetworkValidationError("Loopback interface 'lo' cannot be used as a parent interface for VLAN.")
        return valid_name


class BridgeValidator:
    """Security and semantic validation for Linux Bridge devices."""

    @classmethod
    def validate_bridge_name(cls, name: str) -> str:
        valid_name = InterfaceConfigValidator.validate_interface_name(name)
        if valid_name == "lo":
            raise NetworkValidationError("Cannot name a bridge 'lo'.")
        return valid_name

    @classmethod
    def validate_port_interface(cls, iface_name: str) -> str:
        valid_name = InterfaceConfigValidator.validate_interface_name(iface_name)
        if valid_name == "lo":
            raise NetworkValidationError("Loopback interface 'lo' cannot be attached as a bridge port.")
        return valid_name


class BondValidator:
    """Security and semantic validation for Linux Bonding devices."""

    VALID_MODES = [
        "balance-rr",
        "active-backup",
        "balance-xor",
        "broadcast",
        "802.3ad",
        "balance-tlb",
        "balance-alb",
    ]

    MODE_ALIASES = {
        "0": "balance-rr",
        "1": "active-backup",
        "2": "balance-xor",
        "3": "broadcast",
        "4": "802.3ad",
        "5": "balance-tlb",
        "6": "balance-alb",
        "lacp": "802.3ad",
    }

    @classmethod
    def validate_bond_name(cls, name: str) -> str:
        valid_name = InterfaceConfigValidator.validate_interface_name(name)
        if valid_name == "lo":
            raise NetworkValidationError("Cannot name a bond 'lo'.")
        return valid_name

    @classmethod
    def validate_mode(cls, mode: str) -> str:
        if not mode or not isinstance(mode, str):
            raise NetworkValidationError("Bond mode must be a non-empty string.")
        m = mode.strip().lower()
        if any(c in m for c in [";", "&", "|", "`", "$", "(", ")", "<", ">", "\n", "\r", " "]):
            raise NetworkSecurityError(f"Malicious bond mode rejected: '{mode}'.")
        canonical = cls.MODE_ALIASES.get(m, m)
        if canonical not in cls.VALID_MODES:
            raise UnsupportedBondModeError(
                f"Unsupported bond mode '{mode}'. Supported modes: {', '.join(cls.VALID_MODES)}."
            )
        return canonical

    @classmethod
    def validate_slave_interface(cls, iface_name: str) -> str:
        valid_name = InterfaceConfigValidator.validate_interface_name(iface_name)
        if valid_name == "lo":
            raise NetworkValidationError("Loopback interface 'lo' cannot be attached as a bond slave.")
        return valid_name


