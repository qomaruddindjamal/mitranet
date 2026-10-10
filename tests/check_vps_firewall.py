import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

stdin, stdout, stderr = ssh.exec_command('/ip firewall filter print without-paging')
print("=== FILTER ===")
print(stdout.read().decode())

stdin, stdout, stderr = ssh.exec_command('/ip firewall nat print without-paging')
print("=== NAT ===")
print(stdout.read().decode())

ssh.close()
