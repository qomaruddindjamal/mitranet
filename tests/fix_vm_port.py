import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet')

cmd = """
sed -i 's/PORT_FWD=8080/PORT_FWD=8081/g' /etc/mitranet/vms/debian-guest.env
systemctl restart mitranet-vm@debian-guest
sleep 2
systemctl is-active mitranet-vm@debian-guest
ps aux | grep qemu-system-x86_64 | grep debian-guest
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print("OUT:", stdout.read().decode())
print("ERR:", stderr.read().decode())
ssh.close()
