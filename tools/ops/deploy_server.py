
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER

import os
import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(LAB_HOST, port=22, username=LAB_USER, password=LAB_PASS)
sftp = client.open_sftp()

local_server = r'C:\mitranet\src\api\server.py'
remote_dist = '/usr/lib/python3/dist-packages/mitranet/src/api/server.py'
remote_src = '/mitranet/src/api/server.py'

print(f"Deploying {local_server} to {remote_dist} and {remote_src}...")
sftp.put(local_server, remote_dist)
sftp.put(local_server, remote_src)
sftp.close()

stdin, stdout, stderr = client.exec_command("systemctl restart mitranet-webui && systemctl is-active mitranet-webui")
print("mitranet-webui status:", stdout.read().decode('utf-8', errors='ignore').strip())
client.close()
