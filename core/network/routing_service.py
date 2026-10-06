"""
MitraNet Route Configuration Service (Phase 1C).
Enforces:
- Strict validation (CIDR format, IP families, gateway format, table boundaries)
- Safety constraints (protected routes, default management route protection)
- Capture previous state
- Apply via Linux backend
- Discover and verify kernel state
- Safe state restoration on verification failure
"""

import logging
import ipaddress
from typing import List, Optional, Union, Dict, Any
from mitranet.core.network.models import RouteState
from mitranet.core.network.backend import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.routing_discovery import RouteDiscoveryService
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.validator import RouteValidator, InterfaceConfigValidator
from mitranet.core.network.exceptions import (
    NetworkValidationError,
    NetworkSecurityError,
    SafetyConstraintViolationError,
    VerificationFailureError,
    RouteNotFoundError,
    RouteAlreadyExistsError,
    ProtectedRouteError,
    InterfaceNotFoundError,
)

logger = logging.getLogger("mitranet.network.routing_service")


class RouteConfigurationService:
    """Orchestrates route lifecycle operations against Linux kernel."""

    def __init__(
        self,
        backend: Optional[NetworkBackend] = None,
        discovery: Optional[RouteDiscoveryService] = None,
        iface_discovery: Optional[InterfaceDiscoveryService] = None,
    ):
        self.backend = backend or LinuxNetworkBackend()
        self.discovery = discovery or RouteDiscoveryService(backend=self.backend)
        self.iface_discovery = iface_discovery or InterfaceDiscoveryService(backend=self.backend)

    def _resolve_family(self, dest_net: Union[ipaddress.IPv4Network, ipaddress.IPv6Network]) -> str:
        return "inet6" if isinstance(dest_net, ipaddress.IPv6Network) else "inet"

    def _match_route(
        self,
        r: RouteState,
        destination: str,
        family: str,
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        table: int = 254,
    ) -> bool:
        if r.family != family or r.table != table:
            return False
        # Normalize destination comparison
        if r.destination != destination:
            # Check default route equivalence
            if not (r.is_default and destination in ("0.0.0.0/0", "::/0")):
                return False
        if gateway is not None and r.gateway != gateway:
            return False
        if interface is not None and r.interface != interface:
            return False
        return True

    def _find_exact_route(
        self,
        destination: str,
        family: str,
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        table: int = 254,
    ) -> Optional[RouteState]:
        current_routes = self.discovery.get_routes(family=family, table=table)
        for r in current_routes:
            if self._match_route(r, destination, family, gateway, interface, table):
                return r
        return None

    def _check_safety_constraints(
        self,
        destination: str,
        family: str,
        table: int,
        interface: Optional[str],
        action: str,
    ) -> None:
        """Protects loopback routes and management default routes in table main."""
        # 1. Protect loopback routes
        if destination in ("127.0.0.0/8", "127.0.0.1/32", "::1/128") or interface == "lo":
            if action in ("remove", "replace"):
                raise ProtectedRouteError(
                    f"Safety violation: Modifying or removing loopback route '{destination}' is forbidden."
                )

        # 2. In main table (254), protect default route if it is active management route
        if table == 254 and destination in ("0.0.0.0/0", "::/0"):
            if action == "remove":
                # Check if this route is currently routing management traffic
                defaults = self.discovery.get_default_routes(family=family, table=254)
                if defaults:
                    for d in defaults:
                        # If interface is active or matches primary uplink, prevent naive removal
                        if d.interface and d.interface.startswith(("eth0", "ens", "enp")):
                            raise ProtectedRouteError(
                                f"Safety violation: Removing active management default route '{destination}' on '{d.interface}' is forbidden in table main. Use dedicated routing table for testing."
                            )

    def add_route(
        self,
        destination: str,
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        metric: Optional[int] = None,
        table: Optional[Union[int, str]] = 254,
    ) -> RouteState:
        """
        Adds static route to Linux kernel routing table with full validation and runtime verification.
        """
        # Step 1: Validate inputs
        dest_net = RouteValidator.validate_destination(destination)
        family = self._resolve_family(dest_net)
        dest_cidr = str(dest_net)

        gw_ip = RouteValidator.validate_gateway(gateway, expected_family=family)
        gw_str = str(gw_ip) if gw_ip else None

        valid_iface = None
        if interface:
            valid_iface = InterfaceConfigValidator.validate_interface_name(interface)
            # Verify interface exists in system
            self.iface_discovery.get_interface(valid_iface)

        if not gw_str and not valid_iface:
            raise NetworkValidationError("A route requires at least a gateway (via) or an egress interface (dev).")

        valid_metric = RouteValidator.validate_metric(metric)
        valid_table = RouteValidator.validate_table(table)

        # Step 2: Check safety constraints
        self._check_safety_constraints(dest_cidr, family, valid_table, valid_iface, action="add")

        # Step 3: Check for duplicate route
        existing = self._find_exact_route(dest_cidr, family, gw_str, valid_iface, valid_table)
        if existing:
            raise RouteAlreadyExistsError(
                f"Route to {dest_cidr} via {gw_str} dev {valid_iface} in table {valid_table} already exists."
            )

        # Step 4: Apply to kernel
        logger.info(
            "Adding route: %s via %s dev %s metric %s table %s (%s)",
            dest_cidr, gw_str, valid_iface, valid_metric, valid_table, family
        )
        self.backend.add_route(
            destination=dest_cidr,
            family=family,
            gateway=gw_str,
            interface=valid_iface,
            metric=valid_metric,
            table=valid_table,
        )

        # Step 5: Discover and verify
        verified = self._find_exact_route(dest_cidr, family, gw_str, valid_iface, valid_table)
        if not verified:
            raise VerificationFailureError(
                f"Verification failed: Route to '{dest_cidr}' was applied but not found in kernel routing table {valid_table}."
            )
        return verified

    def remove_route(
        self,
        destination: str,
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        table: Optional[Union[int, str]] = 254,
    ) -> bool:
        """
        Removes route from Linux kernel with exact matching and verification.
        """
        # Step 1: Validate inputs
        dest_net = RouteValidator.validate_destination(destination)
        family = self._resolve_family(dest_net)
        dest_cidr = str(dest_net)

        gw_ip = RouteValidator.validate_gateway(gateway, expected_family=family)
        gw_str = str(gw_ip) if gw_ip else None

        valid_iface = None
        if interface:
            valid_iface = InterfaceConfigValidator.validate_interface_name(interface)

        valid_table = RouteValidator.validate_table(table)

        # Step 2: Safety checks
        self._check_safety_constraints(dest_cidr, family, valid_table, valid_iface, action="remove")

        # Step 3: Ensure route exists before removing
        target = self._find_exact_route(dest_cidr, family, gw_str, valid_iface, valid_table)
        if not target:
            raise RouteNotFoundError(
                f"Cannot remove route: Route to '{dest_cidr}' not found in routing table {valid_table}."
            )

        # Step 4: Apply removal
        logger.info("Removing route: %s via %s dev %s in table %s", dest_cidr, gw_str, valid_iface, valid_table)
        self.backend.remove_route(
            destination=dest_cidr,
            family=family,
            gateway=gw_str,
            interface=valid_iface,
            table=valid_table,
        )

        # Step 5: Verify removal
        recheck = self._find_exact_route(dest_cidr, family, gw_str, valid_iface, valid_table)
        if recheck:
            raise VerificationFailureError(
                f"Verification failed: Route to '{dest_cidr}' was removed but is still present in kernel table {valid_table}."
            )
        return True

    def replace_route(
        self,
        destination: str,
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        metric: Optional[int] = None,
        table: Optional[Union[int, str]] = 254,
    ) -> RouteState:
        """Replaces an existing route atomically."""
        dest_net = RouteValidator.validate_destination(destination)
        family = self._resolve_family(dest_net)
        dest_cidr = str(dest_net)

        gw_ip = RouteValidator.validate_gateway(gateway, expected_family=family)
        gw_str = str(gw_ip) if gw_ip else None

        valid_iface = None
        if interface:
            valid_iface = InterfaceConfigValidator.validate_interface_name(interface)
            self.iface_discovery.get_interface(valid_iface)

        valid_metric = RouteValidator.validate_metric(metric)
        valid_table = RouteValidator.validate_table(table)

        self._check_safety_constraints(dest_cidr, family, valid_table, valid_iface, action="replace")

        logger.info("Replacing route: %s via %s dev %s table %s", dest_cidr, gw_str, valid_iface, valid_table)
        self.backend.replace_route(
            destination=dest_cidr,
            family=family,
            gateway=gw_str,
            interface=valid_iface,
            metric=valid_metric,
            table=valid_table,
        )

        verified = self._find_exact_route(dest_cidr, family, gw_str, valid_iface, valid_table)
        if not verified:
            raise VerificationFailureError(
                f"Verification failed: Route '{dest_cidr}' replacement could not be verified in kernel."
            )
        return verified
