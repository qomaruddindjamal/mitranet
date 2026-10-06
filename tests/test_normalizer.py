"""
Test Suite: pfSense Value Normalizer.
"""

import unittest
from mitranet.core.migration.pfsense_normalizer import PfSenseNormalizer


class TestNormalizer(unittest.TestCase):
    def test_to_bool(self):
        self.assertTrue(PfSenseNormalizer.to_bool("1"))
        self.assertTrue(PfSenseNormalizer.to_bool("yes"))
        self.assertTrue(PfSenseNormalizer.to_bool("enable"))
        self.assertFalse(PfSenseNormalizer.to_bool("0"))
        self.assertFalse(PfSenseNormalizer.to_bool(""))
        self.assertFalse(PfSenseNormalizer.to_bool(None))

    def test_to_int(self):
        self.assertEqual(PfSenseNormalizer.to_int("24"), 24)
        self.assertEqual(PfSenseNormalizer.to_int("8080"), 8080)
        self.assertIsNone(PfSenseNormalizer.to_int(""))
        self.assertEqual(PfSenseNormalizer.to_int("invalid", default=1500), 1500)

    def test_to_list(self):
        self.assertEqual(PfSenseNormalizer.to_list(None), [])
        self.assertEqual(PfSenseNormalizer.to_list("item"), ["item"])
        self.assertEqual(PfSenseNormalizer.to_list(["a", "b"]), ["a", "b"])


if __name__ == "__main__":
    unittest.main()
