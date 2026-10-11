"""
Test Suite: Native MitraNet Configuration & pfSense Isolation Verification.
Ensures:
1. Native configuration has NO pfSense root or contamination.
2. Official MitraNet Version is strictly 1.0.2.
3. Canonical examples strictly validate against MitraNet 1.0.2 schema.
"""

import os
import json
import unittest
from jsonschema import validate
from mitranet.core.version import MITRANET_VERSION, SCHEMA_VERSION
from mitranet.core.config.model import MitraNetConfig
from mitranet.core.config.loader import ConfigLoader


class TestNativeBaselineAndIsolation(unittest.TestCase):
    def setUp(self):
        with open("C:/mitranet/schemas/mitranet-config-v1.0.2.schema.json", "r", encoding="utf-8") as f:
            self.schema = json.load(f)

    def test_version_constants(self):
        self.assertEqual(MITRANET_VERSION, "1.0.2")
        self.assertEqual(SCHEMA_VERSION, "1.0.2")

    def test_native_model_no_pfsense_root(self):
        cfg = MitraNetConfig()
        data = cfg.model_dump()
        # Assert neither 'pfsense' nor 'pfSense' is a top-level root key
        self.assertNotIn("pfsense", data)
        self.assertNotIn("pfSense", data)
        self.assertEqual(data["schema_version"], "1.0.2")

    def test_canonical_examples_validate_without_pfsense_keys(self):
        example_files = [
            "C:/mitranet/examples/config.json",
            "C:/mitranet/examples/minimal-config.json",
            "C:/mitranet/examples/router-config.json",
            "C:/mitranet/examples/firewall-config.json",
        ]
        for path in example_files:
            self.assertTrue(os.path.exists(path), f"Missing example file: {path}")
            cfg = ConfigLoader.load_from_file(path)
            data = json.loads(cfg.model_dump_json())

            # Validate against 1.0.2 JSON Schema
            validate(instance=data, schema=self.schema)

            # Assert no contamination
            self.assertNotIn("pfsense", data)
            self.assertNotIn("pfSense", data)
            self.assertEqual(data["schema_version"], "1.0.2")


if __name__ == "__main__":
    unittest.main()
