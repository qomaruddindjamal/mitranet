"""
Test Suite: Configuration Versioning and Upgrades.
"""

import unittest
from mitranet.core.config.versioning import ConfigVersionManager
from mitranet.core.config.exceptions import SchemaVersionError


class TestVersioning(unittest.TestCase):
    def test_valid_version(self):
        v = ConfigVersionManager.validate_version({"schema_version": "1.0.2"})
        self.assertEqual(v, "1.0.2")
        v_legacy = ConfigVersionManager.validate_version({"schema_version": "1.0"})
        self.assertEqual(v_legacy, "1.0")

    def test_upgrade_version(self):
        upgraded = ConfigVersionManager.upgrade_if_needed({"schema_version": "1.0", "system": {}})
        self.assertEqual(upgraded["schema_version"], "1.0.2")

    def test_invalid_version_raises(self):
        with self.assertRaises(SchemaVersionError):
            ConfigVersionManager.validate_version({"schema_version": "99.9"})

    def test_missing_version_raises(self):
        with self.assertRaises(SchemaVersionError):
            ConfigVersionManager.validate_version({})


if __name__ == "__main__":
    unittest.main()
