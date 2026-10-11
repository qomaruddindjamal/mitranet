
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
grep -q "ISO_FILE" /etc/mitranet/vms/aapanel.env || echo "ISO_FILE=/var/lib/mitranet/isos/debian-13-netinst.iso" >> /etc/mitranet/vms/aapanel.env
systemctl restart mitranet-vm@aapanel
sleep 2
systemctl is-active mitranet-vm@aapanel
ps aux | grep qemu-system-x86_64 | grep aapanel
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print("OUT:", stdout.read().decode('ascii', errors='ignore'))
print("ERR:", stderr.read().decode('ascii', errors='ignore'))
ssh.close()
