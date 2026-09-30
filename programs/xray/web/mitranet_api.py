#!/usr/bin/env python3
"""
MitraNet OS - Lightweight Web API & Dashboard Daemon for pfSense / V2Ray / Xray Management
Serves Web Dashboard on Port 8080 (or 80) and REST API at /api/*
"""

from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import os
import subprocess
import shutil
import time

CONFIG_DIR = os.environ.get("MITRANET_CONFIG_DIR", "/usr/local/etc/xray")
NODES_DIR = os.path.join(CONFIG_DIR, "nodes")
ACTIVE_CONFIG = os.path.join(CONFIG_DIR, "config.json")
PORT = int(os.environ.get("MITRANET_API_PORT", 8080))
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DASHBOARD_HTML = os.path.join(SCRIPT_DIR, "dashboard.html")

def run_cmd(cmd):
    try:
        res = subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=10)
        return res.returncode, res.stdout.strip(), res.stderr.strip()
    except Exception as e:
        return 1, "", str(e)

def get_system_info():
    _, hostname, _ = run_cmd("hostname")
    _, uptime_str, _ = run_cmd("uptime")
    _, uname_str, _ = run_cmd("uname -srm")
    
    # Interfaces
    _, ifc_str, _ = run_cmd("ifconfig -u")
    interfaces = []
    current_if = None
    for line in ifc_str.splitlines():
        if line and not line.startswith("\t") and ":" in line:
            name = line.split(":")[0].strip()
            current_if = {"name": name, "ipv4": [], "ipv6": [], "status": "UP"}
            interfaces.append(current_if)
        elif current_if and line.strip().startswith("inet "):
            parts = line.strip().split()
            if len(parts) >= 2:
                current_if["ipv4"].append(parts[1])
        elif current_if and line.strip().startswith("inet6 "):
            parts = line.strip().split()
            if len(parts) >= 2:
                current_if["ipv6"].append(parts[1].split("%")[0])
        elif current_if and "status: " in line:
            current_if["status"] = line.split("status: ")[1].strip().upper()

    # Memory & Swap
    _, swap_str, _ = run_cmd("swapinfo -h 2>/dev/null || true")
    _, df_str, _ = run_cmd("df -h /")
    disk_usage = "/"
    for line in df_str.splitlines()[1:]:
        parts = line.split()
        if len(parts) >= 6:
            disk_usage = f"{parts[2]} / {parts[1]} ({parts[4]})"

    # Services
    _, nginx_pid, _ = run_cmd("pgrep -x nginx")
    _, sshd_pid, _ = run_cmd("pgrep -x sshd")
    _, xray_pid, _ = run_cmd("pgrep -f 'xray run' || pgrep -x xray")

    return {
        "hostname": hostname or "mitranet",
        "uptime": uptime_str or "Active",
        "kernel": uname_str or "FreeBSD 15.0-CURRENT",
        "disk": disk_usage,
        "interfaces": interfaces,
        "services": {
            "nginx": bool(nginx_pid),
            "sshd": bool(sshd_pid),
            "xray": bool(xray_pid)
        }
    }

class MitraNetAPIHandler(BaseHTTPRequestHandler):
    def _send_json(self, status_code, data):
        self.send_response(status_code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Authorization")
        self.end_headers()
        self.wfile.write(json.dumps(data, indent=2).encode("utf-8"))

    def _send_html(self, status_code, html_content):
        self.send_response(status_code)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        self.wfile.write(html_content.encode("utf-8"))

    def do_OPTIONS(self):
        self._send_json(200, {"status": "ok"})

    def do_HEAD(self):
        self.do_GET()

    def do_GET(self):
        path = self.path.split("?")[0].rstrip("/")
        
        # Serve Web Dashboard for root or /index.html
        if path in ("", "/", "/index.html", "/dashboard"):
            if os.path.exists(DASHBOARD_HTML):
                with open(DASHBOARD_HTML, "r", encoding="utf-8") as f:
                    self._send_html(200, f.read())
                    return
            else:
                self._send_html(200, "<h1>pfSense / MitraNet OS Dashboard</h1><p>Dashboard HTML initializing...</p>")
                return

        # System Info Endpoint
        if path in ("/api/system", "/api/system/info"):
            self._send_json(200, get_system_info())
            return

        # REST API Endpoints
        if path in ("/api/mitranet", "/api/mitranet/status"):
            code, out, _ = run_cmd("pgrep -f 'xray run' || pgrep -x xray")
            is_running = bool(out)
            active_node = "None"
            if os.path.exists(ACTIVE_CONFIG):
                try:
                    with open(ACTIVE_CONFIG) as f:
                        cfg = json.load(f)
                        outbounds = cfg.get("outbounds", [])
                        if outbounds:
                            active_node = outbounds[0].get("tag", "proxy")
                except Exception:
                    pass

            self._send_json(200, {
                "status": "online",
                "xray_running": is_running,
                "pids": out.split(),
                "active_node": active_node,
                "ports": {
                    "socks5": 10808,
                    "http": 10809,
                    "transparent": 12345
                }
            })
            return

        elif path in ("/api/mitranet/nodes", "/api/nodes"):
            os.makedirs(NODES_DIR, exist_ok=True)
            files = [f for f in os.listdir(NODES_DIR) if f.endswith(".json")]
            nodes = []
            for f in sorted(files):
                nodes.append({
                    "id": f[:-5],
                    "filename": f
                })
            self._send_json(200, {"nodes": nodes, "total": len(nodes)})
            return

        self._send_json(404, {"error": "Endpoint not found"})

    def do_POST(self):
        path = self.path.rstrip("/")
        content_len = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(content_len).decode("utf-8") if content_len > 0 else "{}"
        
        try:
            req_data = json.loads(body)
        except Exception:
            req_data = {}

        # Authentication login
        if path in ("/api/login", "/api/auth/login"):
            username = req_data.get("username", "").strip()
            password = req_data.get("password", "").strip()
            if (username in ("admin", "root") and password == "pfsense") or (password == "pfsense"):
                token = f"token-{int(time.time())}"
                self._send_json(200, {
                    "success": True,
                    "token": token,
                    "username": username or "admin",
                    "role": "admin"
                })
                return
            else:
                self._send_json(401, {
                    "success": False,
                    "error": "Username atau kata sandi tidak valid. Bawaan: admin / pfsense"
                })
                return

        # Diagnostics / Terminal Execution
        elif path in ("/api/system/exec", "/api/exec"):
            cmd = req_data.get("command", "").strip()
            if not cmd:
                self._send_json(400, {"error": "Missing 'command'"})
                return
            # Security filter for allowed inspection commands
            allowed_prefixes = ("ifconfig", "netstat", "zpool", "zfs", "df", "uptime", "sockstat", "dmesg", "top", "pw", "sysrc", "service", "mitranet-cli", "cat")
            if not any(cmd.startswith(p) for p in allowed_prefixes):
                self._send_json(403, {"error": "Command not permitted in Web console"})
                return
            code, out, err = run_cmd(cmd)
            self._send_json(200, {
                "command": cmd,
                "code": code,
                "output": out if out else err
            })
            return

        elif path in ("/api/mitranet/start", "/start"):
            run_cmd("mitranet-cli start")
            self._send_json(200, {"message": "Xray service started"})
            return

        elif path in ("/api/mitranet/stop", "/stop"):
            run_cmd("mitranet-cli stop")
            self._send_json(200, {"message": "Xray service stopped"})
            return

        elif path in ("/api/mitranet/switch", "/switch"):
            node = req_data.get("node")
            if not node:
                self._send_json(400, {"error": "Missing 'node' in request body"})
                return
            code, out, err = run_cmd(f"mitranet-cli use-node '{node}'")
            self._send_json(200 if code == 0 else 500, {
                "success": code == 0,
                "output": out or err
            })
            return

        elif path in ("/api/mitranet/import", "/import"):
            link = req_data.get("link")
            if not link:
                self._send_json(400, {"error": "Missing 'link' parameter"})
                return
            code, out, err = run_cmd(f"mitranet-cli import-link '{link}'")
            self._send_json(200 if code == 0 else 400, {
                "success": code == 0,
                "message": out or err
            })
            return

        self._send_json(404, {"error": "Endpoint not found"})

def run_server():
    server_address = ("0.0.0.0", PORT)
    httpd = HTTPServer(server_address, MitraNetAPIHandler)
    print(f"[*] MitraNet Web Dashboard & API listening on 0.0.0.0:{PORT}...")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        httpd.server_close()

if __name__ == "__main__":
    run_server()
