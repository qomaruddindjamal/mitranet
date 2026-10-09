import paramiko

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect('192.168.56.101', username='root', password='mitranet')
stdin, stdout, stderr = c.exec_command('grep -n -C 5 "AAPANEL_SPLIT" /usr/lib/python3/dist-packages/mitranet/src/api/server.py')
print('DIST:\n', stdout.read().decode())
stdin, stdout, stderr = c.exec_command('grep -n -C 5 "AAPANEL_SPLIT" /mitranet/src/api/server.py')
print('SRC:\n', stdout.read().decode())
c.close()
