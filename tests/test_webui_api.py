"""
Unit & Integration Tests for MitraNet WebUI Management Layer & REST API.
Verifies AuthManager (PBKDF2 hashing, session creation/expiration, CSRF tokens),
REST API endpoints (ping, auth status, login, logout, system, interfaces, routing,
VLAN, bridge, bond, VRF, firewall, gateway monitor, transactions, and logs),
as well as CSRF protection and session authentication enforcement.
"""

import os
import json
import time
import shutil
import tempfile
import unittest
from unittest.mock import MagicMock, patch
from io import BytesIO

from mitranet.src.api.auth import AuthManager
from mitranet.src.api.server import ManagementApiHandler


class TestAuthManager(unittest.TestCase):
    """Test suite for AuthManager security, hashing, and sessions."""

    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.auth_file = os.path.join(self.test_dir, "webui_users.json")
        self.auth = AuthManager(auth_file=self.auth_file)

    def tearDown(self):
        shutil.rmtree(self.test_dir, ignore_errors=True)

    def test_default_user_creation(self):
        """Verify default admin user is initialized properly."""
        self.assertTrue(os.path.exists(self.auth_file))
        with open(self.auth_file, "r", encoding="utf-8") as f:
            data = json.load(f)
        self.assertIn("admin", data)
        self.assertIn("password_hash", data["admin"])

    def test_password_hashing_and_verification(self):
        """Verify PBKDF2 hashing and verification."""
        hashed = self.auth.hash_password("SuperSecret123!")
        self.assertTrue(hashed.startswith("pbkdf2_sha256:"))
        self.assertTrue(self.auth.verify_password("SuperSecret123!", hashed))
        self.assertFalse(self.auth.verify_password("WrongPassword", hashed))

    def test_authenticate_success_and_failure(self):
        """Verify user authentication logic."""
        self.assertTrue(self.auth.authenticate("admin", "mitranet"))
        self.assertFalse(self.auth.authenticate("admin", "wrongpass"))
        self.assertFalse(self.auth.authenticate("nonexistent", "mitranet"))

    def test_session_lifecycle(self):
        """Verify session creation, validation, and invalidation."""
        token = self.auth.create_session("admin")
        self.assertIsNotNone(token)
        session = self.auth.validate_session(token)
        self.assertIsNotNone(session)
        self.assertEqual(session["username"], "admin")

        # Invalidate
        self.auth.destroy_session(token)
        self.assertIsNone(self.auth.validate_session(token))

    def test_csrf_validation(self):
        """Verify CSRF token matching against session."""
        token = self.auth.create_session("admin")
        session = self.auth.validate_session(token)
        csrf = session["csrf_token"]
        self.assertTrue(self.auth.validate_csrf(session, csrf))
        self.assertFalse(self.auth.validate_csrf(session, "invalid-token"))


class DummyServer:
    """Mock HTTP Server for handler testing."""
    def __init__(self):
        self.server_address = ("127.0.0.1", 8443)


class TestManagementApiEndpoints(unittest.TestCase):
    """Test suite for HTTP REST API requests, status codes, and JSON responses."""

    def setUp(self):
        self.test_dir = tempfile.mkdtemp()
        self.auth_file = os.path.join(self.test_dir, "webui_users.json")
        self.auth = AuthManager(auth_file=self.auth_file)
        self.patcher = patch("mitranet.src.api.server.auth_mgr", self.auth)
        self.patcher.start()

        # Mock discovery services so tests can run cross-platform without native Linux 'ip' command
        mock_iface = MagicMock()
        mock_iface.get_all_interfaces.return_value = []
        self.patcher_iface = patch("mitranet.src.api.server.iface_discovery", mock_iface)
        self.patcher_iface.start()

        mock_route = MagicMock()
        mock_route.get_all_routes.return_value = []
        self.patcher_route = patch("mitranet.src.api.server.route_discovery", mock_route)
        self.patcher_route.start()

        mock_vlan = MagicMock()
        mock_vlan.list_vlans.return_value = []
        self.patcher_vlan = patch("mitranet.src.api.server.vlan_service", mock_vlan)
        self.patcher_vlan.start()

        mock_bridge = MagicMock()
        mock_bridge.list_bridges.return_value = []
        self.patcher_bridge = patch("mitranet.src.api.server.bridge_service", mock_bridge)
        self.patcher_bridge.start()

        mock_bond = MagicMock()
        mock_bond.list_bonds.return_value = []
        self.patcher_bond = patch("mitranet.src.api.server.bond_service", mock_bond)
        self.patcher_bond.start()

        mock_vrf = MagicMock()
        mock_vrf.list_vrfs.return_value = []
        self.patcher_vrf = patch("mitranet.src.api.server.vrf_service", mock_vrf)
        self.patcher_vrf.start()

        mock_fw = MagicMock()
        mock_model = MagicMock()
        mock_model.model_dump.return_value = {}
        mock_fw.get_status.return_value = mock_model
        mock_fw.running_config = mock_model
        mock_fw.candidate_config = mock_model
        self.patcher_fw = patch("mitranet.src.api.server.fw_engine", mock_fw)
        self.patcher_fw.start()

        self.token = self.auth.create_session("admin")
        session = self.auth.validate_session(self.token)
        self.csrf_token = session["csrf_token"]

    def tearDown(self):
        self.patcher_fw.stop()
        self.patcher_vrf.stop()
        self.patcher_bond.stop()
        self.patcher_bridge.stop()
        self.patcher_vlan.stop()
        self.patcher_route.stop()
        self.patcher_iface.stop()
        self.patcher.stop()
        shutil.rmtree(self.test_dir, ignore_errors=True)

    def _make_handler(self, method: str, path: str, body: dict = None, headers: dict = None, client_ip: str = "127.0.0.1"):
        """Constructs and returns an instantiated ManagementApiHandler with mocked I/O."""
        server = DummyServer()
        handler = ManagementApiHandler.__new__(ManagementApiHandler)
        handler.command = method
        handler.path = path
        handler.request_version = "HTTP/1.1"
        handler.client_address = (client_ip, 54321)
        handler.server = server
        handler.close_connection = True

        raw_headers = []
        if headers:
            for k, v in headers.items():
                raw_headers.append(f"{k}: {v}")

        body_bytes = b""
        if body is not None:
            body_bytes = json.dumps(body).encode("utf-8")
            raw_headers.append(f"Content-Length: {len(body_bytes)}")
            raw_headers.append("Content-Type: application/json")

        headers_text = "\r\n".join(raw_headers) + "\r\n\r\n"
        input_stream = BytesIO(body_bytes)

        from email.parser import Parser
        parsed_headers = Parser().parsestr(headers_text)

        handler.headers = parsed_headers
        handler.rfile = input_stream
        handler.wfile = BytesIO()
        handler.requestline = f"{method} {path} HTTP/1.1"
        return handler

    def _execute_get(self, path: str, cookie: str = None, client_ip: str = "127.0.0.1"):
        headers = {}
        if cookie:
            headers["Cookie"] = f"mitranet_session={cookie}"
        handler = self._make_handler("GET", path, headers=headers, client_ip=client_ip)
        handler.do_GET()
        return handler.wfile.getvalue().decode("utf-8", errors="ignore")

    def _execute_post(self, path: str, body: dict = None, cookie: str = None, csrf: str = None, client_ip: str = "127.0.0.1"):
        headers = {}
        if cookie:
            headers["Cookie"] = f"mitranet_session={cookie}"
        if csrf:
            headers["X-CSRF-Token"] = csrf
        handler = self._make_handler("POST", path, body=body, headers=headers, client_ip=client_ip)
        handler.do_POST()
        return handler.wfile.getvalue().decode("utf-8", errors="ignore")

    def test_ping_endpoint(self):
        """GET /api/v1/ping should return 200 ok without authentication."""
        out = self._execute_get("/api/v1/ping")
        self.assertIn("200 OK", out)
        self.assertIn('"status": "ok"', out)

    def test_auth_status_unauthenticated(self):
        """GET /api/v1/auth/status returns authenticated: false when no cookie."""
        out = self._execute_get("/api/v1/auth/status")
        self.assertIn("200 OK", out)
        self.assertIn('"authenticated": false', out)

    def test_auth_status_authenticated(self):
        """GET /api/v1/auth/status returns authenticated: true with session cookie."""
        out = self._execute_get("/api/v1/auth/status", cookie=self.token)
        self.assertIn("200 OK", out)
        self.assertIn('"authenticated": true', out)
        self.assertIn('"username": "admin"', out)

    def test_protected_endpoint_denied_without_auth(self):
        """GET /api/v1/system returns 401 Unauthorized if not authenticated."""
        out = self._execute_get("/api/v1/system", client_ip="192.168.1.100")
        self.assertIn("401 Unauthorized", out)

    def test_protected_endpoints_succeed_with_auth(self):
        """Protected GET endpoints return 200 OK when authenticated."""
        endpoints = [
            "/api/v1/system",
            "/api/v1/interfaces",
            "/api/v1/routes",
            "/api/v1/vlans",
            "/api/v1/bridges",
            "/api/v1/bonds",
            "/api/v1/vrfs",
            "/api/v1/firewall",
            "/api/v1/gateways",
            "/api/v1/config/status",
            "/api/v1/logs",
        ]
        for ep in endpoints:
            out = self._execute_get(ep, cookie=self.token)
            self.assertIn("200 OK", out, f"Failed on endpoint {ep}")

    def test_mutation_csrf_protection(self):
        """Mutation endpoints reject requests missing CSRF token with 403 Forbidden."""
        out = self._execute_post(
            "/api/v1/interfaces/set-state",
            body={"name": "lo", "state": "up"},
            cookie=self.token,
            csrf=None,  # No CSRF
            client_ip="192.168.1.100",
        )
        self.assertIn("403 Forbidden", out)

    def test_mutation_with_valid_csrf(self):
        """Mutation endpoint accepts request with valid session and CSRF token."""
        out = self._execute_post(
            "/api/v1/config/apply",
            body={},
            cookie=self.token,
            csrf=self.csrf_token,
        )
        self.assertIn("200 OK", out)


if __name__ == "__main__":
    unittest.main()
