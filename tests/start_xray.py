
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(LAB_HOST, port=22, username=LAB_USER, password=LAB_PASS)
cmd = """
sed -i 's/"port": 8443/"port": 10443/g' /usr/local/etc/xray/config.json
systemctl reset-failed xray
systemctl restart xray
sleep 1
systemctl is-active xray
pgrep -a xray
"""
stdin, stdout, stderr = c.exec_command(cmd)
print('STDOUT:\n' + stdout.read().decode('utf-8', errors='ignore'))
print('STDERR:\n' + stderr.read().decode('utf-8', errors='ignore'))
c.close()
