
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

cmd = """python3 -c "
import json
from mitranet.core.network.discovery import InterfaceDiscoveryService
s = InterfaceDiscoveryService()
ifaces = s.discover_interfaces()
for i in ifaces:
    print(i.name, i.type, i.altname, i.port_label)
" """

stdin, stdout, stderr = c.exec_command(cmd)
print("STDOUT:\n", stdout.read().decode())
print("STDERR:\n", stderr.read().decode())
c.close()
