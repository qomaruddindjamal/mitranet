
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER

import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

sftp = ssh.open_sftp()
sftp.put('c:/mitranet/src/api/server.py', '/mitranet/src/api/server.py')
sftp.put('c:/mitranet/src/api/server.py', '/usr/lib/python3/dist-packages/mitranet/src/api/server.py')
sftp.close()

stdin, stdout, stderr = ssh.exec_command('pkill -f mitranet.src.api.server; nohup /usr/bin/python3 -m mitranet.src.api.server >/tmp/api.log 2>&1 & sleep 2; ps aux | grep [m]itranet')
print("STDOUT:", stdout.read().decode('utf-8'))
print("STDERR:", stderr.read().decode('utf-8'))
ssh.close()
