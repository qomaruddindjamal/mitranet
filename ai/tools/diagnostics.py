#!/usr/bin/env python3
"""
diagnostics.py - Read-Only Diagnostic Inspection Tools for MitraNet AI Assistant
Licensed under Apache License 2.0.
"""

import os
import subprocess
import json
import urllib.request
import urllib.error
from typing import Dict, Any, List

API_BASE = "http://127.0.0.1:8443/api/v1"

def check_system_services() -> Dict[str, Any]:
    """Check running state of core MitraNet systemd units."""
    services = ["mitranet-webui", "networking", "dnsmasq", "wireguard@wg0"]
    result = {}
    for s in services:
        try:
            r = subprocess.run(["systemctl", "is-active", s], capture_output=True, text=True, timeout=3)
            result[s] = r.stdout.strip()
        except Exception as e:
            result[s] = f"error: {e}"
    return result

def get_booster_telemetry() -> Dict[str, Any]:
    """Fetch live Cloud Speed Booster telemetry from API."""
    url = f"{API_BASE}/vpn/booster/status"
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "MitraNet-AI/1.0.2"})
        with urllib.request.urlopen(req, timeout=3) as resp:
            data = json.loads(resp.read().decode())
            return {"success": True, "data": data.get("data", {})}
    except Exception as e:
        return {"success": False, "error": str(e)}

def get_wireguard_handshakes() -> Dict[str, Any]:
    """Check WireGuard handshakes and interface counters."""
    try:
        r = subprocess.run(["wg", "show", "all", "latest-handshakes"], capture_output=True, text=True, timeout=3)
        if r.returncode != 0:
            return {"success": False, "error": r.stderr.strip() or "wg command failed"}
        lines = r.stdout.strip().splitlines()
        handshakes = []
        for l in lines:
            parts = l.strip().split()
            if len(parts) >= 3:
                handshakes.append({
                    "interface": parts[0],
                    "peer_pubkey": parts[1][:10] + "...",
                    "latest_handshake_epoch": int(parts[2])
                })
        return {"success": True, "handshakes": handshakes}
    except Exception as e:
        return {"success": False, "error": str(e)}

def get_default_routes() -> Dict[str, Any]:
    """Inspect active default routes and routing tables safely."""
    try:
        r = subprocess.run(["ip", "route", "show", "default"], capture_output=True, text=True, timeout=3)
        return {
            "success": True,
            "default_route": r.stdout.strip().splitlines()
        }
    except Exception as e:
        return {"success": False, "error": str(e)}
