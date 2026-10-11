"""
Test Suite: pfSense XML AST Parser.
"""

from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[2]
FIXTURES = REPO_ROOT / "tests" / "fixtures"

import unittest
from mitranet.core.migration.pfsense_xml_parser import PfSenseXmlParser


class TestXmlParser(unittest.TestCase):
    def test_parse_minimal_xml(self):
        data = PfSenseXmlParser.parse_file(str(FIXTURES / "pfsense-minimal.xml"))
        self.assertIn("pfsense", data)
        self.assertEqual(data["pfsense"]["system"]["hostname"], "mitranet-edge")
        self.assertEqual(data["pfsense"]["interfaces"]["wan"]["if"], "em0")

    def test_parse_multiple_duplicate_tags(self):
        data = PfSenseXmlParser.parse_file(str(FIXTURES / "pfsense-firewall.xml"))
        rules = data["pfsense"]["filter"]["rule"]
        self.assertIsInstance(rules, list)
        self.assertEqual(len(rules), 2)
        self.assertEqual(rules[0]["type"], "pass")
        self.assertEqual(rules[1]["type"], "block")


if __name__ == "__main__":
    unittest.main()
