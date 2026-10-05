#!/usr/bin/env python3
# ==============================================================================
# MitraNet Network OS - Enterprise Network & Security Controller
# Enterprise Router & Firewall Architecture (Security Zones, FastPath, Queues, Traffic-Flow,
# SD-WAN, App Filtering, HA Sync)
# ==============================================================================

import os
import json
import time
import subprocess
from typing import Dict, List, Any, Optional

DEFAULT_ENTERPRISE_CONF = "/etc/mitranet/enterprise.json"
FALLBACK_ENTERPRISE_CONF = os.path.join(os.path.dirname(__file__), "..", "..", "rootfs", "etc", "mitranet", "enterprise.json")

class EnterpriseNetworkManager:
    """Enterprise Router/Firewall Features: Security Zones, VRRP HA, NetFlow, Diagnostics, and BRAS."""

    def __init__(self, config_path: Optional[str] = None):
        self.config_path = config_path or (
            DEFAULT_ENTERPRISE_CONF if os.path.exists(os.path.dirname(DEFAULT_ENTERPRISE_CONF)) else FALLBACK_ENTERPRISE_CONF
        )
        self.config = self._load_default_config()
        self.load()

    def _detect_physical_interfaces(self) -> List[str]:
        """Auto-detects real physical Ethernet interfaces in the system (1 to 54+)."""
        net_dir = "/sys/class/net"
        sim_file = "/etc/mitranet/mock_interfaces_count"
        if os.path.exists(sim_file):
            try:
                with open(sim_file, "r") as sf:
                    c = int(sf.read().strip())
                    if c > 0:
                        return [f"eth{i}" for i in range(c)]
            except Exception:
                pass

        if os.path.exists(net_dir):
            all_devs = [d for d in os.listdir(net_dir) if d != "lo"]
            virtual_prefixes = ("docker", "veth", "br", "wg", "tun", "tap", "sit", "ip6tnl", "dummy", "gre", "gretap", "vxlan", "bond", "team")
            phys = []
            for dev in all_devs:
                dev_path = os.path.join(net_dir, dev)
                is_phys = os.path.exists(os.path.join(dev_path, "device"))
                if not is_phys and any(dev.startswith(p) for p in ("ether", "eth", "en", "swp", "ge-", "xe-")):
                    is_phys = True
                if any(dev.startswith(vp) for vp in virtual_prefixes):
                    is_phys = False
                if os.path.exists(os.path.join(dev_path, "wireless")) or os.path.exists(os.path.join(dev_path, "phy80211")):
                    is_phys = False
                if is_phys:
                    phys.append(dev)

            import re
            phys.sort(key=lambda s: [int(t) if t.isdigit() else t.lower() for t in re.split(r'(\d+)', s)])
            if phys:
                return phys
        return ["eth0"]

    def _load_default_config(self) -> Dict[str, Any]:
        devs = self._detect_physical_interfaces()
        wan_if = devs[0] if devs else "eth0"
        lan_if = devs[1] if len(devs) > 1 else wan_if
        dmz_if = [devs[2]] if len(devs) > 2 else []

        return {
            "version": "1.0.0",
            "security_zones": {
                "trust": {
                    "description": "Internal LAN & Trusted Endpoints",
                    "interfaces": [lan_if],
                    "screen": {"syn_flood": False, "ping_sweep": False}
                },
                "untrust": {
                    "description": "External Internet / Public WAN",
                    "interfaces": [wan_if],
                    "screen": {"syn_flood": True, "ping_sweep": True, "ip_spoofing": True}
                },
                "dmz": {
                    "description": "Demilitarized Zone (Public Facing Servers)",
                    "interfaces": dmz_if,
                    "screen": {"syn_flood": True, "ping_sweep": False}
                },
                "vpn": {
                    "description": "Encrypted Overlays (WireGuard, IPsec)",
                    "interfaces": ["wg0"],
                    "screen": {"syn_flood": False, "ping_sweep": False}
                }
            },
            "zone_policies": [
                {
                    "id": "pol-trust-to-untrust",
                    "from_zone": "trust",
                    "to_zone": "untrust",
                    "action": "permit",
                    "log": False,
                    "description": "Allow all LAN clients to browse Internet with FastPath"
                },
                {
                    "id": "pol-vpn-to-trust",
                    "from_zone": "vpn",
                    "to_zone": "trust",
                    "action": "permit",
                    "log": True,
                    "description": "Allow authenticated VPN roadwarriors to access LAN"
                },
                {
                    "id": "pol-untrust-to-dmz",
                    "from_zone": "untrust",
                    "to_zone": "dmz",
                    "action": "inspect",
                    "log": True,
                    "description": "Inspect and filter inbound traffic to DMZ servers"
                },
                {
                    "id": "pol-untrust-to-trust",
                    "from_zone": "untrust",
                    "to_zone": "trust",
                    "action": "deny",
                    "log": True,
                    "description": "Default Deny Inbound from WAN to LAN"
                }
            ],
            "high_availability": {
                "enabled": False,
                "role": "master",  # master or backup
                "vrid": 51,
                "interface": "eth1",
                "virtual_ip": "192.168.1.254/24",
                "priority": 100,
                "advert_int": 1,
                "auth_pass": "MitraNetHA99",
                "conntrackd_sync": True,
                "peer_ip": "192.168.1.2"
            },
            "netflow_exporter": {
                "enabled": False,
                "version": "ipfix",  # v5, v9, ipfix
                "collector_ip": "192.168.1.100",
                "collector_port": 2055,
                "sampling_rate": 1,
                "active_timeout": 60,
                "interfaces": ["eth0", "eth1"]
            },
            "sdwan_failover": {
                "enabled": False,
                "primary_wan": "eth0",
                "secondary_wan": "eth3",
                "target_probe": "1.1.1.1",
                "probe_interval": 3,
                "max_packet_loss_pct": 20,
                "max_latency_ms": 150
            },
            "dns_adblock_sinkhole": {
                "enabled": True,
                "blocked_categories": ["ads", "malware", "gambling"],
                "custom_blocked_domains": ["ads.google.com", "doubleclick.net", "tracker.example.com"],
                "total_queries_blocked": 0
            },
            "subscriber_bras": {
                "enabled": False,
                "interface": "eth1",
                "ip_pool": "10.100.0.2-10.100.255.254",
                "dns1": "1.1.1.1",
                "dns2": "8.8.8.8",
                "radius_enabled": False,
                "radius_server": "127.0.0.1",
                "radius_secret": "radiuskey123"
            }
        }

    def load(self):
        if os.path.exists(self.config_path):
            try:
                with open(self.config_path, "r", encoding="utf-8") as f:
                    data = json.load(f)
                    self.config.update(data)
            except Exception:
                pass

    def save(self) -> bool:
        try:
            d = os.path.dirname(self.config_path)
            if d:
                os.makedirs(d, exist_ok=True)
            with open(self.config_path, "w", encoding="utf-8") as f:
                json.dump(self.config, f, indent=2)
            return True
        except Exception:
            return False

    # --- 1. Zone-Based Firewall (ZBF) ---
    def get_security_zones(self) -> Dict[str, Any]:
        return {
            "zones": self.config.get("security_zones", {}),
            "policies": self.config.get("zone_policies", [])
        }

    def add_zone_policy(self, from_zone: str, to_zone: str, action: str, description: str = "") -> Dict[str, Any]:
        pol_id = f"pol-{from_zone}-to-{to_zone}"
        new_pol = {
            "id": pol_id,
            "from_zone": from_zone,
            "to_zone": to_zone,
            "action": action,
            "log": action in ["deny", "inspect"],
            "description": description or f"Policy from {from_zone} to {to_zone}"
        }
        # Replace or add
        policies = [p for p in self.config.get("zone_policies", []) if p.get("id") != pol_id]
        policies.append(new_pol)
        self.config["zone_policies"] = policies
        self.save()
        return {"status": "success", "policy": new_pol}

    # --- 2. High Availability (VRRP + Conntrackd) ---
    def get_ha_status(self) -> Dict[str, Any]:
        ha = self.config.get("high_availability", {})
        return {
            "enabled": ha.get("enabled", False),
            "role": ha.get("role", "master"),
            "virtual_ip": ha.get("virtual_ip", "192.168.1.254/24"),
            "priority": ha.get("priority", 100),
            "vrid": ha.get("vrid", 51),
            "conntrackd_sync": ha.get("conntrackd_sync", True),
            "active_state": "RUNNING (Master VIP active)" if ha.get("enabled") else "STANDBY / DISABLED"
        }

    def generate_keepalived_conf(self) -> str:
        ha = self.config.get("high_availability", {})
        vip = ha.get("virtual_ip", "192.168.1.254/24")
        return f"""# MitraNet VRRP High Availability (Keepalived Configuration)
vrrp_sync_group VG1 {{
    group {{
        VI_LAN
    }}
}}

vrrp_instance VI_LAN {{
    state {ha.get("role", "MASTER").upper()}
    interface {ha.get("interface", "eth1")}
    virtual_router_id {ha.get("vrid", 51)}
    priority {ha.get("priority", 100)}
    advert_int {ha.get("advert_int", 1)}
    authentication {{
        auth_type PASS
        auth_pass {ha.get("auth_pass", "MitraNetHA99")}
    }}
    virtual_ipaddress {{
        {vip}
    }}
}}
"""

    # --- 3. NetFlow / IPFIX Exporter (pmacctd) ---
    def get_netflow_status(self) -> Dict[str, Any]:
        nf = self.config.get("netflow_exporter", {})
        return {
            "enabled": nf.get("enabled", False),
            "version": nf.get("version", "ipfix"),
            "collector": f"{nf.get('collector_ip', '192.168.1.100')}:{nf.get('collector_port', 2055)}",
            "interfaces": nf.get("interfaces", ["eth0", "eth1"])
        }

    # --- 4. Live Diagnostics (Live Traffic Flow, tcpdump, iperf3, mtr) ---
    def run_ping(self, target: str = "1.1.1.1", count: int = 4) -> Dict[str, Any]:
        try:
            res = subprocess.run(["ping", "-c", str(count), "-W", "2", target],
                                 stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=10)
            return {"target": target, "success": res.returncode == 0, "output": res.stdout.strip()}
        except Exception as e:
            return {"target": target, "success": False, "output": f"Ping error: {str(e)}"}

    def run_traceroute(self, target: str = "1.1.1.1") -> Dict[str, Any]:
        try:
            # Try mtr or traceroute
            res = subprocess.run(["mtr", "-r", "-c", "2", target],
                                 stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=15)
            return {"target": target, "tool": "mtr", "output": res.stdout.strip()}
        except Exception:
            try:
                res = subprocess.run(["traceroute", "-n", "-m", "15", target],
                                     stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=15)
                return {"target": target, "tool": "traceroute", "output": res.stdout.strip()}
            except Exception as e:
                return {"target": target, "tool": "none", "output": f"Traceroute error: {str(e)}"}

    def run_tcpdump_sniff(self, iface: str = "eth0", count: int = 10) -> Dict[str, Any]:
        try:
            res = subprocess.run(["tcpdump", "-i", iface, "-c", str(count), "-n", "-q"],
                                 stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=5)
            lines = res.stdout.strip().splitlines() if res.stdout else []
            return {"interface": iface, "captured_packets": len(lines), "packets": lines}
        except Exception as e:
            return {"interface": iface, "captured_packets": 0, "packets": [], "status": f"tcpdump idle or interface inactive: {str(e)}"}

    def get_live_conntrack(self, limit: int = 50) -> Dict[str, Any]:
        try:
            res = subprocess.run(["conntrack", "-L", "-o", "extended"],
                                 stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=5)
            lines = res.stdout.strip().splitlines() if res.stdout else []
            return {"total_sessions": len(lines), "sessions": lines[:limit]}
        except Exception:
            # Fallback read /proc/net/nf_conntrack
            try:
                with open("/proc/net/nf_conntrack", "r") as f:
                    lines = [line.strip() for line in f.readlines()]
                return {"total_sessions": len(lines), "sessions": lines[:limit]}
            except Exception as e:
                return {"total_sessions": 0, "sessions": [], "status": "nf_conntrack table empty or module not active"}

    # --- 5. DNS AdBlock & Malware Sinkhole ---
    def get_adblock_status(self) -> Dict[str, Any]:
        ab = self.config.get("dns_adblock_sinkhole", {})
        return {
            "enabled": ab.get("enabled", True),
            "blocked_categories": ab.get("blocked_categories", ["ads", "malware", "gambling"]),
            "custom_domains_count": len(ab.get("custom_blocked_domains", [])),
            "sample_domains": ab.get("custom_blocked_domains", []),
            "total_queries_blocked": ab.get("total_queries_blocked", 0)
        }

    # --- 6. Subscriber & PPPoE / IPoE BRAS ---
    def get_bras_status(self) -> Dict[str, Any]:
        bras = self.config.get("subscriber_bras", {})
        return {
            "enabled": bras.get("enabled", False),
            "interface": bras.get("interface", "eth1"),
            "ip_pool": bras.get("ip_pool", "10.100.0.2-10.100.255.254"),
            "radius_auth": bras.get("radius_enabled", False),
            "active_subscribers": 0,
            "engine": "accel-ppp kernel accelerated"
        }
