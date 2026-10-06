#!/usr/bin/env python3
"""
Automated REST API & Configuration Engine Integration Test Suite
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64
"""

import os
import sys
import json
import base64
import unittest
import urllib.request
import urllib.error
import threading
import socketserver
import time

# Ensure api directories are in python path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "api")))
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "api", "REST")))

import server
from config_engine import ConfigurationEngine

TEST_PORT = 8999

class TestRESTAPIIntegration(unittest.TestCase):
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

    def _make_request(self, path, method="GET", data=None, auth=True):
        url = f"http://127.0.0.1:{TEST_PORT}{path}"
        headers = {"Content-Type": "application/json"}
        if auth:
            token = base64.b64encode(b"admin:mitranet").decode("ascii")
            headers["Authorization"] = f"Basic {token}"

        encoded_data = json.dumps(data).encode("utf-8") if data else None
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

    def test_auth_challenge(self):
        status, data = self._make_request("/api/status", auth=False)
        self.assertEqual(status, 401)

    def test_status_endpoint(self):
        status, data = self._make_request("/api/status")
        self.assertEqual(status, 200)
        self.assertIn("os", data)

    def test_config_running_endpoint(self):
        status, data = self._make_request("/api/config/running")
        self.assertEqual(status, 200)
        self.assertEqual(data["status"], "success")
        self.assertIn("interfaces", data["data"])

    def test_config_diff_endpoint(self):
        status, data = self._make_request("/api/config/diff")
        self.assertEqual(status, 200)
        self.assertEqual(data["status"], "success")
        self.assertIn("diff", data)

    def test_config_dry_run_endpoint(self):
        status, data = self._make_request("/api/config/dry-run", method="POST")
        self.assertEqual(status, 200)
        self.assertEqual(data["status"], "success")

    def test_config_validate_endpoint(self):
        status, data = self._make_request("/api/config/validate", method="POST")
        self.assertEqual(status, 200)
        self.assertTrue(data["valid"])

    def test_config_mutation_and_rollback(self):
        # 1. Update candidate
        status, data = self._make_request("/api/config/set", method="POST", data={
            "section": "system",
            "key": "hostname",
            "value": "mitranet-rest-node"
        })
        self.assertEqual(status, 200)

        # 2. Apply change
        status, data = self._make_request("/api/config/apply", method="POST")
        self.assertEqual(status, 200)

        # 3. Verify running has changed
        status, data = self._make_request("/api/config/running")
        self.assertEqual(data["data"]["system"]["hostname"], "mitranet-rest-node")

        # 4. Rollback change
        status, data = self._make_request("/api/config/rollback", method="POST")
        self.assertEqual(status, 200)

        # 5. Verify restored
        status, data = self._make_request("/api/config/running")
        self.assertEqual(data["data"]["system"]["hostname"], "mitranet")

if __name__ == "__main__":
    unittest.main()
