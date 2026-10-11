"""
Differential Planning Tests for MitraNet Firewall.
Phase 3A: Tests differential operations (ADD_RULE, DELETE_RULE, UPDATE_RULE, SET_POLICY, SET_ZONE).
"""

import unittest
from mitranet.core.firewall.models import (
    FirewallTableConfig,
    FirewallRule,
    FirewallZone,
    FirewallPolicy,
    FirewallAction,
)
from mitranet.core.firewall.planner import (
    FirewallPlanner,
    FirewallOperationType,
)


class TestFirewallPlanner(unittest.TestCase):
    """Test suite verifying differential calculation of firewall rules and zones."""

    def test_plan_add_rule(self):
        running = FirewallTableConfig(rules=[FirewallRule(id="r1")])
        candidate = FirewallTableConfig(rules=[FirewallRule(id="r1"), FirewallRule(id="r2")])

        ops = FirewallPlanner.generate_plan(candidate=candidate, running=running)
        self.assertEqual(len(ops), 1)
        self.assertEqual(ops[0].op_type, FirewallOperationType.ADD_RULE)
        self.assertEqual(ops[0].target, "r2")
        self.assertEqual(ops[0].inverse_op, FirewallOperationType.DELETE_RULE)

    def test_plan_delete_rule(self):
        running = FirewallTableConfig(rules=[FirewallRule(id="r1"), FirewallRule(id="r2")])
        candidate = FirewallTableConfig(rules=[FirewallRule(id="r1")])

        ops = FirewallPlanner.generate_plan(candidate=candidate, running=running)
        self.assertEqual(len(ops), 1)
        self.assertEqual(ops[0].op_type, FirewallOperationType.DELETE_RULE)
        self.assertEqual(ops[0].target, "r2")
        self.assertEqual(ops[0].inverse_op, FirewallOperationType.ADD_RULE)

    def test_plan_update_rule(self):
        running = FirewallTableConfig(rules=[FirewallRule(id="r1", priority=100)])
        candidate = FirewallTableConfig(rules=[FirewallRule(id="r1", priority=50)])

        ops = FirewallPlanner.generate_plan(candidate=candidate, running=running)
        self.assertEqual(len(ops), 1)
        self.assertEqual(ops[0].op_type, FirewallOperationType.UPDATE_RULE)
        self.assertEqual(ops[0].target, "r1")

    def test_plan_zone_diff(self):
        running = FirewallTableConfig(zones={"LAN": FirewallZone(name="LAN", interfaces=["enp0s8"])})
        candidate = FirewallTableConfig(
            zones={
                "LAN": FirewallZone(name="LAN", interfaces=["enp0s8", "enp0s9"]),
                "DMZ": FirewallZone(name="DMZ", interfaces=["enp0s10"]),
            }
        )

        ops = FirewallPlanner.generate_plan(candidate=candidate, running=running)
        op_types = [o.op_type for o in ops]
        self.assertIn(FirewallOperationType.CREATE_ZONE, op_types)
        self.assertIn(FirewallOperationType.UPDATE_ZONE, op_types)


if __name__ == "__main__":
    unittest.main()
