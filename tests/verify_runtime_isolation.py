import sys
import paramiko

sys.stdout.reconfigure(encoding='utf-8')
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('127.0.0.1', port=2222, username='root', password='mitranet', timeout=5)

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
