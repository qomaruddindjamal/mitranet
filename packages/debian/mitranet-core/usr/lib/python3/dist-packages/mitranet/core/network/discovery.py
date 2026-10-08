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

            # Check if this interface is wireless (802.11) via sysfs, udev, driver or naming
            import os
            is_wireless_dev = (
                os.path.isdir(f"/sys/class/net/{ifname}/wireless") or
                os.path.isdir(f"/sys/class/net/{ifname}/phy80211") or
                ifname.startswith(("wlan", "wlp", "wls", "wlx", "ath", "ra"))
            )
            # Check device subsystem or driver
            try:
                drv_link = f"/sys/class/net/{ifname}/device/driver"
                if os.path.islink(drv_link):
                    drv_name = os.path.basename(os.readlink(drv_link)).lower()
                    if any(k in drv_name for k in ["80211", "ath", "rtlwifi", "rtw_", "mt76", "brcm", "iwl", "carl9170", "zd1211"]):
                        is_wireless_dev = True
            except Exception:
                pass

            if is_wireless_dev:
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

        # -------------------------------------------------------------
        # MitraNet Alias & Port Label Classification:
        # 1. Optical/Fiber SFP/SFP+/QSFP -> sfp1, sfp2, ...
        # 2. Standard Ethernet (enp*, eth*, etc.) -> eth1, eth2, ...
        # 3. Wireless (WLAN) with 2.4GHz / 5GHz detection -> wlan1-2.4, wlan2-5.8
        # -------------------------------------------------------------
        import os, subprocess, re

        def _is_sfp_port(name: str) -> bool:
            # Check sysfs driver/modalias and ethtool/sfp
            try:
                drv_path = f"/sys/class/net/{name}/device/driver"
                if os.path.islink(drv_path):
                    drv = os.path.basename(os.readlink(drv_path)).lower()
                    if any(x in drv for x in ["ixgbe", "i40e", "ice", "mlx4", "mlx5", "bnxt", "qede", "sfp"]):
                        return True
                # Check sfp eeprom or phy
                if os.path.exists(f"/sys/class/net/{name}/phy") or os.path.exists(f"/sys/class/net/{name}/device/sfp"):
                    return True
            except Exception:
                pass
            return False

        def _detect_wlan_band(name: str) -> str:
            # Detect 2.4GHz vs 5.8GHz / 5GHz for wlan interface
            try:
                res = subprocess.run(["iw", "dev", name, "info"], capture_output=True, text=True, timeout=2)
                phy_match = re.search(r"wiphy\s+(\d+)", res.stdout)
                phy_id = phy_match.group(1) if phy_match else "0"
                
                info_res = subprocess.run(["iw", f"phy{phy_id}", "info"], capture_output=True, text=True, timeout=2)
                has_24 = bool(re.search(r"24\d\d(?:\.\d+)?\s+MHz", info_res.stdout))
                has_5 = bool(re.search(r"5\d\d\d(?:\.\d+)?\s+MHz", info_res.stdout))
                
                if has_24 and not has_5:
                    return "2.4"
                if has_5 and not has_24:
                    return "5.8"
                if has_5 and has_24:
                    return "Dual-Band"
            except Exception:
                pass
            return ""

        sfp_idx = 1
        eth_idx = 1
        wlan_list = []

        for iface in interfaces:
            # Skip loopback, virtual taps, bridges, vlans
            if iface.name == "lo" or iface.type in ["loopback", "bridge", "vlan"]:
                continue
            if iface.name.startswith(("tap-", "veth", "wg")):
                continue

            # Wireless interfaces
            if iface.type == "wlan" or iface.name.startswith(("wlan", "wlp", "wls", "wlx", "ath", "ra")):
                wlan_list.append(iface)
                continue

            # Physical Ethernet / SFP
            if _is_sfp_port(iface.name):
                iface.is_sfp = True
                iface.altname = f"sfp{sfp_idx}"
                iface.port_label = f"SFP Port {sfp_idx}"
                sfp_idx += 1
            elif iface.type == "ether" or iface.name.startswith(("en", "eth")):
                iface.altname = f"eth{eth_idx}"
                iface.port_label = f"LAN/WAN Port {eth_idx}"
                eth_idx += 1

        # Process WLAN interfaces and assign wlan1-2.4 / wlan2-5.8
        if len(wlan_list) == 1:
            w = wlan_list[0]
            band = _detect_wlan_band(w.name)
            suffix = f"-{band}" if band and band != "Dual-Band" else ""
            w.altname = f"wlan1{suffix}"
            w.port_label = f"Wireless 1 ({band or '802.11'})"
        elif len(wlan_list) >= 2:
            # Check bands
            bands = [_detect_wlan_band(w.name) for w in wlan_list]
            # If explicit 2.4 and 5.8 detected, map specifically
            has_explicit_24 = any(b == "2.4" for b in bands)
            has_explicit_5 = any(b in ["5.8", "5"] for b in bands)

            if has_explicit_24 or has_explicit_5:
                wlan_24_idx = 1
                wlan_5_idx = 2
                for w in wlan_list:
                    b = _detect_wlan_band(w.name)
                    if b == "2.4":
                        w.altname = f"wlan{wlan_24_idx}-2.4"
                        w.port_label = f"Wireless {wlan_24_idx} (2.4 GHz)"
                        wlan_24_idx += 2
                    elif b in ["5.8", "5"]:
                        w.altname = f"wlan{wlan_5_idx}-5.8"
                        w.port_label = f"Wireless {wlan_5_idx} (5.8 GHz)"
                        wlan_5_idx += 2
                    else:
                        w.altname = f"wlan{wlan_24_idx}"
                        w.port_label = f"Wireless {wlan_24_idx}"
                        wlan_24_idx += 1
            else:
                # When two radios exist (e.g. dual-band or simultaneous radios), convention is radio1 -> 2.4, radio2 -> 5.8
                for idx, w in enumerate(wlan_list, 1):
                    if idx == 1:
                        w.altname = "wlan1-2.4"
                        w.port_label = "Wireless 1 (2.4 GHz)"
                    elif idx == 2:
                        w.altname = "wlan2-5.8"
                        w.port_label = "Wireless 2 (5.8 GHz)"
                    else:
                        w.altname = f"wlan{idx}"
                        w.port_label = f"Wireless {idx}"

        logger.debug("Discovered %d network interfaces", len(interfaces))
        return interfaces

    def get_interface(self, name: str) -> NetworkInterfaceState:
        """Retrieves operational state for a specific interface name."""
        all_ifaces = self.discover_interfaces()
        for iface in all_ifaces:
            if iface.name == name:
                return iface
        raise InterfaceNotFoundError(f"Interface '{name}' not found.")
