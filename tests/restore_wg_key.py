import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet')

cmd = """
sed -i 's|PrivateKey = .*|PrivateKey = EFEGbYvOOS2y3YcBDWZuAq2OvfaISSPvfVf38AYFWlA=|g' /etc/wireguard/wg0.conf
systemctl restart wg-quick@wg0
sleep 1
systemctl is-active wg-quick@wg0
wg show wg0
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print("OUT:", stdout.read().decode('ascii', errors='ignore'))
print("ERR:", stderr.read().decode('ascii', errors='ignore'))
ssh.close()
