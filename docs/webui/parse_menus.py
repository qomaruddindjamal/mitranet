import re
import json

head_path = r'C:\mitranet\build\pfsense_extracted\usr\local\www\head.inc'
with open(head_path, 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

menu_names = ['system', 'interfaces', 'firewall', 'services', 'vpn', 'status', 'diagnostics']
menus = {}

for name in menu_names:
    var_str = f'${name}_menu'
    pos = text.find(var_str + ' = array();')
    if pos == -1:
        pos = text.find(var_str + ' = [];')
    if pos == -1:
        continue
    
    end_pos = text.find(f'{var_str} = msort(', pos)
    block = text[pos:end_pos]
    
    items = []
    for line in block.splitlines():
        line = line.strip()
        if not line or line.startswith('//') or line.startswith('/*'):
            continue
        # Search for string inside gettext("...") and string inside "/..."
        m = re.search(r'gettext\(["\']([^"\']+)["\']\)\)?,\s*["\']([^"\']+)["\']', line)
        if m:
            items.append({'title': m.group(1), 'url': m.group(2)})
    menus[name] = items

for k, v in menus.items():
    print(f'=== {k.upper()} ({len(v)} items) ===')
    for item in v:
        print(f"  [{item['title']}] -> {item['url']}")

with open(r'C:\mitranet\docs\webui\PFSENSE-MENU-HIERARCHY.json', 'w') as out:
    json.dump(menus, out, indent=2)
