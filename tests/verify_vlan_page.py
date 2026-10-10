import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = client.open_sftp()
with sftp.file('/tmp/test_vlan_page.py', 'w') as f:
    f.write('''
import urllib.request, urllib.parse, http.cookiejar, re

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Login
login_data = urllib.parse.urlencode({'username': 'admin', 'password': 'mitranet'}).encode()
opener.open('http://127.0.0.1:8000/login.php', login_data)

# 2. Get VLAN page
resp = opener.open('http://127.0.0.1:8000/interfaces/interfaces.php?tab=vlan')
print('Final URL after redirect:', resp.geturl())
html = resp.read().decode('utf-8', errors='ignore')

# 3. Check tbody & columns
print('=== COLUMNS ===')
headers = [re.sub(r'<[^>]+>', '', x).strip() for x in re.findall(r'<th[^>]*>(.*?)</th>', html, re.DOTALL)]
print('Headers:', headers)

print('=== FIRST ROW CELLS ===')
cells = [re.sub(r'<[^>]+>', '', x).strip() for x in re.findall(r'<td[^>]*>(.*?)</td>', html, re.DOTALL)[:11]]
print('Cells:', cells)

matches = re.findall(r'data-ifname="([^"]+)"', html)
print('Found ifnames:', matches)

if 'enp1s0.10' in matches:
    print('SUCCESS: enp1s0.10 terdeteksi dalam tabel dengan format kolom VLAN!')
else:
    print('WARNING: enp1s0.10 TIDAK terdeteksi dalam tabel!')
''')
sftp.close()

stdin, stdout, stderr = client.exec_command('python3 /tmp/test_vlan_page.py')
print(stdout.read().decode())
client.close()
