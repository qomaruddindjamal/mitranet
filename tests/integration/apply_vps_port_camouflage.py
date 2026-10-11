
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import VPS_HOST, VPS_PASS, VPS_USER

import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(VPS_HOST, port=22, username=VPS_USER, password=VPS_PASS, look_for_keys=False, allow_agent=False, timeout=8)

print("Applying Port 53 & Port 443 WireGuard rules on MikroTik VPS...")

commands = [
    # Allow port 53 & 443 UDP in input chain before drop rule (place at index 24)
    "/ip firewall filter add chain=input action=accept protocol=udp dst-port=53 comment=\"Accept WireGuard DNS Camouflage\" place-before=24",
    "/ip firewall filter add chain=input action=accept protocol=udp dst-port=443 comment=\"Accept WireGuard QUIC Camouflage\" place-before=24",
    # Redirect incoming UDP 53 & 443 on WAN to WireGuard port 13231
    "/ip firewall nat add chain=dstnat action=redirect to-ports=13231 protocol=udp dst-port=53 comment=\"Redirect UDP 53 to WireGuard\"",
    "/ip firewall nat add chain=dstnat action=redirect to-ports=13231 protocol=udp dst-port=443 comment=\"Redirect UDP 443 to WireGuard\""
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    print(f"CMD: {cmd}")
    if out: print(f"  OUT: {out}")
    if err: print(f"  ERR: {err}")

# Verify
stdin, stdout, stderr = ssh.exec_command('/ip firewall filter print where comment~"Camouflage"')
print("\n=== FILTER CAMOUFLAGE ===")
print(stdout.read().decode())

stdin, stdout, stderr = ssh.exec_command('/ip firewall nat print where comment~"Redirect UDP"')
print("\n=== NAT REDIRECT ===")
print(stdout.read().decode())

ssh.close()
