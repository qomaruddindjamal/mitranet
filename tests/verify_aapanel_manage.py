import paramiko, re

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('192.168.56.101', port=22, username='root', password='mitranet')

# 1. Login to get cookie
login_cmd = """python3 -c "import urllib.request, json
d = json.dumps({'username':'admin', 'password':'mitranet'}).encode()
r = urllib.request.Request('http://127.0.0.1:8443/api/v1/auth/login', data=d, headers={'Content-Type':'application/json'})
resp = urllib.request.urlopen(r)
print(resp.headers.get('Set-Cookie',''))"
"""
stdin, stdout, stderr = client.exec_command(login_cmd)
cookie_header = stdout.read().decode().strip()
cookie_val = [p for p in cookie_header.split(';') if 'mitranet_session=' in p][0].strip()

# 2. Check services_virtual.php?act=add - ensure enp0s3 (WAN) is excluded
stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/services_virtual.php?act=add')
html_add = stdout.read().decode()
opts_add = re.findall(r'<option value="([^"]+)">\s*([^<]+)\s*</option>', html_add)
print("=== Interfaces in 'Tambah VM' ===")
for v, l in opts_add:
    if any(k in v for k in ['enp', 'veth', 'br', 'vlan']):
        print(f"  [{v}] {l.strip()}")

# 3. Check services_virtual.php?act=manage_aapanel
stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/services_virtual.php?act=manage_aapanel')
html_manage = stdout.read().decode()
print("\n=== Manage aaPanel Page Check ===")
if "Manage Built-in VM: aaPanel" in html_manage:
    print("SUCCESS: 'Manage Built-in VM: aaPanel' page rendered!")
else:
    print("FAILED: Manage page not found!")

opts_manage = re.findall(r'<option value="([^"]+)"[^>]*>\s*([^<]+)\s*</option>', html_manage)
for v, l in opts_manage:
    if any(k in v for k in ['enp', 'veth', 'br', 'vlan']):
        print(f"  aaPanel interface option: [{v}] {l.strip()}")

# 4. Check main VM list table for 'Config' button
stdin, stdout, stderr = client.exec_command(f'curl -s -b "{cookie_val}" http://127.0.0.1:8443/services_virtual.php')
html_list = stdout.read().decode()
if "act=manage_aapanel" in html_list:
    print("SUCCESS: Config button for aaPanel present in VM list!")
else:
    print("FAILED: Config button missing from VM list!")

client.close()
