import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

# First authenticate to get cookie
cmd = 'curl -s -i -c /tmp/cookies.txt -X POST -d "username=admin&password=adminpassword" http://127.0.0.1:8000/login.php || true'
ssh.exec_command(cmd)

# Or check auth.inc to see session requirement
stdin, stdout, stderr = ssh.exec_command('cat /mitranet/web/includes/auth.inc | head -n 30')
print(stdout.read().decode())

ssh.close()
