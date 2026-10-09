import paramiko, re

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('192.168.56.101', port=22, username='root', password='mitranet')

# Login to get cookie
login_cmd = """python3 -c "import urllib.request, json
d = json.dumps({'username':'admin', 'password':'mitranet'}).encode()
r = urllib.request.Request('http://127.0.0.1:8443/api/v1/auth/login', data=d, headers={'Content-Type':'application/json'})
resp = urllib.request.urlopen(r)
print(resp.headers.get('Set-Cookie',''))"
"""
stdin, stdout, stderr = client.exec_command(login_cmd)
cookie_header = stdout.read().decode().strip()
cookie_val = [p for p in cookie_header.split(';') if 'mitranet_session=' in p][0].strip()

# Check services_virtual.php?act=add
stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/services_virtual.php?act=add')
html = stdout.read().decode()

optgroups = re.findall(r'<optgroup label="([^"]+)">', html)
print('KVM Network optgroups in HTML:\n', optgroups)

options = re.findall(r'<option value="([^"]+)">\s*([^<]+)\s*</option>', html)
print('\nRelevant options found:')
for val, lbl in options:
    if any(k in val for k in ['veth', 'enp', 'br', 'vlan']):
        print(f"  [{val}] -> {lbl.strip()}")

client.close()
