"""
MitraNet VLAN Discovery and Configuration Service (Phase 1D).
Provides full lifecycle management (discover, create, delete, up, down) for 802.1Q VLAN devices.
"""

import logging
from typing import List, Optional
from mitranet.core.network.models import VlanState
from mitranet.core.network.backend.linux import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.validator import VlanValidator, InterfaceConfigValidator
from mitranet.core.network.exceptions import (
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    VerificationFailureError,
    SafetyConstraintViolationError,
)

logger = logging.getLogger("mitranet.network.vlan")


class VlanService:
    """Manages 802.1Q VLAN devices with validation, verification, and safety constraints."""

    def __init__(
        self,
        backend: Optional[NetworkBackend] = None,
        iface_discovery: Optional[InterfaceDiscoveryService] = None,
    ):
        self.backend = backend or LinuxNetworkBackend()
        self.iface_discovery = iface_discovery or InterfaceDiscoveryService(backend=self.backend)

    def discover_vlans(self) -> List[VlanState]:
        """Discovers all active 802.1Q VLAN interfaces from the Linux kernel."""
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

        vlans: List[VlanState] = []
        for link in detailed_links:
            linkinfo = link.get("linkinfo", {})
            if linkinfo.get("info_kind") == "vlan":
                ifname = link.get("ifname")
                info_data = linkinfo.get("info_data", {})
                vlan_id = info_data.get("id")
                proto = info_data.get("protocol", "802.1Q")
                parent = link.get("link")

                flags = link.get("flags", [])
                admin_state = "UP" if "UP" in flags else "DOWN"
                oper_state = str(link.get("operstate", "UNKNOWN")).upper()
                if oper_state not in ["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"]:
                    oper_state = "UNKNOWN"

                addrs = addr_map.get(ifname, {"ipv4": [], "ipv6": []})

                if ifname and parent and vlan_id is not None:
                    vlans.append(
                        VlanState(
                            name=ifname,
                            parent=parent,
                            vlan_id=int(vlan_id),
                            protocol=proto,
                            mtu=int(link.get("mtu", 1500)),
                            admin_state=admin_state,
                            oper_state=oper_state,
                            mac_address=link.get("address"),
                            ipv4_addresses=addrs["ipv4"],
                            ipv6_addresses=addrs["ipv6"],
                        )
                    )
        return vlans

    def get_vlan(self, name: str) -> VlanState:
        """Retrieves state for a specific VLAN device."""
        valid_name = VlanValidator.validate_vlan_name(name)
        vlans = self.discover_vlans()
        for v in vlans:
            if v.name == valid_name:
                return v
        raise DeviceNotFoundError(f"VLAN interface '{valid_name}' not found.")

    def create_vlan(
        self,
        name: str,
        parent: str,
        vlan_id: int,
        proto: str = "802.1Q",
    ) -> VlanState:
        """
        Creates an 802.1Q VLAN interface.
        Follows lifecycle: VALIDATE -> CAPTURE -> APPLY -> DISCOVER -> VERIFY.
        """
        valid_name = VlanValidator.validate_vlan_name(name)
        valid_parent = VlanValidator.validate_parent_interface(parent)
        valid_vid = VlanValidator.validate_vlan_id(vlan_id)

        # Ensure parent interface exists
        self.iface_discovery.get_interface(valid_parent)

        # Check duplicate
        vlans = self.discover_vlans()
        if any(v.name == valid_name for v in vlans):
            raise DeviceAlreadyExistsError(f"VLAN device '{valid_name}' already exists.")
        if any(v.parent == valid_parent and v.vlan_id == valid_vid for v in vlans):
            raise DeviceAlreadyExistsError(
                f"VLAN ID {valid_vid} on parent interface '{valid_parent}' already exists."
            )

        logger.info("Creating VLAN %s (parent=%s, id=%d)", valid_name, valid_parent, valid_vid)
        self.backend.create_vlan(valid_name, valid_parent, valid_vid, proto=proto)

        # Verification
        vlans_post = self.discover_vlans()
        target = next((v for v in vlans_post if v.name == valid_name), None)
        if not target or target.vlan_id != valid_vid or target.parent != valid_parent:
            # Attempt cleanup on verification failure
            try:
                self.backend.delete_link(valid_name)
            except Exception:
                pass
            raise VerificationFailureError(
                f"Verification failed: VLAN '{valid_name}' with ID {valid_vid} on '{valid_parent}' was not created properly."
            )
        return target

    def delete_vlan(self, name: str) -> bool:
        """Deletes a VLAN interface."""
        valid_name = VlanValidator.validate_vlan_name(name)
        vlans = self.discover_vlans()
        if not any(v.name == valid_name for v in vlans):
            raise DeviceNotFoundError(f"VLAN interface '{valid_name}' does not exist.")

        logger.info("Deleting VLAN %s", valid_name)
        self.backend.delete_link(valid_name)

        # Verification
        vlans_post = self.discover_vlans()
        if any(v.name == valid_name for v in vlans_post):
            raise VerificationFailureError(f"Verification failed: VLAN '{valid_name}' still present after deletion.")
        return True

    def set_vlan_up(self, name: str) -> VlanState:
        """Brings VLAN interface UP."""
        valid_name = VlanValidator.validate_vlan_name(name)
        self.get_vlan(valid_name)
        self.backend.set_interface_up(valid_name)
        return self.get_vlan(valid_name)

    def set_vlan_down(self, name: str) -> VlanState:
        """Brings VLAN interface DOWN."""
        valid_name = VlanValidator.validate_vlan_name(name)
        self.get_vlan(valid_name)
        self.backend.set_interface_down(valid_name)
        return self.get_vlan(valid_name)
