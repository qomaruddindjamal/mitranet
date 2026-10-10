import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)
print("CONNECTED TO MIKROTIK VPS!")

commands = [
    "/ip/service/print where name='dns'",
    "/ip/dns/print",
    "/ip/firewall/nat/print where protocol='udp'",
    "/ip/firewall/filter/print where dst-port=53 or dst-port=13231"
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd)
    print(f"=== {cmd} ===")
    print(stdout.read().decode())

ssh.close()
