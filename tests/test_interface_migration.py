"""
Test Suite: Interface Migration and Compatibility.
"""

import unittest
from mitranet.core.migration.pfsense_xml_parser import PfSenseXmlParser
from mitranet.core.migration.pfsense_mapper import PfSenseMapper
from mitranet.core.migration.compatibility import InterfaceMapper


class TestInterfaceMigration(unittest.TestCase):
    def test_interface_mapping_and_vlans(self):
        data = PfSenseXmlParser.parse_file("C:/mitranet/tests/fixtures/pfsense-interface.xml")
        mapper = PfSenseMapper(InterfaceMapper({"vtnet0": "eth0", "vtnet1": "eth1", "vtnet2": "eth2"}))
        cfg, report = mapper.map_to_mitranet(data)

        # Check interfaces
        self.assertIn("wan", cfg.interfaces)
        self.assertIn("lan", cfg.interfaces)
        self.assertEqual(cfg.interfaces["wan"].device, "eth0")
        self.assertEqual(cfg.interfaces["lan"].device, "eth1")
        self.assertEqual(cfg.interfaces["opt1"].device, "eth2")

        # Check VLAN
        self.assertIn("vlan100", cfg.vlans)
        self.assertEqual(cfg.vlans["vlan100"].id, 100)

        # Check Bridge
        self.assertIn("br0", cfg.bridges)
        self.assertEqual(cfg.bridges["br0"].members, ["lan", "opt1"])


if __name__ == "__main__":
    unittest.main()
