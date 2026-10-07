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

            self._send_json(200, {
                "os": "MitraNet",
                "codename": CODENAME,
                "pretty_name": PRETTY_NAME,
                "version": MITRANET_VERSION,
                "kernel": os.uname().release if hasattr(os, "uname") else "Linux",
                "hostname": socket.gethostname(),
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
                    result.append(d)
                self._send_json(200, result)
            except Exception as e:
                self._send_json(500, {"error": f"Interface discovery failed: {e}"})
            return

        if path.startswith("/api/v1/interfaces/"):
            ifname = path.replace("/api/v1/interfaces/", "").strip()
            try:
                ifaces = iface_discovery.discover_interfaces()
                found = next((i for i in ifaces if i.name == ifname), None)
                if not found:
                    self._send_json(404, {"error": f"Interface '{ifname}' not found"})
                    return
                d = found.model_dump()
                d["traffic"] = SystemMetricsCollector.get_interface_traffic(ifname)
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

        # 4. VLANs
        if path == "/api/v1/vlans":
            try:
                vlans = vlan_service.discover_vlans()
                self._send_json(200, [v.model_dump() for v in vlans])
            except Exception as e:
                self._send_json(500, {"error": f"VLAN query failed: {e}"})
            return

        # 5. Bridges
        if path == "/api/v1/bridges":
            try:
                bridges = bridge_service.discover_bridges()
                self._send_json(200, [b.model_dump() for b in bridges])
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
                    elif len(cols) == 9 and cur_tun:
                        # Peer line
                        dev, pub, psk, endpoint, allowed_ips, latest_handshake, rx, tx, persistent_keepalive = cols
                        cur_tun["peers"].append({
                            "public_key": pub,
                            "endpoint": endpoint,
                            "allowed_ips": allowed_ips,
                            "latest_handshake": latest_handshake,
                            "transfer_rx": rx,
                            "transfer_tx": tx,
                            "persistent_keepalive": persistent_keepalive
                        })
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

        # All subsequent mutation endpoints require valid session AND CSRF
        is_auth, session = self._authenticate_request()
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

        # 10. System General Settings (Hostname)
        if path == "/api/v1/system/update":
            new_hostname = payload.get("hostname", "").strip()
            if not new_hostname:
                self._send_json(400, {"error": "hostname is required"})
                return
            try:
                # Update hostname via /etc/hostname and runtime if permissions permit
                if os.path.exists("/etc/hostname"):
                    with open("/etc/hostname", "w") as hf:
                        hf.write(f"{new_hostname}\n")
                if hasattr(os, "system"):
                    os.system(f"hostname {new_hostname}")
                self._send_json(200, {"success": True, "message": f"Hostname updated to {new_hostname}"})
            except Exception as e:
                self._send_json(500, {"error": f"Failed setting hostname: {e}"})
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
