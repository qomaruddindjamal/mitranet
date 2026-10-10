import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

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
