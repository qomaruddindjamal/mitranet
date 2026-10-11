
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER

import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

cmd = """
echo "=== wg0 config (safe) ==="
grep -E "Address|ListenPort|MTU|AllowedIPs|Endpoint|PostUp" /etc/wireguard/wg0.conf 2>/dev/null
echo "=== sysctl ==="
sysctl net.ipv4.tcp_congestion_control net.core.default_qdisc net.ipv4.ip_forward
echo "=== iptables mangle ==="
iptables -t mangle -L -n -v
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print(stdout.read().decode('utf-8'))
ssh.close()
