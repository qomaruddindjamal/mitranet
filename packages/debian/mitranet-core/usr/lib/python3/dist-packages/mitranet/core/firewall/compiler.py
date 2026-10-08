"""
MitraNet nftables Rule Compiler.
Phase 3A: Translates Pydantic v2 firewall configurations into deterministic,
valid Linux nftables syntax.

Features:
- Standard base chains: input, forward, output with hook and priority
- Stateful conntrack (established, related -> accept; invalid -> drop)
- Loopback interface acceptance
- Management anti-lockout baseline rules
- Zone-specific sub-chains and jumps
- Protocol matching (tcp, udp, icmp, icmpv6, etc.)
- IP/CIDR matching (IPv4, IPv6, inet)
- Single ports and multiport sets
- Rule comments for rule-ID and telemetry tracking
- Rule counters and logging
"""

from typing import List, Optional
from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallAction,
    FirewallFamily,
    FirewallProtocol,
    FirewallDirection,
    FirewallConntrackState,
    NatRule,
    NatType,
)
from mitranet.core.firewall.errors import FirewallCompilationError


class NftablesCompiler:
    """Compiles MitraNet firewall model into native nftables ruleset string."""

    @classmethod
    def compile_rule(cls, rule: FirewallRule, table_family: FirewallFamily) -> str:
        """
        Compiles an individual FirewallRule into an nftables statement.
        Example output:
          iifname "enp0s3" ip saddr 10.0.0.0/24 tcp dport { 22, 80 } ct state new counter log prefix "SSH_IN: " accept comment "mitranet:rule:r_001"
        """
        if not rule.enabled:
            return ""

        tokens: List[str] = []

        # Family check if rule specifies ipv4/ipv6 inside inet table
        if rule.family == FirewallFamily.IPV4:
            ip_prefix = "ip"
        elif rule.family == FirewallFamily.IPV6:
            ip_prefix = "ip6"
        else:
            ip_prefix = "ip" if "." in rule.source or "." in rule.destination else ("ip6" if ":" in rule.source or ":" in rule.destination else None)

        # In-interface match
        if rule.interface and rule.interface != "any":
            tokens.append(f'iifname "{rule.interface}"')

        # Out-interface match
        if rule.out_interface and rule.out_interface != "any":
            tokens.append(f'oifname "{rule.out_interface}"')

        # Source address match
        if rule.source and rule.source != "any":
            family_lead = ip_prefix if ip_prefix else ("ip6" if ":" in rule.source else "ip")
            tokens.append(f"{family_lead} saddr {rule.source}")

        # Destination address match
        if rule.destination and rule.destination != "any":
            family_lead = ip_prefix if ip_prefix else ("ip6" if ":" in rule.destination else "ip")
            tokens.append(f"{family_lead} daddr {rule.destination}")

        # Protocol and Ports match
        proto = rule.protocol
        if proto == FirewallProtocol.TCP_UDP:
            # Multi-protocol layer 4
            tokens.append("meta l4proto { tcp, udp }")
            if rule.source_ports:
                ports_str = ", ".join(rule.source_ports)
                tokens.append(f"th sport {{ {ports_str} }}")
            if rule.destination_ports:
                ports_str = ", ".join(rule.destination_ports)
                tokens.append(f"th dport {{ {ports_str} }}")
        elif proto != FirewallProtocol.ANY:
            proto_name = proto.value
            if proto == FirewallProtocol.ICMP:
                tokens.append("ip protocol icmp")
            elif proto == FirewallProtocol.ICMPV6:
                tokens.append("ip6 nexthdr icmpv6")
            elif proto in (FirewallProtocol.TCP, FirewallProtocol.UDP):
                if rule.source_ports:
                    ports_str = ", ".join(rule.source_ports)
                    tokens.append(f"{proto_name} sport {{ {ports_str} }}")
                if rule.destination_ports:
                    ports_str = ", ".join(rule.destination_ports)
                    tokens.append(f"{proto_name} dport {{ {ports_str} }}")
                if not rule.source_ports and not rule.destination_ports:
                    tokens.append(f"meta l4proto {proto_name}")
            else:
                tokens.append(f"meta l4proto {proto_name}")


        # Conntrack states
        if rule.states:
            state_str = ", ".join([s.value for s in rule.states])
            tokens.append(f"ct state {{ {state_str} }}")

        # Packet and byte counter
        if rule.counter:
            tokens.append("counter")

        # Logging
        if rule.log:
            prefix = rule.log_prefix or f"MN_{rule.id}: "
            tokens.append(f'log prefix "{prefix}"')

        # Verdict
        if rule.action == FirewallAction.ACCEPT:
            tokens.append("accept")
        elif rule.action == FirewallAction.DROP:
            tokens.append("drop")
        elif rule.action == FirewallAction.REJECT:
            tokens.append("reject")
        else:
            tokens.append("drop")

        # Tracking comment
        tokens.append(f'comment "mitranet:rule:{rule.id}"')

        return " ".join(tokens)

    @classmethod
    def compile_table(cls, config: FirewallTableConfig) -> str:
        """
        Compiles the complete table configuration into an atomic nftables script.
        """
        table_family = config.family.value
        table_name = config.name

        lines: List[str] = [
            f"# MitraNet Rinjani 1.0.2 - Native nftables ruleset",
            f"# Generated automatically by MitraNet Firewall Compiler",
            f"table {table_family} {table_name} {{",
        ]

        policy = config.policy

        # 1. Base chain: input
        lines.append(f"    chain input {{")
        lines.append(f"        type filter hook input priority filter; policy {policy.input_default.value};")
        
        # Anti-lockout established / related
        if policy.established_related_accept:
            lines.append("        ct state { established, related } counter accept comment \"mitranet:policy:conntrack_established_related\"")
        if policy.invalid_drop:
            lines.append("        ct state invalid counter drop comment \"mitranet:policy:conntrack_invalid\"")

        # Loopback
        if policy.loopback_accept:
            lines.append("        iifname \"lo\" counter accept comment \"mitranet:policy:loopback_accept\"")

        # Anti-lockout management protection
        if policy.anti_lockout_enabled:
            for iface in policy.management_interfaces:
                ports_str = ", ".join(policy.management_ports)
                lines.append(
                    f'        iifname "{iface}" tcp dport {{ {ports_str} }} counter accept comment "mitranet:anti_lockout:mgmt_ssh"'
                )

        # Sort and render custom input rules
        input_rules = sorted(
            [r for r in config.rules if r.enabled and r.direction == FirewallDirection.IN],
            key=lambda x: x.priority,
        )
        for r in input_rules:
            rule_str = cls.compile_rule(r, config.family)
            if rule_str:
                lines.append(f"        {rule_str}")

        lines.append("    }")
        lines.append("")

        # 2. Base chain: forward
        lines.append(f"    chain forward {{")
        lines.append(f"        type filter hook forward priority filter; policy {policy.forward_default.value};")
        if policy.established_related_accept:
            lines.append("        ct state { established, related } counter accept comment \"mitranet:policy:conntrack_established_related\"")
        if policy.invalid_drop:
            lines.append("        ct state invalid counter drop comment \"mitranet:policy:conntrack_invalid\"")

        forward_rules = sorted(
            [r for r in config.rules if r.enabled and r.direction == FirewallDirection.FORWARD],
            key=lambda x: x.priority,
        )
        for r in forward_rules:
            rule_str = cls.compile_rule(r, config.family)
            if rule_str:
                lines.append(f"        {rule_str}")

        lines.append("    }")
        lines.append("")

        # 3. Base chain: output
        lines.append(f"    chain output {{")
        lines.append(f"        type filter hook output priority filter; policy {policy.output_default.value};")
        if policy.established_related_accept:
            lines.append("        ct state { established, related } counter accept comment \"mitranet:policy:conntrack_established_related\"")

        output_rules = sorted(
            [r for r in config.rules if r.enabled and r.direction == FirewallDirection.OUT],
            key=lambda x: x.priority,
        )
        for r in output_rules:
            rule_str = cls.compile_rule(r, config.family)
            if rule_str:
                lines.append(f"        {rule_str}")

        lines.append("    }")
        lines.append("")

        # 4. NAT chains: prerouting & postrouting (if NAT rules exist or table is inet)
        nat_rules = [r for r in config.nat_rules if r.enabled]
        prerouting_rules = [r for r in nat_rules if r.nat_type in (NatType.PORT_FORWARD, NatType.ONE_TO_ONE)]
        postrouting_rules = [r for r in nat_rules if r.nat_type in (NatType.OUTBOUND, NatType.ONE_TO_ONE)]

        if prerouting_rules or postrouting_rules:
            lines.append("    chain prerouting {")
            lines.append("        type nat hook prerouting priority dstnat; policy accept;")
            for r in sorted(prerouting_rules, key=lambda x: x.priority):
                rule_str = cls.compile_nat_rule(r, "prerouting")
                if rule_str:
                    lines.append(f"        {rule_str}")
            lines.append("    }")
            lines.append("")

            lines.append("    chain postrouting {")
            lines.append("        type nat hook postrouting priority srcnat; policy accept;")
            for r in sorted(postrouting_rules, key=lambda x: x.priority):
                rule_str = cls.compile_nat_rule(r, "postrouting")
                if rule_str:
                    lines.append(f"        {rule_str}")
            lines.append("    }")
            lines.append("")

        lines.append("}")
        lines.append("")

        return "\n".join(lines)

    @classmethod
    def compile_nat_rule(cls, rule: NatRule, chain: str) -> str:
        """Compiles a single NatRule into a valid nftables rule."""
        tokens: List[str] = []

        if chain == "prerouting":
            # DNAT / Port Forward
            if rule.interface and rule.interface != "any":
                tokens.append(f'iifname "{rule.interface}"')

            # Source match
            if rule.src_ip and rule.src_ip != "any":
                family = "ip6" if ":" in rule.src_ip else "ip"
                tokens.append(f"{family} saddr {rule.src_ip}")

            # Original Destination match
            if rule.dst_ip and rule.dst_ip != "any":
                family = "ip6" if ":" in rule.dst_ip else "ip"
                tokens.append(f"{family} daddr {rule.dst_ip}")

            # Protocol & Port
            proto = rule.protocol.value
            if proto in ("tcp", "udp"):
                tokens.append(proto)
                if rule.dst_port:
                    tokens.append(f"dport {rule.dst_port}")
            elif proto == "tcp_udp":
                tokens.append("meta l4proto { tcp, udp }")
                if rule.dst_port:
                    tokens.append(f"th dport {rule.dst_port}")
            elif rule.dst_port:
                tokens.append(f"dport {rule.dst_port}")

            # Counter
            tokens.append("counter")

            # Translation verdict: dnat ip to target_ip:target_port
            target = rule.target_ip or ""
            family = "ip6" if ":" in target else "ip"
            if rule.target_port:
                tokens.append(f"dnat {family} to {target}:{rule.target_port}")
            else:
                tokens.append(f"dnat {family} to {target}")

            tokens.append(f'comment "mitranet:nat:dnat:{rule.id}"')

        elif chain == "postrouting":
            # SNAT / Outbound Masquerade
            if rule.interface and rule.interface != "any":
                tokens.append(f'oifname "{rule.interface}"')

            # Source subnet match
            if rule.src_ip and rule.src_ip != "any":
                family = "ip6" if ":" in rule.src_ip else "ip"
                tokens.append(f"{family} saddr {rule.src_ip}")

            # Destination match
            if rule.dst_ip and rule.dst_ip != "any":
                family = "ip6" if ":" in rule.dst_ip else "ip"
                tokens.append(f"{family} daddr {rule.dst_ip}")

            # Protocol
            proto = rule.protocol.value
            if proto in ("tcp", "udp"):
                tokens.append(proto)
            elif proto == "tcp_udp":
                tokens.append("meta l4proto { tcp, udp }")

            tokens.append("counter")

            if rule.masquerade or not rule.target_ip:
                tokens.append("masquerade")
            else:
                family = "ip6" if ":" in rule.target_ip else "ip"
                tokens.append(f"snat {family} to {rule.target_ip}")

            tokens.append(f'comment "mitranet:nat:snat:{rule.id}"')

        return " ".join(tokens)
