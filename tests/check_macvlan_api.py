import paramiko
import json

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

cmd = 'curl -s -k http://127.0.0.1:8443/api/interfaces -H "X-MitraNet-Key: rinjani-dev-secret"'
stdin, stdout, stderr = client.exec_command(cmd)
raw = stdout.read().decode('utf-8')
client.close()

try:
    data = json.loads(raw)
    ifaces = data.get('data', [])
    print(f"Total interfaces from API: {len(ifaces)}")
    for i in ifaces:
        name = i.get('name', '')
        itype = i.get('type', '')
        if 'mac' in name.lower() or 'mac' in itype.lower():
            print(f"-> Found: {name} (type: {itype}, ip: {i.get('ip')}, mac: {i.get('mac')})")
except Exception as e:
    print("Error parsing JSON:", e)
    print("Raw output snippet:", raw[:300])
