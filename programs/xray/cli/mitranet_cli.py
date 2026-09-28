#!/usr/bin/env python3
"""
MitraNet OS - V2Ray/Xray (VLESS/VMESS) Management CLI Module
Part of MitraNet OSNetwork Enhancements
"""

import sys
import os
import json
import base64
import urllib.parse
import subprocess
import shutil
import time
import platform

CONFIG_DIR = os.environ.get("MITRANET_CONFIG_DIR", "/usr/local/etc/xray")
NODES_DIR = os.path.join(CONFIG_DIR, "nodes")
ACTIVE_CONFIG = os.path.join(CONFIG_DIR, "config.json")
LOG_FILE = "/var/log/xray/xray.log"
XRAY_BIN = shutil.which("xray") or "/usr/local/bin/xray"
PF_CONF_XRAY = "/usr/local/etc/xray/pf_xray.conf"

def detect_cpu_architecture() -> dict:
    """Detect host CPU architecture, endianness, and target profile"""
    machine = platform.machine().lower()
    system = platform.system().lower()
    
    if machine in ("x86_64", "amd64"):
        arch_code = "x86_64"
        category = "Desktop / Server / VM (x86_64Bit)"
    elif machine in ("aarch64", "arm64"):
        arch_code = "arm64"
        category = "ARM64 SBC / Apple Silicon / Cloud VM"
    elif machine.startswith("arm"):
        arch_code = "arm"
        category = "32-bit ARM SBC / Router"
    elif "mips" in machine:
        if sys.byteorder == "little":
            arch_code = "mipsle"
            category = "MIPS Little Endian (MT7621 / mmips)"
        else:
            arch_code = "mipsbe"
            category = "MIPS Big Endian (Atheros / MikroTik RB / smips)"
    elif "ppc" in machine or "powerpc" in machine:
        arch_code = "ppc"
        category = "PowerPC Network Gear (RB1100 / Freescale)"
    elif "riscv" in machine:
        arch_code = "riscv64"
        category = "RISC-V 64-bit SBC"
    else:
        arch_code = machine
        category = "Generic Architecture"

    return {
        "machine": machine,
        "system": system,
        "arch_code": arch_code,
        "category": category,
        "endian": sys.byteorder,
        "is_low_memory": arch_code in ("mipsbe", "mipsle", "smips", "arm")
    }

def run_cmd(cmd):
    try:
        res = subprocess.run(cmd, shell=True, capture_output=True, text=True)
        return res.returncode, res.stdout.strip(), res.stderr.strip()
    except Exception as e:
        return 1, "", str(e)

def ensure_dirs():
    os.makedirs(CONFIG_DIR, exist_ok=True)
    os.makedirs(NODES_DIR, exist_ok=True)
    os.makedirs("/var/log/xray", exist_ok=True)

def parse_vless_link(link: str) -> dict:
    """Parse vless://uuid@host:port?params#name into an Xray outbound structure"""
    u = urllib.parse.urlparse(link)
    if u.scheme != "vless":
        raise ValueError("Invalid scheme, expected vless://")
    
    uuid = u.username
    host = u.hostname
    port = int(u.port or 443)
    query = urllib.parse.parse_qs(u.query)
    name = urllib.parse.unquote(u.fragment) if u.fragment else f"vless-{host}:{port}"
    
    security = query.get("security", ["none"])[0]
    network = query.get("type", ["tcp"])[0]
    flow = query.get("flow", [""])[0]
    sni = query.get("sni", [host])[0]
    pbk = query.get("pbk", [""])[0]
    sid = query.get("sid", [""])[0]
    path = query.get("path", ["/"])[0]
    
    outbound = {
        "tag": "proxy",
        "protocol": "vless",
        "settings": {
            "vnext": [{
                "address": host,
                "port": port,
                "users": [{
                    "id": uuid,
                    "flow": flow,
                    "encryption": "none",
                    "level": 0
                }]
            }]
        },
        "streamSettings": {
            "network": network,
            "security": security
        }
    }
    
    if security == "reality":
        outbound["streamSettings"]["realitySettings"] = {
            "fingerprint": query.get("fp", ["chrome"])[0],
            "serverName": sni,
            "publicKey": pbk,
            "shortId": sid,
            "spiderX": query.get("spx", ["/"])[0]
        }
    elif security == "tls":
        outbound["streamSettings"]["tlsSettings"] = {
            "serverName": sni,
            "allowInsecure": False
        }
        
    if network == "ws":
        outbound["streamSettings"]["wsSettings"] = {
            "path": path,
            "headers": {"Host": query.get("host", [sni])[0]}
        }
    elif network == "grpc":
        outbound["streamSettings"]["grpcSettings"] = {
            "serviceName": query.get("serviceName", [""])[0]
        }
        
    return {"name": name, "outbound": outbound}

def parse_vmess_link(link: str) -> dict:
    """Parse vmess://base64json into an Xray outbound structure"""
    if not link.startswith("vmess://"):
        raise ValueError("Invalid scheme, expected vmess://")
    b64_str = link[8:]
    missing_padding = len(b64_str) % 4
    if missing_padding:
        b64_str += '=' * (4 - missing_padding)
    raw = base64.b64decode(b64_str).decode("utf-8")
    data = json.loads(raw)
    
    host = data.get("add")
    port = int(data.get("port", 443))
    uuid = data.get("id")
    aid = int(data.get("aid", 0))
    network = data.get("net", "tcp")
    security = data.get("tls", "none")
    path = data.get("path", "/")
    sni = data.get("sni", host)
    name = data.get("ps", f"vmess-{host}:{port}")
    
    outbound = {
        "tag": "proxy",
        "protocol": "vmess",
        "settings": {
            "vnext": [{
                "address": host,
                "port": port,
                "users": [{
                    "id": uuid,
                    "alterId": aid,
                    "security": "auto",
                    "level": 0
                }]
            }]
        },
        "streamSettings": {
            "network": network,
            "security": security
        }
    }
    
    if security == "tls":
        outbound["streamSettings"]["tlsSettings"] = {
            "serverName": sni,
            "allowInsecure": False
        }
    if network == "ws":
        outbound["streamSettings"]["wsSettings"] = {
            "path": path,
            "headers": {"Host": data.get("host", sni)}
        }
        
    return {"name": name, "outbound": outbound}

def build_full_config(outbound: dict) -> dict:
    """Combine outbound proxy with standard MitraNet routing and inbounds"""
    return {
        "log": {
            "loglevel": "error" if detect_cpu_architecture()["is_low_memory"] else "warning",
            "access": "/var/log/xray/access.log",
            "error": "/var/log/xray/error.log"
        },
        "dns": {
            "servers": ["1.1.1.1", "8.8.8.8"]
        },
        "inbounds": [
            {
                "tag": "socks-in",
                "port": 10808,
                "listen": "0.0.0.0",
                "protocol": "socks",
                "settings": {"auth": "noauth", "udp": True}
            },
            {
                "tag": "http-in",
                "port": 10809,
                "listen": "0.0.0.0",
                "protocol": "http"
            },
            {
                "tag": "transparent-in",
                "port": 12345,
                "listen": "0.0.0.0",
                "protocol": "dokodemo-door",
                "settings": {"network": "tcp,udp", "followRedirect": True},
                "streamSettings": {"sockopt": {"tproxy": "redirect"}}
            }
        ],
        "outbounds": [
            outbound,
            {"tag": "direct", "protocol": "freedom"},
            {"tag": "block", "protocol": "blackhole"}
        ],
        "routing": {
            "domainStrategy": "IPIfNonMatch",
            "rules": [
                {
                    "type": "field",
                    "ip": ["geoip:private", "10.0.0.0/8", "172.16.0.0/12", "192.168.0.0/16", "127.0.0.0/8"],
                    "outboundTag": "direct"
                },
                {
                    "type": "field",
                    "network": "tcp,udp",
                    "outboundTag": outbound.get("tag", "proxy")
                }
            ]
        }
    }

def cmd_arch():
    info = detect_cpu_architecture()
    print("=" * 60)
    print(" MITRANET OS - HARDWARE & CPU ARCHITECTURE")
    print("=" * 60)
    print(f"[*] Target Architecture : {info['arch_code'].upper()}")
    print(f"[*] Platform Device     : {info['category']}")
    print(f"[*] Machine Identifier  : {info['machine']}")
    print(f"[*] Operating System    : {info['system']}")
    print(f"[*] Byte Endianness     : {info['endian'].upper()}-ENDIAN")
    print(f"[*] Resource Profile    : {'Low-Memory (<128MB Optimized)' if info['is_low_memory'] else 'High-Performance'}")
    print(f"[*] Active Xray Binary  : {XRAY_BIN}")
    print("=" * 60)

def cmd_status():
    code, out, _ = run_cmd("pgrep -f 'xray run' || pgrep -x xray")
    pids = out.split()
    cpu_info = detect_cpu_architecture()
    print("=" * 60)
    print(" MITRANET OS - V2RAY / XRAY STATUS")
    print("=" * 60)
    print(f"[*] Architecture: {cpu_info['arch_code'].upper()} ({cpu_info['category']})")
    if pids:
        print(f"[*] Status     : RUNNING (PID: {', '.join(pids)})")
    else:
        print("[*] Status     : STOPPED")
    
    if os.path.exists(ACTIVE_CONFIG):
        try:
            with open(ACTIVE_CONFIG, "r") as f:
                cfg = json.load(f)
                outbounds = cfg.get("outbounds", [])
                if outbounds:
                    proto = outbounds[0].get("protocol", "unknown")
                    addr = "unknown"
                    port = "unknown"
                    vnext = outbounds[0].get("settings", {}).get("vnext", [])
                    if vnext:
                        addr = vnext[0].get("address")
                        port = vnext[0].get("port")
                    print(f"[*] Active Node: Protocol: {proto.upper()} | Server: {addr}:{port}")
        except Exception:
            print("[*] Active Node: Unable to read config.json")
    else:
        print("[*] Active Node: No active config.json found")
    
    code, out, _ = run_cmd("pfctl -s nat 2>/dev/null | grep 12345")
    if out:
        print("[*] Transparent: ACTIVE (pf redirection enabled on port 12345)")
    else:
        print("[*] Transparent: INACTIVE")
    print(f"[*] SOCKS5 Port: 10808 | HTTP Port: 10809 | TProxy Port: 12345")
    print("=" * 60)

def cmd_start():
    ensure_dirs()
    if not os.path.exists(ACTIVE_CONFIG):
        print(f"[-] Error: Active config not found at {ACTIVE_CONFIG}")
        print("    Use 'mitranet-cli import-link <url>' or 'mitranet-cli use-node <name>' first.")
        return 1
    
    code, out, err = run_cmd(f"{XRAY_BIN} test -c {ACTIVE_CONFIG}")
    if code != 0:
        print(f"[-] Config validation failed:\n{err or out}")
        return 1
    
    run_cmd("pkill -9 -f 'xray run' 2>/dev/null")
    subprocess.Popen(f"{XRAY_BIN} run -c {ACTIVE_CONFIG} > {LOG_FILE} 2>&1", shell=True)
    time.sleep(1)
    cmd_status()
    return 0

def cmd_stop():
    run_cmd("pkill -f 'xray run' || pkill -x xray")
    print("[+] Xray service stopped.")
    return 0

def cmd_restart():
    cmd_stop()
    time.sleep(1)
    return cmd_start()

def cmd_list_nodes():
    ensure_dirs()
    files = [f for f in os.listdir(NODES_DIR) if f.endswith(".json")]
    print("=" * 60)
    print(" MITRANET OS - AVAILABLE NODES")
    print("=" * 60)
    if not files:
        print("  (No nodes imported yet. Use: mitranet-cli import-link <vless://...>)")
        return
    for i, f in enumerate(sorted(files), 1):
        name = f[:-5]
        path = os.path.join(NODES_DIR, f)
        try:
            with open(path, "r") as fp:
                d = json.load(fp)
                proto = d.get("outbounds", [{}])[0].get("protocol", "unknown")
                vnext = d.get("outbounds", [{}])[0].get("settings", {}).get("vnext", [{}])[0]
                addr = vnext.get("address", "-")
                port = vnext.get("port", "-")
                print(f"  [{i}] {name:<25} ({proto.upper()}) -> {addr}:{port}")
        except Exception:
            print(f"  [{i}] {name}")
    print("=" * 60)

def cmd_use_node(name: str):
    ensure_dirs()
    target = os.path.join(NODES_DIR, f"{name}.json")
    if not os.path.exists(target):
        target = os.path.join(NODES_DIR, name)
        if not os.path.exists(target):
            print(f"[-] Node '{name}' not found in {NODES_DIR}")
            return 1
    shutil.copy(target, ACTIVE_CONFIG)
    print(f"[+] Switched active node to '{name}'")
    return cmd_restart()

def cmd_import_link(link: str):
    ensure_dirs()
    link = link.strip()
    if link.startswith("vless://"):
        parsed = parse_vless_link(link)
    elif link.startswith("vmess://"):
        parsed = parse_vmess_link(link)
    else:
        print("[-] Unsupported link format. Supported: vless:// and vmess://")
        return 1
    
    clean_name = "".join([c if c.isalnum() or c in "-_" else "_" for c in parsed["name"]]).strip("_")
    full_cfg = build_full_config(parsed["outbound"])
    
    save_path = os.path.join(NODES_DIR, f"{clean_name}.json")
    with open(save_path, "w") as f:
        json.dump(full_cfg, f, indent=2)
    
    print(f"[+] Successfully imported node '{clean_name}' into {save_path}")
    
    if not os.path.exists(ACTIVE_CONFIG):
        shutil.copy(save_path, ACTIVE_CONFIG)
        print(f"[+] Set as active configuration: {ACTIVE_CONFIG}")
        cmd_restart()
    else:
        print(f"    Activate with: mitranet-cli use-node {clean_name}")
    return 0

def cmd_ping_node():
    print("[*] Testing proxy connection via SOCKS5 (127.0.0.1:10808)...")
    cmd = "curl -s -m 5 -x socks5h://127.0.0.1:10808 -o /dev/null -w 'HTTP: %{http_code} | Time: %{time_total}s\\n' https://www.cloudflare.com"
    code, out, err = run_cmd(cmd)
    if code == 0 and "HTTP:" in out:
        print(f"[+] Proxy Connected Successfully: {out}")
    else:
        print(f"[-] Proxy Test Failed or Timed Out ({err or out})")

def cmd_tproxy(action: str):
    if action == "enable":
        if os.path.exists(PF_CONF_XRAY):
            run_cmd(f"pfctl -f {PF_CONF_XRAY} && pfctl -e")
            print("[+] Transparent proxy redirection enabled in pf.")
        else:
            print(f"[-] pf rules file not found: {PF_CONF_XRAY}")
    elif action == "disable":
        run_cmd("pfctl -d 2>/dev/null")
        print("[+] pf rules disabled.")
    else:
        print("Usage: mitranet-cli tproxy <enable|disable>")

def main():
    if len(sys.argv) < 2:
        print("""
MitraNet OS - V2Ray/Xray Management CLI
Usage: mitranet-cli <command> [options]

Commands:
  status               Show current Xray daemon status & node info
  arch                 Show detected CPU architecture & hardware profile
  start                Start Xray background service
  stop                 Stop Xray service
  restart              Restart Xray service
  list-nodes           List all configured VLESS/VMESS nodes
  use-node <name>      Switch active node and restart
  import-link <url>    Import node from vless:// or vmess:// link
  ping-node            Test latency and connectivity through proxy
  tproxy <enable|disable> Toggle pf transparent proxy redirection
  logs                 Show recent Xray log entries
""")
        return 0
    
    cmd = sys.argv[1].lower()
    if cmd == "status":
        return cmd_status()
    elif cmd == "arch":
        return cmd_arch()
    elif cmd == "start":
        return cmd_start()
    elif cmd == "stop":
        return cmd_stop()
    elif cmd == "restart":
        return cmd_restart()
    elif cmd == "list-nodes":
        return cmd_list_nodes()
    elif cmd == "use-node":
        if len(sys.argv) < 3:
            print("[-] Specify node name: mitranet-cli use-node <name>")
            return 1
        return cmd_use_node(sys.argv[2])
    elif cmd == "import-link":
        if len(sys.argv) < 3:
            print("[-] Specify link: mitranet-cli import-link <vless://...>")
            return 1
        return cmd_import_link(sys.argv[2])
    elif cmd == "ping-node":
        return cmd_ping_node()
    elif cmd == "tproxy":
        action = sys.argv[2] if len(sys.argv) > 2 else ""
        return cmd_tproxy(action)
    elif cmd == "logs":
        if os.path.exists(LOG_FILE):
            run_cmd(f"tail -n 30 {LOG_FILE}")
        else:
            print("[-] No log file found.")
    else:
        print(f"[-] Unknown command: {cmd}")
        return 1

if __name__ == "__main__":
    sys.exit(main() or 0)
