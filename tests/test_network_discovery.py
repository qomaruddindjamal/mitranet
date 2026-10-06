"""
Unit Test Suite for MitraNet Interface Discovery and State Engine (Phase 1A).
Tests:
1. Interface model validation and statistics.
2. Discovery parsing over rtnetlink / iproute2 JSON.
3. Administrative vs Operational state mapping.
4. Physical carrier detection and null-handling.
5. Missing interface errors.
6. Dynamic device name discovery (lo, eth0, ens18.100).
7. IPv4 and IPv6 multi-address handling.
8. Interface types (loopback, ether, vlan).
"""

import json
import unittest
from typing import List, Dict, Any, Optional
from mitranet.core.network.backend import NetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.models import NetworkInterfaceState, InterfaceStatistics
from mitranet.core.network.exceptions import InterfaceNotFoundError


class MockNetworkBackend(NetworkBackend):
    def __init__(self, links_data: List[Dict[str, Any]], addrs_data: List[Dict[str, Any]], carriers: Dict[str, Optional[bool]] = None):
        self.links = links_data
        self.addrs = addrs_data
        self.carriers = carriers or {}

    def get_link_info(self) -> List[Dict[str, Any]]:
        return self.links

    def get_addr_info(self) -> List[Dict[str, Any]]:
        return self.addrs

    def get_carrier(self, iface_name: str) -> Optional[bool]:
        return self.carriers.get(iface_name)

    def get_sys_statistics(self, iface_name: str) -> Optional[Dict[str, int]]:
        return None


class TestInterfaceDiscovery(unittest.TestCase):
    def setUp(self):
        with open("C:/mitranet/tests/fixtures/network/ip_link_sample.json", "r", encoding="utf-8") as f:
            self.sample_links = json.load(f)
        with open("C:/mitranet/tests/fixtures/network/ip_addr_sample.json", "r", encoding="utf-8") as f:
            self.sample_addrs = json.load(f)

        self.backend = MockNetworkBackend(
            links_data=self.sample_links,
            addrs_data=self.sample_addrs,
            carriers={"eth0": True, "eth1": False, "lo": None}
        )
        self.service = InterfaceDiscoveryService(backend=self.backend)

    def test_discover_interfaces_count_and_names(self):
        ifaces = self.service.discover_interfaces()
        self.assertEqual(len(ifaces), 4)
        names = [i.name for i in ifaces]
        self.assertEqual(names, ["lo", "eth0", "eth1", "ens18.100"])

    def test_loopback_discovery(self):
        lo = self.service.get_interface("lo")
        self.assertEqual(lo.name, "lo")
        self.assertEqual(lo.type, "loopback")
        self.assertEqual(lo.mtu, 65536)
        self.assertEqual(lo.admin_state, "UP")
        self.assertIn("127.0.0.1/8", lo.ipv4_addresses)
        self.assertIn("::1/128", lo.ipv6_addresses)
        self.assertIsNone(lo.carrier)

    def test_ethernet_up_carrier_up(self):
        eth0 = self.service.get_interface("eth0")
        self.assertEqual(eth0.name, "eth0")
        self.assertEqual(eth0.index, 2)
        self.assertEqual(eth0.type, "ether")
        self.assertEqual(eth0.mac_address, "52:54:00:12:34:56")
        self.assertEqual(eth0.mtu, 1500)
        self.assertEqual(eth0.admin_state, "UP")
        self.assertEqual(eth0.oper_state, "UP")
        self.assertTrue(eth0.carrier)
        self.assertIn("192.168.100.10/24", eth0.ipv4_addresses)
        self.assertIn("fe80::5054:ff:fe12:3456/64", eth0.ipv6_addresses)
        # Statistics
        self.assertEqual(eth0.statistics.rx_bytes, 1048576)
        self.assertEqual(eth0.statistics.tx_bytes, 524288)

    def test_ethernet_admin_up_oper_down_carrier_down(self):
        eth1 = self.service.get_interface("eth1")
        self.assertEqual(eth1.name, "eth1")
        self.assertEqual(eth1.admin_state, "UP")
        self.assertEqual(eth1.oper_state, "DOWN")
        self.assertFalse(eth1.carrier)
        self.assertEqual(len(eth1.ipv4_addresses), 0)
        self.assertEqual(len(eth1.ipv6_addresses), 0)

    def test_vlan_type_identification(self):
        vlan = self.service.get_interface("ens18.100")
        self.assertEqual(vlan.type, "vlan")
        self.assertEqual(vlan.parent_device, "eth0")
        self.assertIn("10.100.0.1/24", vlan.ipv4_addresses)

    def test_missing_interface_raises_error(self):
        with self.assertRaises(InterfaceNotFoundError):
            self.service.get_interface("eth99")


if __name__ == "__main__":
    unittest.main()
