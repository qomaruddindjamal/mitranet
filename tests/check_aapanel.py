import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet')

cmd = """
which aapanel bt
find / -name "*aapanel*" -maxdepth 3 2>/dev/null
ls -la /www 2>/dev/null
systemctl list-unit-files | grep -i bt
systemctl status mitranet-vm@aapanel --no-pager
"""
stdin, stdout, stderr = ssh.exec_command(cmd)
out_str = stdout.read().decode('utf-8', errors='ignore')
err_str = stderr.read().decode('utf-8', errors='ignore')
print("OUT:", out_str.encode('ascii', errors='ignore').decode('ascii'))
print("ERR:", err_str.encode('ascii', errors='ignore').decode('ascii'))
ssh.close()
