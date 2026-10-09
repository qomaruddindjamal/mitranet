import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = client.open_sftp()
with sftp.file('/tmp/inspect_tab.py', 'w') as f:
    f.write('''
import http.cookiejar, urllib.request, urllib.parse

cj = http.cookiejar.CookieJar()
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# Login
login_data = urllib.parse.urlencode({'username': 'admin', 'password': 'mitranet'}).encode()
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
