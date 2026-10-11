import json
import glob
import os

with open('pfsense_sidebar_complete_audit.json', 'r') as f:
    sidebar = json.load(f)

local_web_files = set(os.path.basename(f) for f in glob.glob('web/*.php'))
local_wg_files = set(os.path.basename(f) for f in glob.glob('web/wg/*.php'))

missing_targets = []
for entry in sidebar:
    if entry['type'] == 'direct':
        url = entry['url']
        fname = os.path.basename(url.split('?')[0])
        if fname not in local_web_files and fname not in local_wg_files:
            missing_targets.append({
                'category': 'DIRECT',
                'name': entry['name'],
                'url': url,
                'file': fname
            })
    else:
        for sub in entry['submenus']:
            url = sub['url']
            fname = os.path.basename(url.split('?')[0])
            if fname not in local_web_files and fname not in local_wg_files:
                missing_targets.append({
                    'category': entry['name'],
                    'name': sub['name'],
                    'url': url,
                    'file': fname
                })

print(f"Total missing targets to implement: {len(missing_targets)}")
for t in missing_targets:
    print(f"[{t['category']}] {t['name']} -> {t['file']}")

with open('missing_targets.json', 'w', encoding='utf-8') as f:
    json.dump(missing_targets, f, indent=2)
