
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
ssh.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)

cmd = """
curl -s -c /tmp/cookies.txt -X POST http://127.0.0.1:8443/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":WEBUI_USER,"password":WEBUI_PASS}'
echo ""
echo "=== INTERFACES ==="
curl -s -b /tmp/cookies.txt http://127.0.0.1:8443/api/v1/interfaces
echo ""
echo "=== GATEWAYS ==="
curl -s -b /tmp/cookies.txt http://127.0.0.1:8443/api/v1/gateways
echo ""
echo "=== VETHERNET ==="
curl -s -b /tmp/cookies.txt http://127.0.0.1:8443/api/v1/vethernet
echo ""
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8', errors='ignore')
print(out)
ssh.close()
