import urllib.request
import base64
import ssl
import json
import os

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

auth = base64.b64encode(b'admin:pfsense').decode()
headers = {'Authorization': f'Basic {auth}'}

with open('pfsense_sidebar_complete_audit.json', 'r') as f:
    sidebar = json.load(f)

urls = []
for entry in sidebar:
    if entry['type'] == 'direct':
        urls.append((entry['name'], entry['url']))
    else:
        for sub in entry['submenus']:
            urls.append((f"{entry['name']} -> {sub['name']}", sub['url']))

print(f"Checking {len(urls)} URLs on live pfSense VM...")
pfsense_pages = {}
os.makedirs('tmp/pfsense_pages/wg', exist_ok=True)

success_count = 0
for label, url in urls:
    clean_path = url.split('?')[0].lstrip('/')
    target_url = f"https://10.10.66.47/{clean_path}"
    try:
        req = urllib.request.Request(target_url, headers=headers)
        with urllib.request.urlopen(req, timeout=4, context=ctx) as r:
            body = r.read().decode('utf-8', errors='ignore')
            pfsense_pages[clean_path] = {
                'status': r.status,
                'length': len(body),
                'label': label
            }
            save_dest = os.path.join('tmp', 'pfsense_pages', clean_path.replace('/', os.sep))
            os.makedirs(os.path.dirname(save_dest), exist_ok=True)
            with open(save_dest, 'w', encoding='utf-8') as pf:
                pf.write(body)
            success_count += 1
            print(f"[OK] {label:<38} -> {clean_path} (HTTP {r.status}, {len(body)} bytes)")
    except Exception as e:
        print(f"[FAIL] {label:<38} -> {clean_path} ({e})")

print(f"\nFinished: {success_count}/{len(urls)} pages successfully fetched from pfSense VM!")
with open('pfsense_fetched_summary.json', 'w', encoding='utf-8') as f:
    json.dump(pfsense_pages, f, indent=2)
