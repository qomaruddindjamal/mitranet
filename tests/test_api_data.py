import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet')

cmd = """
curl -s -c /tmp/cookies.txt -X POST http://127.0.0.1:8443/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"mitranet"}'
echo ""
echo "=== INTERFACES ==="
curl -s -b /tmp/cookies.txt http://127.0.0.1:8443/api/v1/interfaces
echo ""
echo "=== GATEWAYS ==="
curl -s -b /tmp/cookies.txt http://127.0.0.1:8443/api/v1/gateways
echo ""
echo "=== VETHERNET ==="
curl -s -b /tmp/cookies.txt http://127.0.0.1:8443/api/v1/vethernet
echo ""
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
out = stdout.read().decode('utf-8', errors='ignore')
print(out)
ssh.close()
