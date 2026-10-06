"""
Unit Test Suite for MitraNet Interface Configuration Engine (Phase 1B).

Tests:
1. Interface UP / DOWN configuration and verification.
2. Interface MTU modification, boundary checking, and verification.
3. Interface MAC address modification and validation.
4. IPv4 address addition and removal (CIDR validation, duplicate prevention, verification).
5. IPv6 address addition and removal (CIDR validation, duplicate prevention, verification).
6. Security checks: rejection of shell injection strings (e.g. eth0;whoami, $(whoami)).
7. Loopback safety protections: prevent DOWN lo, changing lo MAC, removing 127.0.0.1/8 or ::1/128.
8. Nonexistent interface error handling.
9. Verification mismatch detection.
10. CLI command handlers for up, down, set, address add/remove.
"""

import copy
import json
import unittest
from unittest.mock import patch
from io import StringIO
from typing import List, Dict, Any, Optional

from mitranet.core.network.backend import NetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.validator import InterfaceConfigValidator
from mitranet.core.network.exceptions import (
    InterfaceNotFoundError,
    NetworkValidationError,
    NetworkSecurityError,
    SafetyConstraintViolationError,
    VerificationFailureError,
)
from mitranet.src.cli.main import (
    cmd_interface_up,
    cmd_interface_down,
    cmd_interface_set,
    cmd_interface_address,
)


class MutableMockNetworkBackend(NetworkBackend):
    """
    Mock backend that simulates dynamic kernel state mutations
    for ip link set and ip address add/del operations.
    """
    def __init__(self, links_data: List[Dict[str, Any]], addrs_data: List[Dict[str, Any]], carriers: Dict[str, Optional[bool]] = None):
        self.links = copy.deepcopy(links_data)
        self.addrs = copy.deepcopy(addrs_data)
        self.carriers = carriers or {}
        self.command_history: List[str] = []

    def get_link_info(self) -> List[Dict[str, Any]]:
        return self.links

    def get_addr_info(self) -> List[Dict[str, Any]]:
        return self.addrs

    def get_carrier(self, iface_name: str) -> Optional[bool]:
        return self.carriers.get(iface_name)

    def get_sys_statistics(self, iface_name: str) -> Optional[Dict[str, int]]:
        return None

    def set_interface_up(self, iface_name: str):
        self.command_history.append(f"ip link set dev {iface_name} up")
        for link in self.links:
            if link.get("ifname") == iface_name:
                flags = link.get("flags", [])
                if "UP" not in flags:
                    flags.append("UP")
                link["flags"] = flags
                link["operstate"] = "UP"
                return

    def set_interface_down(self, iface_name: str):
        self.command_history.append(f"ip link set dev {iface_name} down")
        for link in self.links:
            if link.get("ifname") == iface_name:
                flags = [f for f in link.get("flags", []) if f != "UP"]
                link["flags"] = flags
                link["operstate"] = "DOWN"
                return

    def set_mtu(self, iface_name: str, mtu: int):
        self.command_history.append(f"ip link set dev {iface_name} mtu {mtu}")
        for link in self.links:
            if link.get("ifname") == iface_name:
                link["mtu"] = mtu
                return

    def set_mac_address(self, iface_name: str, mac_address: str):
        self.command_history.append(f"ip link set dev {iface_name} address {mac_address}")
        for link in self.links:
            if link.get("ifname") == iface_name:
                link["address"] = mac_address.lower()
                return

    def add_address(self, iface_name: str, cidr: str):
        self.command_history.append(f"ip address add {cidr} dev {iface_name}")
        ip_part, prefix_part = cidr.split("/")
        family = "inet6" if ":" in ip_part else "inet"
        for addr_entry in self.addrs:
            if addr_entry.get("ifname") == iface_name:
                addr_info = addr_entry.setdefault("addr_info", [])
                addr_info.append({
                    "family": family,
                    "local": ip_part,
                    "prefixlen": int(prefix_part),
                    "scope": "global",
                })
                return

    def remove_address(self, iface_name: str, cidr: str):
        self.command_history.append(f"ip address del {cidr} dev {iface_name}")
        ip_part, prefix_part = cidr.split("/")
        prefixlen = int(prefix_part)
        for addr_entry in self.addrs:
            if addr_entry.get("ifname") == iface_name:
                addr_info = addr_entry.get("addr_info", [])
                addr_entry["addr_info"] = [
                    a for a in addr_info
                    if not (a.get("local") == ip_part and a.get("prefixlen") == prefixlen)
                ]
                return


class TestInterfaceConfiguration(unittest.TestCase):
    def setUp(self):
        with open("C:/mitranet/tests/fixtures/network/ip_link_sample.json", "r", encoding="utf-8") as f:
            self.sample_links = json.load(f)
        with open("C:/mitranet/tests/fixtures/network/ip_addr_sample.json", "r", encoding="utf-8") as f:
            self.sample_addrs = json.load(f)

        self.backend = MutableMockNetworkBackend(
            links_data=self.sample_links,
            addrs_data=self.sample_addrs,
            carriers={"eth0": True, "eth1": False, "lo": None}
        )
        self.discovery = InterfaceDiscoveryService(backend=self.backend)
        self.service = InterfaceConfigurationService(backend=self.backend, discovery=self.discovery)

    # 1. UP / DOWN
    def test_set_interface_down_and_up(self):
        # eth0 is originally UP
        iface = self.service.set_interface_down("eth0")
        self.assertEqual(iface.admin_state, "DOWN")
        self.assertIn("ip link set dev eth0 down", self.backend.command_history)

        # now set it UP
        iface_up = self.service.set_interface_up("eth0")
        self.assertEqual(iface_up.admin_state, "UP")
        self.assertIn("ip link set dev eth0 up", self.backend.command_history)

    # 2. MTU
    def test_set_mtu_valid(self):
        iface = self.service.set_mtu("eth0", 9000)
        self.assertEqual(iface.mtu, 9000)
        self.assertIn("ip link set dev eth0 mtu 9000", self.backend.command_history)

    def test_set_mtu_invalid_ranges(self):
        with self.assertRaises(NetworkValidationError):
            self.service.set_mtu("eth0", 50)  # below 68
        with self.assertRaises(NetworkValidationError):
            self.service.set_mtu("eth0", 100000)  # above 65535
        with self.assertRaises(NetworkValidationError):
            self.service.set_mtu("eth0", "invalid_mtu")

    # 3. MAC
    def test_set_mac_valid(self):
        new_mac = "52:54:00:aa:bb:cc"
        iface = self.service.set_mac("eth0", new_mac)
        self.assertEqual(iface.mac_address, new_mac)
        self.assertIn(f"ip link set dev eth0 address {new_mac}", self.backend.command_history)

    def test_set_mac_invalid_format(self):
        with self.assertRaises(NetworkValidationError):
            self.service.set_mac("eth0", "invalid-mac-address")
        with self.assertRaises(NetworkValidationError):
            self.service.set_mac("eth0", "00:11:22:33:44")  # incomplete

    # 4. IPv4 ADD / REMOVE
    def test_ipv4_add_and_remove(self):
        new_ip = "192.168.200.1/24"
        iface = self.service.add_address("eth0", new_ip)
        self.assertIn(new_ip, iface.ipv4_addresses)
        self.assertIn(f"ip address add {new_ip} dev eth0", self.backend.command_history)

        # Remove address
        iface_rem = self.service.remove_address("eth0", new_ip)
        self.assertNotIn(new_ip, iface_rem.ipv4_addresses)
        self.assertIn(f"ip address del {new_ip} dev eth0", self.backend.command_history)

    def test_ipv4_add_duplicate(self):
        # eth0 already has 192.168.100.10/24 in sample data
        with self.assertRaises(NetworkValidationError) as ctx:
            self.service.add_address("eth0", "192.168.100.10/24")
        self.assertIn("already assigned", str(ctx.exception))

    def test_ipv4_remove_nonexistent(self):
        with self.assertRaises(NetworkValidationError) as ctx:
            self.service.remove_address("eth0", "10.99.99.99/24")
        self.assertIn("not assigned", str(ctx.exception))

    # 5. IPv6 ADD / REMOVE
    def test_ipv6_add_and_remove(self):
        new_ipv6 = "2001:db8:abc::1/64"
        iface = self.service.add_address("eth0", new_ipv6)
        self.assertIn(new_ipv6, iface.ipv6_addresses)
        self.assertIn(f"ip address add {new_ipv6} dev eth0", self.backend.command_history)

        iface_rem = self.service.remove_address("eth0", new_ipv6)
        self.assertNotIn(new_ipv6, iface_rem.ipv6_addresses)
        self.assertIn(f"ip address del {new_ipv6} dev eth0", self.backend.command_history)

    def test_invalid_ip_cidr(self):
        with self.assertRaises(NetworkValidationError):
            self.service.add_address("eth0", "192.168.1.1")  # missing prefix
        with self.assertRaises(NetworkValidationError):
            self.service.add_address("eth0", "999.999.999.999/24")  # invalid IP
        with self.assertRaises(NetworkValidationError):
            self.service.add_address("eth0", "2001:db8:::1/64")  # invalid IPv6

    # 6. SECURITY: Injection rejection
    def test_security_injection_rejection(self):
        malicious_names = [
            "eth0;whoami",
            "eth0 && whoami",
            "$(whoami)",
            "`whoami`",
            "eth0|id",
            "eth0>test",
            "eth0\nrm -rf /",
        ]
        for name in malicious_names:
            with self.assertRaises(NetworkSecurityError):
                InterfaceConfigValidator.validate_interface_name(name)
            with self.assertRaises(NetworkSecurityError):
                self.service.set_interface_up(name)

    # 7. SAFETY: Loopback protection
    def test_loopback_safety_constraints(self):
        # Cannot down lo
        with self.assertRaises(SafetyConstraintViolationError) as ctx:
            self.service.set_interface_down("lo")
        self.assertIn("disabling loopback", str(ctx.exception).lower())

        # Cannot alter lo MAC
        with self.assertRaises(SafetyConstraintViolationError):
            self.service.set_mac("lo", "00:11:22:33:44:55")

        # Cannot remove 127.0.0.1/8 or ::1/128
        with self.assertRaises(SafetyConstraintViolationError):
            self.service.remove_address("lo", "127.0.0.1/8")
        with self.assertRaises(SafetyConstraintViolationError):
            self.service.remove_address("lo", "::1/128")

    # 8. Missing Interface
    def test_nonexistent_interface(self):
        with self.assertRaises(InterfaceNotFoundError):
            self.service.set_interface_up("eth99")
        with self.assertRaises(InterfaceNotFoundError):
            self.service.set_mtu("eth99", 1500)

    # 9. Verification Failure
    def test_verification_failure_detection(self):
        # Create a backend that lies / fails to mutate state
        class BrokenBackend(MutableMockNetworkBackend):
            def set_mtu(self, iface_name: str, mtu: int):
                pass  # do nothing, simulated silent driver failure

        broken_backend = BrokenBackend(self.sample_links, self.sample_addrs)
        discovery = InterfaceDiscoveryService(backend=broken_backend)
        svc = InterfaceConfigurationService(backend=broken_backend, discovery=discovery)

        with self.assertRaises(VerificationFailureError) as ctx:
            svc.set_mtu("eth0", 9000)
        self.assertIn("verification failed", str(ctx.exception).lower())

    # 10. CLI Tests
    def test_cli_interface_up_down(self):
        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_down(["eth0"], service=self.service)
        out = buf.getvalue()
        self.assertIn("[SUCCESS] Interface 'eth0' administrative state is now DOWN", out)

        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_up(["eth0"], service=self.service)
        out = buf.getvalue()
        self.assertIn("[SUCCESS] Interface 'eth0' administrative state is now UP", out)

    def test_cli_interface_set_mtu_and_mac(self):
        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_set(["eth0", "mtu", "9000"], service=self.service)
        self.assertIn("[SUCCESS] Interface 'eth0' MTU set to 9000.", buf.getvalue())

        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_set(["eth0", "mac", "52:54:00:99:88:77"], service=self.service)
        self.assertIn("[SUCCESS] Interface 'eth0' MAC address set to 52:54:00:99:88:77.", buf.getvalue())

    def test_cli_interface_address_add_remove(self):
        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_address(["add", "eth0", "10.0.0.1/24"], service=self.service)
        self.assertIn("[SUCCESS] Address 10.0.0.1/24 added to interface 'eth0'.", buf.getvalue())

        buf = StringIO()
        with patch("sys.stdout", buf):
            cmd_interface_address(["remove", "eth0", "10.0.0.1/24"], service=self.service)
        self.assertIn("[SUCCESS] Address 10.0.0.1/24 removed from interface 'eth0'.", buf.getvalue())

    def test_cli_security_error_exit(self):
        buf = StringIO()
        with patch("sys.stdout", buf):
            with self.assertRaises(SystemExit):
                cmd_interface_up(["eth0;whoami"], service=self.service)
        self.assertIn("[VALIDATION ERROR]", buf.getvalue())


if __name__ == "__main__":
    unittest.main()
