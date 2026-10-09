import requests
import re
import os
import urllib3
urllib3.disable_warnings()

s = requests.Session()
s.verify = False

base = 'https://10.10.66.47'
r1 = s.get(base + '/')
m = re.search(r'var\s+csrfMagicToken\s*=\s*"([^"]+)"', r1.text)
csrf = m.group(1) if m else ''

data = {
    '__csrf_magic': csrf,
    'usernamefld': 'admin',
    'passwordfld': 'pfsense',
    'login': 'Sign In'
}

r2 = s.post(base + '/index.php', data=data)
if 'passwordfld' in r2.text and 'Sign In' in r2.text:
    print("FAILED TO LOG IN")
    exit(1)

print("LOGGED IN SUCCESSFULLY TO PFSENSE!")

urls = [
    '/system_advanced_admin.php',
    '/system_camanager.php',
    '/system.php',
    '/system_hasync.php',
    '/pkg_mgr_installed.php',
    '/system_register.php',
    '/system_gateways.php',
    '/wizard.php?xml=setup_wizard.xml',
    '/pkg_mgr_install.php?id=firmware',
    '/system_usermanager.php',
    '/system_usermanager_passwordmg.php'
]

os.makedirs('pfsense_dumps/system', exist_ok=True)

for u in urls:
    r = s.get(base + u)
    fname = u.replace('/', '_').replace('?', '_').replace('=', '_') + '.html'
    fpath = os.path.join('pfsense_dumps/system', fname)
    with open(fpath, 'w', encoding='utf-8') as out:
        out.write(r.text)
    title_m = re.search(r'<title>(.*?)</title>', r.text, re.IGNORECASE)
    title = title_m.group(1) if title_m else 'No title'
    print(f"{u:38} -> HTTP {r.status_code}, len={len(r.text)}, title='{title.strip()}'")
