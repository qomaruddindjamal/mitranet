"""
MitraNet Management REST API Daemon.
Built on Python standard http server (no external dependencies needed in Phase 0).
Provides JSON endpoints for monitoring, candidate configuration editing, and commit-confirm.
"""

import json
from http.server import HTTPServer, BaseHTTPRequestHandler
from mitranet.src.config.engine import ConfigEngine
from mitranet.src.config.models import MitraNetMasterConfig
from mitranet.src.network.firewall import NftablesCompiler

engine = ConfigEngine()


class MitraNetApiHandler(BaseHTTPRequestHandler):
    def _send_json(self, status: int, data: dict):
        self.send_response(status)
        self.send_header("Content-Type", "application/json")
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        self.wfile.write(json.dumps(data, indent=2).encode("utf-8"))

    def do_GET(self):
        if self.path == "/api/v1/system/status":
            self._send_json(200, {
                "os": "MitraNet NOS",
                "version": "1.0.0-phase0",
                "kernel": "Linux 6.x (Debian 13 Trixie)",
                "status": "operational"
            })
        elif self.path == "/api/v1/config/running":
            cfg = engine.get_running_config()
            self._send_json(200, cfg.model_dump())
        elif self.path == "/api/v1/config/candidate":
            cfg = engine.get_candidate_config()
            self._send_json(200, cfg.model_dump())
        elif self.path == "/api/v1/firewall/nftables":
            cfg = engine.get_running_config()
            compiled = NftablesCompiler.compile_ruleset(cfg)
            self._send_json(200, {"ruleset": compiled})
        else:
            self._send_json(404, {"error": "Endpoint not found"})

    def do_POST(self):
        if self.path == "/api/v1/config/commit":
            success, msg = engine.commit("REST API Commit")
            self._send_json(200 if success else 400, {"success": success, "message": msg})
        elif self.path == "/api/v1/config/rollback":
            success, msg = engine.rollback()
            self._send_json(200 if success else 400, {"success": success, "message": msg})
        else:
            self._send_json(404, {"error": "Endpoint not found"})


def run_api_server(port: int = 8443):
    server = HTTPServer(("0.0.0.0", port), MitraNetApiHandler)
    print(f"MitraNet REST API listening on port {port}...")
    server.serve_forever()


if __name__ == "__main__":
    run_api_server()
