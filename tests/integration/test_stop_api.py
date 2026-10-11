
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER

import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

cmd = "curl -s -i -k -X POST -H 'Content-Type: application/json' -d '{\"role\":\"client\"}' http://127.0.0.1:8443/api/v1/vpn/booster/stop"
stdin, stdout, stderr = ssh.exec_command(cmd)
print("=== POST STOP LOCALHOST ===")
print(stdout.read().decode())

cmd2 = "curl -s -i -k -X POST -H 'Content-Type: application/json' -d '{\"role\":\"client\"}' http://10.10.66.228:8443/api/v1/vpn/booster/stop"
stdin, stdout, stderr = ssh.exec_command(cmd2)
print("=== POST STOP NON-LOOPBACK ===")
print(stdout.read().decode())

ssh.close()
