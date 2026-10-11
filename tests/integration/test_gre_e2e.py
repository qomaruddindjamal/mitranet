
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER

import json
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

payload = {
    "type": "gre",
    "name": "gre-vps",
    "local": "10.250.1.2",
    "remote": "10.250.1.1",
    "ttl": 255,
    "mtu": 1476,
    "ip_cidr": "10.254.1.2/30"
}

cmd = f"curl -s -X POST http://127.0.0.1:8443/api/v1/interfaces/tunnel/create -H 'Content-Type: application/json' -d '{json.dumps(payload)}'"
stdin, stdout, stderr = ssh.exec_command(cmd)
print("CREATE RESP:", stdout.read().decode('utf-8'))

# Check interface in kernel
stdin, stdout, stderr = ssh.exec_command("ip addr show dev gre-vps; ip link show dev gre-vps")
print("KERNEL IFACE:\n", stdout.read().decode('utf-8'))

# Ping test to MikroTik VPS across the GRE tunnel
stdin, stdout, stderr = ssh.exec_command("ping -c 4 10.254.1.1")
print("PING TEST (MitraNet -> VPS CHR):\n", stdout.read().decode('utf-8'))

ssh.close()
