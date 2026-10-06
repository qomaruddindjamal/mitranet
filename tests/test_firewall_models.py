"""
Unit and Schema Validation Tests for MitraNet Firewall Models.
Phase 3A: Validates Pydantic v2 models, defaults, enums, and constraint checks.
"""

import unittest
from pydantic import ValidationError
from mitranet.core.firewall.models import (
    FirewallAction,
    FirewallFamily,
    FirewallProtocol,
    FirewallDirection,
    FirewallConntrackState,
    FirewallZone,
    FirewallRule,
    FirewallPolicy,
    FirewallTableConfig,
    FirewallRuleCounter,
    FirewallState,
)


class TestFirewallModels(unittest.TestCase):
    """Test suite verifying Pydantic v2 firewall data models."""

    def test_firewall_zone_valid(self):
        zone = FirewallZone(name="WAN", interfaces=["enp0s3"], description="Internet egress")
        self.assertEqual(zone.name, "WAN")
        self.assertEqual(zone.interfaces, ["enp0s3"])
        self.assertFalse(zone.is_management)

    def test_firewall_zone_invalid_chars(self):
        with self.assertRaises(ValidationError):
            FirewallZone(name="WAN; rm -rf /")

    def test_firewall_rule_defaults(self):
        rule = FirewallRule(id="rule_001")
        self.assertEqual(rule.id, "rule_001")
        self.assertTrue(rule.enabled)
        self.assertEqual(rule.action, FirewallAction.ACCEPT)
        self.assertEqual(rule.protocol, FirewallProtocol.ANY)
        self.assertEqual(rule.direction, FirewallDirection.IN)
        self.assertEqual(rule.priority, 100)
        self.assertTrue(rule.counter)
        self.assertFalse(rule.log)

    def test_firewall_rule_custom_fields(self):
        rule = FirewallRule(
            id="allow_ssh",
            family=FirewallFamily.IPV4,
            interface="enp0s8",
            protocol=FirewallProtocol.TCP,
            source="192.168.1.0/24",
            destination="192.168.1.1",
            destination_ports=["22"],
            states=[FirewallConntrackState.NEW],
            action=FirewallAction.ACCEPT,
            log=True,
            log_prefix="SSH_ACCESS: ",
            priority=50,
        )
        self.assertEqual(rule.destination_ports, ["22"])
        self.assertEqual(rule.log_prefix, "SSH_ACCESS: ")
        self.assertEqual(rule.priority, 50)

    def test_firewall_rule_invalid_id_characters(self):
        with self.assertRaises(ValidationError):
            FirewallRule(id="rule$(whoami)")

    def test_firewall_policy_defaults(self):
        pol = FirewallPolicy()
        self.assertEqual(pol.input_default, FirewallAction.DROP)
        self.assertEqual(pol.forward_default, FirewallAction.DROP)
        self.assertEqual(pol.output_default, FirewallAction.ACCEPT)
        self.assertTrue(pol.established_related_accept)
        self.assertTrue(pol.invalid_drop)
        self.assertTrue(pol.loopback_accept)
        self.assertTrue(pol.anti_lockout_enabled)
        self.assertIn("enp0s3", pol.management_interfaces)
        self.assertIn("22", pol.management_ports)

    def test_firewall_table_config_serialization(self):
        cfg = FirewallTableConfig(
            name="mitranet",
            rules=[FirewallRule(id="r1"), FirewallRule(id="r2", priority=10)],
        )
        data = cfg.model_dump()
        self.assertEqual(data["name"], "mitranet")
        self.assertEqual(len(data["rules"]), 2)
        json_str = cfg.model_dump_json()
        self.assertIn('"name":"mitranet"', json_str)


if __name__ == "__main__":
    unittest.main()
