
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
# If 0.0.0.0/0 is in wg0 AllowedIPs without an active VPS upstream tunnel, it routes all internet to wg0
sed -i 's|AllowedIPs = 10.10.99.1/32, 10.10.77.0/24, 10.10.88.0/24, 0.0.0.0/0|AllowedIPs = 10.10.99.1/32, 10.10.77.0/24, 10.10.88.0/24|g' /etc/wireguard/wg0.conf
systemctl restart wg-quick@wg0
sleep 1
ping -c 2 8.8.8.8
curl -4 -sI http://deb.debian.org | head -n 5
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print("OUT:", stdout.read().decode('ascii', errors='ignore'))
print("ERR:", stderr.read().decode('ascii', errors='ignore'))
ssh.close()
