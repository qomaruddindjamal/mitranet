import os
import re
import json

www_dir = r'C:\mitranet\build\pfsense_extracted\usr\local\www'
all_tabs = {}

for f in sorted(os.listdir(www_dir)):
    if f.endswith('.php'):
        path = os.path.join(www_dir, f)
        with open(path, 'r', encoding='utf-8', errors='ignore') as fp:
            text = fp.read()
            if '$tab_array' in text and 'display_top_tabs' in text:
                matches = re.findall(r"\$tab_array\[\]\s*=\s*array\([^,]+,\s*(?:true|false|[^,]+),\s*['\"]([^'\"]+)['\"]", text)
                if matches:
                    all_tabs[f] = matches

print(f'Total pages with tabs: {len(all_tabs)}')
for p, tabs in sorted(all_tabs.items())[:25]:
    print(f'{p}: {tabs}')

with open(r'C:\mitranet\docs\webui\PFSENSE-TABS-MAP.json', 'w') as out:
    json.dump(all_tabs, out, indent=2)
