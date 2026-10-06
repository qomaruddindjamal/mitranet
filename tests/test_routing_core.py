"""
Unit Tests for Phase 1C: Routing Core.
Covers:
- Route models & validation
- IPv4 & IPv6 destination and gateway validation
- Route discovery & default route filtering
- Route add, remove, and duplicate handling
- Safety constraints (loopback, management route)
- Security checks & shell injection prevention
- Error handling & verification failures
"""

import unittest
from unittest.mock import MagicMock
try:
    from mitranet.core.network.models import RouteState, NetworkInterfaceState
    from mitranet.core.network.backend import NetworkBackend
    from mitranet.core.network.routing_discovery import RouteDiscoveryService
    from mitranet.core.network.routing_service import RouteConfigurationService
    from mitranet.core.network.validator import RouteValidator
    from mitranet.core.network.discovery import InterfaceDiscoveryService
    from mitranet.core.network.exceptions import (
        NetworkValidationError,
        NetworkSecurityError,
        SafetyConstraintViolationError,
        RouteNotFoundError,
        RouteAlreadyExistsError,
        ProtectedRouteError,
        VerificationFailureError,
    )
except ModuleNotFoundError:
    from core.network.models import RouteState, NetworkInterfaceState
    from core.network.backend import NetworkBackend
    from core.network.routing_discovery import RouteDiscoveryService
    from core.network.routing_service import RouteConfigurationService
    from core.network.validator import RouteValidator
    from core.network.discovery import InterfaceDiscoveryService
    from core.network.exceptions import (
        NetworkValidationError,
        NetworkSecurityError,
        SafetyConstraintViolationError,
        RouteNotFoundError,
        RouteAlreadyExistsError,
        ProtectedRouteError,
        VerificationFailureError,
    )


class TestRoutingCore(unittest.TestCase):

    def setUp(self):
        self.mock_backend = MagicMock(spec=NetworkBackend)
        self.discovery = RouteDiscoveryService(backend=self.mock_backend)
        self.iface_discovery = MagicMock(spec=InterfaceDiscoveryService)
        # Mock default interface presence
        self.iface_discovery.get_interface.return_value = NetworkInterfaceState(
            name="enp0s8", index=3, type="ether", mtu=1500, admin_state="UP", oper_state="UP"
        )
        self.service = RouteConfigurationService(
            backend=self.mock_backend,
            discovery=self.discovery,
            iface_discovery=self.iface_discovery,
        )

    # -------------------------------------------------------------------------
    # 1. Validation Tests
    # -------------------------------------------------------------------------

    def test_validate_valid_ipv4_destination(self):
        net = RouteValidator.validate_destination("192.0.2.0/24")
        self.assertEqual(str(net), "192.0.2.0/24")

    def test_validate_valid_ipv6_destination(self):
        net = RouteValidator.validate_destination("2001:db8:100::/64")
        self.assertEqual(str(net), "2001:db8:100::/64")

    def test_validate_default_destinations(self):
        net_v4 = RouteValidator.validate_destination("default")
        self.assertEqual(str(net_v4), "0.0.0.0/0")
        net_v6 = RouteValidator.validate_destination("::/0")
        self.assertEqual(str(net_v6), "::/0")

    def test_validate_invalid_destination(self):
        with self.assertRaises(NetworkValidationError):
            RouteValidator.validate_destination("999.999.999.999/24")
        with self.assertRaises(NetworkValidationError):
            RouteValidator.validate_destination("")

    def test_validate_destination_security_injection(self):
        with self.assertRaises(NetworkSecurityError):
            RouteValidator.validate_destination("192.0.2.0/24;whoami")
        with self.assertRaises(NetworkSecurityError):
            RouteValidator.validate_destination("192.0.2.0/24 && cat /etc/passwd")
        with self.assertRaises(NetworkSecurityError):
            RouteValidator.validate_destination("`reboot`")

    def test_validate_gateway_valid(self):
        gw4 = RouteValidator.validate_gateway("192.0.2.1", expected_family="inet")
        self.assertEqual(str(gw4), "192.0.2.1")
        gw6 = RouteValidator.validate_gateway("2001:db8:100::1", expected_family="inet6")
        self.assertEqual(str(gw6), "2001:db8:100::1")

    def test_validate_gateway_family_mismatch(self):
        with self.assertRaises(NetworkValidationError):
            RouteValidator.validate_gateway("2001:db8:100::1", expected_family="inet")
        with self.assertRaises(NetworkValidationError):
            RouteValidator.validate_gateway("192.0.2.1", expected_family="inet6")

    def test_validate_gateway_security_injection(self):
        with self.assertRaises(NetworkSecurityError):
            RouteValidator.validate_gateway("192.0.2.1$(id)")

    def test_validate_metric_valid_and_invalid(self):
        self.assertEqual(RouteValidator.validate_metric(100), 100)
        self.assertEqual(RouteValidator.validate_metric(None), None)
        with self.assertRaises(NetworkValidationError):
            RouteValidator.validate_metric(-5)
        with self.assertRaises(NetworkValidationError):
            RouteValidator.validate_metric("invalid")

    def test_validate_table_valid_and_invalid(self):
        self.assertEqual(RouteValidator.validate_table(254), 254)
        self.assertEqual(RouteValidator.validate_table("main"), 254)
        self.assertEqual(RouteValidator.validate_table(100), 100)
        with self.assertRaises(NetworkValidationError):
            RouteValidator.validate_table(0)
        with self.assertRaises(NetworkValidationError):
            RouteValidator.validate_table("foo;reboot")

    # -------------------------------------------------------------------------
    # 2. Discovery Tests
    # -------------------------------------------------------------------------

    def test_route_discovery_ipv4_and_ipv6(self):
        self.mock_backend.get_routes.side_effect = lambda family, table: [
            {"dst": "default", "gateway": "10.0.2.2", "dev": "enp0s3", "metric": 100},
            {"dst": "192.0.2.0/24", "gateway": "192.0.2.1", "dev": "enp0s8", "metric": 20},
        ] if family == "inet" else [
            {"dst": "2001:db8:100::/64", "gateway": "2001:db8:100::1", "dev": "enp0s8", "metric": 256},
            {"dst": "default", "gateway": "fe80::1", "dev": "enp0s3", "metric": 1024},
        ]

        routes = self.discovery.get_routes(family=None, table=254)
        self.assertEqual(len(routes), 4)
        v4_routes = self.discovery.get_ipv4_routes(table=254)
        self.assertEqual(len(v4_routes), 2)
        v6_routes = self.discovery.get_ipv6_routes(table=254)
        self.assertEqual(len(v6_routes), 2)

        defaults = self.discovery.get_default_routes(table=254)
        self.assertEqual(len(defaults), 2)
        self.assertTrue(all(d.is_default for d in defaults))

    # -------------------------------------------------------------------------
    # 3. Add & Remove Route Lifecycle Tests
    # -------------------------------------------------------------------------

    def test_add_route_success(self):
        # Initial empty, then discovered after add
        self.mock_backend.get_routes.side_effect = [
            [],  # duplicate check -> empty
            [{"dst": "192.0.2.0/24", "gateway": "192.0.2.1", "dev": "enp0s8", "table": 254}],  # verification
        ]
        self.mock_backend.add_route.return_value = True

        r = self.service.add_route(
            destination="192.0.2.0/24",
            gateway="192.0.2.1",
            interface="enp0s8",
            metric=10,
            table=254,
        )
        self.assertEqual(r.destination, "192.0.2.0/24")
        self.assertEqual(r.gateway, "192.0.2.1")
        self.mock_backend.add_route.assert_called_once_with(
            destination="192.0.2.0/24",
            family="inet",
            gateway="192.0.2.1",
            interface="enp0s8",
            metric=10,
            table=254,
        )

    def test_add_route_duplicate_raises_error(self):
        # Route already exists
        self.mock_backend.get_routes.return_value = [
            {"dst": "192.0.2.0/24", "gateway": "192.0.2.1", "dev": "enp0s8", "table": 254}
        ]
        with self.assertRaises(RouteAlreadyExistsError):
            self.service.add_route(
                destination="192.0.2.0/24",
                gateway="192.0.2.1",
                interface="enp0s8",
                table=254,
            )

    def test_add_route_verification_failure(self):
        self.mock_backend.get_routes.return_value = []
        self.mock_backend.add_route.return_value = True

        with self.assertRaises(VerificationFailureError):
            self.service.add_route(
                destination="192.0.2.0/24",
                gateway="192.0.2.1",
                interface="enp0s8",
            )

    def test_remove_route_success(self):
        self.mock_backend.get_routes.side_effect = [
            [{"dst": "192.0.2.0/24", "gateway": "192.0.2.1", "dev": "enp0s8", "table": 254}],  # existence check
            [],  # post-remove verification check
        ]
        self.mock_backend.remove_route.return_value = True

        res = self.service.remove_route(
            destination="192.0.2.0/24",
            gateway="192.0.2.1",
            interface="enp0s8",
            table=254,
        )
        self.assertTrue(res)
        self.mock_backend.remove_route.assert_called_once_with(
            destination="192.0.2.0/24",
            family="inet",
            gateway="192.0.2.1",
            interface="enp0s8",
            table=254,
        )

    def test_remove_nonexistent_route_raises_error(self):
        self.mock_backend.get_routes.return_value = []
        with self.assertRaises(RouteNotFoundError):
            self.service.remove_route(destination="192.0.2.0/24", table=254)

    # -------------------------------------------------------------------------
    # 4. Safety Constraints
    # -------------------------------------------------------------------------

    def test_loopback_route_removal_forbidden(self):
        with self.assertRaises(ProtectedRouteError):
            self.service.remove_route(destination="127.0.0.0/8", table=254)
        with self.assertRaises(ProtectedRouteError):
            self.service.remove_route(destination="10.0.0.0/24", interface="lo", table=254)

    def test_active_management_default_route_removal_forbidden(self):
        self.mock_backend.get_routes.return_value = [
            {"dst": "default", "gateway": "10.0.2.2", "dev": "enp0s3", "table": 254}
        ]
        with self.assertRaises(ProtectedRouteError):
            self.service.remove_route(destination="0.0.0.0/0", table=254)


if __name__ == "__main__":
    unittest.main()
