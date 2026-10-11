
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER, WEBUI_PASS, WEBUI_USER

import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(LAB_HOST, port=22, username=LAB_USER, password=LAB_PASS)

cmd = """
curl -s -c /tmp/c.txt -X POST http://127.0.0.1:8443/api/v1/auth/login -H 'Content-Type: application/json' -d '{"username":WEBUI_USER,"password":WEBUI_PASS}' >/dev/null
echo "=== BEFORE RESIZE ==="
qemu-img info -U /var/lib/mitranet/vms/aapanel/disk.qcow2 | grep 'virtual size'

echo "=== UPDATE DISK TO 15 GB ==="
curl -s -b /tmp/c.txt -X POST http://127.0.0.1:8443/api/v1/services/kvm/update -H 'Content-Type: application/json' -d '{"id":"aapanel","disk_gb":15}'
echo ""

echo "=== AFTER RESIZE ==="
qemu-img info -U /var/lib/mitranet/vms/aapanel/disk.qcow2 | grep 'virtual size'
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print(stdout.read().decode('utf-8'))
ssh.close()
