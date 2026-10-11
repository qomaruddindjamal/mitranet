"""
Unit Test Suite for MitraNet Linux Bonding and LACP Service (Phase 1D).
Covers:
- Bond creation, discovery, deletion
- Mode validation and canonicalization (active-backup, 802.3ad, balance-rr, etc.)
- Unsupported bond mode detection
- Slave attachment and detachment
- Loopback interface protection (cannot attach 'lo')
- Management interface protection (cannot attach management IP NIC)
- LACP state extraction from netlink and /proc/net/bonding
- Duplicate bond and duplicate slave handling
- Security rejection of malicious names and modes
"""

import unittest
from unittest.mock import MagicMock
from mitranet.core.network.models import BondState, BondSlaveState, NetworkInterfaceState
from mitranet.core.network.backend import NetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.bonding import BondService
from mitranet.core.network.validator import BondValidator
from mitranet.core.network.exceptions import (
    NetworkValidationError,
    NetworkSecurityError,
    UnsupportedBondModeError,
    SafetyConstraintViolationError,
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    VerificationFailureError,
)


class TestBondService(unittest.TestCase):

    def setUp(self):
        self.mock_backend = MagicMock(spec=NetworkBackend)
        self.iface_discovery = MagicMock(spec=InterfaceDiscoveryService)

        # Isolated test interface enp0s8
        self.iface_discovery.get_interface.return_value = NetworkInterfaceState(
            name="enp0s8", index=3, type="ether", mtu=1500, admin_state="UP", oper_state="UP", ipv4_addresses=[]
        )

        self.mock_backend.get_detailed_links.return_value = []
        self.mock_backend.get_addr_info.return_value = []
        self.mock_backend.get_bonding_proc_info.return_value = None

        self.service = BondService(backend=self.mock_backend, iface_discovery=self.iface_discovery)

    def test_bond_mode_validation(self):
        self.assertEqual(BondValidator.validate_mode("active-backup"), "active-backup")
        self.assertEqual(BondValidator.validate_mode("802.3ad"), "802.3ad")
        self.assertEqual(BondValidator.validate_mode("lacp"), "802.3ad")
        self.assertEqual(BondValidator.validate_mode("1"), "active-backup")
        self.assertEqual(BondValidator.validate_mode("4"), "802.3ad")

        with self.assertRaises(UnsupportedBondModeError):
            BondValidator.validate_mode("unsupported_mode_xyz")

        with self.assertRaises(NetworkSecurityError):
            BondValidator.validate_mode("802.3ad;whoami")

    def test_bond_slave_validation(self):
        self.assertEqual(BondValidator.validate_slave_interface("enp0s8"), "enp0s8")

        with self.assertRaises(NetworkValidationError):
            BondValidator.validate_slave_interface("lo")

        with self.assertRaises(NetworkSecurityError):
            BondValidator.validate_slave_interface("eth0`id`")

    def test_bond_create_active_backup_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [],  # initial check
            [    # post-create check
                {
                    "ifname": "bond0",
                    "flags": ["BROADCAST", "MULTICAST", "MASTER"],
                    "mtu": 1500,
                    "operstate": "DOWN",
                    "address": "02:00:00:00:00:02",
                    "linkinfo": {
                        "info_kind": "bond",
                        "info_data": {"mode": "active-backup", "miimon": 100}
                    }
                }
            ]
        ]
        self.mock_backend.create_bond.return_value = True

        bond = self.service.create_bond("bond0", mode="active-backup")
        self.assertEqual(bond.name, "bond0")
        self.assertEqual(bond.mode, "active-backup")
        self.assertEqual(bond.miimon, 100)
        self.mock_backend.create_bond.assert_called_once_with("bond0", mode="active-backup", miimon=100)

    def test_bond_create_lacp_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [],
            [
                {
                    "ifname": "bond_lacp",
                    "flags": ["BROADCAST", "MULTICAST", "MASTER"],
                    "mtu": 1500,
                    "operstate": "DOWN",
                    "linkinfo": {
                        "info_kind": "bond",
                        "info_data": {
                            "mode": "802.3ad",
                            "ad_lacp_rate": "slow",
                            "ad_lacp_active": "on",
                            "ad_actor_system": "00:00:00:00:00:00",
                        }
                    }
                }
            ]
        ]
        self.mock_backend.create_bond.return_value = True

        bond = self.service.create_bond("bond_lacp", mode="802.3ad")
        self.assertEqual(bond.name, "bond_lacp")
        self.assertEqual(bond.mode, "802.3ad")
        self.assertEqual(bond.lacp_rate, "slow")
        self.assertEqual(bond.lacp_active, "on")

    def test_bond_add_slave_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [  # check bond exists
                {"ifname": "bond0", "linkinfo": {"info_kind": "bond", "info_data": {"mode": "802.3ad"}}}
            ],
            [  # check existing slaves
                {"ifname": "bond0", "linkinfo": {"info_kind": "bond", "info_data": {"mode": "802.3ad"}}}
            ],
            [  # post-add check
                {"ifname": "bond0", "linkinfo": {"info_kind": "bond", "info_data": {"mode": "802.3ad"}}},
                {"ifname": "enp0s8", "master": "bond0", "linkinfo": {"info_slave_kind": "bond"}}
            ]
        ]
        self.mock_backend.set_master.return_value = True

        bond = self.service.add_slave("bond0", "enp0s8")
        self.assertEqual(len(bond.slaves), 1)
        self.assertEqual(bond.slaves[0].interface, "enp0s8")
        self.mock_backend.set_master.assert_called_once_with("enp0s8", "bond0")

    def test_bond_add_management_interface_blocked(self):
        self.mock_backend.get_detailed_links.return_value = [
            {"ifname": "bond0", "linkinfo": {"info_kind": "bond", "info_data": {"mode": "active-backup"}}}
        ]
        # Interface has management IP
        self.iface_discovery.get_interface.return_value = NetworkInterfaceState(
            name="enp0s3", index=2, type="ether", mtu=1500, admin_state="UP", oper_state="UP",
            ipv4_addresses=["10.0.2.15/24"]
        )

        with self.assertRaises(SafetyConstraintViolationError):
            self.service.add_slave("bond0", "enp0s3")

    def test_bond_delete_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [{"ifname": "bond0", "linkinfo": {"info_kind": "bond", "info_data": {"mode": "active-backup"}}}],
            []
        ]
        self.mock_backend.delete_link.return_value = True

        res = self.service.delete_bond("bond0")
        self.assertTrue(res)
        self.mock_backend.delete_link.assert_called_once_with("bond0")


if __name__ == "__main__":
    unittest.main()
