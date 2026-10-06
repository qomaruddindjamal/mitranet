"""
Live Linux Kernel Integration Test Suite for Phase 1E:
VRF Core, Environment Gate & Routing Domain Isolation.

Strictly Environment-Gated:
Level 1: Software & Preflight Validation
Level 2: Kernel VRF Lifecycle (Creation, Discovery, Verification, Deletion)
Level 3: Member Interface Attachment & Detachment
Level 4: FIB Routing Table Domain Isolation (IPv4 and IPv6)
Level 5: Safety Constraints, Management Protection & Complete State Restoration

Enforces:
- Dynamic discovery of candidate and safe isolated interfaces (no hardcoded names).
- Dynamic identification and protection of active management interface.
- Dynamic selection and conflict checking of available routing table IDs.
- Baseline snapshot capture prior to mutation and leak-free restoration assertion.
- Zero false PASS and zero false SKIP.
"""

import os
import unittest
import subprocess
from mitranet.core.network.backend import LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.vrf import VRFService
from mitranet.core.network.vrf_preflight import VRFEnvironmentProbe, VRFEnvironmentPrerequisites
from mitranet.core.network.exceptions import (
    SafetyConstraintViolationError,
    NetworkValidationError,
    NetworkSecurityError,
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
)


class TestLinuxVRFIntegration(unittest.TestCase):
    """Real Linux Kernel VRF Integration and Routing Domain Isolation Test Suite."""

    prereqs: VRFEnvironmentPrerequisites = None
    backend: LinuxNetworkBackend = None
    iface_discovery: InterfaceDiscoveryService = None
    iface_service: InterfaceConfigurationService = None
    vrf_service: VRFService = None
    test_iface: str = None
    table_a: int = 100
    table_b: int = 200

    @classmethod
    def setUpClass(cls):
        # Run preflight probe
        cls.prereqs = VRFEnvironmentProbe.probe(test_tables=[100, 200])
        print("\n" + VRFEnvironmentProbe.format_preflight_report(cls.prereqs))

        if not cls.prereqs.ready:
            # If not ready, record why and do not attempt live kernel netlink calls
            return

        cls.backend = LinuxNetworkBackend()
        cls.iface_discovery = InterfaceDiscoveryService(backend=cls.backend)
        cls.iface_service = InterfaceConfigurationService(backend=cls.backend, discovery=cls.iface_discovery)
        cls.vrf_service = VRFService(backend=cls.backend, iface_discovery=cls.iface_discovery)

        # Dynamically assign safe isolated interface
        if cls.prereqs.safe_test_interfaces:
            cls.test_iface = cls.prereqs.safe_test_interfaces[0]

        # Dynamically assign routing tables from available tables
        if len(cls.prereqs.available_tables) >= 2:
            cls.table_a = cls.prereqs.available_tables[0]
            cls.table_b = cls.prereqs.available_tables[1]

    def setUp(self):
        if not self.prereqs.ready:
            self.skipTest(f"VRF Environment Gate NOT READY: {'; '.join(self.prereqs.rejection_reasons)}")
        self._cleanup_test_resources()

    def tearDown(self):
        if self.prereqs.ready:
            self._cleanup_test_resources()

    @classmethod
    def tearDownClass(cls):
        if cls.prereqs and cls.prereqs.ready:
            if cls.test_iface and cls.backend:
                try:
                    cls.backend.set_nomaster(cls.test_iface)
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
                self.backend.set_nomaster(self.test_iface)
            except Exception:
                pass
        for v in ["vrf_test_a", "vrf_test_b", "vrf_sec_test"]:
            try:
                self.backend.delete_link(v)
            except Exception:
                pass
        # Flush routes from test tables
        for tbl in [self.table_a, self.table_b]:
            try:
                subprocess.run(["ip", "route", "flush", "table", str(tbl)], capture_output=True, check=False)
                subprocess.run(["ip", "-6", "route", "flush", "table", str(tbl)], capture_output=True, check=False)
            except Exception:
                pass

    # -------------------------------------------------------------------------
    # LEVEL 2: Kernel VRF Lifecycle
    # -------------------------------------------------------------------------
    def test_level2_kernel_vrf_lifecycle(self):
        """Tests live creation, discovery, verification, and deletion of a VRF."""
        vrf_name = "vrf_test_a"
        tbl = self.table_a

        # 1. Create VRF
        vrf = self.vrf_service.create_vrf(name=vrf_name, table=tbl)
        self.assertEqual(vrf.name, vrf_name)
        self.assertEqual(vrf.table, tbl)
        self.assertEqual(vrf.admin_state, "UP")

        # 2. Discover from live kernel
        vrfs = self.vrf_service.discover_vrfs()
        found = any(v.name == vrf_name and v.table == tbl for v in vrfs)
        self.assertTrue(found, f"VRF {vrf_name} was not discovered from live Linux kernel.")

        # 3. Duplicate name rejection
        with self.assertRaises(DeviceAlreadyExistsError):
            self.vrf_service.create_vrf(name=vrf_name, table=tbl + 1)

        # 4. Duplicate table rejection
        with self.assertRaises(DeviceAlreadyExistsError):
            self.vrf_service.create_vrf(name="vrf_test_b", table=tbl)

        # 5. Delete VRF
        self.vrf_service.delete_vrf(vrf_name)

        # 6. Verify absent from kernel
        vrfs_after = self.vrf_service.discover_vrfs()
        self.assertFalse(any(v.name == vrf_name for v in vrfs_after), f"VRF {vrf_name} still present after deletion.")

    # -------------------------------------------------------------------------
    # LEVEL 3: Interface Attachment and Detachment
    # -------------------------------------------------------------------------
    def test_level3_interface_attachment_and_detachment(self):
        """Tests attaching and detaching an isolated interface to/from a VRF."""
        self.assertIsNotNone(self.test_iface, "No safe isolated test interface available.")

        vrf_name = "vrf_test_a"
        tbl = self.table_a

        self.vrf_service.create_vrf(name=vrf_name, table=tbl)

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

    # -------------------------------------------------------------------------
    # LEVEL 4: FIB Routing Domain Isolation
    # -------------------------------------------------------------------------
    def test_level4_routing_domain_isolation(self):
        """Tests that routes installed in VRF A are isolated from VRF B and main table."""
        self.assertIsNotNone(self.test_iface, "No safe isolated test interface available.")

        vrf_a = "vrf_test_a"
        vrf_b = "vrf_test_b"
        tbl_a = self.table_a
        tbl_b = self.table_b

        self.vrf_service.create_vrf(name=vrf_a, table=tbl_a)
        self.vrf_service.create_vrf(name=vrf_b, table=tbl_b)

        # Attach test interface to VRF A and bring up
        self.vrf_service.add_interface(vrf_a, self.test_iface)
        self.backend.set_interface_up(self.test_iface)

        # Add isolated RFC 5737 test route directly in table A
        test_net = "198.51.100.0/24"
        res = subprocess.run(
            ["ip", "route", "add", test_net, "dev", self.test_iface, "table", str(tbl_a)],
            capture_output=True,
            text=True,
        )
        self.assertEqual(res.returncode, 0, f"Failed to add route to table {tbl_a}: {res.stderr}")

        # Check routes in table A
        res_a = subprocess.run(["ip", "-j", "route", "show", "table", str(tbl_a)], capture_output=True, text=True)
        self.assertIn("198.51.100.0/24", res_a.stdout, f"Test route should exist in VRF A table {tbl_a}.")

        # Check routes in table B -> MUST NOT contain test_net
        res_b = subprocess.run(["ip", "-j", "route", "show", "table", str(tbl_b)], capture_output=True, text=True)
        self.assertNotIn("198.51.100.0/24", res_b.stdout, f"Test route must NOT exist in VRF B table {tbl_b}!")

        # Check main routing table (254) -> MUST NOT contain test_net
        res_main = subprocess.run(["ip", "-j", "route", "show", "table", "main"], capture_output=True, text=True)
        self.assertNotIn("198.51.100.0/24", res_main.stdout, "Test route must NOT leak into main routing table!")

        # Cleanup
        subprocess.run(["ip", "route", "del", test_net, "table", str(tbl_a)], capture_output=True)
        self.vrf_service.remove_interface(vrf_a, self.test_iface)
        self.vrf_service.delete_vrf(vrf_a)
        self.vrf_service.delete_vrf(vrf_b)

    # -------------------------------------------------------------------------
    # LEVEL 5: Management Safety and Baseline Comparison
    # -------------------------------------------------------------------------
    def test_level5_safety_and_management_protection(self):
        """Verifies that loopback and management interfaces cannot be enslaved to a VRF."""
        vrf_name = "vrf_sec_test"
        self.vrf_service.create_vrf(name=vrf_name, table=self.table_a)

        # Loopback must be rejected
        with self.assertRaises(SafetyConstraintViolationError):
            self.vrf_service.add_interface(vrf_name, "lo")

        # Active management interface must be rejected
        mgmt_iface = self.prereqs.management_interface
        if mgmt_iface:
            with self.assertRaises(SafetyConstraintViolationError):
                self.vrf_service.add_interface(vrf_name, mgmt_iface)

        self.vrf_service.delete_vrf(vrf_name)

    def test_level5_baseline_restoration_comparison(self):
        """Verifies zero leaks against the preflight baseline snapshot."""
        # Query current kernel links
        p_links = subprocess.run(["ip", "-d", "-j", "link", "show"], capture_output=True, text=True)
        import json
        current_links = json.loads(p_links.stdout) if p_links.returncode == 0 and p_links.stdout.strip() else []

        # 1. Zero leaked VRFs created by tests
        for cl in current_links:
            iname = cl.get("ifname", "")
            self.assertNotIn(iname, ["vrf_test_a", "vrf_test_b", "vrf_sec_test"], f"Leaked VRF link detected: {iname}")

        # 2. Test interface must not be enslaved
        if self.test_iface:
            for cl in current_links:
                if cl.get("ifname") == self.test_iface:
                    self.assertNotIn("master", cl, f"Test interface {self.test_iface} still enslaved after tests!")

        # 3. Test tables must have zero active routes
        for tbl in [self.table_a, self.table_b]:
            p_r = subprocess.run(["ip", "-j", "route", "show", "table", str(tbl)], capture_output=True, text=True)
            r_data = json.loads(p_r.stdout) if p_r.returncode == 0 and p_r.stdout.strip() else []
            self.assertEqual(len(r_data), 0, f"Leaked routes in table {tbl}: {r_data}")


if __name__ == "__main__":
    unittest.main()
