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

        lines.append("}")
        lines.append("")

        return "\n".join(lines)
