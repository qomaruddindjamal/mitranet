
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

cmds = [
    '/interface gre remove [find name=gre-mitranet]',
    '/ip address remove [find interface=gre-mitranet]',
    '/interface gre add name=gre-mitranet remote-address=10.250.1.2 local-address=10.250.1.1 comment=Stage2-GRE',
    '/ip address add address=10.254.1.1/30 interface=gre-mitranet',
    '/interface gre print detail where name=gre-mitranet',
    '/ip address print where interface=gre-mitranet'
]

for cmd in cmds:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='ignore')
    if out.strip():
        print(f"[{cmd}]:\n{out}")

ssh.close()
