#!/usr/bin/env python3
"""
Automated Web UI to REST API Integration Verification Suite
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64
"""

import os
import sys
import json
import unittest
import base64
import urllib.request
import urllib.error
import threading
import socketserver
import time

# Ensure api directories are in python path
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "api")))
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "api", "REST")))

import server

TEST_PORT = 8997

class TestWebUIAPIIntegration(unittest.TestCase):
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

    def test_interfaces_web_api(self):
        # Called by interfaces.js
        status, data = self._make_request("/api/interfaces")
        self.assertEqual(status, 200)
        self.assertIn("status", data)
        self.assertEqual(data["status"], "success")

    def test_firewall_status_web_api(self):
        # Called by firewall.js
        status, data = self._make_request("/api/firewall/status")
        self.assertEqual(status, 200)
        self.assertIn("engine", data)

    def test_hardware_sensors_web_api(self):
        # Called by hardware.js
        status, data = self._make_request("/api/hardware/sensors")
        self.assertEqual(status, 200)
        self.assertIn("source", data)

    def test_config_backup_web_api(self):
        # Called by diagnostics.js
        status, data = self._make_request("/api/config/backup")
        self.assertEqual(status, 200)
        self.assertIn("system", data)
        self.assertIn("canonical_config", data)

    def test_terminal_exec_rbac(self):
        # Called by terminal.js - requires administrator
        status, data = self._make_request("/api/terminal/exec", method="POST", data={
            "command": "echo test_terminal"
        })
        self.assertEqual(status, 200)
        self.assertIn("output", data)

if __name__ == "__main__":
    unittest.main()
