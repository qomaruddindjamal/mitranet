import paramiko
import json

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=10)

def run(c):
    stdin, stdout, stderr = ssh.exec_command(c)
    return stdout.read().decode('utf-8', errors='ignore').strip()

print("=== BOOSTER CONFIG IN MINI PC ===")
out = run('cat /etc/mitranet/secrets/booster_config.json')
try:
    d = json.loads(out)
    print("Stream Keys:")
    print(json.dumps(d.get('stream_keys', {}), indent=2))
except Exception:
    print(out)

ssh.close()
