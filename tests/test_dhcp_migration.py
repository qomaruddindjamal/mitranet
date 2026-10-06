"""
Test Suite: DHCP and DNS Migration.
"""

import unittest
from mitranet.core.migration.pfsense_xml_parser import PfSenseXmlParser
from mitranet.core.migration.pfsense_mapper import PfSenseMapper


class TestDhcpDnsMigration(unittest.TestCase):
    def test_dhcp_migration(self):
        data = PfSenseXmlParser.parse_file("C:/mitranet/tests/fixtures/pfsense-dhcp.xml")
        mapper = PfSenseMapper()
        cfg, report = mapper.map_to_mitranet(data)

        self.assertIn("lan", cfg.dhcp)
        dhcp = cfg.dhcp["lan"]
        self.assertEqual(dhcp.range_start, "192.168.1.100")
        self.assertEqual(dhcp.range_end, "192.168.1.200")
        self.assertEqual(dhcp.gateway, "192.168.1.1")

    def test_dns_migration(self):
        data = PfSenseXmlParser.parse_file("C:/mitranet/tests/fixtures/pfsense-dns.xml")
        mapper = PfSenseMapper()
        cfg, report = mapper.map_to_mitranet(data)

        self.assertTrue(cfg.dns.enabled)
        self.assertTrue(cfg.dns.dnssec)


if __name__ == "__main__":
    unittest.main()
