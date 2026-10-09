import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = ssh.open_sftp()
sftp.put('c:/mitranet/core/network/discovery.py', '/mitranet/core/network/discovery.py')
sftp.put('c:/mitranet/core/network/discovery.py', '/usr/lib/python3/dist-packages/mitranet/core/network/discovery.py')
sftp.put('c:/mitranet/core/network/backend/linux.py', '/mitranet/core/network/backend/linux.py')
sftp.put('c:/mitranet/core/network/backend/linux.py', '/usr/lib/python3/dist-packages/mitranet/core/network/backend/linux.py')
sftp.put('c:/mitranet/src/api/server.py', '/mitranet/src/api/server.py')
sftp.put('c:/mitranet/src/api/server.py', '/usr/lib/python3/dist-packages/mitranet/src/api/server.py')
sftp.close()

stdin, stdout, stderr = ssh.exec_command('systemctl restart mitranet-api 2>&1 || pkill -f "mitranet.src.api.server"')
print("Restart output:", stdout.read().decode())
print("Stderr:", stderr.read().decode())

ssh.close()
