import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', port=22, username='root', password='mitranet', timeout=5)

sftp = ssh.open_sftp()
with open('c:/mitranet/test_wifi_detect.py', 'r', encoding='utf-8') as f:
    code = f.read()

s_marker = 'remote_script = """'
e_marker = '"""\n\nstdin,'
s = code.index(s_marker) + len(s_marker)
e = code.index(e_marker)
remote_py = code[s:e]

with sftp.open('/tmp/test_wifi.py', 'w') as f:
    f.write(remote_py)
sftp.close()

stdin, stdout, stderr = ssh.exec_command('python3 /tmp/test_wifi.py')
print("STDOUT:\n", stdout.read().decode('utf-8', errors='ignore'))
print("STDERR:\n", stderr.read().decode('utf-8', errors='ignore'))
ssh.close()
