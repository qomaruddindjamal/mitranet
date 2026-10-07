"""
MitraNet Management REST API & WebUI Server.
Provides authenticated JSON endpoints for system, interfaces, routing, VLAN,
bridge, bond, VRF, firewall, gateway monitor, configuration transactions, and logs.
Zero external runtime dependencies (Python standard library http.server).
"""

import os
import sys
import json
import time
import socket
import logging
from http.server import ThreadingHTTPServer, BaseHTTPRequestHandler
from http import cookies
from urllib.parse import urlparse, parse_qs
from typing import Dict, Any, Optional, Tuple

from mitranet.core.version import MITRANET_VERSION, CODENAME, PRETTY_NAME
from mitranet.src.api.auth import AuthManager

# Core Subsystems
from mitranet.core.network.discovery import InterfaceDiscoveryService
from mitranet.core.network.config_service import InterfaceConfigurationService
from mitranet.core.network.routing_discovery import RouteDiscoveryService
from mitranet.core.network.routing_service import RouteConfigurationService
from mitranet.core.network.vlan import VlanService
from mitranet.core.network.bridge import BridgeService
from mitranet.core.network.bonding import BondService
from mitranet.core.network.vrf import VRFService
from mitranet.core.firewall.engine import FirewallTransactionEngine
from mitranet.core.firewall.models import (
    FirewallRule,
    FirewallAction,
    FirewallProtocol,
    FirewallDirection,
    FirewallPolicy,
    FirewallTableConfig,
    NatRule,
    NatType,
)
from mitranet.core.transaction.engine import NetworkTransactionEngine
from mitranet.core.services.sysmetrics import SystemMetricsCollector

logger = logging.getLogger("mitranet.api")

# Singleton Services
auth_mgr = AuthManager()
iface_discovery = InterfaceDiscoveryService()
iface_config = InterfaceConfigurationService()
route_discovery = RouteDiscoveryService()
route_config = RouteConfigurationService()
vlan_service = VlanService()
bridge_service = BridgeService()
bond_service = BondService()
vrf_service = VRFService()
fw_engine = FirewallTransactionEngine()
tx_engine = NetworkTransactionEngine()


class ManagementApiHandler(BaseHTTPRequestHandler):
    """HTTP Request Handler for MitraNet Management API and Static WebUI."""

    server_version = "MitraNet-WebUI/1.0.2"

    def _get_cookie_token(self) -> Optional[str]:
        """Extracts session token from Cookie header."""
        cookie_header = self.headers.get("Cookie")
        if not cookie_header:
            return None
        try:
            c = cookies.SimpleCookie()
            c.load(cookie_header)
            if "mitranet_session" in c:
                return c["mitranet_session"].value
        except Exception:
            return None
        return None

    def _authenticate_request(self) -> Tuple[bool, Optional[Dict[str, Any]]]:
        """Verifies session token from cookie."""
        token = self._get_cookie_token()
        session = auth_mgr.validate_session(token)
        if session:
            return True, session
        return False, None

    def _send_json(
        self,
        status: int,
        data: Any,
        headers: Optional[Dict[str, str]] = None,
        set_cookie: Optional[str] = None,
    ) -> None:
        """Sends structured JSON response."""
        resp_bytes = json.dumps(data, indent=2).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(resp_bytes)))
        self.send_header("X-Content-Type-Options", "nosniff")
        self.send_header("X-Frame-Options", "DENY")
        self.send_header("Content-Security-Policy", "default-src 'self' 'unsafe-inline'; img-src 'self' data:;")
        if set_cookie:
            self.send_header("Set-Cookie", set_cookie)
        if headers:
            for k, v in headers.items():
                self.send_header(k, v)
        self.end_headers()
        self.wfile.write(resp_bytes)

    def _send_file(self, file_path: str, content_type: str) -> None:
        """Serves static frontend asset safely."""
        if not os.path.isfile(file_path):
            self._send_json(404, {"error": "File not found"})
            return
        try:
            with open(file_path, "rb") as f:
                content = f.read()
            self.send_response(200)
            self.send_header("Content-Type", content_type)
            self.send_header("Content-Length", str(len(content)))
            self.send_header("X-Content-Type-Options", "nosniff")
            self.send_header("X-Frame-Options", "DENY")
            self.send_header("Cache-Control", "no-cache")
            self.end_headers()
            self.wfile.write(content)
        except Exception as e:
            self._send_json(500, {"error": f"Failed reading asset: {e}"})

    def _read_body_json(self) -> Optional[Dict[str, Any]]:
        """Parses incoming JSON payload."""
        try:
            length = int(self.headers.get("Content-Length", 0))
            if length == 0:
                return {}
            body = self.rfile.read(length).decode("utf-8")
            return json.loads(body)
        except Exception:
            return None

    def _proxy_to_php(self, method: str) -> None:
        """Proxies WebUI HTTP requests to local PHP-S backend (port 8000) preserving headers & cookies."""
        import urllib.request
        import urllib.error

        target_url = f"http://127.0.0.1:8000{self.path}"
        req_headers = {}
        for k, v in self.headers.items():
            if k.lower() not in ("host", "content-length"):
                req_headers[k] = v
        req_headers["Host"] = "127.0.0.1:8000"

        req_data = None
        if method == "POST":
            length = int(self.headers.get("Content-Length", 0))
            if length > 0:
                req_data = self.rfile.read(length)

        try:
            req = urllib.request.Request(target_url, data=req_data, headers=req_headers, method=method)
            # Do not follow redirects automatically so cookies & Location are passed back to client
            class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
                def redirect_request(self, req, fp, code, msg, hdrs, newurl):
                    return None

            opener = urllib.request.build_opener(NoRedirectHandler)
            try:
                resp = opener.open(req)
                status_code = resp.status
                headers = resp.headers
                content = resp.read()
            except urllib.error.HTTPError as e:
                status_code = e.code
                headers = e.headers
                content = e.read()

            self.send_response(status_code)
            for k, v in headers.items():
                if k.lower() in ("transfer-encoding", "content-length", "connection"):
                    continue
                if k.lower() == "set-cookie":
                    # headers.get_all or raw lines preserve multiple cookies
                    for sc in headers.get_all("Set-Cookie", [v]):
                        self.send_header("Set-Cookie", sc)
                else:
                    self.send_header(k, v)
            self.send_header("Content-Length", str(len(content)))
            self.end_headers()
            self.wfile.write(content)
        except Exception as e:
            logger.error("Failed proxying to PHP WebUI: %s", e)
            self._send_json(502, {"error": f"WebUI backend gateway error: {e}"})

    # =========================================================================
    # GET DISPATCHER
    # =========================================================================

    def do_GET(self) -> None:
        parsed = urlparse(self.path)
        path = parsed.path

        # If not an API endpoint, forward to PHP WebUI
        if not path.startswith("/api/v1/"):
            self._proxy_to_php("GET")
            return

        # Public Status Endpoint
        if path == "/api/v1/ping":
            self._send_json(200, {"status": "ok", "time": time.time()})
            return

        # Authentication Check Endpoint
        if path == "/api/v1/auth/status":
            is_auth, session = self._authenticate_request()
            if is_auth and session:
                self._send_json(200, {
                    "authenticated": True,
                    "username": session["username"],
                    "csrf_token": session["csrf_token"],
                })
            else:
                self._send_json(200, {"authenticated": False})
            return

        # Protected Management Endpoints
        is_auth, session = self._authenticate_request()
        if not is_auth:
            self._send_json(401, {"error": "Authentication required"})
            return

        # 1. System Info & Real-Time Metrics
        if path == "/api/v1/system":
            cpu = SystemMetricsCollector.get_cpu_stats()
            mem = SystemMetricsCollector.get_memory_stats()
            
            # Mem info calculations (kB)
            mem_total = mem.get("MemTotal", 0)
            mem_free = mem.get("MemFree", 0)
            mem_avail = mem.get("MemAvailable", mem_free)
            mem_used = max(0, mem_total - mem_avail)
            mem_pct = round((mem_used / mem_total * 100), 1) if mem_total > 0 else 0

            # Uptime
            uptime_sec = 0
            if os.path.exists("/proc/uptime"):
                try:
                    with open("/proc/uptime", "r") as uf:
                        uptime_sec = float(uf.read().split()[0])
                except Exception:
                    pass

            # DNS servers and domain from /etc/resolv.conf
            dns_servers = []
            domain_name = "home.arpa"
            if os.path.exists("/etc/resolv.conf"):
                try:
                    with open("/etc/resolv.conf", "r") as rf:
                        for line in rf:
                            parts = line.strip().split()
                            if len(parts) >= 2:
                                if parts[0] == "nameserver" and parts[1] not in dns_servers:
                                    dns_servers.append(parts[1])
                                elif parts[0] in ("domain", "search"):
                                    domain_name = parts[1]
                except Exception:
                    pass

            # Timezone
            current_tz = "Etc/UTC"
            if os.path.exists("/etc/timezone"):
                try:
                    with open("/etc/timezone", "r") as tf:
                        current_tz = tf.read().strip()
                except Exception:
                    pass
            elif os.path.islink("/etc/localtime"):
                try:
                    link_target = os.readlink("/etc/localtime")
                    if "zoneinfo/" in link_target:
                        current_tz = link_target.split("zoneinfo/")[-1]
                except Exception:
                    pass

            self._send_json(200, {
                "os": "MitraNet",
                "codename": CODENAME,
                "pretty_name": PRETTY_NAME,
                "version": MITRANET_VERSION,
                "kernel": os.uname().release if hasattr(os, "uname") else "Linux",
                "hostname": socket.gethostname(),
                "domain": domain_name,
                "dns_servers": dns_servers,
                "timezone": current_tz,
                "timeservers": "pool.ntp.org",
                "uptime_seconds": uptime_sec,
                "cpu": {

                    "user": cpu.get("user", 0),
                    "system": cpu.get("system", 0),
                    "idle": cpu.get("idle", 0),
                },
                "memory": {
                    "total_kb": mem_total,
                    "used_kb": mem_used,
                    "available_kb": mem_avail,
                    "used_percent": mem_pct,
                },
            })
            return

        # 2. Interfaces
        if path == "/api/v1/interfaces":
            try:
                ifaces = iface_discovery.discover_interfaces()
                result = []
                for i in ifaces:
                    d = i.model_dump()
                    traffic = SystemMetricsCollector.get_interface_traffic(i.name)
                    d["traffic"] = traffic
                    # is_up is True if administratively UP or link operational UP
                    d["is_up"] = (d.get("admin_state") == "UP" or "UP" in d.get("flags", []) or d.get("oper_state") == "UP")
                    result.append(d)
                self._send_json(200, result)
            except Exception as e:
                self._send_json(500, {"error": f"Interface discovery failed: {e}"})
            return

        if path.startswith("/api/v1/interfaces/"):
            if path == "/api/v1/interfaces/vethernet":
                # Handled by vEthernet section below
                pass
            else:
                ifname = path.replace("/api/v1/interfaces/", "").strip()
                try:
                    ifaces = iface_discovery.discover_interfaces()
                    found = next((i for i in ifaces if i.name == ifname), None)
                    if not found:
                        self._send_json(404, {"error": f"Interface '{ifname}' not found"})
                        return
                    d = found.model_dump()
                    d["traffic"] = SystemMetricsCollector.get_interface_traffic(ifname)
                    d["is_up"] = (d.get("admin_state") == "UP" or "UP" in d.get("flags", []) or d.get("oper_state") == "UP")
                    self._send_json(200, d)
                except Exception as e:
                    self._send_json(500, {"error": str(e)})
                return

        # 3. Routing
        if path == "/api/v1/routes":
            try:
                r_v4 = route_discovery.get_routes(family="inet")
                r_v6 = route_discovery.get_routes(family="inet6")
                self._send_json(200, {
                    "ipv4": [r.model_dump() for r in r_v4],
                    "ipv6": [r.model_dump() for r in r_v6],
                })
            except Exception as e:
                self._send_json(500, {"error": f"Routing query failed: {e}"})
            return

        if path == "/api/v1/gateways":
            try:
                gateways = []
                r_v4 = route_discovery.get_routes(family="inet")
                for r in r_v4:
                    rd = r.model_dump()
                    if rd.get("destination") in ("0.0.0.0/0", "default") or rd.get("dst") == "default":
                        gw_ip = rd.get("gateway") or rd.get("via") or ""
                        dev = rd.get("interface") or rd.get("dev") or ""
                        if gw_ip:
                            gateways.append({
                                "name": f"WAN_{dev.upper()}",
                                "interface": dev,
                                "gateway": gw_ip,
                                "monitor_ip": gw_ip,
                                "default": True,
                                "status": "online",
                                "description": f"Interface {dev} Default IPv4 Gateway"
                            })
                # Check WireGuard or other default gateways
                r_v6 = route_discovery.get_routes(family="inet6")
                for r in r_v6:
                    rd = r.model_dump()
                    if rd.get("destination") in ("::/0", "default"):
                        gw_ip = rd.get("gateway") or rd.get("via") or ""
                        dev = rd.get("interface") or rd.get("dev") or ""
                        if gw_ip:
                            gateways.append({
                                "name": f"WAN6_{dev.upper()}",
                                "interface": dev,
                                "gateway": gw_ip,
                                "monitor_ip": gw_ip,
                                "default": True,
                                "status": "online",
                                "description": f"Interface {dev} Default IPv6 Gateway"
                            })
                self._send_json(200, gateways)
            except Exception as e:
                self._send_json(500, {"error": f"Gateway query failed: {e}"})
            return


        # 4. VLANs
        if path == "/api/v1/vlans":
            try:
                vlans = vlan_service.discover_vlans()
                self._send_json(200, [v.model_dump() for v in vlans])
            except Exception as e:
                self._send_json(500, {"error": f"VLAN query failed: {e}"})
            return

        # 5. Bridges (exclude vEthernet / KVM guest subnets)
        if path == "/api/v1/bridges":
            try:
                # Load saved vethernet names so they are not treated as standard bridges
                veth_names = set()
                conf_file = "/etc/mitranet/network/vethernet.json"
                if os.path.isfile(conf_file):
                    try:
                        with open(conf_file, "r") as cf:
                            veth_db = json.load(cf)
                            veth_names = set(veth_db.keys())
                    except Exception:
                        pass

                raw_bridges = bridge_service.discover_bridges()
                # A bridge is a dedicated user-created bridge if it is NOT a vethernet device
                std_bridges = []
                for b in raw_bridges:
                    b_name = b.name
                    if b_name not in veth_names and not b_name.startswith("veth"):
                        std_bridges.append(b.model_dump())
                self._send_json(200, std_bridges)
            except Exception as e:
                self._send_json(500, {"error": f"Bridge query failed: {e}"})
            return

        # 6. Bonds
        if path == "/api/v1/bonds":
            try:
                bonds = bond_service.discover_bonds()
                self._send_json(200, [b.model_dump() for b in bonds])
            except Exception as e:
                self._send_json(500, {"error": f"Bond query failed: {e}"})
            return

        # 7. VRFs
        if path == "/api/v1/vrfs":
            try:
                vrfs = vrf_service.discover_vrfs()
                self._send_json(200, [v.model_dump() for v in vrfs])
            except Exception as e:
                self._send_json(500, {"error": f"VRF query failed: {e}"})
            return

        # 8. Firewall
        if path == "/api/v1/firewall":
            try:
                st = fw_engine.get_status()
                cfg = fw_engine.running_config
                cand = fw_engine.candidate_config
                self._send_json(200, {
                    "status": st.model_dump(),
                    "config": cfg.model_dump(),
                    "candidate": cand.model_dump(),
                })
            except Exception as e:
                self._send_json(500, {"error": f"Firewall query failed: {e}"})
            return

        # 9. Gateways
        if path == "/api/v1/gateways":
            # Real gateway detection from routing table and active interfaces
            try:
                v4_routes = route_discovery.get_routes(family="inet")
                gateways = []
                for r in v4_routes:
                    if (r.destination == "0.0.0.0/0" or r.destination == "default") and r.gateway:
                        gateways.append({
                            "name": f"GW_{r.interface}",
                            "gateway": r.gateway,
                            "interface": r.interface,
                            "metric": r.metric or 0,
                            "status": "online",
                            "protocol": r.protocol,
                        })
                self._send_json(200, gateways)
            except Exception as e:
                self._send_json(500, {"error": f"Gateway query failed: {e}"})
            return

        # 10. Configuration & Transaction State
        if path == "/api/v1/config/status":
            try:
                self._send_json(200, {
                    "running_version": tx_engine.running_config.config_version,
                    "candidate_version": tx_engine.candidate_config.config_version,
                    "current_transaction": tx_engine.current_record.model_dump() if tx_engine.current_record else None,
                    "is_locked": tx_engine.lock.is_locked(),
                })
            except Exception as e:
                self._send_json(500, {"error": f"Config state query failed: {e}"})
            return

        # 11. Logs
        if path == "/api/v1/logs":
            qs = parse_qs(parsed.query)
            cat = qs.get("category", ["system"])[0]
            lines = []
            
            # Safely query Linux journal or log files without path traversal
            if cat == "firewall":
                if os.path.exists("/var/log/messages"):
                    with open("/var/log/messages", "r", errors="ignore") as f:
                        lines = [l.strip() for l in f.readlines() if "MN_" in l or "nftables" in l][-50:]
            elif cat == "gateway":
                if os.path.exists("/var/log/mitranet/gateway-monitor.log"):
                    with open("/var/log/mitranet/gateway-monitor.log", "r", errors="ignore") as f:
                        lines = [l.strip() for l in f.readlines()][-50:]
            else:
                # Default system log
                if os.path.exists("/var/log/syslog"):
                    with open("/var/log/syslog", "r", errors="ignore") as f:
                        lines = [l.strip() for l in f.readlines()][-50:]
                elif os.path.exists("/var/log/messages"):
                    with open("/var/log/messages", "r", errors="ignore") as f:
                        lines = [l.strip() for l in f.readlines()][-50:]

            self._send_json(200, {"category": cat, "lines": lines})
            return

        # 12. ARP Table (Read from Linux /proc/net/arp)
        if path == "/api/v1/arp":
            arp_entries = []
            if os.path.exists("/proc/net/arp"):
                try:
                    with open("/proc/net/arp", "r") as f:
                        lines = f.readlines()
                    # Skip header line
                    for line in lines[1:]:
                        parts = line.split()
                        if len(parts) >= 6:
                            ip_addr = parts[0]
                            hw_type = parts[1]
                            flags = parts[2]
                            hw_addr = parts[3]
                            mask = parts[4]
                            dev = parts[5]
                            arp_entries.append({
                                "ip": ip_addr,
                                "hw_type": hw_type,
                                "flags": flags,
                                "mac": hw_addr,
                                "mask": mask,
                                "interface": dev,
                                "status": "active" if hw_addr != "00:00:00:00:00:00" else "incomplete"
                            })
                except Exception as e:
                    logger.warning("Error reading /proc/net/arp: %s", e)
            self._send_json(200, arp_entries)
            return

        # 13. Conntrack / State Table (Read from /proc/net/nf_conntrack)
        if path == "/api/v1/conntrack":
            states = []
            if os.path.exists("/proc/net/nf_conntrack"):
                try:
                    with open("/proc/net/nf_conntrack", "r", errors="ignore") as f:
                        lines = f.readlines()
                    for line in lines[-200:]: # Top 200 states
                        parts = line.split()
                        if len(parts) >= 4:
                            proto = parts[2]
                            entry = {
                                "raw": line.strip(),
                                "protocol": proto,
                                "details": parts[3:]
                            }
                            states.append(entry)
                except Exception as e:
                    logger.warning("Error reading /proc/net/nf_conntrack: %s", e)
            self._send_json(200, {"total": len(states), "states": states})
            return

        # 14. Packages List
        if path == "/api/v1/packages":
            import subprocess
            packages = []
            try:
                cmd = ["dpkg-query", "-W", "-f=${Package}|${Version}|${Status}|${Description}\\n"]
                proc = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=10)
                for line in proc.stdout.splitlines():
                    parts = line.split("|")
                    if len(parts) >= 3 and "installed" in parts[2]:
                        pkg_name = parts[0]
                        version = parts[1]
                        desc = parts[3].split("\n")[0] if len(parts) > 3 else ""
                        packages.append({
                            "name": pkg_name,
                            "version": version,
                            "status": "installed",
                            "description": desc,
                            "category": "system"
                        })
            except Exception as e:
                logger.warning("Error querying dpkg packages: %s", e)
            self._send_json(200, {"packages": packages})
            return

        # 15. WireGuard Status & Config
        if path == "/api/v1/wireguard":
            import subprocess
            tunnels = []
            wg_running = False
            try:
                proc = subprocess.run(["systemctl", "is-active", "wg-quick@wg0"], stdout=subprocess.PIPE, text=True)
                wg_running = (proc.stdout.strip() == "active")

                show_proc = subprocess.run(["wg", "show", "all", "dump"], stdout=subprocess.PIPE, text=True)
                lines = show_proc.stdout.strip().splitlines()
                
                cur_tun = None
                for line in lines:
                    cols = line.split("\t")
                    if len(cols) == 5:
                        # Interface line
                        dev, priv, pub, port, fwmark = cols
                        cur_tun = {
                            "name": dev,
                            "public_key": pub,
                            "listen_port": port,
                            "enabled": True,
                            "peers": []
                        }
                        tunnels.append(cur_tun)
                        # Parse comments for descriptions if available
                        peer_descr = "WireGuard Peer"
                        if "r9T/01aMV" in pub:
                            peer_descr = "MikroTik CHR VPS (103.93.162.168)"
                        elif "ho3tpxf" in pub:
                            peer_descr = "Smartphone HP Direct (Local WiFi)"
                        cur_tun["peers"].append({
                            "public_key": pub,
                            "description": peer_descr,
                            "endpoint": endpoint,
                            "allowed_ips": allowed_ips,
                            "latest_handshake": latest_handshake,
                            "transfer_rx": rx,
                            "transfer_tx": tx,
                            "persistent_keepalive": persistent_keepalive
                        })
                # Add tunnel description
                for t in tunnels:
                    t["description"] = "Tunnel to MikroTik CHR VPS (103.93.162.168)"
                    t["interface"] = "WGVPN (opt1)"
            except Exception as e:
                logger.warning("Error getting wireguard status: %s", e)


            # Fallback if config exists on disk but wg not running
            if not tunnels and os.path.exists("/etc/wireguard/wg0.conf"):
                tunnels.append({
                    "name": "wg0",
                    "public_key": "Configured on /etc/wireguard/wg0.conf",
                    "listen_port": 51820,
                    "enabled": wg_running,
                    "peers": []
                })

            self._send_json(200, {
                "running": wg_running,
                "tunnels": tunnels
            })
            return

        # 16. Xray-core Status & Config
        if path == "/api/v1/xray":
            import subprocess
            xray_running = False
            version_str = "Xray 1.8.24 (go1.23.0 linux/amd64)"
            cfg_obj = {}
            try:
                proc = subprocess.run(["systemctl", "is-active", "xray"], stdout=subprocess.PIPE, text=True)
                xray_running = (proc.stdout.strip() == "active")

                vproc = subprocess.run(["/usr/local/bin/xray", "version"], stdout=subprocess.PIPE, text=True)
                if vproc.returncode == 0:
                    version_str = vproc.stdout.splitlines()[0]

                if os.path.exists("/usr/local/etc/xray/config.json"):
                    with open("/usr/local/etc/xray/config.json", "r") as f:
                        cfg_obj = json.load(f)
            except Exception as e:
                logger.warning("Error querying xray status: %s", e)

            self._send_json(200, {
                "running": xray_running,
                "version": version_str,
                "config": cfg_obj
            })
            return

        # 17. Users List
        if path == "/api/v1/users":
            users_list = []
            if os.path.exists(auth_mgr.auth_file):
                try:
                    with open(auth_mgr.auth_file, "r", encoding="utf-8") as f:
                        udata = json.load(f)
                    for uname, info in udata.items():
                        users_list.append({
                            "username": uname,
                            "scope": "system" if uname == "admin" else "user",
                            "status": "enabled",
                            "groups": ["admins"] if uname == "admin" else ["users"],
                            "created_at": info.get("created_at", 0)
                        })
                except Exception as e:
                    logger.warning("Error reading users: %s", e)
            self._send_json(200, {"users": users_list})
            return

        # 17b. Certificates & Certificate Authorities
        if path == "/api/v1/certificates":
            import glob, ssl
            cas = []
            certs = []
            
            # Read standard system CA bundles
            ca_files = [
                "/etc/ssl/certs/ca-certificates.crt",
                "/etc/ssl/certs/GlobalSign_Root_CA_-_R3.pem",
                "/etc/ssl/certs/ISRG_Root_X2.pem"
            ]
            for cf in ca_files:
                if os.path.exists(cf):
                    base_name = os.path.basename(cf).replace(".pem", "").replace(".crt", "").replace("_", " ")
                    cas.append({
                        "name": base_name,
                        "internal": False,
                        "issuer": "External / System Root",
                        "count": 1,
                        "distinguished_name": f"CN={base_name}",
                        "in_use": True
                    })

            # Check appliance local certs in /etc/mitranet/certs
            app_cert_dir = "/etc/mitranet/certs"
            if os.path.exists(app_cert_dir):
                for f in glob.glob(os.path.join(app_cert_dir, "*.crt")) + glob.glob(os.path.join(app_cert_dir, "*.pem")):
                    cname = os.path.basename(f)
                    certs.append({
                        "name": cname,
                        "issuer": "MitraNet Appliance CA",
                        "type": "Server Certificate",
                        "expires": "2030-01-01",
                        "distinguished_name": f"CN={cname}, O=MitraNet",
                        "in_use": True
                    })

            self._send_json(200, {
                "authorities": cas,
                "certificates": certs
            })
            return


        # 18. Speedtest Servers and History
        if path == "/api/v1/tools/speedtest/servers":
            servers = [
                {"id": "auto", "name": "Automatic Selection", "location": "Nearest", "country": "ID"},
                {"id": "50552", "name": "Telkom Indonesia", "location": "Jakarta", "country": "ID"},
                {"id": "32168", "name": "Biznet Networks", "location": "Jakarta", "country": "ID"},
                {"id": "24241", "name": "Indosat Ooredoo Hutchison", "location": "Surabaya", "country": "ID"},
                {"id": "48834", "name": "MyRepublic ID", "location": "Bandung", "country": "ID"}
            ]
            self._send_json(200, {"success": True, "servers": servers})
            return

        if path == "/api/v1/tools/speedtest/history":
            hist_file = "/etc/mitranet/secrets/speedtest_history.json"
            history = []
            if os.path.exists(hist_file):
                try:
                    with open(hist_file, "r") as f:
                        history = json.load(f)
                except Exception:
                    history = []
            self._send_json(200, {"success": True, "history": history})
            return

        # 18b. Virtual Ethernet (vEthernet / Host-Guest Subnets) - Live Inspection
        if path == "/api/v1/interfaces/vethernet":
            import subprocess
            vethernets = []
            conf_file = "/etc/mitranet/network/vethernet.json"
            saved_configs = {}
            if os.path.isfile(conf_file):
                try:
                    with open(conf_file, "r") as cf:
                        saved_configs = json.load(cf)
                except Exception:
                    saved_configs = {}

            # Query real system interfaces via ip -j addr
            try:
                ip_proc = subprocess.run(["ip", "-j", "addr", "show"], stdout=subprocess.PIPE, text=True)
                if ip_proc.returncode == 0:
                    data = json.loads(ip_proc.stdout)
                    for iface in data:
                        ifname = iface.get("ifname", "")
                        # Include if it matches veth*, vnet*, or is saved in vethernet config
                        if ifname.startswith("veth") or ifname.startswith("vnet") or ifname in saved_configs:
                            addrs = []
                            for addr_info in iface.get("addr_info", []):
                                if addr_info.get("family") == "inet":
                                    local_ip = addr_info.get("local")
                                    prefixlen = addr_info.get("prefixlen")
                                    addrs.append(f"{local_ip}/{prefixlen}")

                            # Find members if bridge
                            members = []
                            try:
                                br_proc = subprocess.run(["ip", "-j", "link", "show", "master", ifname], stdout=subprocess.PIPE, text=True)
                                if br_proc.returncode == 0 and br_proc.stdout.strip():
                                    for m in json.loads(br_proc.stdout):
                                        members.append(m.get("ifname"))
                            except Exception:
                                pass

                            cfg = saved_configs.get(ifname, {})
                            vethernets.append({
                                "name": ifname,
                                "ip_cidr": addrs[0] if addrs else cfg.get("ip_cidr", ""),
                                "description": cfg.get("description", "Virtual Ethernet Subnet"),
                                "operstate": iface.get("operstate", "UNKNOWN"),
                                "mac": iface.get("address", ""),
                                "members": members,
                                "status": "UP" if iface.get("operstate") in ("UP", "UNKNOWN") else "DOWN"
                            })
            except Exception as e:
                logger.error(f"Error querying vethernets: {e}")

            self._send_json(200, {"success": True, "vethernet": vethernets})
            return

        # 18b. DHCP Server Settings (dnsmasq per-interface config)
        if path == "/api/v1/services/dhcp":
            import glob
            dhcp_configs = {}
            conf_dir = "/etc/dnsmasq.d"
            if os.path.isdir(conf_dir):
                for fpath in glob.glob(f"{conf_dir}/*.conf"):
                    fname = os.path.basename(fpath)
                    ifname = fname.replace("vethernet_", "").replace("dhcp_", "").replace(".conf", "")
                    cfg = {
                        "interface": ifname,
                        "enabled": True,
                        "range_start": "",
                        "range_end": "",
                        "gateway": "",
                        "dns": [],
                        "lease_time": "12h"
                    }
                    try:
                        with open(fpath, "r") as cf:
                            for line in cf:
                                line = line.strip()
                                if line.startswith("dhcp-range="):
                                    parts = line.split("=", 1)[1].split(",")
                                    if len(parts) >= 3:
                                        cfg["range_start"] = parts[1]
                                        cfg["range_end"] = parts[2]
                                        if len(parts) >= 5:
                                            cfg["lease_time"] = parts[4]
                                elif line.startswith("dhcp-option=") and "option:router" in line:
                                    cfg["gateway"] = line.split(",")[-1]
                                elif line.startswith("dhcp-option=") and "option:dns-server" in line:
                                    cfg["dns"] = line.split(",")[2:]
                        dhcp_configs[ifname] = cfg
                    except Exception:
                        pass
            self._send_json(200, {"success": True, "dhcp": dhcp_configs})
            return

        # 19. Virtual Machines (KVM / Containers) - Live Inspection (No Dummy)
        if path == "/api/v1/services/kvm":
            import subprocess
            vms = []
            vms_dir = "/var/lib/mitranet/vms"
            conf_dir = "/etc/mitranet/vms"

            if os.path.isdir(vms_dir):
                for item in sorted(os.listdir(vms_dir)):
                    item_path = os.path.join(vms_dir, item)
                    if os.path.isdir(item_path):
                        vm_id = item
                        disk_path = os.path.join(item_path, "disk.qcow2")
                        env_path = os.path.join(conf_dir, f"{vm_id}.env")

                        # Read config
                        vcpu = 1
                        ram_mb = 1024
                        port_fwd = 8888
                        iso_file = ""
                        vnc_port = 5900
                        net_mode = "veth"
                        veth_iface = "veth0"
                        guest_ip = ""
                        if os.path.isfile(env_path):
                            try:
                                with open(env_path, "r") as ef:
                                    for line in ef:
                                        line = line.strip()
                                        if line.startswith("RAM_MB="):
                                            ram_mb = int(line.split("=", 1)[1])
                                        elif line.startswith("VCPU="):
                                            vcpu = int(line.split("=", 1)[1])
                                        elif line.startswith("PORT_FWD="):
                                            port_fwd = int(line.split("=", 1)[1])
                                        elif line.startswith("ISO_FILE="):
                                            iso_file = line.split("=", 1)[1].strip()
                                        elif line.startswith("VNC_PORT="):
                                            vnc_port = int(line.split("=", 1)[1])
                                        elif line.startswith("NET_MODE="):
                                            net_mode = line.split("=", 1)[1].strip()
                                        elif line.startswith("VETH_IFACE="):
                                            veth_iface = line.split("=", 1)[1].strip()
                                        elif line.startswith("GUEST_IP="):
                                            guest_ip = line.split("=", 1)[1].strip()
                            except Exception:
                                pass

                        # Query real disk size using qemu-img
                        disk_gb = 10
                        disk_actual_size = "0 MB"
                        if os.path.isfile(disk_path):
                            try:
                                img_proc = subprocess.run(
                                    ["qemu-img", "info", "-U", disk_path],
                                    stdout=subprocess.PIPE, text=True, timeout=3
                                )
                                if img_proc.returncode == 0:
                                    for line in img_proc.stdout.splitlines():
                                        if "virtual size:" in line:
                                            # e.g. virtual size: 10 GiB (10737418240 bytes)
                                            parts = line.split("virtual size:")[-1].strip()
                                            disk_actual_size = parts.split("(")[0].strip()
                                            if "GiB" in disk_actual_size:
                                                disk_gb = int(float(disk_actual_size.replace("GiB", "").strip()))
                            except Exception:
                                pass

                        # Check real systemd service status
                        status = "STOPPED"
                        active_proc = subprocess.run(
                            ["systemctl", "is-active", f"mitranet-vm@{vm_id}"],
                            stdout=subprocess.PIPE, text=True
                        )
                        if active_proc.stdout.strip() == "active":
                            status = "RUNNING"

                        # Check PID and memory RSS if running
                        pid = None
                        rss_kb = 0
                        if status == "RUNNING":
                            try:
                                p_proc = subprocess.run(
                                    ["systemctl", "show", f"mitranet-vm@{vm_id}", "--property=MainPID"],
                                    stdout=subprocess.PIPE, text=True
                                )
                                for pline in p_proc.stdout.splitlines():
                                    if pline.startswith("MainPID="):
                                        pid = int(pline.split("=")[1])
                                        break
                                if pid and os.path.exists(f"/proc/{pid}/status"):
                                    with open(f"/proc/{pid}/status", "r") as sf:
                                        for sline in sf:
                                            if sline.startswith("VmRSS:"):
                                                rss_kb = int(sline.split()[1])
                                                break
                            except Exception:
                                pass

                        vms.append({
                            "id": vm_id,
                            "name": vm_id.capitalize() if vm_id != "aapanel" else "aaPanel",
                            "description": "aaPanel Linux Control Panel Environment (Built-in VM)" if vm_id == "aapanel" else f"MitraNet Virtual Guest ({vm_id})",
                            "is_default": (vm_id == "aapanel"),
                            "vcpu": vcpu,
                            "ram_mb": ram_mb,
                            "ram_rss_mb": round(rss_kb / 1024, 1) if rss_kb > 0 else 0,
                            "disk_gb": disk_gb,
                            "disk_size_info": disk_actual_size,
                            "port_fwd": port_fwd,
                            "iso": iso_file,
                            "vnc_port": vnc_port,
                            "net_mode": net_mode,
                            "veth_iface": veth_iface,
                            "guest_ip": guest_ip,
                            "interface": veth_iface if veth_iface else ("veth0" if net_mode == "veth" else "user-virtio"),
                            "bridge": veth_iface if veth_iface else "default",
                            "status": status,
                            "pid": pid,
                        })

            # Real ISO files list
            isos = []
            iso_dir = "/var/lib/mitranet/isos"
            if os.path.isdir(iso_dir):
                for f in sorted(os.listdir(iso_dir)):
                    fpath = os.path.join(iso_dir, f)
                    if os.path.isfile(fpath) and (f.endswith(".iso") or f.endswith(".img")):
                        sz_bytes = os.path.getsize(fpath)
                        sz_mb = round(sz_bytes / (1024 * 1024), 1)
                        sz_str = f"{round(sz_mb/1024, 2)} GB" if sz_mb > 1024 else f"{sz_mb} MB"
                        isos.append({
                            "filename": f,
                            "path": fpath,
                            "size_bytes": sz_bytes,
                            "size_str": sz_str,
                            "mtime": os.path.getmtime(fpath)
                        })

            self._send_json(200, {"vms": vms, "isos": isos})
            return

        # 20. Packages List (Debian .deb package integration)
        if path == "/api/v1/packages":
            import subprocess
            pkgs = []
            try:
                # Query dpkg-query for installed networking/mitranet packages
                proc = subprocess.run(
                    ["dpkg-query", "-W", "-f=${Package}\t${Version}\t${Status}\t${Description}\n"],
                    stdout=subprocess.PIPE, text=True
                )
                if proc.returncode == 0:
                    for line in proc.stdout.splitlines():
                        parts = line.split("\t")
                        if len(parts) >= 3 and "installed" in parts[2]:
                            p_name = parts[0]
                            # Highlight firewall/vpn/mitranet related packages
                            if any(k in p_name for k in ("mitranet", "wireguard", "nftables", "dnsmasq", "xray", "nginx", "php", "iproute2")):
                                pkgs.append({
                                    "name": p_name,
                                    "version": parts[1],
                                    "status": "installed",
                                    "descr": parts[3].split("\n")[0] if len(parts) > 3 else "Debian system package"
                                })
            except Exception as e:
                logger.warning("Error reading packages: %s", e)
            self._send_json(200, {"packages": pkgs})
            return

        # 21. System Firmware / Update Check
        if path == "/api/v1/system/update/check":
            import subprocess
            res = {
                "current_version": MITRANET_VERSION + "-RELEASE",
                "latest_version": MITRANET_VERSION + "-RELEASE",
                "up_to_date": True,
                "branch": "stable",
                "kernel": os.uname().release if hasattr(os, "uname") else "Linux",
                "updates_available": []
            }
            try:
                # Check apt upgradable packages
                proc = subprocess.run(["apt", "list", "--upgradable"], stdout=subprocess.PIPE, text=True, timeout=5)
                lines = [l for l in proc.stdout.splitlines() if "/" in l and "Listing" not in l]
                if lines:
                    res["up_to_date"] = False
                    res["updates_available"] = lines[:10]
            except Exception:
                pass
            self._send_json(200, res)
            return

        self._send_json(404, {"error": "Endpoint not found"})


    # =========================================================================
    # POST DISPATCHER (Mutations)
    # =========================================================================

    def do_POST(self) -> None:
        parsed = urlparse(self.path)
        path = parsed.path

        # If not an API endpoint, forward to PHP WebUI
        if not path.startswith("/api/v1/"):
            self._proxy_to_php("POST")
            return

        # 1. Login Endpoint
        if path == "/api/v1/auth/login":
            data = self._read_body_json() or {}
            username = data.get("username", "").strip()
            password = data.get("password", "")

            if not username or not password:
                self._send_json(400, {"error": "Username and password required"})
                return

            if auth_mgr.authenticate(username, password):
                token = auth_mgr.create_session(username)
                session = auth_mgr.sessions[token]
                cookie_str = f"mitranet_session={token}; Path=/; HttpOnly; SameSite=Strict"
                self._send_json(200, {
                    "success": True,
                    "username": username,
                    "session_token": token,
                    "csrf_token": session["csrf_token"],
                }, set_cookie=cookie_str)
            else:
                self._send_json(401, {"error": "Invalid username or password"})
            return

        # 2. Logout Endpoint
        if path == "/api/v1/auth/logout":
            token = self._get_cookie_token()
            auth_mgr.destroy_session(token)
            cookie_str = "mitranet_session=deleted; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT"
            self._send_json(200, {"success": True}, set_cookie=cookie_str)
            return

        # All subsequent mutation endpoints require valid session AND CSRF (or internal loopback call from PHP)
        client_host = self.client_address[0] if hasattr(self, 'client_address') and self.client_address else ""
        is_loopback = client_host in ("127.0.0.1", "::1", "localhost")

        is_auth, session = self._authenticate_request()
        if not is_loopback:
            if not is_auth or not session:
                self._send_json(401, {"error": "Authentication required"})
                return

            csrf_header = self.headers.get("X-CSRF-Token")
            if not auth_mgr.validate_csrf(session, csrf_header):
                self._send_json(403, {"error": "CSRF token validation failed"})
                return

        payload = self._read_body_json() or {}

        # 3. Interface Mutations
        if path == "/api/v1/interfaces/set-state":
            name = payload.get("name")
            state = payload.get("state")  # up or down
            if not name or state not in ("up", "down"):
                self._send_json(400, {"error": "Interface name and state (up/down) required"})
                return
            try:
                if state == "up": iface_config.set_interface_up(name)
                else: iface_config.set_interface_down(name)
                self._send_json(200, {"success": True, "message": f"Interface '{name}' set to {state.upper()}"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/interfaces/set-mtu":
            name = payload.get("name")
            mtu = payload.get("mtu")
            try:
                mtu_int = int(mtu)
                iface_config.set_mtu(name, mtu_int)
                self._send_json(200, {"success": True, "message": f"Interface '{name}' MTU set to {mtu_int}"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/interfaces/address/add":
            name = payload.get("name")
            cidr = payload.get("cidr")
            try:
                iface_config.add_address(name, cidr)
                self._send_json(200, {"success": True, "message": f"Address '{cidr}' added to '{name}'"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/interfaces/address/remove":
            name = payload.get("name")
            cidr = payload.get("cidr")
            try:
                iface_config.remove_address(name, cidr)
                self._send_json(200, {"success": True, "message": f"Address '{cidr}' removed from '{name}'"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        # 4. Route Mutations
        if path == "/api/v1/routes/add":
            dest = payload.get("destination")
            gw = payload.get("gateway")
            dev = payload.get("interface")
            metric = payload.get("metric")
            try:
                route_config.add_route(destination=dest, gateway=gw, interface=dev, metric=metric)
                self._send_json(200, {"success": True, "message": f"Route '{dest}' added successfully"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/routes/remove":
            dest = payload.get("destination")
            gw = payload.get("gateway")
            dev = payload.get("interface")
            try:
                route_config.remove_route(destination=dest, gateway=gw, interface=dev)
                self._send_json(200, {"success": True, "message": f"Route '{dest}' removed successfully"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        # 5. VLAN Mutations
        if path == "/api/v1/vlans/create":
            name = payload.get("name")
            parent = payload.get("parent")
            vid = payload.get("vlan_id")
            try:
                vlan_service.create_vlan(name, parent, int(vid))
                self._send_json(200, {"success": True, "message": f"VLAN interface '{name}' created"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/vlans/delete":
            name = payload.get("name")
            try:
                vlan_service.delete_vlan(name)
                self._send_json(200, {"success": True, "message": f"VLAN interface '{name}' deleted"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        # 6. Bridge Mutations
        if path == "/api/v1/bridges/create":
            name = payload.get("name")
            try:
                bridge_service.create_bridge(name)
                self._send_json(200, {"success": True, "message": f"Bridge '{name}' created"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/bridges/delete":
            name = payload.get("name")
            try:
                bridge_service.delete_bridge(name)
                self._send_json(200, {"success": True, "message": f"Bridge '{name}' deleted"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        # 7. VRF Mutations
        if path == "/api/v1/vrfs/create":
            name = payload.get("name")
            table = payload.get("table_id")
            try:
                vrf_service.create_vrf(name, int(table))
                self._send_json(200, {"success": True, "message": f"VRF '{name}' created with table {table}"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/vrfs/delete":
            name = payload.get("name")
            try:
                vrf_service.delete_vrf(name)
                self._send_json(200, {"success": True, "message": f"VRF '{name}' deleted"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        # 8. Firewall Mutations
        if path == "/api/v1/firewall/rule/add":
            rid = payload.get("id")
            act = payload.get("action", "accept")
            proto = payload.get("protocol", "any")
            src = payload.get("source", "any")
            dst = payload.get("destination", "any")
            prio = int(payload.get("priority", 100))
            iface = payload.get("interface", "any")

            try:
                cand = fw_engine.candidate_config
                if any(r.id == rid for r in cand.rules):
                    self._send_json(400, {"error": f"Rule ID '{rid}' already exists"})
                    return
                new_rule = FirewallRule(
                    id=rid,
                    action=FirewallAction(act),
                    protocol=FirewallProtocol(proto),
                    source=src,
                    destination=dst,
                    priority=prio,
                    interface=iface,
                )
                cand.rules.append(new_rule)
                fw_engine.save_candidate(cand)
                self._send_json(200, {"success": True, "message": f"Rule '{rid}' added to candidate"})
            except Exception as e:
                logger.exception("Error adding firewall rule: %s", e)
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/firewall/rule/delete":
            rid = payload.get("id")
            try:
                cand = fw_engine.candidate_config
                cand.rules = [r for r in cand.rules if r.id != rid]
                fw_engine.save_candidate(cand)
                self._send_json(200, {"success": True, "message": f"Rule '{rid}' deleted from candidate"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        # NAT Rule Mutations (Port Forward, Outbound, 1:1)
        if path == "/api/v1/firewall/nat/add":
            rid = payload.get("id", "").strip()
            ntype = payload.get("nat_type", "port_forward")
            iface = payload.get("interface", "any")
            proto = payload.get("protocol", "tcp")
            src_ip = payload.get("src_ip", "any")
            src_port = payload.get("src_port")
            dst_ip = payload.get("dst_ip", "any")
            dst_port = payload.get("dst_port")
            target_ip = payload.get("target_ip")
            target_port = payload.get("target_port")
            masq = bool(payload.get("masquerade", False))
            prio = int(payload.get("priority", 100))
            descr = payload.get("description", "")

            try:
                cand = fw_engine.candidate_config
                if any(r.id == rid for r in cand.nat_rules):
                    self._send_json(400, {"error": f"NAT Rule ID '{rid}' already exists"})
                    return
                new_nat = NatRule(
                    id=rid,
                    nat_type=NatType(ntype),
                    interface=iface,
                    protocol=FirewallProtocol(proto),
                    src_ip=src_ip,
                    src_port=src_port if src_port != "any" else None,
                    dst_ip=dst_ip,
                    dst_port=dst_port if dst_port != "any" else None,
                    target_ip=target_ip,
                    target_port=target_port if target_port != "any" else None,
                    masquerade=masq,
                    priority=prio,
                    description=descr,
                )
                cand.nat_rules.append(new_nat)
                fw_engine.save_candidate(cand)
                self._send_json(200, {"success": True, "message": f"NAT Rule '{rid}' added to candidate"})
            except Exception as e:
                logger.exception("Error adding NAT rule: %s", e)
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/firewall/nat/delete":
            rid = payload.get("id")
            try:
                cand = fw_engine.candidate_config
                cand.nat_rules = [r for r in cand.nat_rules if r.id != rid]
                fw_engine.save_candidate(cand)
                self._send_json(200, {"success": True, "message": f"NAT Rule '{rid}' deleted from candidate"})
            except Exception as e:
                self._send_json(400, {"error": str(e)})
            return

        if path == "/api/v1/firewall/apply":
            try:
                rec = fw_engine.apply_and_commit()
                self._send_json(200, {"success": True, "transaction_id": rec.transaction_id, "state": rec.state.value})
            except Exception as e:
                self._send_json(500, {"error": f"Firewall apply failed: {e}"})
            return

        if path == "/api/v1/firewall/reload":
            try:
                fw_engine.save_candidate(fw_engine.running_config.model_copy(deep=True))
                rec = fw_engine.apply_and_commit()
                self._send_json(200, {"success": True, "transaction_id": rec.transaction_id})
            except Exception as e:
                self._send_json(500, {"error": f"Firewall reload failed: {e}"})
            return

        # 9. Configuration Transaction Mutations
        if path == "/api/v1/config/apply":
            try:
                success, tx_id = tx_engine.apply_and_commit()
                self._send_json(200, {"success": success, "transaction_id": tx_id})
            except Exception as e:
                self._send_json(500, {"error": f"Transaction apply failed: {e}"})
            return

        if path == "/api/v1/config/rollback":
            snap_id = payload.get("snapshot_id")
            if not snap_id:
                self._send_json(400, {"error": "snapshot_id required for rollback"})
                return
            try:
                tx_engine.rollback_to_snapshot(snap_id)
                self._send_json(200, {"success": True, "message": f"Rolled back to snapshot '{snap_id}'"})
            except Exception as e:
                self._send_json(500, {"error": f"Rollback failed: {e}"})
            return

        # 10. System General Settings (Hostname, Domain, DNS, Timezone)
        if path == "/api/v1/system/update":
            new_hostname = payload.get("hostname", "").strip()
            new_domain = payload.get("domain", "").strip()
            dns_list = payload.get("dns_servers", [])
            new_tz = payload.get("timezone", "").strip()
            
            changes = []
            try:
                if new_hostname:
                    if os.path.exists("/etc/hostname"):
                        with open("/etc/hostname", "w") as hf:
                            hf.write(f"{new_hostname}\n")
                    if hasattr(os, "system"):
                        os.system(f"hostname {new_hostname}")
                    changes.append(f"hostname={new_hostname}")

                if new_domain or dns_list:
                    lines = []
                    if new_domain:
                        lines.append(f"domain {new_domain}")
                        lines.append(f"search {new_domain}")
                    for d in dns_list:
                        d = str(d).strip()
                        if d and not any(c in d for c in ";&|`$<>"):
                            lines.append(f"nameserver {d}")
                    if lines:
                        try:
                            with open("/etc/resolv.conf", "w") as rf:
                                rf.write("\n".join(lines) + "\n")
                            changes.append("DNS updated")
                        except Exception:
                            pass

                if new_tz and "/" in new_tz:
                    tz_path = f"/usr/share/zoneinfo/{new_tz}"
                    if os.path.exists(tz_path):
                        try:
                            if os.path.exists("/etc/localtime") or os.path.islink("/etc/localtime"):
                                os.remove("/etc/localtime")
                            os.symlink(tz_path, "/etc/localtime")
                            with open("/etc/timezone", "w") as tf:
                                tf.write(f"{new_tz}\n")
                            changes.append(f"timezone={new_tz}")
                        except Exception:
                            pass

                self._send_json(200, {"success": True, "message": "System configuration updated: " + ", ".join(changes) if changes else "No changes"})
            except Exception as e:
                self._send_json(500, {"error": f"Failed updating system settings: {e}"})
            return


        # 11. Diagnostic Tools (Ping, Traceroute)
        if path == "/api/v1/diag/ping":
            target = payload.get("host", "").strip()
            count = min(max(int(payload.get("count", 3)), 1), 10)
            if not target or any(c in target for c in ";&|`$<>"):
                self._send_json(400, {"error": "Invalid target host"})
                return
            try:
                import subprocess
                cmd = ["ping", "-c", str(count), "-W", "2", target]
                proc = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, timeout=15)
                self._send_json(200, {"host": target, "count": count, "output": proc.stdout, "returncode": proc.returncode})
            except Exception as e:
                self._send_json(500, {"error": f"Ping execution failed: {e}"})
            return

        if path == "/api/v1/diag/traceroute":
            target = payload.get("host", "").strip()
            if not target or any(c in target for c in ";&|`$<>"):
                self._send_json(400, {"error": "Invalid target host"})
                return
            try:
                import subprocess
                cmd = ["traceroute", "-m", "15", "-w", "2", target]
                proc = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, timeout=20)
                self._send_json(200, {"host": target, "output": proc.stdout, "returncode": proc.returncode})
            except Exception as e:
                self._send_json(500, {"error": f"Traceroute execution failed: {e}"})
            return

        # 12. WireGuard Service Mutations
        if path == "/api/v1/wireguard/service":
            action = payload.get("action", "") # start, stop, restart
            if action not in ("start", "stop", "restart"):
                self._send_json(400, {"error": "Invalid action. Use start, stop, or restart"})
                return
            try:
                import subprocess
                cmd = ["systemctl", action, "wg-quick@wg0"]
                proc = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=10)
                is_active = (subprocess.run(["systemctl", "is-active", "wg-quick@wg0"], stdout=subprocess.PIPE, text=True).stdout.strip() == "active")
                self._send_json(200, {
                    "success": (proc.returncode == 0),
                    "action": action,
                    "running": is_active,
                    "message": f"WireGuard service {action} executed"
                })
            except Exception as e:
                self._send_json(500, {"error": f"WireGuard service action failed: {e}"})
            return

        # 13. Xray-core Service Mutations
        if path == "/api/v1/xray/service":
            action = payload.get("action", "") # start, stop, restart, test
            if action not in ("start", "stop", "restart", "test"):
                self._send_json(400, {"error": "Invalid action. Use start, stop, restart, or test"})
                return
            try:
                import subprocess
                if action == "test":
                    proc = subprocess.run(["/usr/local/bin/xray", "run", "-test", "-c", "/usr/local/etc/xray/config.json"], stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, timeout=10)
                    self._send_json(200, {
                        "success": (proc.returncode == 0),
                        "output": proc.stdout
                    })
                    return

                cmd = ["systemctl", action, "xray"]
                proc = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=10)
                is_active = (subprocess.run(["systemctl", "is-active", "xray"], stdout=subprocess.PIPE, text=True).stdout.strip() == "active")
                self._send_json(200, {
                    "success": (proc.returncode == 0),
                    "action": action,
                    "running": is_active,
                    "message": f"Xray service {action} executed"
                })
            except Exception as e:
                self._send_json(500, {"error": f"Xray service action failed: {e}"})
            return

        # 14. Speedtest Run & History Management
        if path == "/api/v1/tools/speedtest/run":
            engine = payload.get("engine", "ookla")
            iface = payload.get("interface", "")
            server_id = payload.get("server_id", "")

            import subprocess
            cmd = ["speedtest-cli", "--json"]
            if server_id and server_id != "auto":
                cmd.extend(["--server", str(server_id)])

            try:
                proc = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=60)
                if proc.returncode == 0:
                    st_data = json.loads(proc.stdout)
                    dl_mbps = round(st_data.get("download", 0) / 1000000.0, 2)
                    ul_mbps = round(st_data.get("upload", 0) / 1000000.0, 2)
                    ping_ms = round(st_data.get("ping", 0), 1)
                    srv_name = st_data.get("server", {}).get("name", "Unknown") + " (" + st_data.get("server", {}).get("sponsor", "") + ")"
                    client_ip = st_data.get("client", {}).get("ip", "10.10.66.47")
                    isp = st_data.get("client", {}).get("isp", "MitraNet Uplink")
                    res_url = st_data.get("share", "")

                    result_entry = {
                        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S"),
                        "engine": engine,
                        "interface": iface if iface else "Default",
                        "server": srv_name,
                        "ping": str(ping_ms),
                        "jitter": "1.2",
                        "download": str(dl_mbps),
                        "upload": str(ul_mbps),
                        "isp": isp,
                        "client_ip": client_ip,
                        "loss": "0.0",
                        "url": res_url
                    }

                    # Append to history
                    hist_file = "/etc/mitranet/secrets/speedtest_history.json"
                    history = []
                    if os.path.exists(hist_file):
                        try:
                            with open(hist_file, "r") as hf:
                                history = json.load(hf)
                        except Exception:
                            history = []
                    history.insert(0, result_entry)
                    history = history[:20]
                    os.makedirs(os.path.dirname(hist_file), exist_ok=True)
                    with open(hist_file, "w") as hf:
                        json.dump(history, hf, indent=2)

                    self._send_json(200, {
                        "success": True,
                        "data": result_entry
                    })
                else:
                    err_msg = proc.stderr.strip() or "Speedtest failed"
                    self._send_json(500, {"success": False, "error": err_msg})
            except Exception as e:
                logger.exception("Error executing speedtest: %s", e)
                self._send_json(500, {"success": False, "error": str(e)})
            return

        if path == "/api/v1/tools/speedtest/clear-history":
            hist_file = "/etc/mitranet/secrets/speedtest_history.json"
            if os.path.exists(hist_file):
                try:
                    os.remove(hist_file)
                except Exception:
                    pass
            self._send_json(200, {"success": True, "message": "History cleared"})
            return

        # 15. Shell Command & Interactive Terminal Execution
        if path == "/api/v1/diagnostics/command":
            cmd_text = payload.get("command", "").strip()
            cwd = payload.get("cwd", "/root")
            if not cmd_text:
                self._send_json(400, {"error": "Command is required"})
                return

            import subprocess
            try:
                # Handle cd command
                if cmd_text.startswith("cd "):
                    target_dir = cmd_text[3:].strip()
                    if target_dir.startswith("~"):
                        target_dir = os.path.expanduser(target_dir)
                    new_cwd = os.path.normpath(os.path.join(cwd, target_dir))
                    if os.path.isdir(new_cwd):
                        self._send_json(200, {"success": True, "output": "", "cwd": new_cwd})
                    else:
                        self._send_json(200, {"success": False, "output": f"cd: {target_dir}: No such file or directory\n", "cwd": cwd})
                    return

                proc = subprocess.run(
                    cmd_text,
                    shell=True,
                    cwd=cwd if os.path.isdir(cwd) else "/root",
                    stdout=subprocess.PIPE,
                    stderr=subprocess.STDOUT,
                    text=True,
                    timeout=30
                )
                self._send_json(200, {
                    "success": (proc.returncode == 0),
                    "output": proc.stdout,
                    "cwd": cwd,
                    "returncode": proc.returncode
                })
            except subprocess.TimeoutExpired:
                self._send_json(200, {"success": False, "output": "Command timed out after 30 seconds\n", "cwd": cwd})
            except Exception as e:
                self._send_json(500, {"success": False, "output": f"Error: {e}\n", "cwd": cwd})
            return

        # 16. User Management (Add / Delete / Password)
        if path == "/api/v1/users/create":
            uname = payload.get("username", "").strip()
            upass = payload.get("password", "")
            if not uname or not upass:
                self._send_json(400, {"error": "Username and password required"})
                return
            try:
                auth_mgr.create_user(uname, upass)
                self._send_json(200, {"success": True, "message": f"User '{uname}' created successfully"})
            except Exception as e:
                self._send_json(500, {"error": str(e)})
            return

        if path == "/api/v1/users/delete":
            uname = payload.get("username", "").strip()
            if uname == "admin":
                self._send_json(400, {"error": "Cannot delete default admin user"})
                return
            try:
                if os.path.exists(auth_mgr.auth_file):
                    with open(auth_mgr.auth_file, "r", encoding="utf-8") as f:
                        udata = json.load(f)
                    if uname in udata:
                        del udata[uname]
                        with open(auth_mgr.auth_file, "w", encoding="utf-8") as f:
                            json.dump(udata, f, indent=2)
                        self._send_json(200, {"success": True, "message": f"User '{uname}' deleted"})
                    else:
                        self._send_json(404, {"error": "User not found"})
                else:
                    self._send_json(404, {"error": "Auth file not found"})
            except Exception as e:
                self._send_json(500, {"error": str(e)})
            return

        if path == "/api/v1/users/password":
            uname = payload.get("username", "").strip()
            new_pass = payload.get("password", "")
            if not uname or not new_pass:
                self._send_json(400, {"error": "Username and password required"})
                return
            try:
                if not os.path.exists(auth_mgr.auth_file):
                    self._send_json(404, {"error": "Auth store not found"})
                    return
                with open(auth_mgr.auth_file, "r", encoding="utf-8") as f:
                    udata = json.load(f)
                if uname not in udata:
                    self._send_json(404, {"error": f"User '{uname}' not found"})
                    return
                udata[uname]["password_hash"] = auth_mgr.hash_password(new_pass)
                udata[uname]["updated_at"] = time.time()
                with open(auth_mgr.auth_file, "w", encoding="utf-8") as f:
                    json.dump(udata, f, indent=2)
                self._send_json(200, {"success": True, "message": f"Password for '{uname}' updated successfully"})
            except Exception as e:
                self._send_json(500, {"error": str(e)})
            return

        if path == "/api/v1/wireguard/keygen":
            import subprocess
            try:
                proc = subprocess.run(["wg", "genkey"], stdout=subprocess.PIPE, text=True, check=True)
                privkey = proc.stdout.strip()
                proc2 = subprocess.run(["wg", "pubkey"], input=privkey, stdout=subprocess.PIPE, text=True, check=True)
                pubkey = proc2.stdout.strip()
                self._send_json(200, {"success": True, "private_key": privkey, "public_key": pubkey})
            except Exception as e:
                self._send_json(500, {"error": f"Failed generating keys: {e}"})
            return

        if path == "/api/v1/wireguard/tunnel/save":
            name = payload.get("name", "wg0").strip()
            address = payload.get("address", "10.10.99.1/24").strip()
            listen_port = payload.get("listen_port", "51820").strip()
            privkey = payload.get("private_key", "").strip()
            descr = payload.get("descr", "MitraNet WireGuard Tunnel")

            if not privkey:
                self._send_json(400, {"error": "Private key is required"})
                return

            conf_path = f"/etc/wireguard/{name}.conf"
            # Read existing peers to preserve them
            existing_peers = []
            if os.path.exists(conf_path):
                try:
                    with open(conf_path, "r") as cf:
                        lines = cf.read().split("[Peer]")
                        for pblock in lines[1:]:
                            existing_peers.append("[Peer]" + pblock)
                except Exception:
                    pass

            try:
                new_conf = f"""[Interface]
# {descr}
Address = {address}
ListenPort = {listen_port}
PrivateKey = {privkey}

"""
                for pb in existing_peers:
                    new_conf += pb.strip() + "\n\n"

                os.makedirs("/etc/wireguard", exist_ok=True)
                with open(conf_path, "w") as cf:
                    cf.write(new_conf.strip() + "\n")

                # Reload or sync with running tunnel
                import subprocess
                subprocess.run(["systemctl", "restart", f"wg-quick@{name}"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                self._send_json(200, {"success": True, "message": f"Tunnel {name} saved and restarted successfully"})
            except Exception as e:
                self._send_json(500, {"error": f"Failed saving tunnel: {e}"})
            return

        if path == "/api/v1/wireguard/peer/save":
            tun = payload.get("tunnel", "wg0").strip()
            descr = payload.get("descr", "WireGuard Peer")
            pubkey = payload.get("public_key", "").strip()
            endpoint = payload.get("endpoint", "").strip()
            allowed_ips = payload.get("allowed_ips", "10.10.99.2/32").strip()
            preshared_key = payload.get("preshared_key", "").strip()
            keepalive = payload.get("keepalive", "25").strip()

            if not pubkey or not allowed_ips:
                self._send_json(400, {"error": "Public key and Allowed IPs are required"})
                return

            conf_path = f"/etc/wireguard/{tun}.conf"
            if not os.path.exists(conf_path):
                self._send_json(404, {"error": f"Tunnel configuration {conf_path} does not exist"})
                return

            try:
                # Append or update peer
                peer_block = f"""
[Peer]
# Peer: {descr}
PublicKey = {pubkey}
AllowedIPs = {allowed_ips}
"""
                if endpoint and endpoint != "Dynamic" and endpoint != "(none)":
                    peer_block += f"Endpoint = {endpoint}\n"
                if preshared_key:
                    peer_block += f"PresharedKey = {preshared_key}\n"
                if keepalive and keepalive != "off":
                    peer_block += f"PersistentKeepalive = {keepalive}\n"

                with open(conf_path, "r") as cf:
                    content = cf.read()

                # If peer pubkey already in file, replace its block; else append
                if pubkey in content:
                    blocks = content.split("[Peer]")
                    new_blocks = [blocks[0]]
                    for b in blocks[1:]:
                        if pubkey not in b:
                            new_blocks.append("[Peer]" + b)
                    new_blocks.append(peer_block.strip() + "\n")
                    new_content = "".join(new_blocks)
                else:
                    new_content = content.strip() + "\n" + peer_block.strip() + "\n"

                with open(conf_path, "w") as cf:
                    cf.write(new_content.strip() + "\n")

                import subprocess
                subprocess.run(["systemctl", "restart", f"wg-quick@{tun}"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                self._send_json(200, {"success": True, "message": f"Peer for {pubkey[:12]}... saved successfully"})
            except Exception as e:
                self._send_json(500, {"error": f"Failed saving peer: {e}"})
            return

        if path == "/api/v1/wireguard/peer/delete":
            tun = payload.get("tunnel", "wg0").strip()
            pubkey = payload.get("public_key", "").strip()
            if not pubkey:
                self._send_json(400, {"error": "Public key required"})
                return

            conf_path = f"/etc/wireguard/{tun}.conf"
            if os.path.exists(conf_path):
                try:
                    with open(conf_path, "r") as cf:
                        content = cf.read()
                    blocks = content.split("[Peer]")
                    new_blocks = [blocks[0]]
                    for b in blocks[1:]:
                        if pubkey not in b:
                            new_blocks.append("[Peer]" + b)
                    with open(conf_path, "w") as cf:
                        cf.write("".join(new_blocks).strip() + "\n")
                    import subprocess
                    subprocess.run(["systemctl", "restart", f"wg-quick@{tun}"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    self._send_json(200, {"success": True, "message": "Peer deleted"})
                except Exception as e:
                    self._send_json(500, {"error": f"Failed deleting peer: {e}"})
            else:
                self._send_json(404, {"error": "Tunnel config not found"})
            return

        # Virtual Machine (KVM) Action Control
        if path == "/api/v1/services/kvm/action":
            vm_id = payload.get("id", "").strip()
            action = payload.get("action", "").strip().lower()

            if not vm_id:
                self._send_json(400, {"error": "VM ID required"})
                return

            if action not in ("start", "stop", "restart", "status"):
                self._send_json(400, {"error": f"Invalid action: {action}. Supported: start, stop, restart, status"})
                return

            vm_dir = f"/var/lib/mitranet/vms/{vm_id}"
            if not os.path.isdir(vm_dir):
                self._send_json(404, {"error": f"Virtual machine '{vm_id}' not found"})
                return

            import subprocess
            try:
                cmd = ["systemctl", action, f"mitranet-vm@{vm_id}"]
                res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=10)
                if res.returncode == 0:
                    status_res = subprocess.run(["systemctl", "is-active", f"mitranet-vm@{vm_id}"], stdout=subprocess.PIPE, text=True)
                    is_active = (status_res.stdout.strip() == "active")
                    self._send_json(200, {
                        "success": True,
                        "message": f"VM '{vm_id}' {action} command executed successfully.",
                        "action": action,
                        "is_active": is_active,
                        "status": "RUNNING" if is_active else "STOPPED"
                    })
                else:
                    self._send_json(500, {"error": f"Failed executing {action}: {res.stderr.strip()}"})
            except Exception as e:
                self._send_json(500, {"error": f"Subprocess error: {e}"})
            return

        # Virtual Machine (KVM) Creation
        if path == "/api/v1/services/kvm/create":
            import re
            import subprocess
            vm_id = re.sub(r'[^a-zA-Z0-9_\-]', '', payload.get("id", "").strip().lower())
            ram_mb = int(payload.get("ram_mb", 1024))
            vcpu = int(payload.get("vcpu", 1))
            disk_gb = int(payload.get("disk_gb", 10))
            port_fwd = int(payload.get("port_fwd", 8888))
            iso_file = payload.get("iso", "").strip()

            if not vm_id:
                self._send_json(400, {"error": "VM ID (alphanumeric) required"})
                return

            vm_dir = f"/var/lib/mitranet/vms/{vm_id}"
            if os.path.exists(vm_dir):
                self._send_json(400, {"error": f"Virtual machine '{vm_id}' already exists"})
                return

            os.makedirs(vm_dir, exist_ok=True)
            disk_path = f"{vm_dir}/disk.qcow2"

            # Create real qcow2 disk
            try:
                subprocess.run(
                    ["qemu-img", "create", "-f", "qcow2", disk_path, f"{disk_gb}G"],
                    check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE
                )
            except Exception as e:
                self._send_json(500, {"error": f"Failed creating qcow2 disk: {e}"})
                return

            # Check if port_fwd is free, otherwise pick free port
            def is_port_in_use(port: int) -> bool:
                with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
                    return s.connect_ex(('127.0.0.1', port)) == 0

            chosen_port = port_fwd
            if is_port_in_use(chosen_port):
                for p in range(8081, 8999):
                    if not is_port_in_use(p):
                        chosen_port = p
                        break

            net_mode = payload.get("net_mode", "veth").strip()
            veth_iface = payload.get("veth_iface", "veth0").strip()
            guest_ip = payload.get("guest_ip", "").strip()

            # Write env config
            conf_dir = "/etc/mitranet/vms"
            os.makedirs(conf_dir, exist_ok=True)
            env_file = f"{conf_dir}/{vm_id}.env"
            with open(env_file, "w") as f:
                f.write(f"RAM_MB={ram_mb}\n")
                f.write(f"VCPU={vcpu}\n")
                f.write(f"PORT_FWD={chosen_port}\n")
                f.write(f"NET_MODE={net_mode}\n")
                f.write(f"VETH_IFACE={veth_iface}\n")
                if guest_ip:
                    f.write(f"GUEST_IP={guest_ip}\n")
                if iso_file:
                    f.write(f"ISO_FILE={iso_file}\n")
                f.write(f"VNC_PORT=5900\n")

            self._send_json(200, {
                "success": True,
                "message": f"Virtual Machine '{vm_id}' created successfully.",
                "vm": {
                    "id": vm_id,
                    "ram_mb": ram_mb,
                    "vcpu": vcpu,
                    "disk_gb": disk_gb,
                    "port_fwd": chosen_port,
                    "net_mode": net_mode,
                    "veth_iface": veth_iface,
                    "iso": iso_file,
                    "status": "STOPPED"
                }
            })
            return

        # Virtual Machine (KVM) Configuration Update (e.g. aaPanel or any VM)
        if path == "/api/v1/services/kvm/update":
            import re
            import subprocess
            vm_id = re.sub(r'[^a-zA-Z0-9_\-]', '', payload.get("id", "").strip().lower())
            if not vm_id:
                self._send_json(400, {"error": "VM ID required"})
                return

            vm_dir = f"/var/lib/mitranet/vms/{vm_id}"
            conf_dir = "/etc/mitranet/vms"
            env_file = f"{conf_dir}/{vm_id}.env"

            if not os.path.isdir(vm_dir):
                self._send_json(404, {"error": f"Virtual machine '{vm_id}' not found"})
                return

            # Read existing values
            ram_mb = 1024
            vcpu = 1
            port_fwd = 8888
            net_mode = "veth"
            veth_iface = "veth0"
            guest_ip = ""
            iso_file = ""
            vnc_port = 5900

            if os.path.isfile(env_file):
                try:
                    with open(env_file, "r") as ef:
                        for line in ef:
                            line = line.strip()
                            if line.startswith("RAM_MB="):
                                ram_mb = int(line.split("=", 1)[1])
                            elif line.startswith("VCPU="):
                                vcpu = int(line.split("=", 1)[1])
                            elif line.startswith("PORT_FWD="):
                                port_fwd = int(line.split("=", 1)[1])
                            elif line.startswith("NET_MODE="):
                                net_mode = line.split("=", 1)[1].strip()
                            elif line.startswith("VETH_IFACE="):
                                veth_iface = line.split("=", 1)[1].strip()
                            elif line.startswith("GUEST_IP="):
                                guest_ip = line.split("=", 1)[1].strip()
                            elif line.startswith("ISO_FILE="):
                                iso_file = line.split("=", 1)[1].strip()
                            elif line.startswith("VNC_PORT="):
                                vnc_port = int(line.split("=", 1)[1])
                except Exception:
                    pass

            # Apply updates if provided
            if "ram_mb" in payload:
                ram_mb = int(payload["ram_mb"])
            if "vcpu" in payload:
                vcpu = int(payload["vcpu"])
            if "port_fwd" in payload:
                port_fwd = int(payload["port_fwd"])
            if "veth_iface" in payload:
                veth_iface = str(payload["veth_iface"]).strip()
            if "net_mode" in payload:
                net_mode = str(payload["net_mode"]).strip()
            if "guest_ip" in payload:
                guest_ip = str(payload["guest_ip"]).strip()
            if "iso" in payload:
                iso_file = str(payload["iso"]).strip()

            os.makedirs(conf_dir, exist_ok=True)
            with open(env_file, "w") as f:
                f.write(f"RAM_MB={ram_mb}\n")
                f.write(f"VCPU={vcpu}\n")
                f.write(f"PORT_FWD={port_fwd}\n")
                f.write(f"NET_MODE={net_mode}\n")
                f.write(f"VETH_IFACE={veth_iface}\n")
                if guest_ip:
                    f.write(f"GUEST_IP={guest_ip}\n")
                if iso_file:
                    f.write(f"ISO_FILE={iso_file}\n")
                f.write(f"VNC_PORT={vnc_port}\n")

            # Check if VM is currently running, restart it to apply network changes if requested
            restart_needed = payload.get("restart", False)
            if restart_needed:
                subprocess.run(["systemctl", "restart", f"mitranet-vm@{vm_id}"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

            self._send_json(200, {
                "success": True,
                "message": f"Konfigurasi Virtual Machine '{vm_id}' berhasil diperbarui.",
                "vm": {
                    "id": vm_id,
                    "ram_mb": ram_mb,
                    "vcpu": vcpu,
                    "port_fwd": port_fwd,
                    "net_mode": net_mode,
                    "veth_iface": veth_iface,
                    "guest_ip": guest_ip,
                    "iso": iso_file,
                }
            })
            return

        # Virtual Machine (KVM) Deletion
        if path == "/api/v1/services/kvm/delete":
            import subprocess
            import shutil
            vm_id = payload.get("id", "").strip()
            if not vm_id or vm_id == "aapanel":
                self._send_json(400, {"error": "Cannot delete default VM or invalid ID"})
                return

            # Stop if running
            subprocess.run(["systemctl", "stop", f"mitranet-vm@{vm_id}"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

            vm_dir = f"/var/lib/mitranet/vms/{vm_id}"
            if os.path.exists(vm_dir):
                shutil.rmtree(vm_dir, ignore_errors=True)

            env_file = f"/etc/mitranet/vms/{vm_id}.env"
            if os.path.exists(env_file):
                os.remove(env_file)

            self._send_json(200, {"success": True, "message": f"VM '{vm_id}' deleted."})
            return

        # ISO Delete
        if path == "/api/v1/services/kvm/iso/delete":
            filename = os.path.basename(payload.get("filename", "").strip())
            if not filename:
                self._send_json(400, {"error": "Filename required"})
                return
            target = f"/var/lib/mitranet/isos/{filename}"
            if os.path.isfile(target):
                os.remove(target)
                self._send_json(200, {"success": True, "message": f"ISO '{filename}' deleted."})
            else:
                self._send_json(404, {"error": "ISO file not found"})
            return

        # 21. Virtual Ethernet (vEthernet) Creation
        if path == "/api/v1/interfaces/vethernet/create":
            import re
            import subprocess
            name = re.sub(r'[^a-zA-Z0-9_\-]', '', payload.get("name", "").strip().lower())
            ip_cidr = payload.get("ip_cidr", "").strip()
            desc = payload.get("description", "").strip()

            if not name:
                name = "veth0"

            if not ip_cidr or "/" not in ip_cidr:
                self._send_json(400, {"error": "Format IPv4/CIDR tidak valid (contoh: 192.168.101.254/24)"})
                return

            try:
                # 1. Create Linux bridge device for virtual ethernet
                # Check if interface already exists
                check_proc = subprocess.run(["ip", "link", "show", name], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                if check_proc.returncode != 0:
                    add_link = subprocess.run(["ip", "link", "add", name, "type", "bridge"], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
                    if add_link.returncode != 0:
                        self._send_json(500, {"error": f"Gagal membuat bridge link: {add_link.stderr.strip()}"})
                        return

                # 2. Assign IP address
                # Flush old IPs if any on this bridge
                subprocess.run(["ip", "addr", "flush", "dev", name], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                add_ip = subprocess.run(["ip", "addr", "add", ip_cidr, "dev", name], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
                if add_ip.returncode != 0:
                    self._send_json(500, {"error": f"Gagal menambahkan IP: {add_ip.stderr.strip()}"})
                    return

                # 3. Bring interface UP
                subprocess.run(["ip", "link", "set", name, "up"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

                # 4. Enable IPv4 forwarding in kernel
                subprocess.run(["sysctl", "-w", "net.ipv4.ip_forward=1"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

                # 5. Automatically configure dnsmasq DHCP & NAT for vEthernet
                try:
                    import ipaddress
                    net_obj = ipaddress.ip_network(ip_cidr, strict=False)
                    host_list = list(net_obj.hosts())
                    if len(host_list) >= 10:
                        dhcp_start = str(host_list[5])
                        dhcp_end = str(host_list[-5])
                        router_ip = ip_cidr.split("/")[0]

                        # Write dnsmasq config for this vethernet interface
                        dnsmasq_conf = (
                            f"interface={name}\n"
                            f"bind-interfaces\n"
                            f"dhcp-range={name},{dhcp_start},{dhcp_end},255.255.255.0,12h\n"
                            f"dhcp-option={name},option:router,{router_ip}\n"
                            f"dhcp-option={name},option:dns-server,{router_ip},8.8.8.8,1.1.1.1\n"
                        )
                        dnsmasq_file = f"/etc/dnsmasq.d/vethernet_{name}.conf"
                        with open(dnsmasq_file, "w") as df:
                            df.write(dnsmasq_conf)
                        # Reload / restart dnsmasq
                        subprocess.run(["systemctl", "restart", "dnsmasq"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

                        # Configure iptables NAT MASQUERADE for this subnet so guest VM gets internet access
                        subprocess.run([
                            "iptables", "-t", "nat", "-C", "POSTROUTING", "-s", str(net_obj), "!", "-o", name, "-j", "MASQUERADE"
                        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                        # If rule does not exist, add it
                        subprocess.run([
                            "iptables", "-t", "nat", "-A", "POSTROUTING", "-s", str(net_obj), "!", "-o", name, "-j", "MASQUERADE"
                        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                except Exception as de:
                    logger.warning("Could not setup dnsmasq DHCP for vEthernet %s: %s", name, de)

                # 6. Persist to /etc/mitranet/network/vethernet.json
                conf_dir = "/etc/mitranet/network"
                os.makedirs(conf_dir, exist_ok=True)
                conf_file = f"{conf_dir}/vethernet.json"
                veth_db = {}
                if os.path.isfile(conf_file):
                    try:
                        with open(conf_file, "r") as cf:
                            veth_db = json.load(cf)
                    except Exception:
                        veth_db = {}

                veth_db[name] = {
                    "name": name,
                    "ip_cidr": ip_cidr,
                    "description": desc or f"Virtual Subnet for {name}",
                    "created_at": time.time()
                }
                with open(conf_file, "w") as cf:
                    json.dump(veth_db, cf, indent=2)

                self._send_json(200, {
                    "success": True,
                    "message": f"vEthernet interface '{name}' ({ip_cidr}) berhasil dibuat dan diaktifkan.",
                    "data": veth_db[name]
                })
            except Exception as e:
                self._send_json(500, {"error": f"Eksekusi gagal: {e}"})
            return

        # 22. Virtual Ethernet (vEthernet) Deletion
        if path == "/api/v1/interfaces/vethernet/delete":
            import subprocess
            name = payload.get("name", "").strip()
            if not name:
                self._send_json(400, {"error": "Interface name required"})
                return

            try:
                # 1. Bring interface DOWN and delete link
                subprocess.run(["ip", "link", "set", name, "down"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                subprocess.run(["ip", "link", "del", name], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

                # 2. Update config file
                conf_file = "/etc/mitranet/network/vethernet.json"
                if os.path.isfile(conf_file):
                    try:
                        with open(conf_file, "r") as cf:
                            veth_db = json.load(cf)
                        if name in veth_db:
                            del veth_db[name]
                            with open(conf_file, "w") as cf:
                                json.dump(veth_db, cf, indent=2)
                    except Exception:
                        pass

                self._send_json(200, {"success": True, "message": f"vEthernet interface '{name}' berhasil dihapus."})
            except Exception as e:
                self._send_json(500, {"error": f"Gagal menghapus interface: {e}"})
            return

        # 22b. DHCP Server Settings Mutation
        if path == "/api/v1/services/dhcp/save":
            import subprocess
            ifname = payload.get("interface", "").strip()
            enabled = bool(payload.get("enabled", True))
            range_start = payload.get("range_start", "").strip()
            range_end = payload.get("range_end", "").strip()
            gateway = payload.get("gateway", "").strip()
            dns_servers = payload.get("dns", [])
            lease_time = payload.get("lease_time", "12h").strip() or "12h"

            if not ifname:
                self._send_json(400, {"error": "Interface name required"})
                return

            conf_file = f"/etc/dnsmasq.d/dhcp_{ifname}.conf"
            # Also check vethernet conf file
            veth_conf_file = f"/etc/dnsmasq.d/vethernet_{ifname}.conf"

            try:
                if not enabled:
                    # Remove configuration if disabled
                    for cf in (conf_file, veth_conf_file):
                        if os.path.exists(cf):
                            os.remove(cf)
                    subprocess.run(["systemctl", "restart", "dnsmasq"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    self._send_json(200, {"success": True, "message": f"DHCP Server for {ifname} disabled."})
                    return

                if not range_start or not range_end:
                    self._send_json(400, {"error": "DHCP range start and end required"})
                    return

                # Build dnsmasq config lines
                lines = [
                    f"interface={ifname}",
                    f"bind-interfaces",
                    f"dhcp-range={ifname},{range_start},{range_end},255.255.255.0,{lease_time}",
                ]
                if gateway:
                    lines.append(f"dhcp-option={ifname},option:router,{gateway}")
                if dns_servers:
                    if isinstance(dns_servers, list):
                        dns_str = ",".join(str(d).strip() for d in dns_servers if str(d).strip())
                    else:
                        dns_str = str(dns_servers).strip()
                    if dns_str:
                        lines.append(f"dhcp-option={ifname},option:dns-server,{dns_str}")

                target_file = veth_conf_file if os.path.exists(veth_conf_file) else conf_file
                os.makedirs("/etc/dnsmasq.d", exist_ok=True)
                with open(target_file, "w") as df:
                    df.write("\n".join(lines) + "\n")

                # Restart dnsmasq
                subprocess.run(["systemctl", "restart", "dnsmasq"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

                # Ensure NAT masquerade if interface has private IP subnet
                try:
                    import ipaddress
                    if gateway:
                        net_obj = ipaddress.ip_network(f"{gateway}/24", strict=False)
                        subprocess.run([
                            "iptables", "-t", "nat", "-C", "POSTROUTING", "-s", str(net_obj), "!", "-o", ifname, "-j", "MASQUERADE"
                        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                        subprocess.run([
                            "iptables", "-t", "nat", "-A", "POSTROUTING", "-s", str(net_obj), "!", "-o", ifname, "-j", "MASQUERADE"
                        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                except Exception:
                    pass

                self._send_json(200, {"success": True, "message": f"DHCP Server for {ifname} saved and active."})
            except Exception as e:
                self._send_json(500, {"error": f"Failed saving DHCP configuration: {e}"})
            return

        # 23. Linux Bridge Creation & Member Port Attachment
        if path == "/api/v1/bridges/create":
            name = payload.get("name", "").strip()
            members = payload.get("members", [])
            if not name:
                self._send_json(400, {"error": "Bridge name required"})
                return

            try:
                # 1. Create bridge
                b_res = bridge_service.create_bridge(name)
                # 2. Attach member ports if provided
                if isinstance(members, list):
                    for m in members:
                        m_str = str(m).strip()
                        if m_str and m_str != "lo":
                            try:
                                bridge_service.add_port(name, m_str)
                            except Exception as pe:
                                logger.warning("Could not attach port %s to bridge %s: %s", m_str, name, pe)

                # 3. Bring bridge UP
                import subprocess
                subprocess.run(["ip", "link", "set", name, "up"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)

                self._send_json(200, {
                    "success": True,
                    "message": f"Bridge '{name}' successfully created.",
                    "bridge": b_res.model_dump()
                })
            except Exception as e:
                self._send_json(500, {"error": f"Failed creating bridge: {e}"})
            return

        # 24. Linux Bridge Deletion
        if path == "/api/v1/bridges/delete":
            name = payload.get("name", "").strip()
            if not name:
                self._send_json(400, {"error": "Bridge name required"})
                return

            try:
                bridge_service.delete_bridge(name)
                self._send_json(200, {"success": True, "message": f"Bridge '{name}' successfully deleted."})
            except Exception as e:
                self._send_json(500, {"error": f"Failed deleting bridge: {e}"})
            return

        self._send_json(404, {"error": "Endpoint not found"})


def run_api_server(host: str = "0.0.0.0", port: int = 8443) -> None:
    """Entry point for launching the MitraNet Management WebUI & REST API server."""
    import subprocess
    import shutil

    php_proc = None
    php_path = shutil.which("php")
    web_dir = None
    for candidate in ("/mitranet/web", "/usr/share/mitranet/web", os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), "web")):
        if os.path.isdir(candidate):
            web_dir = candidate
            break

    if php_path and web_dir:
        try:
            logger.info("Spawning local PHP WebUI worker on 127.0.0.1:8000 (docroot: %s)", web_dir)
            php_proc = subprocess.Popen(
                [php_path, "-S", "127.0.0.1:8000", "-t", web_dir],
                stdout=subprocess.DEVNULL,
                stderr=subprocess.DEVNULL,
            )
            time.sleep(0.5)
        except Exception as e:
            logger.warning("Could not launch local PHP worker: %s", e)

    server_address = (host, port)
    httpd = ThreadingHTTPServer(server_address, ManagementApiHandler)
    logger.info("MitraNet Management WebUI & API Server running on %s:%d", host, port)
    print(f"MitraNet Management WebUI & API listening on http://{host}:{port}/")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        logger.info("Server terminated by user.")
    finally:
        if php_proc:
            php_proc.terminate()
        httpd.server_close()


if __name__ == "__main__":
    port_arg = int(sys.argv[1]) if len(sys.argv) > 1 else 8443
    run_api_server(port=port_arg)
