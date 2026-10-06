"""
MitraNet Linux Bridge Discovery and Configuration Service (Phase 1D).
Provides full lifecycle management (create, delete, up, down, add_port, remove_port) for Linux bridges.
"""

import logging
from typing import List, Optional
from mitranet.core.network.models import BridgeState, BridgePortState
from mitranet.core.network.backend.linux import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.validator import BridgeValidator, InterfaceConfigValidator
from mitranet.core.network.exceptions import (
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    VerificationFailureError,
    SafetyConstraintViolationError,
    NetworkValidationError,
)

logger = logging.getLogger("mitranet.network.bridge")


class BridgeService:
    """Manages Linux Bridge devices and member ports with safety and verification."""

    def __init__(
        self,
        backend: Optional[NetworkBackend] = None,
        iface_discovery: Optional[InterfaceDiscoveryService] = None,
    ):
        self.backend = backend or LinuxNetworkBackend()
        self.iface_discovery = iface_discovery or InterfaceDiscoveryService(backend=self.backend)

    def discover_bridges(self) -> List[BridgeState]:
        """Discovers all Linux bridges and their member ports from the kernel."""
        detailed_links = self.backend.get_detailed_links()
        addr_info = self.backend.get_addr_info()

        # Build address mapping
        addr_map = {}
        for item in addr_info:
            ifname = item.get("ifname")
            if not ifname:
                continue
            addr_map[ifname] = {"ipv4": [], "ipv6": []}
            for a in item.get("addr_info", []):
                family = a.get("family")
                local_ip = a.get("local")
                prefixlen = a.get("prefixlen")
                if local_ip and prefixlen is not None:
                    cidr = f"{local_ip}/{prefixlen}"
                    if family == "inet":
                        addr_map[ifname]["ipv4"].append(cidr)
                    elif family == "inet6":
                        addr_map[ifname]["ipv6"].append(cidr)

        # Build slave port mapping: master_name -> list of BridgePortState
        ports_map = {}
        for link in detailed_links:
            master = link.get("master")
            if master:
                linkinfo = link.get("linkinfo", {})
                slave_kind = linkinfo.get("info_slave_kind")
                if slave_kind == "bridge":
                    slave_data = linkinfo.get("info_slave_data", {})
                    if master not in ports_map:
                        ports_map[master] = []
                    ports_map[master].append(
                        BridgePortState(
                            interface=link.get("ifname", ""),
                            state=slave_data.get("state", "disabled"),
                            priority=slave_data.get("priority"),
                            cost=slave_data.get("cost"),
                        )
                    )

        bridges: List[BridgeState] = []
        for link in detailed_links:
            linkinfo = link.get("linkinfo", {})
            if linkinfo.get("info_kind") == "bridge":
                ifname = link.get("ifname")
                info_data = linkinfo.get("info_data", {})
                stp_state = info_data.get("stp_state", 0)

                flags = link.get("flags", [])
                admin_state = "UP" if "UP" in flags else "DOWN"
                oper_state = str(link.get("operstate", "UNKNOWN")).upper()
                if oper_state not in ["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"]:
                    oper_state = "UNKNOWN"

                addrs = addr_map.get(ifname, {"ipv4": [], "ipv6": []})
                bridge_ports = ports_map.get(ifname, [])

                if ifname:
                    bridges.append(
                        BridgeState(
                            name=ifname,
                            admin_state=admin_state,
                            oper_state=oper_state,
                            mac_address=link.get("address"),
                            mtu=int(link.get("mtu", 1500)),
                            stp_enabled=(stp_state != 0),
                            ports=bridge_ports,
                            ipv4_addresses=addrs["ipv4"],
                            ipv6_addresses=addrs["ipv6"],
                        )
                    )
        return bridges

    def get_bridge(self, name: str) -> BridgeState:
        """Retrieves state for a specific bridge device."""
        valid_name = BridgeValidator.validate_bridge_name(name)
        bridges = self.discover_bridges()
        for b in bridges:
            if b.name == valid_name:
                return b
        raise DeviceNotFoundError(f"Bridge interface '{valid_name}' not found.")

    def create_bridge(self, name: str) -> BridgeState:
        """Creates a Linux Bridge interface with verification."""
        valid_name = BridgeValidator.validate_bridge_name(name)
        bridges = self.discover_bridges()
        if any(b.name == valid_name for b in bridges):
            raise DeviceAlreadyExistsError(f"Bridge device '{valid_name}' already exists.")

        logger.info("Creating bridge %s", valid_name)
        self.backend.create_bridge(valid_name)

        bridges_post = self.discover_bridges()
        target = next((b for b in bridges_post if b.name == valid_name), None)
        if not target:
            try:
                self.backend.delete_link(valid_name)
            except Exception:
                pass
            raise VerificationFailureError(f"Verification failed: Bridge '{valid_name}' was not created in kernel.")
        return target

    def delete_bridge(self, name: str) -> bool:
        """Deletes a Linux Bridge interface with safety protection."""
        valid_name = BridgeValidator.validate_bridge_name(name)
        bridge = self.get_bridge(valid_name)

        # Release ports if any
        for port in bridge.ports:
            try:
                self.backend.set_nomaster(port.interface)
            except Exception:
                pass

        logger.info("Deleting bridge %s", valid_name)
        self.backend.delete_link(valid_name)

        bridges_post = self.discover_bridges()
        if any(b.name == valid_name for b in bridges_post):
            raise VerificationFailureError(f"Verification failed: Bridge '{valid_name}' still present after deletion.")
        return True

    def add_port(self, bridge_name: str, iface_name: str) -> BridgeState:
        """Attaches an existing interface to a bridge as a member port."""
        valid_bname = BridgeValidator.validate_bridge_name(bridge_name)
        valid_port = BridgeValidator.validate_port_interface(iface_name)

        # Validate existence
        self.get_bridge(valid_bname)
        iface = self.iface_discovery.get_interface(valid_port)

        # Safety: protect management interface from accidental bridging disruption
        if any(addr.startswith("10.0.2.") for addr in iface.ipv4_addresses):
            raise SafetyConstraintViolationError(
                f"Safety violation: Cannot attach active management interface '{valid_port}' to bridge."
            )

        # Check if already attached
        bridge = self.get_bridge(valid_bname)
        if any(p.interface == valid_port for p in bridge.ports):
            raise DeviceAlreadyExistsError(f"Interface '{valid_port}' is already attached to bridge '{valid_bname}'.")

        logger.info("Adding port %s to bridge %s", valid_port, valid_bname)
        self.backend.set_master(valid_port, valid_bname)

        # Verification
        bridge_post = self.get_bridge(valid_bname)
        if not any(p.interface == valid_port for p in bridge_post.ports):
            raise VerificationFailureError(
                f"Verification failed: Interface '{valid_port}' not found in bridge '{valid_bname}' ports."
            )
        return bridge_post

    def remove_port(self, bridge_name: str, iface_name: str) -> BridgeState:
        """Detaches a member interface from a bridge."""
        valid_bname = BridgeValidator.validate_bridge_name(bridge_name)
        valid_port = BridgeValidator.validate_port_interface(iface_name)

        bridge = self.get_bridge(valid_bname)
        if not any(p.interface == valid_port for p in bridge.ports):
            raise DeviceNotFoundError(f"Interface '{valid_port}' is not a port of bridge '{valid_bname}'.")

        logger.info("Removing port %s from bridge %s", valid_port, valid_bname)
        self.backend.set_nomaster(valid_port)

        # Verification
        bridge_post = self.get_bridge(valid_bname)
        if any(p.interface == valid_port for p in bridge_post.ports):
            raise VerificationFailureError(
                f"Verification failed: Interface '{valid_port}' still attached to bridge '{valid_bname}'."
            )
        return bridge_post
