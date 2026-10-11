import requests
import re
import json
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

r_log = s.post(base + '/index.php', data=data)
if 'passwordfld' in r_log.text:
    print("Login failed")
    exit(1)

with open('pfsense_sidebar_complete_audit.json', 'r') as f:
    sidebar = json.load(f)

urls = []
for entry in sidebar:
    if entry['type'] == 'direct':
        urls.append((entry['name'], entry['url']))
    else:
        for sub in entry['submenus']:
            urls.append((f"{entry['name']} -> {sub['name']}", sub['url']))

print(f"Fetching authenticated HTML for {len(urls)} pages...")
os.makedirs('tmp/pfsense_pages/wg', exist_ok=True)

success_count = 0
for label, url in urls:
    clean_path = url.split('?')[0].lstrip('/')
    target_url = f"{base}/{clean_path}"
    try:
        r = s.get(target_url, timeout=5)
        if 'passwordfld' in r.text and 'Sign In' in r.text:
            print(f"[AUTH FAIL] {label:<38} -> {clean_path}")
            continue
        save_dest = os.path.join('tmp', 'pfsense_pages', clean_path.replace('/', os.sep))
        os.makedirs(os.path.dirname(save_dest), exist_ok=True)
        with open(save_dest, 'w', encoding='utf-8') as pf:
            pf.write(r.text)
        success_count += 1
        print(f"[OK] {label:<38} -> {clean_path} (HTTP {r.status_code}, {len(r.text)} bytes)")
    except Exception as e:
        print(f"[ERROR] {label:<38} -> {clean_path} ({e})")

print(f"\nAuthenticated fetch complete: {success_count}/{len(urls)} pages saved!")
