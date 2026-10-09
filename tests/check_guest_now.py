import paramiko

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect('192.168.56.101', username='root', password='mitranet')
cmd = """ssh -o StrictHostKeyChecking=no root@192.168.101.118 "cat /www/server/panel/default.pl; echo '---'; sqlite3 /www/server/panel/data/default.db 'SELECT username FROM users;'" """
stdin, stdout, stderr = c.exec_command(cmd)
print('GUEST NOW:\n', stdout.read().decode())
print('STDERR:\n', stderr.read().decode())
c.close()
