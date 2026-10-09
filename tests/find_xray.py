import paramiko

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect('192.168.56.101', port=22, username='root', password='mitranet')
stdin, stdout, stderr = c.exec_command("find / -name '*xray*' 2>/dev/null")
print('STDOUT:\n' + stdout.read().decode())
print('STDERR:\n' + stderr.read().decode())
c.close()
