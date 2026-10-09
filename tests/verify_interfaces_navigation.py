import requests

s = requests.Session()
s.post('http://192.168.56.101:8443/login.php', data={'username': 'admin', 'password': 'mitranet'}, allow_redirects=False)

print('=== 1. TEST SIDEBAR INTERFACES LINK ===')
r_home = s.get('http://192.168.56.101:8443/index.php')
for line in r_home.text.splitlines():
    if 'Interfaces' in line and 'interfaces_assign.php' in line:
        print('Found direct link:', line.strip())
    if 'sub-interfaces' in line:
        print('UNEXPECTED sub-interfaces found:', line.strip())

print('\n=== 2. TEST SAVE INTERFACE REDIRECT TO INTERFACES_ASSIGN.PHP ===')
r_save = s.post('http://192.168.56.101:8443/interfaces.php?if=veth0', data={
    'action': 'save_interface',
    'interface': 'veth0',
    'enable': 'yes',
    'mtu': '1500',
    'ipaddr': '192.168.101.254',
    'subnet': '24'
}, allow_redirects=False)
print('Save status code:', r_save.status_code)
print('Location header:', r_save.headers.get('Location'))

print('\n=== 3. TEST FOLLOW REDIRECT TO INTERFACES_ASSIGN.PHP ===')
r_dest = s.get('http://192.168.56.101:8443/' + r_save.headers.get('Location'))
print('Destination status:', r_dest.status_code)
for line in r_dest.text.splitlines():
    if any(k in line for k in ['alert-success', 'Konfigurasi interface', 'Interface Assignments', 'NO-CARRIER', 'veth0']):
        print('Snippet:', line.strip()[:120])

print('\n=== 4. TEST BACK TO LIST BUTTON ON INTERFACES.PHP?IF=VETH0 ===')
r_edit = s.get('http://192.168.56.101:8443/interfaces.php?if=veth0')
for line in r_edit.text.splitlines():
    if 'Back to List' in line:
        print('Back button link:', line.strip())
