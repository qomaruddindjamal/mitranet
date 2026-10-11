
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER, VM_PASS

import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

sftp = client.open_sftp()
with sftp.file('/tmp/ssh_to_router.py', 'w') as f:
    f.write('''
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.77.1', port=22, username='citramedia', password=VM_PASS, timeout=5)
print("SUCCESS: Connected to MikroTik VPS via WireGuard!")
stdin, stdout, stderr = ssh.exec_command('/system/identity/print; /tool/bandwidth-server/print')
print(stdout.read().decode())
ssh.close()
''')
sftp.close()

stdin, stdout, stderr = client.exec_command('python3 /tmp/ssh_to_router.py')
print(stdout.read().decode())
print(stderr.read().decode())

client.close()
