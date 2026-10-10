import paramiko
import json

# Mini PC client keys
# Stream 1 pubkey: ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o=
# Stream 2 pubkey: jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc=

# VPS Interfaces:
# wg-boost1 port 51831 public-key: V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8=
# wg-boost2 port 51832 public-key: e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8=

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('103.93.162.168', port=22, username='citramedia', password='K0323205', look_for_keys=False, allow_agent=False, timeout=8)

def run(c):
    stdin, stdout, stderr = ssh.exec_command(c)
    return stdout.read().decode('utf-8', errors='ignore').strip()

print("--- VPS Status ---")
print("Peers:\n", run('/interface wireguard peers print detail'))
ssh.close()
