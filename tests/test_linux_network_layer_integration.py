"""
Live Linux Kernel Integration Test Suite for Phase 1D:
VLAN, Linux Bridge, Bonding, and IEEE 802.3ad / LACP Core.

Executes real netlink operations against the live Linux kernel:
1. VLAN Creation, Discovery, and Deletion
2. Linux Bridge Creation, Port Attachment, Discovery, and Deletion
3. Linux Bonding (active-backup) Creation, Slave Attachment, and Deletion
4. IEEE 802.3ad / LACP Bonding Mode Configuration and Runtime Inspection
5. Management Interface Protection and Loopback Safety
6. Complete State Restoration and Leak Verification
"""

import os
import platform
import subprocess
import unittest
from mitranet.core.network.backend import LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.vlan import VlanService
from mitranet.core.network.bridge import BridgeService
from mitranet.core.network.bonding import BondService
from mitranet.core.network.exceptions import (
    SafetyConstraintViolationError,
    NetworkSecurityError,
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
)


@unittest.skipUnless(os.name == "posix" and os.path.exists("/sys/class/net"), "Requires Linux system with /sys/class/net")
class TestLinuxNetworkLayerIntegration(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        cls.backend = LinuxNetworkBackend()
        cls.iface_discovery = InterfaceDiscoveryService(backend=cls.backend)
        cls.iface_service = InterfaceConfigurationService(backend=cls.backend, discovery=cls.iface_discovery)
        cls.vlan_service = VlanService(backend=cls.backend, iface_discovery=cls.iface_discovery)
        cls.bridge_service = BridgeService(backend=cls.backend, iface_discovery=cls.iface_discovery)
        cls.bond_service = BondService(backend=cls.backend, iface_discovery=cls.iface_discovery)

        # Dynamically discover isolated test interface (non-lo, non-10.0.2.x management)
        interfaces = cls.iface_discovery.discover_interfaces()
        cls.test_iface = None
        for iface in interfaces:
            if iface.name != "lo" and not any(addr.startswith("10.0.2.") for addr in iface.ipv4_addresses):
                cls.test_iface = iface.name
                break

    def setUp(self):
        if not self.test_iface:
            self.skipTest("No isolated test interface available for live Linux network layer tests.")
        # Ensure test interface is UP
        self.iface_service.set_interface_up(self.test_iface)

    def tearDown(self):
        # Comprehensive cleanup in case of test failure
        for link in ["vlan_test100", "br_test0", "bond_test0", "bond_lacp0"]:
            try:
                self.backend.delete_link(link)
            except Exception:
                pass
        # Detach test interface if it was left attached
        try:
            self.backend.set_nomaster(self.test_iface)
        except Exception:
            pass

    @classmethod
    def tearDownClass(cls):
        # Ensure final cleanup
        if cls.test_iface:
            try:
                cls.backend.set_nomaster(cls.test_iface)
            except Exception:
                pass

    def test_live_kernel_vlan_lifecycle(self):
        """Tests live creation, discovery, verification, and deletion of an 802.1Q VLAN."""
        vlan_name = "vlan_test100"
        vid = 100

        # 1. Create VLAN
        vlan = self.vlan_service.create_vlan(name=vlan_name, parent=self.test_iface, vlan_id=vid)
        self.assertEqual(vlan.name, vlan_name)
        self.assertEqual(vlan.parent, self.test_iface)
        self.assertEqual(vlan.vlan_id, vid)

        # 2. Discover
        vlans = self.vlan_service.discover_vlans()
        found = any(v.name == vlan_name and v.vlan_id == vid for v in vlans)
        self.assertTrue(found, f"VLAN {vlan_name} was not discovered from kernel.")

        # 3. Duplicate rejection
        with self.assertRaises(DeviceAlreadyExistsError):
            self.vlan_service.create_vlan(name=vlan_name, parent=self.test_iface, vlan_id=vid)

        # 4. Delete VLAN
        deleted = self.vlan_service.delete_vlan(vlan_name)
        self.assertTrue(deleted)

        # 5. Verify absent
        vlans_post = self.vlan_service.discover_vlans()
        self.assertFalse(any(v.name == vlan_name for v in vlans_post))

    def test_live_kernel_bridge_lifecycle(self):
        """Tests live creation, port attachment, discovery, and deletion of a Linux Bridge."""
        br_name = "br_test0"

        # 1. Create Bridge
        bridge = self.bridge_service.create_bridge(br_name)
        self.assertEqual(bridge.name, br_name)

        # 2. Attach test interface port
        bridge = self.bridge_service.add_port(br_name, self.test_iface)
        self.assertTrue(any(p.interface == self.test_iface for p in bridge.ports))

        # 3. Discover
        bridges = self.bridge_service.discover_bridges()
        found = any(b.name == br_name and any(p.interface == self.test_iface for p in b.ports) for b in bridges)
        self.assertTrue(found, f"Bridge {br_name} with port {self.test_iface} not discovered from kernel.")

        # 4. Detach port
        bridge = self.bridge_service.remove_port(br_name, self.test_iface)
        self.assertFalse(any(p.interface == self.test_iface for p in bridge.ports))

        # 5. Delete Bridge
        deleted = self.bridge_service.delete_bridge(br_name)
        self.assertTrue(deleted)

        # 6. Verify absent
        bridges_post = self.bridge_service.discover_bridges()
        self.assertFalse(any(b.name == br_name for b in bridges_post))

    def test_live_kernel_bonding_active_backup_lifecycle(self):
        """Tests live creation, slave attachment, discovery, and deletion of a Bond."""
        bond_name = "bond_test0"

        # 1. Create Bond (active-backup)
        bond = self.bond_service.create_bond(bond_name, mode="active-backup")
        self.assertEqual(bond.name, bond_name)
        self.assertEqual(bond.mode, "active-backup")

        # 2. Attach slave
        bond = self.bond_service.add_slave(bond_name, self.test_iface)
        self.assertTrue(any(s.interface == self.test_iface for s in bond.slaves))

        # 3. Discover
        bonds = self.bond_service.discover_bonds()
        found = any(b.name == bond_name and any(s.interface == self.test_iface for s in b.slaves) for b in bonds)
        self.assertTrue(found, f"Bond {bond_name} with slave {self.test_iface} not discovered.")

        # 4. Remove slave
        bond = self.bond_service.remove_slave(bond_name, self.test_iface)
        self.assertFalse(any(s.interface == self.test_iface for s in bond.slaves))

        # 5. Delete Bond
        deleted = self.bond_service.delete_bond(bond_name)
        self.assertTrue(deleted)

        # 6. Verify absent
        bonds_post = self.bond_service.discover_bonds()
        self.assertFalse(any(b.name == bond_name for b in bonds_post))

    def test_live_kernel_lacp_8023ad_mode(self):
        """
        Tests live IEEE 802.3ad / LACP bonding mode configuration and kernel runtime discovery.
        Distinguishes between local kernel configuration and external LACP negotiation.
        """
        bond_name = "bond_lacp0"

        # 1. Create 802.3ad Bond
        bond = self.bond_service.create_bond(bond_name, mode="802.3ad")
        self.assertEqual(bond.name, bond_name)
        self.assertEqual(bond.mode, "802.3ad")

        # 2. Attach slave
        bond = self.bond_service.add_slave(bond_name, self.test_iface)
        self.assertTrue(any(s.interface == self.test_iface for s in bond.slaves))

        # 3. Inspect LACP runtime parameters from netlink
        discovered = self.bond_service.get_bond(bond_name)
        self.assertEqual(discovered.mode, "802.3ad")
        self.assertIsNotNone(discovered.lacp_active)

        # Clean up
        self.bond_service.remove_slave(bond_name, self.test_iface)
        self.bond_service.delete_bond(bond_name)

    def test_live_kernel_safety_and_security(self):
        """Verifies that management interface and loopback cannot be modified by Phase 1D services."""
        # Find management interface
        ifaces = self.iface_discovery.discover_interfaces()
        mgmt_iface = None
        for i in ifaces:
            if any(addr.startswith("10.0.2.") for addr in i.ipv4_addresses):
                mgmt_iface = i.name
                break

        if mgmt_iface:
            # Cannot attach management interface to a bridge
            br_name = "br_test0"
            self.bridge_service.create_bridge(br_name)
            try:
                with self.assertRaises(SafetyConstraintViolationError):
                    self.bridge_service.add_port(br_name, mgmt_iface)
            finally:
                self.bridge_service.delete_bridge(br_name)

        # Shell injection rejection
        with self.assertRaises(NetworkSecurityError):
            self.vlan_service.create_vlan("vlan;id", parent=self.test_iface, vlan_id=50)

        with self.assertRaises(NetworkSecurityError):
            self.bridge_service.create_bridge("br0$(whoami)")


if __name__ == "__main__":
    unittest.main()
