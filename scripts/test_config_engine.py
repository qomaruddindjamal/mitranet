#!/usr/bin/env python3
"""
Unit and Integration Test Suite for MitraNet Configuration Engine and CLI
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64
"""

import os
import sys
import json
import unittest
import tempfile
import shutil

# Ensure api directory is in python path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "api")))
from config_engine import ConfigurationEngine, LockError

class TestConfigurationEngine(unittest.TestCase):
    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.config_path = os.path.join(self.test_dir, "test_config.json")
        self.engine = ConfigurationEngine(config_path=self.config_path)

    def tearDown(self):
        shutil.rmtree(self.test_dir, ignore_errors=True)

    def test_default_config_loading(self):
        cfg = self.engine.get_running_config()
        self.assertIn("interfaces", cfg)
        self.assertIn("eth0", cfg["interfaces"])
        self.assertEqual(cfg["system"]["hostname"], "mitranet")

    def test_validation_success(self):
        errs = self.engine.validate()
        self.assertEqual(len(errs), 0)

    def test_validation_duplicate_ip(self):
        # Assign duplicate IP to different address key
        self.engine.set_candidate_value("addresses", "eth2_v4", {
            "interface": "eth0",
            "ip": "192.168.1.100",  # Duplicate of eth0_v4
            "prefix": 24,
            "family": "ipv4"
        })
        errs = self.engine.validate()
        self.assertTrue(any(e["code"] == "ADDR_DUPLICATE_IP" for e in errs))

    def test_validation_orphan_interface(self):
        self.engine.set_candidate_value("addresses", "orphan_v4", {
            "interface": "nonexistent99",
            "ip": "172.16.0.1",
            "prefix": 24,
            "family": "ipv4"
        })
        errs = self.engine.validate()
        self.assertTrue(any(e["code"] == "ADDR_ORPHAN_IFACE" for e in errs))

    def test_dry_run_does_not_modify_running(self):
        self.engine.set_candidate_value("system", "hostname", "newhost")
        ok, msg, errs = self.engine.apply_and_commit(actor="test", dry_run=True)
        self.assertTrue(ok)
        self.assertEqual(self.engine.get_running_config()["system"]["hostname"], "mitranet")

    def test_apply_and_commit(self):
        self.engine.set_candidate_value("system", "hostname", "gateway-01")
        ok, msg, errs = self.engine.apply_and_commit(actor="test", dry_run=False)
        self.assertTrue(ok)
        self.assertEqual(self.engine.get_running_config()["system"]["hostname"], "gateway-01")

    def test_rollback(self):
        # 1. First commit
        self.engine.set_candidate_value("system", "hostname", "host-modified")
        ok, msg, errs = self.engine.apply_and_commit(actor="test")
        self.assertTrue(ok)
        self.assertEqual(self.engine.get_running_config()["system"]["hostname"], "host-modified")

        # 2. Rollback
        ok, msg = self.engine.rollback()
        self.assertTrue(ok)
        self.assertEqual(self.engine.get_running_config()["system"]["hostname"], "mitranet")

    def test_locking_concurrency(self):
        lock_id = self.engine.acquire_lock(owner="test_session_1")
        self.assertTrue(bool(lock_id))
        # Second acquire should fail quickly
        with self.assertRaises(LockError):
            self.engine.acquire_lock(owner="test_session_2", timeout_sec=1)
        # Release lock
        released = self.engine.release_lock(lock_id)
        self.assertTrue(released)

if __name__ == "__main__":
    unittest.main()
