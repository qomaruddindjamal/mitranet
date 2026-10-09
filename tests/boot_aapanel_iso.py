import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', username='root', password='mitranet')

cmd = """
grep -q "ISO_FILE" /etc/mitranet/vms/aapanel.env || echo "ISO_FILE=/var/lib/mitranet/isos/debian-13-netinst.iso" >> /etc/mitranet/vms/aapanel.env
systemctl restart mitranet-vm@aapanel
sleep 2
systemctl is-active mitranet-vm@aapanel
ps aux | grep qemu-system-x86_64 | grep aapanel
"""

stdin, stdout, stderr = ssh.exec_command(cmd)
print("OUT:", stdout.read().decode('ascii', errors='ignore'))
print("ERR:", stderr.read().decode('ascii', errors='ignore'))
ssh.close()
