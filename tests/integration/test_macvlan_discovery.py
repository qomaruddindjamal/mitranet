
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER

import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

sftp = client.open_sftp()
with sftp.file('/tmp/disco.py', 'w') as f:
    f.write('''
from mitranet.core.network.discovery import InterfaceDiscoveryService
svc = InterfaceDiscoveryService()
ifaces = svc.discover_interfaces()
print(f"Total discovered: {len(ifaces)}")
for i in ifaces:
    if "mac" in i.name.lower() or "mac" in i.type.lower():
        print(f"FOUND: name={i.name}, type={i.type}, parent={i.parent_device}, mac={i.mac_address}")
''')
sftp.close()

stdin, stdout, stderr = client.exec_command('python3 /tmp/disco.py')
print("STDOUT:\n", stdout.read().decode())
print("STDERR:\n", stderr.read().decode())
client.close()
