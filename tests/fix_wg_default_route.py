import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet')

cmd = """
# If 0.0.0.0/0 is in wg0 AllowedIPs without an active VPS upstream tunnel, it routes all internet to wg0
sed -i 's|AllowedIPs = 10.10.99.1/32, 10.10.77.0/24, 10.10.88.0/24, 0.0.0.0/0|AllowedIPs = 10.10.99.1/32, 10.10.77.0/24, 10.10.88.0/24|g' /etc/wireguard/wg0.conf
systemctl restart wg-quick@wg0
sleep 1
ping -c 2 8.8.8.8
curl -4 -sI http://deb.debian.org | head -n 5
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print("OUT:", stdout.read().decode('ascii', errors='ignore'))
print("ERR:", stderr.read().decode('ascii', errors='ignore'))
ssh.close()
