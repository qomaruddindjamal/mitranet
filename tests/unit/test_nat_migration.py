"""
Test Suite: NAT Outbound and Port Forward Migration.
"""

from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[2]
FIXTURES = REPO_ROOT / "tests" / "fixtures"

import unittest
from mitranet.core.migration.pfsense_xml_parser import PfSenseXmlParser
from mitranet.core.migration.pfsense_mapper import PfSenseMapper


class TestNatMigration(unittest.TestCase):
    def test_nat_migration(self):
        data = PfSenseXmlParser.parse_file(str(FIXTURES / "pfsense-nat.xml"))
        mapper = PfSenseMapper()
        cfg, report = mapper.map_to_mitranet(data)

        # Check Outbound
        self.assertEqual(len(cfg.nat.outbound), 1)
        self.assertEqual(cfg.nat.outbound[0].mode, "masquerade")

        # Check Port Forward
        self.assertEqual(len(cfg.nat.port_forward), 1)
        pf = cfg.nat.port_forward[0]
        self.assertEqual(pf.external_port, 8080)
        self.assertEqual(pf.internal_ip, "192.168.1.150")
        self.assertEqual(pf.internal_port, 80)


if __name__ == "__main__":
    unittest.main()
