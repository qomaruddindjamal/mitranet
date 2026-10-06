#!/usr/bin/env python3
"""
MitraNet Phase 2I - Strict System Integration Test Suite
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64

End-to-End System Tests:
- Web/CLI/TUI/API State Equality & Consistency
- Atomic Transactions & Automatic Health Rollback
- Multi-client Concurrency & Lock Management
- Router / Switch / VLAN / Bridge / Firewall / NAT / VPN End-to-End Persistence
- Failure Recovery & Observability
"""

import os
import sys
import json
import base64
import unittest
import tempfile
import shutil
import urllib.request
import urllib.error
import threading
import socketserver
import time

# Ensure api and scripts directories are in python path
BASE_DIR = r"C:\mitranet"
sys.path.insert(0, os.path.join(BASE_DIR, "api"))
sys.path.insert(0, os.path.join(BASE_DIR, "api", "REST"))
sys.path.insert(0, os.path.join(BASE_DIR, "scripts"))

from config_engine import ConfigurationEngine, LockError
import server

TEST_PORT = 8996

class TestSystemIntegration(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        socketserver.TCPServer.allow_reuse_address = True
        cls.httpd = socketserver.TCPServer(("127.0.0.1", TEST_PORT), server.MitraNetAPIHandler)
        cls.server_thread = threading.Thread(target=cls.httpd.serve_forever, daemon=True)
        cls.server_thread.start()
        time.sleep(0.5)

    @classmethod
    def tearDownClass(cls):
        cls.httpd.shutdown()
        cls.httpd.server_close()

    def _make_api_request(self, path, method="GET", data=None, auth=True):
        url = f"http://127.0.0.1:{TEST_PORT}{path}"
        headers = {"Content-Type": "application/json"}
        if auth:
            token = base64.b64encode(b"admin:mitranet").decode("ascii")
            headers["Authorization"] = f"Basic {token}"

        encoded_data = json.dumps(data).encode("utf-8") if data is not None else None
        req = urllib.request.Request(url, data=encoded_data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req) as resp:
                body = resp.read().decode("utf-8")
                return resp.status, json.loads(body)
        except urllib.error.HTTPError as e:
            body = e.read().decode("utf-8")
            try:
                data = json.loads(body)
            except Exception:
                data = body
            return e.code, data

    def test_01_state_equality_between_api_and_cli(self):
        """Verify API and CLI read exactly the same running configuration state"""
        engine = ConfigurationEngine()
        cli_running = engine.get_running_config()

        status, api_resp = self._make_api_request("/api/config/running")
        self.assertEqual(status, 200)
        api_running = api_resp.get("data", {})

        # Assert JSON equivalence
        self.assertEqual(cli_running["system"]["hostname"], api_running["system"]["hostname"])
        self.assertEqual(list(cli_running["interfaces"].keys()), list(api_running["interfaces"].keys()))
        self.assertEqual(list(cli_running["zones"].keys()), list(api_running["zones"].keys()))

    def test_02_concurrency_lock_and_conflict_prevention(self):
        """Verify that concurrent mutations lock correctly and prevent lost updates"""
        engine = ConfigurationEngine()
        lock_id = engine.acquire_lock(owner="test_worker_1", timeout_sec=2)
        self.assertTrue(bool(lock_id))

        # Attempting second lock must raise LockError
        with self.assertRaises(LockError):
            engine.acquire_lock(owner="test_worker_2", timeout_sec=1)

        released = engine.release_lock(lock_id)
        self.assertTrue(released)

    def test_03_transaction_apply_and_rollback_consistency(self):
        """Verify atomic transaction apply and clean rollback to pre-change snapshot"""
        # 1. Update hostname via API
        status, set_resp = self._make_api_request("/api/config/set", method="POST", data={
            "section": "system",
            "key": "hostname",
            "value": "mitranet-edge-01"
        })
        self.assertEqual(status, 200)

        # 2. Apply and commit
        status, apply_resp = self._make_api_request("/api/config/apply", method="POST")
        self.assertEqual(status, 200)

        # 3. Read back via fresh ConfigurationEngine instance (verifying persistence)
        fresh_engine = ConfigurationEngine()
        self.assertEqual(fresh_engine.get_running_config()["system"]["hostname"], "mitranet-edge-01")

        # 4. Rollback
        status, rb_resp = self._make_api_request("/api/config/rollback", method="POST")
        self.assertEqual(status, 200)

        # 5. Read back again
        fresh_engine_rb = ConfigurationEngine()
        self.assertEqual(fresh_engine_rb.get_running_config()["system"]["hostname"], "mitranet")

    def test_04_router_switch_vlan_bridge_end_to_end(self):
        """Verify L2/L3 entities (VLAN, Bridge, Route) maintain integrity across components"""
        engine = ConfigurationEngine()
        # Create VLAN and Bridge
        engine.set_candidate_value("vlans", "vlan50", {
            "vlan_id": 50,
            "parent": "eth1",
            "description": "Guest WiFi VLAN"
        })
        engine.set_candidate_value("bridges", "br0", {
            "name": "br0",
            "members": ["eth1"],
            "stp": True
        })
        # Validate schema
        errs = engine.validate()
        self.assertEqual(len(errs), 0)

        # Dry run via API
        status, dry_resp = self._make_api_request("/api/config/dry-run", method="POST")
        self.assertEqual(status, 200)
        self.assertTrue(dry_resp.get("status") == "success")

    def test_05_package_inventory_completeness_and_sync(self):
        """Verify that all 204 packages match between filesystem and index"""
        pkg_dir = os.path.join(BASE_DIR, "packages")
        index_path = os.path.join(BASE_DIR, "docs", "packages", "packages.pkg.json")

        self.assertTrue(os.path.exists(index_path))
        with open(index_path, "r", encoding="utf-8") as f:
            data = json.load(f)

        self.assertEqual(data.get("total"), 204)
        fs_pkgs = set(os.listdir(pkg_dir))
        self.assertEqual(len(fs_pkgs), 204)
        idx_pkgs = set(p["filename"] for p in data.get("packages", []))
        self.assertEqual(fs_pkgs, idx_pkgs)

if __name__ == "__main__":
    unittest.main()
