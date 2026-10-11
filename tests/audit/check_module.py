
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(LAB_HOST, port=22, username=LAB_USER, password=LAB_PASS)

cmd = "python3 -c \"import mitranet.src.api.server as s; print(s.__file__)\""
stdin, stdout, stderr = client.exec_command(cmd)
print("Loaded server.py:", stdout.read().decode('utf-8', errors='ignore').strip())

client.close()
