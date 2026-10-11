
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER

import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=10)

print("=== 1. CREATING MACVLAN VIRTUAL INTERFACE mac0 ON enp1s0 ===")
setup_cmds = [
    # Remove if exists
    "ip link del mac0 2>/dev/null || true",
    # Generate macvlan with random MAC
    "ip link add link enp1s0 name mac0 address 02:42:0a:0a:42:01 type macvlan mode bridge",
    "ip link set mac0 up",
    # Request DHCP lease on mac0
    "dhcpcd -4 -n mac0",
    "sleep 3",
    "ip addr show mac0"
]

for cmd in setup_cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out:
        print(f"[OUT] {out}")
    if err:
        print(f"[ERR] {err}")

ssh.close()
