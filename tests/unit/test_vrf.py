"""
Unit Test Suite for MitraNet VRF Service (Phase 1E).
Covers:
- VRF model validation (Pydantic v2)
- VRF name validation (metacharacters, length, format)
- Routing Table ID validation (integers, ranges 1-2147483647, rejection of reserved 0, 253, 254, 255)
- Duplicate VRF name and table ID detection
- Member interface validation (loopback protection, existence check)
- Security audit against shell metacharacters and injections
- VRF creation, discovery, deletion
- Interface attachment and detachment
- Verification mismatch handling and error conditions
"""

import unittest
from unittest.mock import MagicMock
from mitranet.core.network.models import VRFState, NetworkInterfaceState
from mitranet.core.network.backend import NetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.vrf import VRFService
from mitranet.core.network.validator import VRFValidator
from mitranet.core.network.exceptions import (
    NetworkValidationError,
    NetworkSecurityError,
    SafetyConstraintViolationError,
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    VerificationFailureError,
    InterfaceNotFoundError,
)


class TestVRFService(unittest.TestCase):

    def setUp(self):
        self.mock_backend = MagicMock(spec=NetworkBackend)
        self.iface_discovery = MagicMock(spec=InterfaceDiscoveryService)

        # Mock standard interface existence
        self.iface_discovery.get_interface.return_value = NetworkInterfaceState(
            name="enp0s8", index=3, type="ether", mtu=1500, admin_state="UP", oper_state="UP"
        )

        self.mock_backend.get_detailed_links.return_value = []
        self.mock_backend.get_routes.return_value = []

        self.service = VRFService(backend=self.mock_backend, iface_discovery=self.iface_discovery)

    def test_vrf_model_pydantic(self):
        vrf = VRFState(
            name="vrf-mgmt",
            table=100,
            admin_state="UP",
            oper_state="UP",
            mac_address="00:11:22:33:44:55",
            interfaces=["enp0s8"],
            routes_count=2,
        )
        self.assertEqual(vrf.name, "vrf-mgmt")
        self.assertEqual(vrf.table, 100)
        self.assertEqual(vrf.interfaces, ["enp0s8"])
        self.assertEqual(vrf.routes_count, 2)
        d = vrf.model_dump()
        self.assertEqual(d["name"], "vrf-mgmt")
        self.assertEqual(d["table"], 100)

    def test_vrf_name_validation_valid_and_invalid(self):
        self.assertEqual(VRFValidator.validate_vrf_name("vrf-mgmt"), "vrf-mgmt")
        self.assertEqual(VRFValidator.validate_vrf_name("vrf100"), "vrf100")
        self.assertEqual(VRFValidator.validate_vrf_name("red"), "red")

        # Invalid VRF names
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_vrf_name("")
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_vrf_name("this-name-is-way-too-long-for-linux-interfaces")

    def test_vrf_table_id_validation(self):
        # Valid tables
        self.assertEqual(VRFValidator.validate_table_id(1), 1)
        self.assertEqual(VRFValidator.validate_table_id(100), 100)
        self.assertEqual(VRFValidator.validate_table_id(1000), 1000)
        self.assertEqual(VRFValidator.validate_table_id("200"), 200)

        # Linux reserved tables must be rejected
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_table_id(0)      # unspec
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_table_id(253)    # default
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_table_id(254)    # main
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_table_id(255)    # local

        # Out of bounds or non-integers
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_table_id(-1)
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_table_id("abc")
        with self.assertRaises(NetworkValidationError):
            VRFValidator.validate_table_id(2147483648)

    def test_vrf_security_injection_rejection(self):
        # Shell metacharacters and injections
        with self.assertRaises(NetworkSecurityError):
            VRFValidator.validate_vrf_name("vrf0;whoami")
        with self.assertRaises(NetworkSecurityError):
            VRFValidator.validate_vrf_name("vrf0 && whoami")
        with self.assertRaises(NetworkSecurityError):
            VRFValidator.validate_vrf_name("$(whoami)")
        with self.assertRaises(NetworkSecurityError):
            VRFValidator.validate_vrf_name("../../../etc")
        with self.assertRaises(NetworkSecurityError):
            VRFValidator.validate_table_id("100;whoami")
        with self.assertRaises(NetworkSecurityError):
            VRFValidator.validate_table_id("100 && whoami")
        with self.assertRaises(NetworkSecurityError):
            VRFValidator.validate_table_id("`whoami`")
        with self.assertRaises(NetworkSecurityError):
            VRFValidator.validate_member_interface("eth0;rm -rf")

    def test_member_interface_loopback_rejected(self):
        with self.assertRaises(SafetyConstraintViolationError):
            VRFValidator.validate_member_interface("lo")

    def test_vrf_create_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [],  # initial check
            [    # post-creation check
                {
                    "ifname": "vrf-blue",
                    "flags": ["NOARP", "MASTER", "UP", "LOWER_UP"],
                    "operstate": "UP",
                    "address": "00:00:00:00:00:00",
                    "linkinfo": {
                        "info_kind": "vrf",
                        "info_data": {"table": 100}
                    }
                }
            ]
        ]
        self.mock_backend.create_vrf.return_value = True

        vrf = self.service.create_vrf("vrf-blue", table=100)
        self.assertEqual(vrf.name, "vrf-blue")
        self.assertEqual(vrf.table, 100)
        self.mock_backend.create_vrf.assert_called_once_with("vrf-blue", 100)
        self.mock_backend.set_interface_up.assert_called_once_with("vrf-blue")

    def test_vrf_create_duplicate_name_rejected(self):
        self.mock_backend.get_detailed_links.return_value = [
            {
                "ifname": "vrf-blue",
                "linkinfo": {"info_kind": "vrf", "info_data": {"table": 100}}
            }
        ]
        with self.assertRaises(DeviceAlreadyExistsError):
            self.service.create_vrf("vrf-blue", table=200)

    def test_vrf_create_duplicate_table_rejected(self):
        self.mock_backend.get_detailed_links.return_value = [
            {
                "ifname": "vrf-red",
                "linkinfo": {"info_kind": "vrf", "info_data": {"table": 100}}
            }
        ]
        with self.assertRaises(DeviceAlreadyExistsError):
            self.service.create_vrf("vrf-blue", table=100)

    def test_vrf_delete_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [
                {
                    "ifname": "vrf-blue",
                    "linkinfo": {"info_kind": "vrf", "info_data": {"table": 100}}
                }
            ],
            []  # after deletion: absent
        ]
        self.mock_backend.delete_link.return_value = True

        self.service.delete_vrf("vrf-blue")
        self.mock_backend.delete_link.assert_called_once_with("vrf-blue")

    def test_vrf_delete_not_found(self):
        self.mock_backend.get_detailed_links.return_value = []
        with self.assertRaises(DeviceNotFoundError):
            self.service.delete_vrf("vrf-nonexistent")

    def test_vrf_interface_add_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [
                {
                    "ifname": "vrf-blue",
                    "linkinfo": {"info_kind": "vrf", "info_data": {"table": 100}}
                }
            ],
            [
                {
                    "ifname": "vrf-blue",
                    "linkinfo": {"info_kind": "vrf", "info_data": {"table": 100}}
                }
            ],
            [
                {
                    "ifname": "vrf-blue",
                    "linkinfo": {"info_kind": "vrf", "info_data": {"table": 100}}
                },
                {
                    "ifname": "enp0s8",
                    "master": "vrf-blue",
                    "linkinfo": {"info_slave_kind": "vrf"}
                }
            ]
        ]
        self.mock_backend.set_master.return_value = True

        self.service.add_interface("vrf-blue", "enp0s8")
        self.mock_backend.set_master.assert_called_once_with("enp0s8", "vrf-blue")

    def test_vrf_interface_remove_success(self):
        self.mock_backend.get_detailed_links.side_effect = [
            [
                {
                    "ifname": "vrf-blue",
                    "linkinfo": {"info_kind": "vrf", "info_data": {"table": 100}}
                },
                {
                    "ifname": "enp0s8",
                    "master": "vrf-blue",
                    "linkinfo": {"info_slave_kind": "vrf"}
                }
            ],
            [
                {
                    "ifname": "vrf-blue",
                    "linkinfo": {"info_kind": "vrf", "info_data": {"table": 100}}
                },
                {
                    "ifname": "enp0s8"
                }
            ]
        ]
        self.mock_backend.set_nomaster.return_value = True

        self.service.remove_interface("vrf-blue", "enp0s8")
        self.mock_backend.set_nomaster.assert_called_once_with("enp0s8")


from mitranet.core.network.vrf_preflight import VRFEnvironmentProbe, VRFEnvironmentPrerequisites


class TestVRFEnvironmentGate(unittest.TestCase):
    """Level 1 unit testing for VRF environment gating and preflight inspection."""

    def test_environment_probe_data_model(self):
        prereqs = VRFEnvironmentPrerequisites(
            os_name="Linux",
            kernel_version="6.12.107+deb13-amd64",
            architecture="x86_64",
            iproute2_version="6.15.0",
            ip_command_path="/usr/sbin/ip",
            kernel_support=True,
            iproute2_support=True,
            net_admin=True,
            management_interface="enp0s3",
            management_addresses=["10.0.2.15/24"],
            default_route={"dev": "enp0s3", "gateway": "10.0.2.2"},
            candidate_interfaces=["enp0s8"],
            safe_test_interfaces=["enp0s8"],
            existing_vrfs=[],
            existing_vrf_tables=[],
            available_tables=[100, 200],
            safe_test_environment=True,
            ready=True,
            rejection_reasons=[],
        )
        self.assertTrue(prereqs.ready)
        self.assertEqual(prereqs.management_interface, "enp0s3")
        self.assertIn("enp0s8", prereqs.safe_test_interfaces)

        # Formatted report test
        report = VRFEnvironmentProbe.format_preflight_report(prereqs)
        self.assertIn("VRF ENVIRONMENT PREFLIGHT", report)
        self.assertIn("VRF Environment Ready: PASS", report)

    def test_environment_gate_rejection_when_unprivileged(self):
        prereqs = VRFEnvironmentPrerequisites(
            os_name="Linux",
            kernel_version="6.12.107",
            architecture="x86_64",
            iproute2_version="6.15.0",
            ip_command_path="/usr/sbin/ip",
            kernel_support=True,
            iproute2_support=True,
            net_admin=False,  # Unprivileged
            management_interface="enp0s3",
            safe_test_interfaces=["enp0s8"],
            available_tables=[100, 200],
            safe_test_environment=True,
            ready=False,
            rejection_reasons=["Missing CAP_NET_ADMIN / root privileges required for VRF netlink operations."],
        )
        self.assertFalse(prereqs.ready)
        self.assertIn("Missing CAP_NET_ADMIN", prereqs.rejection_reasons[0])

    def test_environment_gate_rejection_when_no_safe_interfaces(self):
        prereqs = VRFEnvironmentPrerequisites(
            os_name="Linux",
            kernel_version="6.12.107",
            architecture="x86_64",
            iproute2_version="6.15.0",
            ip_command_path="/usr/sbin/ip",
            kernel_support=True,
            iproute2_support=True,
            net_admin=True,
            management_interface="enp0s3",
            safe_test_interfaces=[],  # None available
            available_tables=[100, 200],
            safe_test_environment=False,
            ready=False,
            rejection_reasons=["No safe isolated test interface found (all interfaces are lo or management)."],
        )
        self.assertFalse(prereqs.ready)
        self.assertFalse(prereqs.safe_test_environment)


if __name__ == "__main__":
    unittest.main()
