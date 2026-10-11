
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
ssh.connect(VPS_HOST, port=22, username=VPS_USER, password=VPS_PASS, look_for_keys=False, allow_agent=False, timeout=5)

stdin, stdout, stderr = ssh.exec_command('/ip firewall nat print stats where comment~"Redirect UDP"')
print("=== NAT STATS ===")
print(stdout.read().decode())

stdin, stdout, stderr = ssh.exec_command('/ip firewall filter print stats where comment~"Camouflage"')
print("=== FILTER STATS ===")
print(stdout.read().decode())

ssh.close()
