"""
Semantic, Port, IP, and Anti-Lockout Validation Tests for MitraNet Firewall.
Phase 3A: Validates address families, port boundaries, duplicate IDs, and management lockout prevention.
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
)
from mitranet.core.firewall.validator import FirewallValidator
from mitranet.core.firewall.errors import (
    FirewallValidationError,
    FirewallSecurityViolationError,
)


class TestFirewallValidator(unittest.TestCase):
    """Test suite for semantic checks and anti-lockout protection."""

    def test_valid_table_passes(self):
        cfg = FirewallTableConfig(
            name="mitranet",
            rules=[
                FirewallRule(
                    id="r_web",
                    protocol=FirewallProtocol.TCP,
                    destination_ports=["80", "443"],
                    action=FirewallAction.ACCEPT,
                )
            ],
        )
        warns = FirewallValidator.validate_table_config(cfg, known_interfaces={"enp0s3", "enp0s8"})
        self.assertEqual(warns, [])

    def test_ip_address_family_mismatch(self):
        rule = FirewallRule(
            id="bad_family",
            family=FirewallFamily.IPV4,
            source="2001:db8::1",
        )
        cfg = FirewallTableConfig(rules=[rule])
        with self.assertRaises(FirewallValidationError):
            FirewallValidator.validate_table_config(cfg)

    def test_invalid_port_range(self):
        rule = FirewallRule(
            id="bad_ports",
            protocol=FirewallProtocol.TCP,
            destination_ports=["1000-500"],
        )
        cfg = FirewallTableConfig(rules=[rule])
        with self.assertRaises(FirewallValidationError):
            FirewallValidator.validate_table_config(cfg)

    def test_duplicate_rule_id_rejected(self):
        cfg = FirewallTableConfig(
            rules=[
                FirewallRule(id="dup_id", priority=10),
                FirewallRule(id="dup_id", priority=20),
            ]
        )
        with self.assertRaises(FirewallValidationError) as ctx:
            FirewallValidator.validate_table_config(cfg)
        self.assertIn("Duplicate firewall rule ID", str(ctx.exception))

    def test_anti_lockout_blocks_blanket_drop_on_management(self):
        cfg = FirewallTableConfig(
            policy=FirewallPolicy(
                anti_lockout_enabled=True,
                management_interfaces=["enp0s3"],
            ),
            rules=[
                FirewallRule(
                    id="drop_all_mgmt",
                    direction=FirewallDirection.IN,
                    interface="enp0s3",
                    action=FirewallAction.DROP,
                    priority=10,
                )
            ],
        )
        with self.assertRaises(FirewallSecurityViolationError) as ctx:
            FirewallValidator.validate_table_config(cfg, known_interfaces={"enp0s3", "enp0s8"})
        self.assertIn("Anti-Lockout Protection", str(ctx.exception))

    def test_anti_lockout_allows_drop_if_preceded_by_mgmt_accept(self):
        cfg = FirewallTableConfig(
            policy=FirewallPolicy(
                anti_lockout_enabled=True,
                management_interfaces=["enp0s3"],
                management_ports=["22"],
            ),
            rules=[
                FirewallRule(
                    id="allow_ssh",
                    direction=FirewallDirection.IN,
                    interface="enp0s3",
                    protocol=FirewallProtocol.TCP,
                    destination_ports=["22"],
                    action=FirewallAction.ACCEPT,
                    priority=10,
                ),
                FirewallRule(
                    id="drop_rest",
                    direction=FirewallDirection.IN,
                    interface="enp0s3",
                    action=FirewallAction.DROP,
                    priority=20,
                ),
            ],
        )
        warns = FirewallValidator.validate_table_config(cfg, known_interfaces={"enp0s3", "enp0s8"})
        self.assertEqual(warns, [])


if __name__ == "__main__":
    unittest.main()
