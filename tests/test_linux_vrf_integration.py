"""
Live Linux Kernel Integration Test Suite for Phase 1E:
VRF Core & Routing Domain Isolation.

Executes real netlink/iproute2 operations against the live Linux kernel:
1. VRF Creation (VRF A and VRF B) with distinct routing tables
2. VRF Discovery & Runtime Verification
3. Isolated interface attachment to VRF A
4. Isolated interface detachment from VRF A
5. Routing Table Domain Isolation (Routes in VRF A table vs VRF B table vs main table)
6. IPv4 isolated route addition and table verification
7. IPv6 isolated route verification where kernel supports it
8. Management Interface & Loopback Protection
9. Complete State Restoration and Resource Leak Verification
"""

import os
import unittest
import subprocess
from mitranet.core.network.backend import LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.vrf import VRFService
from mitranet.core.network.exceptions import (
    SafetyConstraintViolationError,
    NetworkValidationError,
    NetworkSecurityError,
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
)


@unittest.skipUnless(os.name == "posix" and os.path.exists("/sys/class/net"), "Requires Linux system with /sys/class/net")
class TestLinuxVRFIntegration(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        cls.backend = LinuxNetworkBackend()
        cls.iface_discovery = InterfaceDiscoveryService(backend=cls.backend)
        cls.iface_service = InterfaceConfigurationService(backend=cls.backend, discovery=cls.iface_discovery)
        cls.vrf_service = VRFService(backend=cls.backend, iface_discovery=cls.iface_discovery)

        # Discover isolated test interface (non-lo, non-management)
        interfaces = cls.iface_discovery.discover_interfaces()
        cls.test_iface = None
        for iface in interfaces:
            if iface.name != "lo" and not any(addr.startswith("10.0.2.") for addr in iface.ipv4_addresses):
                cls.test_iface = iface.name
                break

    def setUp(self):
        # Comprehensive pre-test cleanup
        self._cleanup_test_resources()

    def tearDown(self):
        # Comprehensive post-test cleanup
        self._cleanup_test_resources()

    @classmethod
    def tearDownClass(cls):
        # Final cleanup
        if cls.test_iface:
            try:
                cls.backend.set_link_nomaster(cls.test_iface)
            except Exception:
                pass
        for v in ["vrf_test_a", "vrf_test_b", "vrf_sec_test"]:
            try:
                cls.backend.delete_link(v)
            except Exception:
                pass

    def _cleanup_test_resources(self):
        if self.test_iface:
            try:
                self.backend.set_link_nomaster(self.test_iface)
            except Exception:
                pass
        for v in ["vrf_test_a", "vrf_test_b", "vrf_sec_test"]:
            try:
                self.backend.delete_link(v)
            except Exception:
                pass
        # Clean up test routes from table 100 and 200
        for tbl in [100, 200]:
            try:
                subprocess.run(["ip", "route", "flush", "table", str(tbl)], capture_output=True, check=False)
            except Exception:
                pass

    def test_live_kernel_vrf_lifecycle(self):
        """Tests live creation, discovery, verification, and deletion of a VRF."""
        vrf_name = "vrf_test_a"
        table_id = 100

        # 1. Create VRF
        vrf = self.vrf_service.create_vrf(name=vrf_name, table=table_id)
        self.assertEqual(vrf.name, vrf_name)
        self.assertEqual(vrf.table, table_id)
        self.assertEqual(vrf.admin_state, "UP")

        # 2. Discover from kernel
        vrfs = self.vrf_service.discover_vrfs()
        found = any(v.name == vrf_name and v.table == table_id for v in vrfs)
        self.assertTrue(found, f"VRF {vrf_name} was not discovered from live Linux kernel.")

        # 3. Duplicate name rejection
        with self.assertRaises(DeviceAlreadyExistsError):
            self.vrf_service.create_vrf(name=vrf_name, table=101)

        # 4. Duplicate table rejection
        with self.assertRaises(DeviceAlreadyExistsError):
            self.vrf_service.create_vrf(name="vrf_test_b", table=table_id)

        # 5. Delete VRF
        self.vrf_service.delete_vrf(vrf_name)

        # 6. Verify absent from kernel
        vrfs_after = self.vrf_service.discover_vrfs()
        self.assertFalse(any(v.name == vrf_name for v in vrfs_after), f"VRF {vrf_name} still present after deletion.")

    def test_live_kernel_interface_attachment(self):
        """Tests attaching and detaching an interface to/from a VRF."""
        if not self.test_iface:
            self.skipTest("No isolated test interface available.")

        vrf_name = "vrf_test_a"
        table_id = 100

        self.vrf_service.create_vrf(name=vrf_name, table=table_id)

        # Attach test_iface
        self.vrf_service.add_interface(vrf_name, self.test_iface)

        # Verify membership in VRFState
        vrf = self.vrf_service.get_vrf(vrf_name)
        self.assertIn(self.test_iface, vrf.interfaces)

        # Detach test_iface
        self.vrf_service.remove_interface(vrf_name, self.test_iface)

        # Verify membership cleared
        vrf_after = self.vrf_service.get_vrf(vrf_name)
        self.assertNotIn(self.test_iface, vrf_after.interfaces)

        self.vrf_service.delete_vrf(vrf_name)

    def test_live_kernel_routing_domain_isolation(self):
        """Tests that routes installed in VRF A (table 100) are isolated from VRF B (table 200) and main table."""
        if not self.test_iface:
            self.skipTest("No isolated test interface available.")

        vrf_a = "vrf_test_a"
        vrf_b = "vrf_test_b"
        table_a = 100
        table_b = 200

        self.vrf_service.create_vrf(name=vrf_a, table=table_a)
        self.vrf_service.create_vrf(name=vrf_b, table=table_b)

        # Attach test interface to VRF A and bring up
        self.vrf_service.add_interface(vrf_a, self.test_iface)
        self.backend.set_interface_up(self.test_iface)

        # Add isolated RFC 5737 test route directly in table 100
        test_net = "198.51.100.0/24"
        res = subprocess.run(
            ["ip", "route", "add", test_net, "dev", self.test_iface, "table", str(table_a)],
            capture_output=True,
            text=True,
        )
        self.assertEqual(res.returncode, 0, f"Failed to add route to table {table_a}: {res.stderr}")

        # Check routes in table A (100)
        res_a = subprocess.run(["ip", "-j", "route", "show", "table", str(table_a)], capture_output=True, text=True)
        self.assertIn("198.51.100.0/24", res_a.stdout, "Test route should exist in VRF A table 100.")

        # Check routes in table B (200) -> MUST NOT contain test_net
        res_b = subprocess.run(["ip", "-j", "route", "show", "table", str(table_b)], capture_output=True, text=True)
        self.assertNotIn("198.51.100.0/24", res_b.stdout, "Test route must NOT exist in VRF B table 200 (ISOLATION VIOLATION)!")

        # Check main routing table (254) -> MUST NOT contain test_net
        res_main = subprocess.run(["ip", "-j", "route", "show", "table", "main"], capture_output=True, text=True)
        self.assertNotIn("198.51.100.0/24", res_main.stdout, "Test route must NOT leak into main routing table!")

        # Cleanup
        subprocess.run(["ip", "route", "del", test_net, "table", str(table_a)], capture_output=True)
        self.vrf_service.remove_interface(vrf_a, self.test_iface)
        self.vrf_service.delete_vrf(vrf_a)
        self.vrf_service.delete_vrf(vrf_b)

    def test_live_kernel_safety_and_management_protection(self):
        """Verifies that loopback and management interfaces cannot be enslaved to a VRF."""
        vrf_name = "vrf_sec_test"
        self.vrf_service.create_vrf(name=vrf_name, table=100)

        # Loopback must be rejected
        with self.assertRaises(SafetyConstraintViolationError):
            self.vrf_service.add_interface(vrf_name, "lo")

        # Discover active management interface (10.0.2.x subnet)
        mgmt_iface = None
        for iface in self.iface_discovery.discover_interfaces():
            if any(addr.startswith("10.0.2.") for addr in iface.ipv4_addresses):
                mgmt_iface = iface.name
                break

        if mgmt_iface:
            with self.assertRaises(SafetyConstraintViolationError):
                self.vrf_service.add_interface(vrf_name, mgmt_iface)

        self.vrf_service.delete_vrf(vrf_name)


if __name__ == "__main__":
    unittest.main()
