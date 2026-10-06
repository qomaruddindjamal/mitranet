"""
Test Suite: End-to-End Migration Pipeline from real pfSense ISO config.xml to MitraNet JSON.
"""

import os
import unittest
from mitranet.core.migration.exporter import MigrationExporter
from mitranet.core.config.loader import ConfigLoader


class TestEndToEndMigration(unittest.TestCase):
    def test_full_pfsense_iso_config_migration(self):
        xml_input = "C:/mitranet/tests/fixtures/pfsense-full.xml"
        out_json = "C:/mitranet/tmp/test_migrated.json"
        out_report_json = "C:/mitranet/tmp/test_report.json"
        out_report_md = "C:/mitranet/tmp/test_report.md"

        cfg, report = MigrationExporter.migrate_pfsense_file(
            xml_filepath=xml_input,
            output_json_path=out_json,
            output_report_json_path=out_report_json,
            output_report_md_path=out_report_md
        )

        # 1. Assert file creations
        self.assertTrue(os.path.exists(out_json))
        self.assertTrue(os.path.exists(out_report_json))
        self.assertTrue(os.path.exists(out_report_md))

        # 2. Verify loadable JSON
        reloaded_cfg = ConfigLoader.load_from_file(out_json)
        self.assertEqual(reloaded_cfg.schema_version, "1.0.2")
        self.assertEqual(reloaded_cfg.system.hostname, "pfSense")
        self.assertEqual(reloaded_cfg.system.domain, "home.arpa")

        # 3. Verify interfaces
        self.assertIn("wan", reloaded_cfg.interfaces)
        self.assertIn("lan", reloaded_cfg.interfaces)
        self.assertEqual(reloaded_cfg.interfaces["wan"].device, "eth0")
        self.assertEqual(reloaded_cfg.interfaces["lan"].device, "eth1")
        self.assertEqual(reloaded_cfg.interfaces["lan"].ipv4.address, "192.168.1.1")

        # 4. Verify DHCP
        self.assertIn("lan", reloaded_cfg.dhcp)
        self.assertEqual(reloaded_cfg.dhcp["lan"].range_start, "192.168.1.100")
        self.assertEqual(reloaded_cfg.dhcp["lan"].range_end, "192.168.1.199")

        # 5. Verify Report counts
        self.assertTrue(report.summary.get("migrated", 0) > 0)
        self.assertEqual(report.summary.get("failed", 0), 0)


if __name__ == "__main__":
    unittest.main()
