#!/usr/bin/env python3
"""
MitraNet OS - Lightweight Web API & Dashboard Daemon for V2Ray/Xray Management
Serves Web Dashboard on Port 80 / 8080 and REST API at /api/mitranet/*
"""

from http.server import HTTPServer, BaseHTTPRequestHandler
import json
import os
import subprocess
import shutil

CONFIG_DIR = os.environ.get("MITRANET_CONFIG_DIR", "/usr/local/etc/xray")
NODES_DIR = os.path.join(CONFIG_DIR, "nodes")
ACTIVE_CONFIG = os.path.join(CONFIG_DIR, "config.json")
PORT = int(os.environ.get("MITRANET_API_PORT", 8080))
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DASHBOARD_HTML = os.path.join(SCRIPT_DIR, "dashboard.html")

def run_cmd(cmd):
    try:
        res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
        return res.returncode, res.stdout.strip(), res.stderr.strip()
    except Exception as e:
        return 1, "", str(e)

class MitraNetAPIHandler(BaseHTTPRequestHandler):
    def _send_json(self, status_code, data):
        self.send_response(status_code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
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
                self._send_html(200, "<h1>MitraNet OS Dashboard</h1><p>Dashboard HTML not found.</p>")
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

        elif path == "/api/mitranet/nodes":
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

        if path in ("/api/mitranet/start", "/start"):
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
