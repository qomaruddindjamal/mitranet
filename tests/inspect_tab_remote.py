
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import MINIPC_HOST, MINIPC_PASS, MINIPC_USER, WEBUI_PASS, WEBUI_USER

import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(MINIPC_HOST, username=MINIPC_USER, password=MINIPC_PASS, timeout=5)

sftp = client.open_sftp()
with sftp.file('/tmp/inspect_tab.py', 'w') as f:
    f.write('''
import http.cookiejar, urllib.request, urllib.parse

cj = http.cookiejar.CookieJar()
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# Login
login_data = urllib.parse.urlencode({'username': WEBUI_USER, 'password': WEBUI_PASS}).encode()
op.open('http://127.0.0.1:8000/login.php', login_data)

# Get tab=macvlan
res = op.open('http://127.0.0.1:8000/interfaces/interfaces.php?tab=macvlan').read().decode('utf-8', 'ignore')

print("--- ROWS IN TAB MACVLAN ---")
found_row = False
for line in res.splitlines():
    if 'data-ifname' in line or 'No interfaces found' in line:
        print(line.strip())
        found_row = True
if not found_row:
    print("NO ROWS FOUND AT ALL")
''')
sftp.close()

stdin, stdout, stderr = client.exec_command('python3 /tmp/inspect_tab.py')
print(stdout.read().decode())
client.close()
