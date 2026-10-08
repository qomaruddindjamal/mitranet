"""
MitraNet Interface Discovery and State Service.
Coordinates querying the backend, parsing Linux netlink/iproute2 data structures,
and synthesizing NetworkInterfaceState domain models.
"""

import logging
from typing import List, Dict, Any, Optional
from mitranet.core.network.models import NetworkInterfaceState, InterfaceStatistics
from mitranet.core.network.backend.linux import NetworkBackend, LinuxNetworkBackend
from mitranet.core.network.exceptions import InterfaceNotFoundError

logger = logging.getLogger("mitranet.network.discovery")


class InterfaceDiscoveryService:
    def __init__(self, backend: Optional[NetworkBackend] = None):
        self.backend = backend or LinuxNetworkBackend()

    def discover_interfaces(self) -> List[NetworkInterfaceState]:
        """Discovers and compiles state for all active kernel interfaces."""
        logger.debug("Starting kernel network interface discovery")
        raw_links = self.backend.get_link_info()
        raw_addrs = self.backend.get_addr_info()

        # Build address mapping: ifname -> {ipv4: [...], ipv6: [...]}
        addr_map: Dict[str, Dict[str, List[str]]] = {}
        for item in raw_addrs:
            ifname = item.get("ifname")
            if not ifname:
                continue
            if ifname not in addr_map:
                addr_map[ifname] = {"ipv4": [], "ipv6": []}

            addr_info = item.get("addr_info", [])
            for a in addr_info:
                family = a.get("family")
                local_ip = a.get("local")
                prefixlen = a.get("prefixlen")
                if local_ip and prefixlen is not None:
                    cidr = f"{local_ip}/{prefixlen}"
                    if family == "inet":
                        addr_map[ifname]["ipv4"].append(cidr)
                    elif family == "inet6":
                        addr_map[ifname]["ipv6"].append(cidr)

        interfaces: List[NetworkInterfaceState] = []
        for link in raw_links:
            ifname = link.get("ifname")
            ifindex = link.get("ifindex")
            if not ifname or ifindex is None:
                continue

            # Link type classification
            link_type = link.get("link_type", "unknown")
            linkinfo = link.get("linkinfo", {})
            info_kind = linkinfo.get("info_kind")
            final_type = info_kind if info_kind else link_type

            # Check if this interface is wireless (802.11) via sysfs or naming
            import os
            if os.path.isdir(f"/sys/class/net/{ifname}/wireless") or os.path.isdir(f"/sys/class/net/{ifname}/phy80211") or ifname.startswith(("wlan", "wlp", "wls", "ath", "ra")):
                final_type = "wlan"

            # Administrative state (from kernel flags)
            flags = link.get("flags", [])
            admin_state = "UP" if "UP" in flags else "DOWN"

            # Operational state
            oper_state = str(link.get("operstate", "UNKNOWN")).upper()
            if oper_state not in ["UP", "DOWN", "UNKNOWN", "DORMANT", "LOWERLAYERDOWN"]:
                oper_state = "UNKNOWN"

            # Carrier
            carrier_val = self.backend.get_carrier(ifname)

            # Statistics
            raw_stats = link.get("stats64") or link.get("stats")
            stats_dict: Dict[str, int] = {}
            if raw_stats:
                rx = raw_stats.get("rx", {})
                tx = raw_stats.get("tx", {})
                stats_dict = {
                    "rx_bytes": rx.get("bytes", raw_stats.get("rx_bytes", 0)),
                    "rx_packets": rx.get("packets", raw_stats.get("rx_packets", 0)),
                    "rx_errors": rx.get("errors", raw_stats.get("rx_errors", 0)),
                    "rx_dropped": rx.get("dropped", raw_stats.get("rx_dropped", 0)),
                    "tx_bytes": tx.get("bytes", raw_stats.get("tx_bytes", 0)),
                    "tx_packets": tx.get("packets", raw_stats.get("tx_packets", 0)),
                    "tx_errors": tx.get("errors", raw_stats.get("tx_errors", 0)),
                    "tx_dropped": tx.get("dropped", raw_stats.get("tx_dropped", 0)),
                }
            else:
                sys_stats = self.backend.get_sys_statistics(ifname)
                if sys_stats:
                    stats_dict = sys_stats

            statistics = InterfaceStatistics(**stats_dict)

            # IP Addresses
            if_addrs = addr_map.get(ifname, {"ipv4": [], "ipv6": []})

            iface_model = NetworkInterfaceState(
                name=ifname,
                index=int(ifindex),
                type=final_type,
                mac_address=link.get("address"),
                mtu=int(link.get("mtu", 1500)),
                admin_state=admin_state,
                oper_state=oper_state,
                carrier=carrier_val,
                flags=flags,
                ipv4_addresses=if_addrs["ipv4"],
                ipv6_addresses=if_addrs["ipv6"],
                statistics=statistics,
                parent_device=link.get("link"),
            )
            interfaces.append(iface_model)

        logger.debug("Discovered %d network interfaces", len(interfaces))
        return interfaces

    def get_interface(self, name: str) -> NetworkInterfaceState:
        """Retrieves operational state for a specific interface name."""
        all_ifaces = self.discover_interfaces()
        for iface in all_ifaces:
            if iface.name == name:
                return iface
        raise InterfaceNotFoundError(f"Interface '{name}' not found.")
