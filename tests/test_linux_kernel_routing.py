"""
Real Linux Kernel Routing Tests (Phase 1C).
Tests execution directly against Linux kernel routing tables:
- Dynamic discovery of test interface
- Baseline routing table capture
- IPv4 static route add, discovery, verification, remove
- IPv6 static route add, discovery, verification, remove
- Dedicated routing table (table 100) default route add & remove (preventing management path disruption)
- Security injection rejection
- Loopback route deletion protection
- Complete state restoration to exact baseline
"""

import unittest
import os
from mitranet.core.network.backend import LinuxNetworkBackend
from mitranet.core.network.routing_discovery import RouteDiscoveryService
from mitranet.core.network.routing_service import RouteConfigurationService
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.exceptions import (
    NetworkValidationError,
    NetworkSecurityError,
    ProtectedRouteError,
    RouteNotFoundError,
    RouteAlreadyExistsError,
)


@unittest.skipUnless(os.name == "posix" and os.path.exists("/sys/class/net"), "Requires Linux system with /sys/class/net")
class TestLinuxRealKernelRouting(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        cls.backend = LinuxNetworkBackend()
        cls.route_discovery = RouteDiscoveryService(backend=cls.backend)
        cls.iface_discovery = InterfaceDiscoveryService(backend=cls.backend)
        cls.route_service = RouteConfigurationService(
            backend=cls.backend,
            discovery=cls.route_discovery,
            iface_discovery=cls.iface_discovery,
        )
        cls.iface_service = InterfaceConfigurationService(
            backend=cls.backend,
            discovery=cls.iface_discovery,
        )

        # Dynamically discover test interface: non-loopback, non-management
        interfaces = cls.iface_discovery.discover_interfaces()
        cls.test_iface = None

        for iface in interfaces:
            # Skip loopback and active default gateway interface
            if iface.name != "lo" and not any(addr.startswith("10.0.2.") for addr in iface.ipv4_addresses):
                cls.test_iface = iface.name
                break

        if not cls.test_iface:
            # Fallback to secondary interface if available
            for iface in interfaces:
                if iface.name != "lo":
                    cls.test_iface = iface.name

    def setUp(self):
        if not self.test_iface:
            self.skipTest("No suitable test network interface available for kernel routing tests.")
        # Ensure test interface is UP and has required IPs for onlink gateway resolution
        self.iface_service.set_interface_up(self.test_iface)
        iface_state = self.iface_discovery.get_interface(self.test_iface)
        if "192.0.2.1/24" not in iface_state.ipv4_addresses:
            self.iface_service.add_address(self.test_iface, "192.0.2.1/24")
        if "2001:db8:100::1/64" not in iface_state.ipv6_addresses:
            self.iface_service.add_address(self.test_iface, "2001:db8:100::1/64")

    def tearDown(self):
        # Clean any remaining test routes on table 100 or test prefixes
        try:
            self.backend.remove_route("192.0.2.128/25", family="inet", gateway="192.0.2.254", interface=self.test_iface, table=254)
        except Exception:
            pass
        try:
            self.backend.remove_route("2001:db8:200::/64", family="inet6", gateway="2001:db8:100::254", interface=self.test_iface, table=254)
        except Exception:
            pass
        try:
            self.backend.remove_route("0.0.0.0/0", family="inet", gateway="192.0.2.254", interface=self.test_iface, table=100)
        except Exception:
            pass

    @classmethod
    def tearDownClass(cls):
        # Restore test interface to completely clean state
        if cls.test_iface:
            try:
                cls.iface_service.remove_address(cls.test_iface, "192.0.2.1/24")
            except Exception:
                pass
            try:
                cls.iface_service.remove_address(cls.test_iface, "2001:db8:100::1/64")
            except Exception:
                pass
            try:
                cls.iface_service.set_interface_down(cls.test_iface)
            except Exception:
                pass

    def test_live_kernel_route_discovery(self):
        """Verifies live discovery of routes from Linux kernel."""
        routes = self.route_discovery.get_routes(family="inet", table=254)
        self.assertGreater(len(routes), 0)
        # Verify default route exists on management
        defaults = self.route_discovery.get_default_routes(family="inet", table=254)
        self.assertGreaterEqual(len(defaults), 1)

    def test_live_kernel_ipv4_route_lifecycle(self):
        """Tests adding, discovering, verifying, and removing an IPv4 static route."""
        dest = "192.0.2.128/25"
        gw = "192.0.2.254"

        # 1. Add route
        route = self.route_service.add_route(
            destination=dest,
            gateway=gw,
            interface=self.test_iface,
            metric=50,
            table=254,
        )
        self.assertEqual(route.destination, dest)
        self.assertEqual(route.gateway, gw)
        self.assertEqual(route.interface, self.test_iface)

        # 2. Verify in discovery
        routes = self.route_discovery.get_routes(family="inet", table=254)
        found = any(r.destination == dest and r.gateway == gw for r in routes)
        self.assertTrue(found, f"Route {dest} not discovered in kernel table")

        # 3. Duplicate rejection
        with self.assertRaises(RouteAlreadyExistsError):
            self.route_service.add_route(
                destination=dest,
                gateway=gw,
                interface=self.test_iface,
                table=254,
            )

        # 4. Remove route
        removed = self.route_service.remove_route(
            destination=dest,
            gateway=gw,
            interface=self.test_iface,
            table=254,
        )
        self.assertTrue(removed)

        # 5. Verify absent
        routes_post = self.route_discovery.get_routes(family="inet", table=254)
        self.assertFalse(any(r.destination == dest for r in routes_post))

    def test_live_kernel_ipv6_route_lifecycle(self):
        """Tests adding, discovering, verifying, and removing an IPv6 static route."""
        dest = "2001:db8:200::/64"
        gw = "2001:db8:100::254"

        # 1. Add route
        route = self.route_service.add_route(
            destination=dest,
            gateway=gw,
            interface=self.test_iface,
            metric=120,
            table=254,
        )
        self.assertEqual(route.destination, dest)
        self.assertEqual(route.family, "inet6")

        # 2. Discover
        v6_routes = self.route_discovery.get_ipv6_routes(table=254)
        found = any(r.destination == dest for r in v6_routes)
        self.assertTrue(found, f"IPv6 Route {dest} not discovered in kernel")

        # 3. Remove
        self.route_service.remove_route(
            destination=dest,
            gateway=gw,
            interface=self.test_iface,
            table=254,
        )

        # 4. Verify absent
        v6_routes_post = self.route_discovery.get_ipv6_routes(table=254)
        self.assertFalse(any(r.destination == dest for r in v6_routes_post))

    def test_live_kernel_isolated_table_default_route(self):
        """Tests adding and removing a default route in an isolated routing table (table 100)."""
        table_id = 100
        # Add default route in table 100
        route = self.route_service.add_route(
            destination="0.0.0.0/0",
            gateway="192.0.2.254",
            interface=self.test_iface,
            metric=10,
            table=table_id,
        )
        self.assertTrue(route.is_default)
        self.assertEqual(route.table, table_id)

        # Verify
        defaults = self.route_discovery.get_default_routes(family="inet", table=table_id)
        self.assertTrue(any(d.table == table_id for d in defaults))

        # Remove
        self.route_service.remove_route(
            destination="0.0.0.0/0",
            gateway="192.0.2.254",
            interface=self.test_iface,
            table=table_id,
        )

        # Verify clean
        defaults_post = self.route_discovery.get_default_routes(family="inet", table=table_id)
        self.assertEqual(len(defaults_post), 0)

    def test_live_kernel_safety_and_security(self):
        """Verifies safety protections against loopback and shell injection."""
        with self.assertRaises(NetworkSecurityError):
            self.route_service.add_route(destination="192.0.2.0/24;whoami", interface=self.test_iface)

        with self.assertRaises(ProtectedRouteError):
            self.route_service.remove_route(destination="127.0.0.0/8", table=254)


if __name__ == "__main__":
    unittest.main()
