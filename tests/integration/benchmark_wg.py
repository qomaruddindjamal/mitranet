
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
ssh.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=10)

commands = [
    "ip addr show wg0",
    "ping -c 5 10.10.77.1",
    "curl --interface 10.10.77.3 -s https://ifconfig.me",
    "curl --interface 10.10.77.3 -o /dev/null -s -w 'Speed: %{speed_download} B/s, Time: %{time_total}s\n' https://speed.cloudflare.com/__down?bytes=25000000",
    "curl --interface 10.10.77.3 -o /dev/null -s -w 'Speed: %{speed_download} B/s, Time: %{time_total}s\n' https://speed.cloudflare.com/__down?bytes=50000000",
    "/usr/local/bin/speedtest-cli --simple"
]

for cmd in commands:
    print(f"=== RUNNING: {cmd} ===")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out:
        print(f"STDOUT:\n{out}")
    if err:
        print(f"STDERR:\n{err}")

ssh.close()
