#!/usr/bin/env python3
"""
Automated Security and Input Validation Test Suite for MitraNet REST API
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

TEST_PORT = 8998

class TestAPISecurity(unittest.TestCase):
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

    def test_unauthenticated_request_rejected(self):
        status, data = self._make_request("/api/config/running", auth=False)
        self.assertEqual(status, 401)

    def test_malformed_json_handling(self):
        url = f"http://127.0.0.1:{TEST_PORT}/api/config/set"
        token = base64.b64encode(b"admin:mitranet").decode("ascii")
        headers = {"Content-Type": "application/json", "Authorization": f"Basic {token}"}
        req = urllib.request.Request(url, data=b"INVALID_MALFORMED_JSON", headers=headers, method="POST")
        try:
            with urllib.request.urlopen(req) as resp:
                status = resp.status
        except urllib.error.HTTPError as e:
            status = e.code
        # Server must handle gracefully without 500 traceback crash
        self.assertIn(status, [400, 422])

    def test_invalid_parameters_handled(self):
        status, data = self._make_request("/api/config/set", method="POST", data={
            "section": "system"
            # Missing "key" parameter
        })
        self.assertEqual(status, 400)

    def test_endpoint_not_found_clean_json(self):
        status, data = self._make_request("/api/nonexistent_route_9999")
        self.assertEqual(status, 404)

if __name__ == "__main__":
    unittest.main()
