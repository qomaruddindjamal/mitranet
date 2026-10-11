import requests, re, urllib3, bs4
urllib3.disable_warnings()

s = requests.Session()
s.verify = False

base = 'https://10.10.66.47'
r1 = s.get(base + '/')
m = re.search(r'var\s+csrfMagicToken\s*=\s*"([^"]+)"', r1.text)
csrf = m.group(1) if m else ''

data = {'__csrf_magic': csrf, 'usernamefld': 'admin', 'passwordfld': 'pfsense', 'login': 'Sign In'}
s.post(base + '/index.php', data=data)

r_p1 = s.get(base + '/wg/vpn_wg_peers_edit.php?peer=1')
soup = bs4.BeautifulSoup(r_p1.text, 'html.parser')
print("=== Peer 1 Fields ===")
for inp in soup.find_all(['input', 'select']):
    if inp.get('name') and inp.get('value'):
        print(inp.get('name'), '=', inp.get('value'))
