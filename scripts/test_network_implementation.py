#!/usr/bin/env python3
"""
Automated Test Suite for MitraNet Phase 2G Network Capabilities
(Router, Switch, Firewall, NAT, VPN, DHCP, DNS, Zones)
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64
"""

import os
import sys
import json
import unittest
import tempfile
import shutil

# Ensure api directory is in python path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "api")))
from config_engine import ConfigurationEngine

class TestNetworkImplementation(unittest.TestCase):
    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.config_path = os.path.join(self.test_dir, "test_network_config.json")
        self.engine = ConfigurationEngine(config_path=self.config_path)

    def tearDown(self):
        shutil.rmtree(self.test_dir, ignore_errors=True)

    def test_router_address_and_route_creation(self):
        # 1. Add new LAN interface address
        self.engine.set_candidate_value("addresses", "eth1_v4_secondary", {
            "interface": "eth1",
            "ip": "10.0.1.1",
            "prefix": 24,
            "family": "ipv4"
        })
        # 2. Add static route
        self.engine.set_candidate_value("routes", "branch_office", {
            "destination": "10.50.0.0/16",
            "gateway": "10.0.0.254",
            "interface": "eth1",
            "metric": 20
        })
        ok, msg, errs = self.engine.apply_and_commit(actor="test_router")
        self.assertTrue(ok)
        self.assertIn("branch_office", self.engine.get_running_config()["routes"])

    def test_switch_vlan_and_bridge_validation(self):
        # Add valid VLAN pointing to eth1
        self.engine.set_candidate_value("vlans", "vlan100", {
            "vlan_id": 100,
            "parent": "eth1",
            "description": "Voice VLAN"
        })
        errs = self.engine.validate()
        self.assertEqual(len(errs), 0)

        # Add invalid VLAN pointing to non-existent interface
        self.engine.set_candidate_value("vlans", "vlan200", {
            "vlan_id": 200,
            "parent": "eth99_missing",
            "description": "Invalid VLAN"
        })
        errs = self.engine.validate()
        self.assertTrue(any(e["code"] == "VLAN_INVALID_PARENT" for e in errs))

    def test_firewall_rule_and_nat_config(self):
        cfg = self.engine.get_running_config()
        self.assertIn("firewall", cfg)
        self.assertIn("rules", cfg["firewall"])
        self.assertIn("nat", cfg)
        self.assertIn("outbound", cfg["nat"])

    def test_dhcp_and_dns_config(self):
        cfg = self.engine.get_running_config()
        self.assertIn("dhcp", cfg)
        self.assertIn("dns", cfg)
        self.assertTrue(cfg["dns"]["enabled"])

    def test_zones_assignment(self):
        cfg = self.engine.get_running_config()
        self.assertIn("zones", cfg)
        self.assertIn("WAN", cfg["zones"])
        self.assertIn("LAN", cfg["zones"])

if __name__ == "__main__":
    unittest.main()
