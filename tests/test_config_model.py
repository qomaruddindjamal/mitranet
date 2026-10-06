"""
Test Suite: MitraNet Canonical Configuration Model.
"""

import unittest
from mitranet.core.config.model import (
    MitraNetConfig,
    SystemConfig,
    InterfaceConfig,
    InterfaceIPv4,
    FirewallRule,
    OutboundNatRule,
    DHCPSubnet,
)


class TestConfigModel(unittest.TestCase):
    def test_default_model(self):
        cfg = MitraNetConfig()
        self.assertEqual(cfg.schema_version, "1.0.2")
        self.assertEqual(cfg.config_version, 1)
        self.assertEqual(cfg.system.hostname, "mitranet")
        self.assertEqual(cfg.system.domain, "home.arpa")

    def test_interface_dictionary_structure(self):
        cfg = MitraNetConfig()
        cfg.interfaces["lan"] = InterfaceConfig(
            device="eth1",
            role="lan",
            ipv4=InterfaceIPv4(mode="static", address="192.168.1.1", prefix=24)
        )
        self.assertIn("lan", cfg.interfaces)
        self.assertEqual(cfg.interfaces["lan"].device, "eth1")
        self.assertEqual(cfg.interfaces["lan"].ipv4.address, "192.168.1.1")

    def test_model_json_serialization(self):
        cfg = MitraNetConfig()
        cfg.interfaces["wan"] = InterfaceConfig(
            device="eth0",
            role="wan",
            ipv4=InterfaceIPv4(mode="dhcp")
        )
        json_str = cfg.model_dump_json()
        self.assertIn('"schema_version":"1.0.2"', json_str)
        self.assertIn('"device":"eth0"', json_str)


if __name__ == "__main__":
    unittest.main()
