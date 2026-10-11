"""
Test Suite: JSON Schema Draft 2020-12 Validation.
"""

import json
import unittest
from jsonschema import validate
from mitranet.core.config.model import MitraNetConfig, InterfaceConfig, InterfaceIPv4


class TestJsonSchema(unittest.TestCase):
    def setUp(self):
        with open("C:/mitranet/schemas/mitranet-config-v1.0.2.schema.json", "r", encoding="utf-8") as f:
            self.schema = json.load(f)

    def test_valid_default_config_against_schema(self):
        cfg = MitraNetConfig()
        cfg.interfaces["lan"] = InterfaceConfig(
            device="eth1",
            role="lan",
            ipv4=InterfaceIPv4(mode="static", address="192.168.1.1", prefix=24)
        )
        data = json.loads(cfg.model_dump_json())
        self.assertEqual(data["schema_version"], "1.0.2")
        # Should not raise
        validate(instance=data, schema=self.schema)

    def test_missing_required_property_fails(self):
        invalid_data = {
            "schema_version": "1.0",
            # missing config_version, system, interfaces
        }
        with self.assertRaises(Exception):
            validate(instance=invalid_data, schema=self.schema)


if __name__ == "__main__":
    unittest.main()
