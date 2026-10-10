import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

rules = [
    '/ip firewall filter add chain=input action=accept in-interface=wg-boost1 comment="Accept Boost1" place-before=0',
    '/ip firewall filter add chain=input action=accept in-interface=wg-boost2 comment="Accept Boost2" place-before=0',
    '/ip firewall filter add chain=forward action=accept in-interface=wg-boost1 comment="Forward Boost1" place-before=0',
    '/ip firewall filter add chain=forward action=accept in-interface=wg-boost2 comment="Forward Boost2" place-before=0',
    '/ip firewall filter add chain=forward action=accept out-interface=wg-boost1 comment="Forward Out Boost1" place-before=0',
    '/ip firewall filter add chain=forward action=accept out-interface=wg-boost2 comment="Forward Out Boost2" place-before=0',
    '/ip firewall nat add chain=srcnat src-address=10.250.1.0/30 action=masquerade comment="NAT Boost1" place-before=0',
    '/ip firewall nat add chain=srcnat src-address=10.250.2.0/30 action=masquerade comment="NAT Boost2" place-before=0'
]

for r in rules:
    stdin, stdout, stderr = ssh.exec_command(r)
    err = stderr.read().decode().strip()
    out = stdout.read().decode().strip()
    print(f"Rule: {r[:40]}... -> Out: {out} Err: {err}")

ssh.close()
print("FIREWALL RULES ADDED SUCCESSFULLY!")
