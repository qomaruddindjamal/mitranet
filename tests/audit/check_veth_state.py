
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import WEBUI_PASS, WEBUI_USER

import requests

s = requests.Session()
s.post('http://192.168.56.101:8443/login.php', data={'username': WEBUI_USER, 'password': WEBUI_PASS}, allow_redirects=False)

# Check GET /interfaces.php?if=veth0
r = s.get('http://192.168.56.101:8443/interfaces.php?if=veth0')
print('=== GET /interfaces.php?if=veth0 ===')
for line in r.text.splitlines():
    if any(k in line for k in ['name="enable"', 'ipaddr', 'General Configuration', 'label-success', 'label-danger']):
        print(line.strip())

# Test saving interface with enable checked
print('\n=== POST save_interface enable=yes ===')
r_post = s.post('http://192.168.56.101:8443/interfaces.php?if=veth0', data={
    'action': 'save_interface',
    'interface': 'veth0',
    'enable': 'yes',
    'mtu': '1500',
    'ipaddr': '192.168.101.254',
    'subnet': '24'
})
print('POST response status:', r_post.status_code)
for line in r_post.text.splitlines():
    if any(k in line for k in ['name="enable"', 'alert', 'Konfigurasi interface']):
        print(line.strip())

# Test saving interface without enable checked (disable)
print('\n=== POST save_interface disabled ===')
r_post_dis = s.post('http://192.168.56.101:8443/interfaces.php?if=veth0', data={
    'action': 'save_interface',
    'interface': 'veth0',
    'mtu': '1500',
    'ipaddr': '192.168.101.254',
    'subnet': '24'
})
for line in r_post_dis.text.splitlines():
    if any(k in line for k in ['name="enable"', 'alert', 'Konfigurasi interface']):
        print(line.strip())

# Re-enable
print('\n=== POST save_interface re-enable ===')
r_post_re = s.post('http://192.168.56.101:8443/interfaces.php?if=veth0', data={
    'action': 'save_interface',
    'interface': 'veth0',
    'enable': 'yes',
    'mtu': '1500',
    'ipaddr': '192.168.101.254',
    'subnet': '24'
})
for line in r_post_re.text.splitlines():
    if any(k in line for k in ['name="enable"', 'alert', 'Konfigurasi interface']):
        print(line.strip())
