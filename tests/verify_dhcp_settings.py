
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import LAB_HOST, LAB_PASS, LAB_USER, WEBUI_PASS, WEBUI_USER

import paramiko, re

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(LAB_HOST, port=22, username=LAB_USER, password=LAB_PASS)

# 1. Login to get cookie
login_cmd = """python3 -c "import urllib.request, json
d = json.dumps({'username':WEBUI_USER, 'password':WEBUI_PASS}).encode()
r = urllib.request.Request('http://127.0.0.1:8443/api/v1/auth/login', data=d, headers={'Content-Type':'application/json'})
resp = urllib.request.urlopen(r)
print(resp.headers.get('Set-Cookie',''))"
"""
stdin, stdout, stderr = client.exec_command(login_cmd)
cookie_header = stdout.read().decode().strip()
cookie_val = [p for p in cookie_header.split(';') if 'mitranet_session=' in p][0].strip()

# 2. Check /api/v1/services/dhcp
stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/api/v1/services/dhcp')
print("API /services/dhcp response:\n", stdout.read().decode())

# 3. Check services_dhcp_settings.php page
stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/services_dhcp_settings.php')
html = stdout.read().decode()

if "DHCP Server Configuration for" in html:
    print("SUCCESS: services_dhcp_settings.php rendered properly!")
else:
    print("FAILED: Page did not render properly!")

tabs = re.findall(r'<a href="services_dhcp_settings\.php\?if=([^"]+)">\s*<i[^>]*></i>\s*([^<]+)', html)
print("DHCP Interface Tabs:")
for k, l in tabs:
    print(f"  [{k}] -> {l.strip()}")

client.close()
