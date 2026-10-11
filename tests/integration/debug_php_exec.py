
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

cmd = """php -d display_errors=1 -d error_reporting=E_ALL -r '
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST["act"] = "stop";
$_POST["id"] = "aapanel";
require "/mitranet/web/services_virtual.php";
'"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print("=== PHP CLI RUN OF services_virtual.php ===")
print("OUT:", stdout.read().decode('utf-8', errors='ignore'))
print("ERR:", stderr.read().decode('utf-8', errors='ignore'))
ssh.close()
