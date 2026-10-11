
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
print("CONNECTED TO MIKROTIK VPS!")

commands = [
    "/ip/service/print where name='dns'",
    "/ip/dns/print",
    "/ip/firewall/nat/print where protocol='udp'",
    "/ip/firewall/filter/print where dst-port=53 or dst-port=13231"
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(f"=== {cmd} ===")
    print(stdout.read().decode())

ssh.close()
