"""
Test Suite: Firewall Rules and Aliases Migration.
"""

import unittest
from mitranet.core.migration.pfsense_xml_parser import PfSenseXmlParser
from mitranet.core.migration.pfsense_mapper import PfSenseMapper


class TestFirewallMigration(unittest.TestCase):
    def test_firewall_rules_and_aliases(self):
        data = PfSenseXmlParser.parse_file("C:/mitranet/tests/fixtures/pfsense-firewall.xml")
        mapper = PfSenseMapper()
        cfg, report = mapper.map_to_mitranet(data)

        # Check aliases
        self.assertIn("admin_workstations", cfg.firewall.aliases)
        self.assertEqual(cfg.firewall.aliases["admin_workstations"].type, "host")
        self.assertEqual(len(cfg.firewall.aliases["admin_workstations"].entries), 2)

        self.assertIn("web_ports", cfg.firewall.aliases)
        self.assertEqual(cfg.firewall.aliases["web_ports"].type, "port")

        # Check rules
        self.assertEqual(len(cfg.firewall.rules), 2)
        self.assertEqual(cfg.firewall.rules[0].action, "accept")
        self.assertEqual(cfg.firewall.rules[1].action, "drop")


if __name__ == "__main__":
    unittest.main()
