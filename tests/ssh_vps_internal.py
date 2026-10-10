import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = client.open_sftp()
with sftp.file('/tmp/ssh_to_router.py', 'w') as f:
    f.write('''
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.77.1', port=22, username='citramedia', password='K0323205', timeout=5)
print("SUCCESS: Connected to MikroTik VPS via WireGuard!")
stdin, stdout, stderr = ssh.exec_command('/system/identity/print; /tool/bandwidth-server/print')
print(stdout.read().decode())
ssh.close()
''')
sftp.close()

stdin, stdout, stderr = client.exec_command('python3 /tmp/ssh_to_router.py')
print(stdout.read().decode())
print(stderr.read().decode())

client.close()
