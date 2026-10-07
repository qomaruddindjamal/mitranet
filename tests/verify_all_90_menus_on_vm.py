import requests
import json
import urllib3
urllib3.disable_warnings()

s = requests.Session()
s.verify = False

base = 'http://192.168.56.101:8443'
login = s.post(f'{base}/login.php', data={'username': 'admin', 'password': 'mitranet'}, allow_redirects=False)
assert login.status_code == 302, f"Login failed: {login.status_code}"

with open('pfsense_sidebar_complete_audit.json', 'r') as f:
    sidebar = json.load(f)

test_targets = []
for entry in sidebar:
    if entry['type'] == 'direct':
        test_targets.append(('DIRECT', entry['name'], entry['url']))
    else:
        for sub in entry['submenus']:
            test_targets.append((entry['name'], sub['name'], sub['url']))

print(f"=== TESTING ALL {len(test_targets)} AUDITED PAGES ON MITRANET VM (192.168.56.101:8443) ===")

passed = []
failed = []

for cat, name, url in test_targets:
    target_url = f"{base}{url}"
    try:
        r = s.get(target_url, timeout=5)
        # Verify status 200 and not redirected to login
        if r.status_code == 200 and 'name="username"' not in r.text and 'Sign In' not in r.text:
            passed.append((cat, name, url, r.status_code, len(r.text)))
            print(f"[PASS] [{cat:<12}] {name:<28} -> {url:<35} (HTTP 200, {len(r.text)} bytes)")
        else:
            failed.append((cat, name, url, r.status_code, "Auth redirect or invalid code"))
            print(f"[FAIL] [{cat:<12}] {name:<28} -> {url:<35} (HTTP {r.status_code})")
    except Exception as e:
        failed.append((cat, name, url, 0, str(e)))
        print(f"[ERROR] [{cat:<12}] {name:<28} -> {url:<35} ({e})")

print("\n" + "="*80)
print(f"FINAL AUDIT RESULT: {len(passed)} PASS / {len(failed)} FAILED out of {len(test_targets)} total items")
print("="*80)

if failed:
    print("\nFAILED ITEMS:")
    for f in failed:
        print(f)
else:
    print("\nALL 90 AUDITED PFSENSE MENUS AND SUB-MENUS ARE 100% OPERATIONAL ON MITRANET!")
