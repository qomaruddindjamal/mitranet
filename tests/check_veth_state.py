import requests

s = requests.Session()
s.post('http://192.168.56.101:8443/login.php', data={'username': 'admin', 'password': 'mitranet'}, allow_redirects=False)

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
