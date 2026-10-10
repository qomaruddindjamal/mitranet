import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

def run(c):
    stdin, stdout, stderr = ssh.exec_command(c)
    return stdout.read().decode('utf-8', errors='ignore').strip()

print("=== FIREWALL FILTER ===")
print(run('/ip firewall filter print where chain="input"'))

print("\n=== FIREWALL NAT ===")
print(run('/ip firewall nat print where chain="srcnat"'))

ssh.close()
