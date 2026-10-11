import paramiko
import json

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

stdin, stdout, stderr = ssh.exec_command('curl -s http://127.0.0.1:8443/api/v1/interfaces')
out = stdout.read().decode()
try:
    data = json.loads(out)
    for d in data:
        if d.get('name') in ['vxlan100', 'gre-vps', 'ipip-vps', 'eoip-vps', 'enp1s0']:
            print(f"{d['name']}: type={d.get('type')}, is_up={d.get('is_up')}, oper={d.get('oper_state')}, ip={d.get('ipv4_addresses')}")
except Exception as e:
    print("Error:", e, out[:200])

ssh.close()
