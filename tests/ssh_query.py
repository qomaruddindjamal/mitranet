import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('192.168.56.101', port=22, username='root', password='mitranet')

import json

# Login first to get session cookie
stdin, stdout, stderr = client.exec_command('''python3 -c "
import urllib.request, json
data = json.dumps({'username': 'admin', 'password': 'mitranet'}).encode()
req = urllib.request.Request('http://127.0.0.1:8443/api/v1/auth/login', data=data, headers={'Content-Type': 'application/json'})
try:
    with urllib.request.urlopen(req) as resp:
        cookie = resp.headers.get('Set-Cookie', '')
        print(cookie)
except Exception as e:
    print('LOGIN ERR:', e)
"''')
cookie_header = stdout.read().decode().strip()
print('Cookie:', cookie_header)

# Extract cookie value
cookie_val = ''
for part in cookie_header.split(';'):
    if 'mitranet_session=' in part:
        cookie_val = part.strip()
        break

stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/api/v1/bridges')
print('CURL /api/v1/bridges:\n', stdout.read().decode())

stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/api/v1/interfaces')
print('CURL /api/v1/interfaces:\n', stdout.read().decode())

# Check candidate interfaces in interfaces_bridge.php
stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/interfaces_bridge.php')
html = stdout.read().decode()
if 'No bridges configured' in html:
    print('Web: No bridges configured (veth0 excluded!)')
else:
    print('Web: Configured Bridges table has content')

import re
opts = re.findall(r'<option value="([^"]+)">([^<]+)</option>', html)
print('Candidate select options:', opts)

client.close()
