"""
Compiler Tests for MitraNet nftables Translation.
Phase 3A: Validates deterministic synthesis of tables, base chains, conntrack,
ports, addresses, verdicts, and comments.
"""

import unittest
from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallPolicy,
    FirewallAction,
    FirewallFamily,
    FirewallProtocol,
    FirewallDirection,
    FirewallConntrackState,
)
from mitranet.core.firewall.compiler import NftablesCompiler


class TestFirewallCompiler(unittest.TestCase):
    """Test suite for nftables ruleset code generation."""

    def test_default_baseline_compiler_output(self):
        cfg = FirewallTableConfig(
            name="mitranet",
            policy=FirewallPolicy(
                input_default=FirewallAction.DROP,
                forward_default=FirewallAction.DROP,
                output_default=FirewallAction.ACCEPT,
                anti_lockout_enabled=True,
                management_interfaces=["enp0s3"],
                management_ports=["22"],
            ),
        )
        res = NftablesCompiler.compile_table(cfg)
        self.assertIn("table inet mitranet {", res)
        self.assertIn("chain input {", res)
        self.assertIn("policy drop;", res)
        self.assertIn("ct state { established, related } counter accept", res)
        self.assertIn("ct state invalid counter drop", res)
        self.assertIn('iifname "lo" counter accept', res)
        self.assertIn('iifname "enp0s3" tcp dport { 22 } counter accept', res)
        self.assertIn("chain forward {", res)
        self.assertIn("chain output {", res)

    def test_custom_rule_compilation(self):
        rule = FirewallRule(
            id="allow_dns",
            direction=FirewallDirection.IN,
            interface="enp0s8",
            protocol=FirewallProtocol.TCP_UDP,
            destination_ports=["53"],
            states=[FirewallConntrackState.NEW],
            action=FirewallAction.ACCEPT,
            log=True,
            log_prefix="DNS_QRY: ",
            counter=True,
        )
        cfg = FirewallTableConfig(rules=[rule])
        res = NftablesCompiler.compile_table(cfg)
        self.assertIn('iifname "enp0s8"', res)
        self.assertIn("meta l4proto { tcp, udp }", res)
        self.assertIn("th dport { 53 }", res)
        self.assertIn("ct state { new }", res)
        self.assertIn('log prefix "DNS_QRY: "', res)
        self.assertIn("counter accept", res)
        self.assertIn('comment "mitranet:rule:allow_dns"', res)

    def test_ipv6_rule_compilation(self):
        rule = FirewallRule(
            id="v6_icmp",
            family=FirewallFamily.IPV6,
            direction=FirewallDirection.IN,
            protocol=FirewallProtocol.ICMPV6,
            action=FirewallAction.ACCEPT,
        )
        cfg = FirewallTableConfig(rules=[rule])
        res = NftablesCompiler.compile_table(cfg)
        self.assertIn("ip6 nexthdr icmpv6", res)
        self.assertIn('comment "mitranet:rule:v6_icmp"', res)


if __name__ == "__main__":
    unittest.main()
