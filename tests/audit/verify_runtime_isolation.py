
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_PASS, VM_NAT_HOST, VM_NAT_PORT, VM_USER

import sys
import paramiko

sys.stdout.reconfigure(encoding='utf-8')
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(VM_NAT_HOST, port=int(VM_NAT_PORT), username=VM_USER, password=MINIPC_PASS, timeout=5)

cmds = """
echo "=== FREEBSD RUNTIME DEPENDENCIES CHECK ==="
which pfctl 2>/dev/null || echo "pfctl: NOT FOUND (0 pfctl: PASS)"
which pf 2>/dev/null || echo "pf: NOT FOUND (0 pf: PASS)"
which bsdconfig 2>/dev/null || echo "bsdconfig: NOT FOUND (PASS)"
ls /boot/kernel 2>/dev/null || echo "FreeBSD kernel dir: NOT FOUND (PASS)"
echo "=== SYSTEMD SERVICES STATUS ==="
systemctl is-active mitranet-webui
systemctl is-active mitranet-firewall
systemctl is-active mitranet-gateway-monitor
echo "=== DEBIAN PACKAGES STATUS ==="
dpkg -l mitranet-core mitranet-config-engine mitranet-network-engine mitranet-gateway-monitor
dpkg -l php-cli php-curl | grep -E '^ii'
"""

stdin, stdout, stderr = client.exec_command(cmds)
print(stdout.read().decode('utf-8', errors='replace'))
if stderr.read():
    print("STDERR:", stderr.read().decode('utf-8', errors='replace'))
