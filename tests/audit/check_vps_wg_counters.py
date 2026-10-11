
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import VPS_HOST, VPS_PASS, VPS_USER

import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(VPS_HOST, port=22, username=VPS_USER, password=VPS_PASS, look_for_keys=False, allow_agent=False, timeout=8)

stdin, stdout, stderr = ssh.exec_command('/interface print detail where name~"wg" without-paging')
print("=== VPS INTERFACE DETAILS ===")
print(stdout.read().decode())

stdin, stdout, stderr = ssh.exec_command('/interface/wireguard/peers/print detail where interface~"wg-boost" without-paging')
print("=== VPS PEER DETAILS ===")
print(stdout.read().decode())

ssh.close()
