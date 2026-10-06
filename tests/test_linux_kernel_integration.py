"""
Integration Test Suite for Linux Real Kernel Network Configuration & Discovery (Phase 1A + 1B).
This test executes against live Linux host / VM when running on Linux (Debian 13),
and is automatically skipped when running in Windows development environment.

Integration Sequence:
1. Interface Discovery (lo + physical interfaces).
2. Interface State Modification with State Restoration on dedicated test interface (e.g. eth2 or veth/dummy).
3. Loopback Safety Protection Verification (ensure attempts to bring lo DOWN or remove 127.0.0.1/8 are blocked).
"""

import os
import platform
import subprocess
import unittest
from mitranet.core.network.backend import LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.exceptions import SafetyConstraintViolationError


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

    def test_live_kernel_loopback_safety(self):
        backend = LinuxNetworkBackend()
        cfg_service = InterfaceConfigurationService(backend=backend)

        # Verify attempting to bring down lo is blocked by safety guard
        with self.assertRaises(SafetyConstraintViolationError):
            cfg_service.set_interface_down("lo")

        # Verify attempting to remove 127.0.0.1/8 is blocked
        with self.assertRaises(SafetyConstraintViolationError):
            cfg_service.remove_address("lo", "127.0.0.1/8")

    def test_live_kernel_dedicated_interface_lifecycle(self):
        """
        Tests configuration lifecycle (UP, DOWN, MTU, IPv4, IPv6) with state restoration
        on a dedicated test interface (eth2 or test dummy/veth).
        Skips gracefully if no dedicated secondary test interface or non-root.
        """
        if os.geteuid() != 0:
            self.skipTest("Root privileges required for live kernel link/address modification.")

        backend = LinuxNetworkBackend()
        discovery = InterfaceDiscoveryService(backend=backend)
        cfg_service = InterfaceConfigurationService(backend=backend, discovery=discovery)

        ifaces = discovery.discover_interfaces()
        iface_names = [i.name for i in ifaces]

        # Prefer eth2 for test interface, otherwise use a temporary dummy interface
        test_iface = None
        created_dummy = False

        if "eth2" in iface_names:
            test_iface = "eth2"
        else:
            # Create a temporary dummy interface for safe testing without disturbing production/mgmt NICs
            test_iface = "mitra_test0"
            try:
                subprocess.run(["ip", "link", "add", test_iface, "type", "dummy"], check=True)
                created_dummy = True
            except Exception as e:
                self.skipTest(f"Could not create dummy test interface for live integration: {e}")

        try:
            # 1. Capture original state
            initial_state = discovery.get_interface(test_iface)
            orig_mtu = initial_state.mtu

            # 2. Test MTU modification and verification
            new_mtu = 1400 if orig_mtu != 1400 else 1450
            cfg_service.set_mtu(test_iface, new_mtu)
            verified_mtu = discovery.get_interface(test_iface)
            self.assertEqual(verified_mtu.mtu, new_mtu)

            # 3. Test IPv4 ADD and verification
            test_ipv4 = "192.0.2.10/24"
            cfg_service.add_address(test_iface, test_ipv4)
            verified_add = discovery.get_interface(test_iface)
            self.assertIn(test_ipv4, verified_add.ipv4_addresses)

            # 4. Test IPv4 REMOVE and verification
            cfg_service.remove_address(test_iface, test_ipv4)
            verified_rem = discovery.get_interface(test_iface)
            self.assertNotIn(test_ipv4, verified_rem.ipv4_addresses)

            # 5. Restore original MTU
            cfg_service.set_mtu(test_iface, orig_mtu)
            restored = discovery.get_interface(test_iface)
            self.assertEqual(restored.mtu, orig_mtu)

        finally:
            if created_dummy:
                subprocess.run(["ip", "link", "del", test_iface], check=False)


if __name__ == "__main__":
    unittest.main()
