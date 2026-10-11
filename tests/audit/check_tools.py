import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

cmd = "modprobe macsec 2>/dev/null; which keepalived tcpdump ethtool ip; lsmod | grep -E 'macsec|vrf'"
stdin, stdout, stderr = ssh.exec_command(cmd)
print("MINI PC TOOLS & MODULES:")
print(stdout.read().decode())
print("STDERR:", stderr.read().decode())

ssh.close()
