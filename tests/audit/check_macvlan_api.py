
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER

import paramiko
import json

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

cmd = 'curl -s -k http://127.0.0.1:8443/api/interfaces -H "X-MitraNet-Key: rinjani-dev-secret"'
stdin, stdout, stderr = client.exec_command(cmd)
raw = stdout.read().decode('utf-8')
client.close()

try:
    data = json.loads(raw)
    ifaces = data.get('data', [])
    print(f"Total interfaces from API: {len(ifaces)}")
    for i in ifaces:
        name = i.get('name', '')
        itype = i.get('type', '')
        if 'mac' in name.lower() or 'mac' in itype.lower():
            print(f"-> Found: {name} (type: {itype}, ip: {i.get('ip')}, mac: {i.get('mac')})")
except Exception as e:
    print("Error parsing JSON:", e)
    print("Raw output snippet:", raw[:300])
