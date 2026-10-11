
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
c.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS)
stdin, stdout, stderr = c.exec_command('grep -n -C 5 "AAPANEL_SPLIT" /usr/lib/python3/dist-packages/mitranet/src/api/server.py')
print('DIST:\n', stdout.read().decode())
stdin, stdout, stderr = c.exec_command('grep -n -C 5 "AAPANEL_SPLIT" /mitranet/src/api/server.py')
print('SRC:\n', stdout.read().decode())
c.close()
