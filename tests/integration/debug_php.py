
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

# Test direct curl to 127.0.0.1:8000
cmd = """curl -i -X POST http://127.0.0.1:8000/services_virtual.php -d "act=stop&id=aapanel" """
stdin, stdout, stderr = ssh.exec_command(cmd)
print("=== CURL TO PHP PORT 8000 ===")
print("OUT:", stdout.read().decode('utf-8', errors='ignore'))
print("ERR:", stderr.read().decode('utf-8', errors='ignore'))

# Check php error log
stdin, stdout, stderr = ssh.exec_command("php -i | grep error_log")
print("=== PHP ERROR LOG CONFIG ===")
print(stdout.read().decode('utf-8', errors='ignore'))

ssh.close()
