import paramiko

c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect('192.168.56.101', port=22, username='root', password='mitranet')
cmd = """
sed -i 's/"port": 8443/"port": 10443/g' /usr/local/etc/xray/config.json
systemctl reset-failed xray
systemctl restart xray
sleep 1
systemctl is-active xray
pgrep -a xray
"""
stdin, stdout, stderr = c.exec_command(cmd)
print('STDOUT:\n' + stdout.read().decode('utf-8', errors='ignore'))
print('STDERR:\n' + stderr.read().decode('utf-8', errors='ignore'))
c.close()
