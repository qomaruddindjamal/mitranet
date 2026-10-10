import paramiko
import json

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

def run(c):
    stdin, stdout, stderr = ssh.exec_command(c)
    return stdout.read().decode('utf-8', errors='ignore').strip()

print("=== INTERFACES WIREGUARD ===")
print(run('/interface wireguard print'))

print("\n=== IP ADDRESSES ===")
print(run('/ip address print'))

print("\n=== WIREGUARD PEERS ===")
print(run('/interface wireguard peers print'))

ssh.close()
