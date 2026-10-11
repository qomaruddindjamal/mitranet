
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)

cmd = """
which aapanel bt
find / -name "*aapanel*" -maxdepth 3 2>/dev/null
ls -la /www 2>/dev/null
systemctl list-unit-files | grep -i bt
systemctl status mitranet-vm@aapanel --no-pager
"""
stdin, stdout, stderr = ssh.exec_command(cmd)
out_str = stdout.read().decode('utf-8', errors='ignore')
err_str = stderr.read().decode('utf-8', errors='ignore')
print("OUT:", out_str.encode('ascii', errors='ignore').decode('ascii'))
print("ERR:", err_str.encode('ascii', errors='ignore').decode('ascii'))
ssh.close()
