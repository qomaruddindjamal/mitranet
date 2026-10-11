import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

stdin, stdout, stderr = ssh.exec_command('ps aux')
for line in stdout.read().decode('utf-8').splitlines():
    if 'python' in line or 'mitranet' in line or 'server' in line or 'systemctl' in line:
        print(line)

ssh.close()
