import json
import glob
import os

with open('pfsense_sidebar_complete_audit.json', 'r') as f:
    sidebar = json.load(f)

local_web_files = set(os.path.basename(f) for f in glob.glob('web/*.php'))
local_wg_files = set(os.path.basename(f) for f in glob.glob('web/wg/*.php'))

print(f"Total local web files: {len(local_web_files)}")
print(f"Total local wg files: {len(local_wg_files)}")

results = []

for entry in sidebar:
    if entry['type'] == 'direct':
        url = entry['url']
        fname = os.path.basename(url.split('?')[0])
        exists = (fname in local_web_files) or (fname in local_wg_files)
        results.append({
            'menu': 'DIRECT',
            'sub': entry['name'],
            'url': url,
            'file': fname,
            'status': 'PASS' if exists else 'MISSING'
        })
    else:
        for sub in entry['submenus']:
            url = sub['url']
            fname = os.path.basename(url.split('?')[0])
            exists = (fname in local_web_files) or (fname in local_wg_files)
            results.append({
                'menu': entry['name'],
                'sub': sub['name'],
                'url': url,
                'file': fname,
                'status': 'PASS' if exists else 'MISSING'
            })

passed = [r for r in results if r['status'] == 'PASS']
missing = [r for r in results if r['status'] == 'MISSING']

print("\n" + "="*80)
print(f"AUDIT SUMMARY: {len(passed)} PASS / {len(missing)} MISSING out of {len(results)} total items")
print("="*80)

print("\n=== CURRENTLY IMPLEMENTED (PASS) ===")
for r in passed:
    print(f" [PASS] [{r['menu']}] {r['sub']:<26} -> {r['url']}")

print("\n=== CURRENTLY MISSING (NEEDS IMPLEMENTATION/ROUTING) ===")
for r in missing:
    print(f" [MISSING] [{r['menu']}] {r['sub']:<26} -> {r['url']}")

with open('menu_audit_pass_missing.json', 'w') as f:
    json.dump({'passed': passed, 'missing': missing}, f, indent=2)
