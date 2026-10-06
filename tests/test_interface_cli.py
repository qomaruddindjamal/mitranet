"""
Test Suite: Interface CLI Commands (Phase 1A).
Tests:
1. cmd_interface_list standard table and JSON output.
2. cmd_interface_show standard details and JSON output.
3. cmd_interface_show missing interface error exit.
"""

import json
import unittest
from unittest.mock import patch
from io import StringIO
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.tests.test_network_discovery import MockNetworkBackend
from mitranet.src.cli.main import cmd_interface_list, cmd_interface_show


class TestInterfaceCLI(unittest.TestCase):
    def setUp(self):
        with open("C:/mitranet/tests/fixtures/network/ip_link_sample.json", "r", encoding="utf-8") as f:
            links = json.load(f)
        with open("C:/mitranet/tests/fixtures/network/ip_addr_sample.json", "r", encoding="utf-8") as f:
            addrs = json.load(f)

        backend = MockNetworkBackend(
            links_data=links,
            addrs_data=addrs,
            carriers={"eth0": True, "eth1": False, "lo": None}
        )
        self.service = InterfaceDiscoveryService(backend=backend)

    def test_cli_interface_list_table(self):
        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_list([], service=self.service)
        out = buf.getvalue()
        self.assertIn("INTERFACE", out)
        self.assertIn("lo", out)
        self.assertIn("eth0", out)
        self.assertIn("eth1", out)
        self.assertIn("ens18.100", out)

    def test_cli_interface_list_json(self):
        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_list(["--json"], service=self.service)
        out = buf.getvalue()
        data = json.loads(out)
        self.assertEqual(len(data), 4)
        self.assertEqual(data[1]["name"], "eth0")

    def test_cli_interface_show_details(self):
        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_show(["eth0"], service=self.service)
        out = buf.getvalue()
        self.assertIn("Interface: eth0", out)
        self.assertIn("MAC Address         : 52:54:00:12:34:56", out)
        self.assertIn("192.168.100.10/24", out)
        self.assertIn("Physical Carrier    : UP", out)

    def test_cli_interface_show_missing(self):
        buf = StringIO()
        with patch("sys.stdout", buf):
            with self.assertRaises(SystemExit):
                cmd_interface_show(["eth99"], service=self.service)
        out = buf.getvalue()
        self.assertIn("Interface 'eth99' not found.", out)


if __name__ == "__main__":
    unittest.main()
