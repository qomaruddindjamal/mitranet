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
from http.server import HTTPServer, BaseHTTPRequestHandler
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

    # =========================================================================
    # GET DISPATCHER
    # =========================================================================

    def do_GET(self) -> None:
        parsed = urlparse(self.path)
        path = parsed.path

        # Static WebUI Routes
        if path in ("/", "/index.html", "/ui", "/ui/"):
            static_dir = os.path.join(os.path.dirname(__file__), "static")
            self._send_file(os.path.join(static_dir, "index.html"), "text/html; charset=utf-8")
            return

        if path.startswith("/static/"):
            filename = os.path.basename(path)
            static_dir = os.path.join(os.path.dirname(__file__), "static")
            target = os.path.join(static_dir, filename)
            ct = "text/plain"
            if filename.endswith(".html"): ct = "text/html; charset=utf-8"
            elif filename.endswith(".css"): ct = "text/css; charset=utf-8"
            elif filename.endswith(".js"): ct = "application/javascript; charset=utf-8"
            elif filename.endswith(".svg"): ct = "image/svg+xml"
            self._send_file(target, ct)
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

        self._send_json(404, {"error": "Endpoint not found"})

    # =========================================================================
    # POST DISPATCHER (Mutations)
    # =========================================================================

    def do_POST(self) -> None:
        parsed = urlparse(self.path)
        path = parsed.path

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

        self._send_json(404, {"error": "Endpoint not found"})


def run_api_server(host: str = "0.0.0.0", port: int = 8443) -> None:
    """Entry point for launching the MitraNet Management WebUI & REST API server."""
    server_address = (host, port)
    httpd = HTTPServer(server_address, ManagementApiHandler)
    logger.info("MitraNet Management WebUI & API Server running on %s:%d", host, port)
    print(f"MitraNet Management WebUI & API listening on http://{host}:{port}/")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        logger.info("Server terminated by user.")
    finally:
        httpd.server_close()


if __name__ == "__main__":
    port_arg = int(sys.argv[1]) if len(sys.argv) > 1 else 8443
    run_api_server(port=port_arg)
