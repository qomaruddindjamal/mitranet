import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=5)

stdin, stdout, stderr = ssh.exec_command('/ip firewall nat print stats where comment~"Redirect UDP"')
print("=== NAT STATS ===")
print(stdout.read().decode())

stdin, stdout, stderr = ssh.exec_command('/ip firewall filter print stats where comment~"Camouflage"')
print("=== FILTER STATS ===")
print(stdout.read().decode())

ssh.close()
