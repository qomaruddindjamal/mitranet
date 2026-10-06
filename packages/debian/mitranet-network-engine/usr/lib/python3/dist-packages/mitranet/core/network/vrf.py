"""
MitraNet Linux Virtual Routing and Forwarding (VRF) Service (Phase 1E).
Provides full lifecycle management:
- VRF discovery
- VRF creation & deletion
- Table ID validation & isolation
- Member interface attachment & detachment
- Route domain inspection & verification
- Safety constraints & rollback
"""

import logging
from typing import List, Optional
from mitranet.core.network.models import VRFState
from mitranet.core.network.backend.linux import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.routing_discovery import RouteDiscoveryService
from mitranet.core.network.validator import VRFValidator, InterfaceConfigValidator
from mitranet.core.network.exceptions import (
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    VerificationFailureError,
    SafetyConstraintViolationError,
    NetworkValidationError,
)

logger = logging.getLogger("mitranet.network.vrf")


class VRFService:
    """Manages Linux VRF devices, routing domains, and interface assignments."""

    def __init__(
        self,
        backend: Optional[NetworkBackend] = None,
        iface_discovery: Optional[InterfaceDiscoveryService] = None,
        route_discovery: Optional[RouteDiscoveryService] = None,
    ):
        self.backend = backend or LinuxNetworkBackend()
        self.iface_discovery = iface_discovery or InterfaceDiscoveryService(backend=self.backend)
        self.route_discovery = route_discovery or RouteDiscoveryService(backend=self.backend)

    def discover_vrfs(self) -> List[VRFState]:
        """Discovers all Linux VRF devices and member interfaces from kernel netlink state."""
        detailed_links = self.backend.get_detailed_links()

        # Build slave membership mapping: master_name -> list of slave interfaces
        slaves_map = {}
        for link in detailed_links:
            master = link.get("master")
            if master:
                linkinfo = link.get("linkinfo", {})
                slave_kind = linkinfo.get("info_slave_kind")
                if slave_kind == "vrf":
                    if master not in slaves_map:
                        slaves_map[master] = []
                    slaves_map[master].append(link.get("ifname", ""))

        vrfs: List[VRFState] = []
        for link in detailed_links:
            linkinfo = link.get("linkinfo", {})
            if linkinfo.get("info_kind") == "vrf":
                ifname = link.get("ifname")
                info_data = linkinfo.get("info_data", {})
                table_id = info_data.get("table")

                flags = link.get("flags", [])
                admin_state = "UP" if "UP" in flags else "DOWN"
                oper_state = str(link.get("operstate", "UNKNOWN")).upper()
                if oper_state not in ["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"]:
                    oper_state = "UNKNOWN"

                member_ifaces = slaves_map.get(ifname, [])

                # Count active routes in VRF table if available
                routes_count = None
                if table_id:
                    try:
                        v4_routes = self.route_discovery.get_routes(family="inet", table=int(table_id))
                        routes_count = len(v4_routes)
                    except Exception:
                        pass

                if ifname and table_id is not None:
                    vrfs.append(
                        VRFState(
                            name=ifname,
                            table=int(table_id),
                            admin_state=admin_state,
                            oper_state=oper_state,
                            mac_address=link.get("address"),
                            interfaces=member_ifaces,
                            routes_count=routes_count,
                        )
                    )
        return vrfs

    def get_vrf(self, name: str) -> VRFState:
        """Retrieves state for a specific VRF device."""
        valid_name = VRFValidator.validate_vrf_name(name)
        vrfs = self.discover_vrfs()
        for v in vrfs:
            if v.name == valid_name:
                return v
        raise DeviceNotFoundError(f"VRF interface '{valid_name}' not found.")

    def create_vrf(self, name: str, table_id: Optional[int] = None, table: Optional[int] = None) -> VRFState:
        """
        Creates a Linux VRF device with specified routing table ID.
        Lifecycle: VALIDATE -> CAPTURE -> APPLY -> DISCOVER -> VERIFY.
        """
        tid = table if table is not None else table_id
        valid_name = VRFValidator.validate_vrf_name(name)
        valid_table = VRFValidator.validate_table_id(tid)

        vrfs = self.discover_vrfs()
        if any(v.name == valid_name for v in vrfs):
            raise DeviceAlreadyExistsError(f"VRF device '{valid_name}' already exists.")
        if any(v.table == valid_table for v in vrfs):
            raise DeviceAlreadyExistsError(
                f"Routing table {valid_table} is already assigned to VRF '{next(v.name for v in vrfs if v.table == valid_table)}'."
            )

        logger.info("Creating VRF %s with table %d", valid_name, valid_table)
        self.backend.create_vrf(valid_name, valid_table)

        # Set VRF device UP (standard for Linux VRF masters)
        try:
            self.backend.set_interface_up(valid_name)
        except Exception:
            pass

        # Verification
        vrfs_post = self.discover_vrfs()
        target = next((v for v in vrfs_post if v.name == valid_name), None)
        if not target or target.table != valid_table:
            try:
                self.backend.delete_link(valid_name)
            except Exception:
                pass
            raise VerificationFailureError(
                f"Verification failed: VRF '{valid_name}' with table {valid_table} was not created in kernel."
            )
        return target

    def delete_vrf(self, name: str) -> bool:
        """Deletes a Linux VRF device with safety protection."""
        valid_name = VRFValidator.validate_vrf_name(name)
        vrf = self.get_vrf(valid_name)

        # Detach member interfaces before deletion
        for iface in vrf.interfaces:
            try:
                self.backend.set_nomaster(iface)
            except Exception:
                pass

        logger.info("Deleting VRF %s", valid_name)
        self.backend.delete_link(valid_name)

        # Verification
        vrfs_post = self.discover_vrfs()
        if any(v.name == valid_name for v in vrfs_post):
            raise VerificationFailureError(f"Verification failed: VRF '{valid_name}' still present after deletion.")
        return True

    def add_interface(self, vrf_name: str, iface_name: str) -> VRFState:
        """Assigns a network interface to a VRF domain."""
        valid_vname = VRFValidator.validate_vrf_name(vrf_name)
        valid_iface = VRFValidator.validate_member_interface(iface_name)

        # Verify existence
        vrf = self.get_vrf(valid_vname)
        iface = self.iface_discovery.get_interface(valid_iface)

        # Safety check: Protect management interface
        if any(addr.startswith("10.0.2.") for addr in iface.ipv4_addresses):
            raise SafetyConstraintViolationError(
                f"Safety violation: Cannot assign active management interface '{valid_iface}' to VRF."
            )

        if valid_iface in vrf.interfaces:
            raise DeviceAlreadyExistsError(
                f"Interface '{valid_iface}' is already a member of VRF '{valid_vname}'."
            )

        # Check if already assigned to another VRF
        all_vrfs = self.discover_vrfs()
        for other_vrf in all_vrfs:
            if other_vrf.name != valid_vname and valid_iface in other_vrf.interfaces:
                raise NetworkValidationError(
                    f"Interface '{valid_iface}' is already assigned to VRF '{other_vrf.name}'."
                )

        logger.info("Assigning interface %s to VRF %s", valid_iface, valid_vname)
        self.backend.set_master(valid_iface, valid_vname)

        # Verification
        vrf_post = self.get_vrf(valid_vname)
        if valid_iface not in vrf_post.interfaces:
            raise VerificationFailureError(
                f"Verification failed: Interface '{valid_iface}' not found in VRF '{valid_vname}' interfaces."
            )
        return vrf_post

    def remove_interface(self, vrf_name: str, iface_name: str) -> VRFState:
        """Detaches a network interface from a VRF domain."""
        valid_vname = VRFValidator.validate_vrf_name(vrf_name)
        valid_iface = VRFValidator.validate_member_interface(iface_name)

        vrf = self.get_vrf(valid_vname)
        if valid_iface not in vrf.interfaces:
            raise DeviceNotFoundError(
                f"Interface '{valid_iface}' is not a member of VRF '{valid_vname}'."
            )

        logger.info("Detaching interface %s from VRF %s", valid_iface, valid_vname)
        self.backend.set_nomaster(valid_iface)

        # Verification
        vrf_post = self.get_vrf(valid_vname)
        if valid_iface in vrf_post.interfaces:
            raise VerificationFailureError(
                f"Verification failed: Interface '{valid_iface}' still assigned to VRF '{valid_vname}'."
            )
        return vrf_post
