import paramiko
import time

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

stdin, stdout, stderr = ssh.exec_command('/interface print detail where name~"wg" without-paging')
print("=== VPS INTERFACE DETAILS ===")
print(stdout.read().decode())

stdin, stdout, stderr = ssh.exec_command('/interface/wireguard/peers/print detail where interface~"wg-boost" without-paging')
print("=== VPS PEER DETAILS ===")
print(stdout.read().decode())

ssh.close()
