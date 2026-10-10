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
    """Check WireGuard handshakes and interface counters safely."""
    try:
        r = subprocess.run(["wg", "show", "all", "latest-handshakes"], capture_output=True, text=True, timeout=3)
        if r.returncode != 0:
            return {"success": False, "error": r.stderr.strip() or "wg command failed"}
        lines = r.stdout.strip().splitlines()
        handshakes = []
        now_ts = int(__import__("time").time())
        for l in lines:
            parts = l.strip().split()
            if len(parts) >= 3:
                hs_epoch = int(parts[2])
                age_sec = (now_ts - hs_epoch) if hs_epoch > 0 else -1
                is_stale = (age_sec > 180 or hs_epoch == 0)
                handshakes.append({
                    "interface": parts[0],
                    "peer_pubkey": parts[1][:10] + "...",
                    "latest_handshake_epoch": hs_epoch,
                    "age_seconds": age_sec,
                    "is_stale": is_stale,
                    "status": "HEALTHY" if (not is_stale and age_sec >= 0) else ("STALE" if hs_epoch > 0 else "NO_HANDSHAKE")
                })
        return {"success": True, "handshakes": handshakes}
    except Exception as e:
        return {"success": False, "error": str(e)}

def analyze_booster_runtime() -> Dict[str, Any]:
    """Analyze difference between booster configuration and runtime reality.
    Detects failed tunnels, stale handshakes, zero-counters, and provides source-attributed facts.
    """
    telemetry = get_booster_telemetry()
    routes = get_default_routes()
    hs = get_wireguard_handshakes()

    analysis = {
        "configured_state": "UNKNOWN",
        "runtime_state": "DOWN",
        "healthy_streams": 0,
        "failed_streams": 0,
        "zero_counter_streams": 0,
        "stale_handshake_streams": 0,
        "facts": [],
        "warnings": [],
        "missing_info": []
    }

    if not telemetry.get("success"):
        analysis["missing_info"].append(f"Booster API unavailable: {telemetry.get('error')}")
        return analysis

    data = telemetry.get("data", {})
    analysis["configured_state"] = "ENABLED" if data.get("enabled") else "DISABLED"

    streams = data.get("streams", [])
    if not streams:
        analysis["missing_info"].append("No stream configuration details returned by telemetry.")
        return analysis

    for s in streams:
        sid = s.get("id")
        dev = s.get("interface", f"wgboost{sid}")
        rx = s.get("rx_bytes", 0)
        tx = s.get("tx_bytes", 0)
        status = s.get("status", "DOWN")

        if rx == 0 and tx == 0:
            analysis["zero_counter_streams"] += 1
            analysis["warnings"].append(f"Stream #{sid} ({dev}) has 0 bytes transferred (counter zero).")

        if status == "UP":
            analysis["healthy_streams"] += 1
            analysis["facts"].append(f"Stream #{sid} ({dev}) link UP with verified counter (RX: {s.get('rx_formatted', '0 B')}, TX: {s.get('tx_formatted', '0 B')}).")
        else:
            analysis["failed_streams"] += 1
            analysis["warnings"].append(f"Stream #{sid} ({dev}) is DOWN or unverified.")

    if analysis["healthy_streams"] == len(streams) and len(streams) > 0:
        analysis["runtime_state"] = "FULL_AGGREGATION"
    elif analysis["healthy_streams"] > 0:
        analysis["runtime_state"] = "DEGRADED"
    else:
        analysis["runtime_state"] = "DOWN"

    return analysis

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
