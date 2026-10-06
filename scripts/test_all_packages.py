#!/usr/bin/env python3
"""
Automated Test Suite for MitraNet 204 Package Inventory Completeness & Integrity
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64
"""

import os
import sys
import json
import unittest
import hashlib

BASE_DIR = r"C:\mitranet"
PKG_DIR = os.path.join(BASE_DIR, "packages")
INDEX_PATH = os.path.join(BASE_DIR, "docs", "packages", "packages.pkg.json")

class TestAllPackages(unittest.TestCase):
    def test_package_count_exact_204(self):
        items = os.listdir(PKG_DIR)
        self.assertEqual(len(items), 204, f"Expected exactly 204 items, found {len(items)}")

    def test_no_duplicate_filenames(self):
        items = os.listdir(PKG_DIR)
        self.assertEqual(len(items), len(set(items)), "Duplicate filenames detected in packages store")

    def test_split_package_presence(self):
        items = os.listdir(PKG_DIR)
        self.assertIn("mitranet-base-1.0.0.pkg", items)
        self.assertIn("mitranet-base-1.0.0.pkg.partaa", items)
        self.assertIn("mitranet-base-1.0.0.pkg.partab", items)

    def test_packages_index_consistency(self):
        self.assertTrue(os.path.exists(INDEX_PATH), "packages.pkg.json missing from docs/packages/")
        with open(INDEX_PATH, "r", encoding="utf-8") as fp:
            data = json.load(fp)
        self.assertEqual(data.get("total"), 204)
        indexed_names = set(p["filename"] for p in data.get("packages", []))
        actual_names = set(os.listdir(PKG_DIR))
        self.assertEqual(indexed_names, actual_names, "Mismatch between indexed packages and filesystem")

if __name__ == "__main__":
    unittest.main()
