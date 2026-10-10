import paramiko
import json
import time

ssh_vps = paramiko.SSHClient()
ssh_vps.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh_vps.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

ssh_mpc = paramiko.SSHClient()
ssh_mpc.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh_mpc.connect('10.10.66.228', username='root', password='mitranet', timeout=10)

def run_vps(c):
    stdin, stdout, stderr = ssh_vps.exec_command(c)
    return stdout.read().decode('utf-8', errors='ignore').strip()

def run_mpc(c):
    stdin, stdout, stderr = ssh_mpc.exec_command(c)
    return stdout.read().decode('utf-8', errors='ignore').strip()

print("[1] Applying Booster on Mini PC...")
payload = {
    "role": "client",
    "vps_host": "103.93.162.168",
    "stream_count": 2,
    "tunnel_type": "wireguard",
    "balancer_mode": "ecmp",
    "dscp_mode": "AF41",
    "clamp_mss": 1360,
    "enable_bbr": True,
    "peer_public_keys": [
        "V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8=",
        "e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8="
    ]
}

cmd = f"curl -s -X POST http://127.0.0.1:8443/api/v1/vpn/booster/apply -H 'Content-Type: application/json' -d '{json.dumps(payload)}'"
apply_out = run_mpc(cmd)
print("Apply Result:", apply_out)

print("\n[2] Waiting 3 seconds for WireGuard handshakes...")
time.sleep(3)

print("\n[3] Checking Ping to VPS Gateway IPs from Mini PC...")
print("Ping Stream 1 (10.250.1.1):", run_mpc("ping -c 2 -W 1 -I wgboost1 10.250.1.1"))
print("Ping Stream 2 (10.250.2.1):", run_mpc("ping -c 2 -W 1 -I wgboost2 10.250.2.1"))

print("\n[4] Mini PC WireGuard show:")
print(run_mpc("wg show"))

print("\n[5] Booster Telemetry Status:")
telemetry = run_mpc("curl -s http://127.0.0.1:8443/api/v1/vpn/booster/status")
try:
    d = json.loads(telemetry)
    print("Enabled:", d.get("data", {}).get("enabled"))
    for s in d.get("data", {}).get("streams", []):
        print(f" -> {s['interface']} ({s['ip']}): status={s['status']}, latency={s['latency']}, rx={s['rx_formatted']}, tx={s['tx_formatted']}")
except Exception:
    print(telemetry)

ssh_vps.close()
ssh_mpc.close()
