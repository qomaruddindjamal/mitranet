import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = ssh.open_sftp()
sftp.put('c:/mitranet/src/api/server.py', '/mitranet/src/api/server.py')
sftp.put('c:/mitranet/src/api/server.py', '/usr/lib/python3/dist-packages/mitranet/src/api/server.py')
sftp.close()

stdin, stdout, stderr = ssh.exec_command('pkill -f mitranet.src.api.server; nohup /usr/bin/python3 -m mitranet.src.api.server >/tmp/api.log 2>&1 & sleep 2; ps aux | grep [m]itranet')
print("STDOUT:", stdout.read().decode('utf-8'))
print("STDERR:", stderr.read().decode('utf-8'))
ssh.close()
