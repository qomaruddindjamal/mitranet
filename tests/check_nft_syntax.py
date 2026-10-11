
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

variants = [
    'iifname "enp0s8" tcp ip daddr 192.168.56.101 dport 8080 counter dnat ip to 192.168.56.101:8443',
    'iifname "enp0s8" ip daddr 192.168.56.101 tcp dport 8080 counter dnat ip to 192.168.56.101:8443',
    'iifname "enp0s8" ip daddr 192.168.56.101 tcp dport 8080 dnat ip to 192.168.56.101:8443'
]

for v in variants:
    content = f"""table inet test_t {{
    chain prerouting {{
        type nat hook prerouting priority dstnat; policy accept;
        {v}
    }}
}}
"""
    sftp = ssh.open_sftp()
    with sftp.file('/tmp/test_var.nft', 'w') as f:
        f.write(content)
    sftp.close()
    
    stdin, stdout, stderr = ssh.exec_command('nft -c -f /tmp/test_var.nft')
    err = stderr.read().decode().strip()
    print(f"Variant: {v}")
    print(f"Result: {'OK' if not err else err}\n")

ssh.close()
