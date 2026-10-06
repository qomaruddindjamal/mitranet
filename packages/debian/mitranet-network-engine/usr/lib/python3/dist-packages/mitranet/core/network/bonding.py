"""
MitraNet Linux Bonding and LACP Service (Phase 1D).
Provides full lifecycle management (create, delete, up, down, add_slave, remove_slave)
for Linux bonding and IEEE 802.3ad / LACP aggregation.
"""

import logging
import re
from typing import List, Optional
from mitranet.core.network.models import BondState, BondSlaveState
from mitranet.core.network.backend.linux import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.validator import BondValidator, InterfaceConfigValidator
from mitranet.core.network.exceptions import (
    DeviceAlreadyExistsError,
    DeviceNotFoundError,
    VerificationFailureError,
    SafetyConstraintViolationError,
    NetworkValidationError,
)

logger = logging.getLogger("mitranet.network.bonding")


class BondService:
    """Manages Linux Bonding interfaces and slave memberships with LACP state inspection."""

    def __init__(
        self,
        backend: Optional[NetworkBackend] = None,
        iface_discovery: Optional[InterfaceDiscoveryService] = None,
    ):
        self.backend = backend or LinuxNetworkBackend()
        self.iface_discovery = iface_discovery or InterfaceDiscoveryService(backend=self.backend)

    def _parse_proc_bonding(self, text: str) -> dict:
        """Parses /proc/net/bonding/<bond_name> file content into structured dictionary."""
        info = {
            "active_slave": None,
            "slaves": {},
            "mii_status": None,
        }
        current_slave = None
        for line in text.splitlines():
            line_str = line.strip()
            if line_str.startswith("Currently Active Slave:"):
                info["active_slave"] = line_str.split(":", 1)[1].strip()
            elif line_str.startswith("MII Status:"):
                if not current_slave:
                    info["mii_status"] = line_str.split(":", 1)[1].strip()
                else:
                    info["slaves"][current_slave]["mii_status"] = line_str.split(":", 1)[1].strip()
            elif line_str.startswith("Slave Interface:"):
                current_slave = line_str.split(":", 1)[1].strip()
                info["slaves"][current_slave] = {
                    "mii_status": None,
                    "link_failure_count": 0,
                    "perm_hwaddr": None,
                }
            elif current_slave and line_str.startswith("Permanent HW addr:"):
                info["slaves"][current_slave]["perm_hwaddr"] = line_str.split(":", 1)[1].strip()
            elif current_slave and line_str.startswith("Link Failure Count:"):
                try:
                    info["slaves"][current_slave]["link_failure_count"] = int(line_str.split(":", 1)[1].strip())
                except ValueError:
                    pass
        return info

    def discover_bonds(self) -> List[BondState]:
        """Discovers all bonding master devices and their slaves from the kernel."""
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

        # Build slave membership mapping: master_name -> list of slave ifnames
        slaves_map = {}
        for link in detailed_links:
            master = link.get("master")
            if master:
                linkinfo = link.get("linkinfo", {})
                slave_kind = linkinfo.get("info_slave_kind")
                if slave_kind == "bond":
                    if master not in slaves_map:
                        slaves_map[master] = []
                    slaves_map[master].append(link.get("ifname", ""))

        bonds: List[BondState] = []
        for link in detailed_links:
            linkinfo = link.get("linkinfo", {})
            if linkinfo.get("info_kind") == "bond":
                ifname = link.get("ifname")
                info_data = linkinfo.get("info_data", {})
                mode = info_data.get("mode", "active-backup")

                flags = link.get("flags", [])
                admin_state = "UP" if "UP" in flags else "DOWN"
                oper_state = str(link.get("operstate", "UNKNOWN")).upper()
                if oper_state not in ["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"]:
                    oper_state = "UNKNOWN"

                addrs = addr_map.get(ifname, {"ipv4": [], "ipv6": []})

                # Check /proc/net/bonding/<name> for runtime statistics and slave states
                proc_text = self.backend.get_bonding_proc_info(ifname)
                proc_info = self._parse_proc_bonding(proc_text) if proc_text else {}

                slave_names = slaves_map.get(ifname, [])
                bond_slaves: List[BondSlaveState] = []
                for sname in slave_names:
                    s_proc = proc_info.get("slaves", {}).get(sname, {})
                    bond_slaves.append(
                        BondSlaveState(
                            interface=sname,
                            mii_status=s_proc.get("mii_status"),
                            link_failure_count=s_proc.get("link_failure_count", 0),
                            perm_hwaddr=s_proc.get("perm_hwaddr"),
                        )
                    )

                if ifname:
                    bonds.append(
                        BondState(
                            name=ifname,
                            mode=mode,
                            admin_state=admin_state,
                            oper_state=oper_state,
                            mac_address=link.get("address"),
                            mtu=int(link.get("mtu", 1500)),
                            miimon=info_data.get("miimon"),
                            slaves=bond_slaves,
                            active_slave=proc_info.get("active_slave"),
                            lacp_rate=info_data.get("ad_lacp_rate"),
                            lacp_active=info_data.get("ad_lacp_active"),
                            ad_actor_system=info_data.get("ad_actor_system"),
                            ipv4_addresses=addrs["ipv4"],
                            ipv6_addresses=addrs["ipv6"],
                        )
                    )
        return bonds

    def get_bond(self, name: str) -> BondState:
        """Retrieves state for a specific bond device."""
        valid_name = BondValidator.validate_bond_name(name)
        bonds = self.discover_bonds()
        for b in bonds:
            if b.name == valid_name:
                return b
        raise DeviceNotFoundError(f"Bond interface '{valid_name}' not found.")

    def create_bond(self, name: str, mode: str = "active-backup", miimon: int = 100) -> BondState:
        """Creates a Linux Bonding master device with specified mode."""
        valid_name = BondValidator.validate_bond_name(name)
        valid_mode = BondValidator.validate_mode(mode)

        bonds = self.discover_bonds()
        if any(b.name == valid_name for b in bonds):
            raise DeviceAlreadyExistsError(f"Bond device '{valid_name}' already exists.")

        logger.info("Creating bond %s (mode=%s, miimon=%d)", valid_name, valid_mode, miimon)
        self.backend.create_bond(valid_name, mode=valid_mode, miimon=miimon)

        bonds_post = self.discover_bonds()
        target = next((b for b in bonds_post if b.name == valid_name), None)
        if not target or target.mode != valid_mode:
            try:
                self.backend.delete_link(valid_name)
            except Exception:
                pass
            raise VerificationFailureError(
                f"Verification failed: Bond '{valid_name}' with mode '{valid_mode}' was not created in kernel."
            )
        return target

    def delete_bond(self, name: str) -> bool:
        """Deletes a Linux Bonding interface with safety checks."""
        valid_name = BondValidator.validate_bond_name(name)
        bond = self.get_bond(valid_name)

        # Release slaves if any
        for slave in bond.slaves:
            try:
                self.backend.set_nomaster(slave.interface)
            except Exception:
                pass

        logger.info("Deleting bond %s", valid_name)
        self.backend.delete_link(valid_name)

        bonds_post = self.discover_bonds()
        if any(b.name == valid_name for b in bonds_post):
            raise VerificationFailureError(f"Verification failed: Bond '{valid_name}' still present after deletion.")
        return True

    def add_slave(self, bond_name: str, iface_name: str) -> BondState:
        """Attaches an existing network interface as a slave to the bond."""
        valid_bname = BondValidator.validate_bond_name(bond_name)
        valid_slave = BondValidator.validate_slave_interface(iface_name)

        self.get_bond(valid_bname)
        iface = self.iface_discovery.get_interface(valid_slave)

        # Safety: protect management interface
        if any(addr.startswith("10.0.2.") for addr in iface.ipv4_addresses):
            raise SafetyConstraintViolationError(
                f"Safety violation: Cannot attach active management interface '{valid_slave}' to bond."
            )

        bond = self.get_bond(valid_bname)
        if any(s.interface == valid_slave for s in bond.slaves):
            raise DeviceAlreadyExistsError(f"Interface '{valid_slave}' is already a slave of bond '{valid_bname}'.")

        logger.info("Adding slave %s to bond %s", valid_slave, valid_bname)
        # Bringing slave DOWN before attaching is standard practice for bonding drivers
        try:
            self.backend.set_interface_down(valid_slave)
        except Exception:
            pass
        self.backend.set_master(valid_slave, valid_bname)

        bond_post = self.get_bond(valid_bname)
        if not any(s.interface == valid_slave for s in bond_post.slaves):
            raise VerificationFailureError(
                f"Verification failed: Interface '{valid_slave}' not found in bond '{valid_bname}' slaves."
            )
        return bond_post

    def remove_slave(self, bond_name: str, iface_name: str) -> BondState:
        """Detaches a slave interface from the bond."""
        valid_bname = BondValidator.validate_bond_name(bond_name)
        valid_slave = BondValidator.validate_slave_interface(iface_name)

        bond = self.get_bond(valid_bname)
        if not any(s.interface == valid_slave for s in bond.slaves):
            raise DeviceNotFoundError(f"Interface '{valid_slave}' is not a slave of bond '{valid_bname}'.")

        logger.info("Removing slave %s from bond %s", valid_slave, valid_bname)
        self.backend.set_nomaster(valid_slave)

        bond_post = self.get_bond(valid_bname)
        if any(s.interface == valid_slave for s in bond_post.slaves):
            raise VerificationFailureError(
                f"Verification failed: Interface '{valid_slave}' still attached to bond '{valid_bname}'."
            )
        return bond_post
