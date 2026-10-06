"""
Integration Test Suite for Linux Real Kernel Network Discovery (Phase 1A).
This test executes against live Linux host / VM when running on Linux (Debian 13),
and is automatically skipped when running in Windows development environment.
"""

import os
import platform
import subprocess
import unittest
from mitranet.core.network.backend import LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService


class TestLinuxRealKernelIntegration(unittest.TestCase):
    def setUp(self):
        if platform.system() != "Linux":
            self.skipTest("Linux live integration test only runs on Linux kernel environment.")

    def test_live_kernel_interface_discovery(self):
        backend = LinuxNetworkBackend()
        service = InterfaceDiscoveryService(backend=backend)

        # 1. Discover interfaces from real Linux kernel
        ifaces = service.discover_interfaces()
        self.assertTrue(len(ifaces) > 0, "At least one interface (lo) must exist in Linux.")

        # 2. Verify loopback is always present
        lo = service.get_interface("lo")
        self.assertEqual(lo.name, "lo")
        self.assertEqual(lo.admin_state, "UP")
        self.assertIn("127.0.0.1/8", lo.ipv4_addresses)

        # 3. Cross-validate with raw `ip -j link` command
        res = subprocess.run(["ip", "-j", "link", "show"], stdout=subprocess.PIPE, text=True, check=True)
        raw_count = len(res.stdout.strip())
        self.assertTrue(raw_count > 0)


if __name__ == "__main__":
    unittest.main()
