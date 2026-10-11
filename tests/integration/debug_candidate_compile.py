
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
ssh.connect(LAB_HOST, username=LAB_USER, password=LAB_PASS, timeout=5)

cmd = """python3 -c "
import json
from mitranet.core.firewall.models import FirewallTableConfig
from mitranet.core.firewall.compiler import NftablesCompiler

with open('/var/lib/mitranet/firewall/candidate.json') as f:
    cfg = FirewallTableConfig(**json.load(f))
print(NftablesCompiler.compile_table(cfg))
"
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print('OUTPUT:\n', stdout.read().decode())
print('ERROR:\n', stderr.read().decode())
ssh.close()
