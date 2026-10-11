"""
Unit Test Suite for MitraNet 802.1Q VLAN Service (Phase 1D).
Covers:
- VLAN creation, discovery, deletion
- Duplicate VLAN detection (by name and by parent+id)
- VLAN ID boundary and format validation (1-4094, rejecting 0, 4095, strings)
- Parent interface existence and loopback protection
- Shell injection and metacharacter security rejection
- Verification mismatch detection
- Error handling
"""

import unittest
from unittest.mock import MagicMock
from mitranet.core.network.models import VlanState, NetworkInterfaceState
from mitranet.core.network.backend import NetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.vlan import VlanService
from mitranet.core.network.validator import VlanValidator
from mitranet.core.network.exceptions import (
    NetworkValidationError,
    NetworkSecurityError,
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    VerificationFailureError,
    InterfaceNotFoundError,
)


class TestVlanService(unittest.TestCase):

    def setUp(self):
        self.mock_backend = MagicMock(spec=NetworkBackend)
        self.iface_discovery = MagicMock(spec=InterfaceDiscoveryService)

        # Default parent interface enp0s8 exists
        self.iface_discovery.get_interface.return_value = NetworkInterfaceState(
            name="enp0s8", index=3, type="ether", mtu=1500, admin_state="UP", oper_state="UP"
        )

        self.mock_backend.get_detailed_links.return_value = []
        self.mock_backend.get_addr_info.return_value = []

        self.service = VlanService(backend=self.mock_backend, iface_discovery=self.iface_discovery)

    def test_vlan_id_validation_valid_and_invalid(self):
        # Valid VLAN IDs
        self.assertEqual(VlanValidator.validate_vlan_id(1), 1)
        self.assertEqual(VlanValidator.validate_vlan_id(100), 100)
        self.assertEqual(VlanValidator.validate_vlan_id(4094), 4094)
        self.assertEqual(VlanValidator.validate_vlan_id("200"), 200)

        # Invalid VLAN IDs
        with self.assertRaises(NetworkValidationError):
            VlanValidator.validate_vlan_id(0)
        with self.assertRaises(NetworkValidationError):
            VlanValidator.validate_vlan_id(4095)
        with self.assertRaises(NetworkValidationError):
            VlanValidator.validate_vlan_id(-10)
        with self.assertRaises(NetworkValidationError):
            VlanValidator.validate_vlan_id("invalid")

    def test_vlan_parent_validation_loopback_rejected(self):
        with self.assertRaises(NetworkValidationError):
            VlanValidator.validate_parent_interface("lo")

    def test_vlan_security_injection_rejected(self):
        with self.assertRaises(NetworkSecurityError):
            VlanValidator.validate_vlan_name("vlan100;whoami")
        with self.assertRaises(NetworkSecurityError):
            VlanValidator.validate_parent_interface("eth0$(id)")

    def test_vlan_create_success(self):
        # Mock detailed links returned after creation
        self.mock_backend.get_detailed_links.side_effect = [
            [],  # initial check
            [    # post-creation check
                {
                    "ifname": "enp0s8.100",
                    "link": "enp0s8",
                    "flags": ["BROADCAST", "MULTICAST"],
                    "mtu": 1500,
                    "operstate": "DOWN",
                    "address": "08:00:27:11:22:33",
                    "linkinfo": {
                        "info_kind": "vlan",
                        "info_data": {"id": 100, "protocol": "802.1Q"}
                    }
                }
            ]
        ]
        self.mock_backend.create_vlan.return_value = True

        vlan = self.service.create_vlan("enp0s8.100", parent="enp0s8", vlan_id=100)
        self.assertEqual(vlan.name, "enp0s8.100")
        self.assertEqual(vlan.parent, "enp0s8")
        self.assertEqual(vlan.vlan_id, 100)
        self.assertEqual(vlan.protocol, "802.1Q")
        self.mock_backend.create_vlan.assert_called_once_with("enp0s8.100", "enp0s8", 100, proto="802.1Q")

    def test_vlan_create_duplicate_raises_error(self):
        self.mock_backend.get_detailed_links.return_value = [
            {
                "ifname": "enp0s8.100",
                "link": "enp0s8",
                "flags": ["BROADCAST", "MULTICAST"],
                "mtu": 1500,
                "linkinfo": {
                    "info_kind": "vlan",
                    "info_data": {"id": 100, "protocol": "802.1Q"}
                }
            }
        ]

        with self.assertRaises(DeviceAlreadyExistsError):
            self.service.create_vlan("enp0s8.100", parent="enp0s8", vlan_id=100)

        with self.assertRaises(DeviceAlreadyExistsError):
            self.service.create_vlan("vlan100_custom", parent="enp0s8", vlan_id=100)

    def test_vlan_create_parent_not_found(self):
        self.iface_discovery.get_interface.side_effect = InterfaceNotFoundError("eth99 not found")
        with self.assertRaises(InterfaceNotFoundError):
            self.service.create_vlan("eth99.10", parent="eth99", vlan_id=10)

    def test_vlan_create_verification_failure(self):
        # Backend returns True on create_vlan but get_detailed_links remains empty
        self.mock_backend.get_detailed_links.return_value = []
        self.mock_backend.create_vlan.return_value = True

        with self.assertRaises(VerificationFailureError):
            self.service.create_vlan("enp0s8.200", parent="enp0s8", vlan_id=200)

    def test_vlan_delete_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [
                {
                    "ifname": "enp0s8.100",
                    "link": "enp0s8",
                    "linkinfo": {"info_kind": "vlan", "info_data": {"id": 100}}
                }
            ],
            []  # post-deletion check
        ]
        self.mock_backend.delete_link.return_value = True

        res = self.service.delete_vlan("enp0s8.100")
        self.assertTrue(res)
        self.mock_backend.delete_link.assert_called_once_with("enp0s8.100")

    def test_vlan_delete_nonexistent_raises_error(self):
        self.mock_backend.get_detailed_links.return_value = []
        with self.assertRaises(DeviceNotFoundError):
            self.service.delete_vlan("enp0s8.999")


if __name__ == "__main__":
    unittest.main()
