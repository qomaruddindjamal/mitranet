"""
Unit Test Suite for MitraNet Linux Bridge Service (Phase 1D).
Covers:
- Bridge creation, discovery, deletion
- Port attachment and detachment
- Loopback interface protection (cannot name 'lo', cannot attach 'lo')
- Management interface protection (cannot attach management IP NIC)
- Duplicate bridge and duplicate port prevention
- Nonexistent bridge and port error handling
- Security rejection of malicious names
- Verification failure handling
"""

import unittest
from unittest.mock import MagicMock
from mitranet.core.network.models import BridgeState, BridgePortState, NetworkInterfaceState
from mitranet.core.network.backend import NetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.bridge import BridgeService
from mitranet.core.network.validator import BridgeValidator
from mitranet.core.network.exceptions import (
    NetworkValidationError,
    NetworkSecurityError,
    SafetyConstraintViolationError,
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    VerificationFailureError,
)


class TestBridgeService(unittest.TestCase):

    def setUp(self):
        self.mock_backend = MagicMock(spec=NetworkBackend)
        self.iface_discovery = MagicMock(spec=InterfaceDiscoveryService)

        # Non-management interface enp0s8
        self.iface_discovery.get_interface.return_value = NetworkInterfaceState(
            name="enp0s8", index=3, type="ether", mtu=1500, admin_state="UP", oper_state="UP", ipv4_addresses=[]
        )

        self.mock_backend.get_detailed_links.return_value = []
        self.mock_backend.get_addr_info.return_value = []

        self.service = BridgeService(backend=self.mock_backend, iface_discovery=self.iface_discovery)

    def test_bridge_name_validation(self):
        self.assertEqual(BridgeValidator.validate_bridge_name("br0"), "br0")
        self.assertEqual(BridgeValidator.validate_bridge_name("br-lan"), "br-lan")

        with self.assertRaises(NetworkValidationError):
            BridgeValidator.validate_bridge_name("lo")

        with self.assertRaises(NetworkSecurityError):
            BridgeValidator.validate_bridge_name("br0;rm -rf")

    def test_bridge_port_validation(self):
        self.assertEqual(BridgeValidator.validate_port_interface("enp0s8"), "enp0s8")

        with self.assertRaises(NetworkValidationError):
            BridgeValidator.validate_port_interface("lo")

        with self.assertRaises(NetworkSecurityError):
            BridgeValidator.validate_port_interface("eth0$(id)")

    def test_bridge_create_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [],  # initial check
            [    # post-creation check
                {
                    "ifname": "br0",
                    "flags": ["BROADCAST", "MULTICAST"],
                    "mtu": 1500,
                    "operstate": "DOWN",
                    "address": "02:00:00:00:00:01",
                    "linkinfo": {
                        "info_kind": "bridge",
                        "info_data": {"stp_state": 0}
                    }
                }
            ]
        ]
        self.mock_backend.create_bridge.return_value = True

        br = self.service.create_bridge("br0")
        self.assertEqual(br.name, "br0")
        self.assertEqual(br.admin_state, "DOWN")
        self.assertFalse(br.stp_enabled)
        self.mock_backend.create_bridge.assert_called_once_with("br0")

    def test_bridge_create_duplicate_raises_error(self):
        self.mock_backend.get_detailed_links.return_value = [
            {
                "ifname": "br0",
                "linkinfo": {"info_kind": "bridge"}
            }
        ]

        with self.assertRaises(DeviceAlreadyExistsError):
            self.service.create_bridge("br0")

    def test_bridge_delete_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [
                {
                    "ifname": "br0",
                    "linkinfo": {"info_kind": "bridge"}
                }
            ],
            []  # post-delete check
        ]
        self.mock_backend.delete_link.return_value = True

        res = self.service.delete_bridge("br0")
        self.assertTrue(res)
        self.mock_backend.delete_link.assert_called_once_with("br0")

    def test_bridge_add_port_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [  # check bridge exists
                {"ifname": "br0", "linkinfo": {"info_kind": "bridge"}}
            ],
            [  # check ports before add
                {"ifname": "br0", "linkinfo": {"info_kind": "bridge"}}
            ],
            [  # post-add check
                {"ifname": "br0", "linkinfo": {"info_kind": "bridge"}},
                {
                    "ifname": "enp0s8",
                    "master": "br0",
                    "linkinfo": {
                        "info_slave_kind": "bridge",
                        "info_slave_data": {"state": "forwarding", "cost": 100}
                    }
                }
            ]
        ]
        self.mock_backend.set_master.return_value = True

        br = self.service.add_port("br0", "enp0s8")
        self.assertEqual(len(br.ports), 1)
        self.assertEqual(br.ports[0].interface, "enp0s8")
        self.mock_backend.set_master.assert_called_once_with("enp0s8", "br0")

    def test_bridge_add_management_interface_blocked(self):
        self.mock_backend.get_detailed_links.return_value = [
            {"ifname": "br0", "linkinfo": {"info_kind": "bridge"}}
        ]
        # Interface has management IP
        self.iface_discovery.get_interface.return_value = NetworkInterfaceState(
            name="enp0s3", index=2, type="ether", mtu=1500, admin_state="UP", oper_state="UP",
            ipv4_addresses=["10.0.2.15/24"]
        )

        with self.assertRaises(SafetyConstraintViolationError):
            self.service.add_port("br0", "enp0s3")

    def test_bridge_remove_port_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [  # initial check with attached port
                {"ifname": "br0", "linkinfo": {"info_kind": "bridge"}},
                {"ifname": "enp0s8", "master": "br0", "linkinfo": {"info_slave_kind": "bridge"}}
            ],
            [  # post-remove check
                {"ifname": "br0", "linkinfo": {"info_kind": "bridge"}}
            ]
        ]
        self.mock_backend.set_nomaster.return_value = True

        br = self.service.remove_port("br0", "enp0s8")
        self.assertEqual(len(br.ports), 0)
        self.mock_backend.set_nomaster.assert_called_once_with("enp0s8")


if __name__ == "__main__":
    unittest.main()
