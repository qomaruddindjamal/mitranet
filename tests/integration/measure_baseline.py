
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
ssh.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

cmds = [
    'ip route show',
    'wg show',
    'curl -s -w "\\nTime: %{time_total}s | Speed: %{speed_download} bytes/s | HTTP: %{http_code}\\n" -o /dev/null http://speed.cloudflare.com/__down?bytes=10000000',
    'ping -c 4 -W 2 103.93.162.168'
]

for cmd in cmds:
    print(f"=== CMD: {cmd} ===")
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(stdout.read().decode())

ssh.close()
