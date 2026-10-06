import os
import json

src_dir = r'C:\mitranet\build\pfsense_extracted\usr\local\www'
web_dir = r'C:\mitranet\web'

# List all PHP files in source
src_files = [f for f in sorted(os.listdir(src_dir)) if f.endswith('.php')]

# Load menu hierarchy & tabs
with open(r'C:\mitranet\docs\webui\PFSENSE-MENU-HIERARCHY.json', 'r') as f:
    menus = json.load(f)

with open(r'C:\mitranet\docs\webui\PFSENSE-TABS-MAP.json', 'r') as f:
    tabs = json.load(f)

# Collect all URLs referenced in menus
menu_urls = set()
for cat, items in menus.items():
    for item in items:
        clean_url = item['url'].split('?')[0].lstrip('/')
        menu_urls.add(clean_url)

# Audit each source file
inventory = []
for f in src_files:
    in_menu = f in menu_urls
    has_tabs = f in tabs
    exists_mitranet = os.path.exists(os.path.join(web_dir, f))
    
    # Categorize
    status = "NOT_STARTED"
    if exists_mitranet:
        status = "IMPLEMENTED"
        
    entry = {
        'source_file': f,
        'in_menu': in_menu,
        'tabs': tabs.get(f, []),
        'status': status,
        'exists_in_mitranet': exists_mitranet
    }
    inventory.append(entry)

print(f'Total pfSense source PHP files: {len(src_files)}')
print(f'Total menu-referenced PHP files: {len(menu_urls)}')
print(f'Currently implemented in MitraNet: {sum(1 for x in inventory if x["exists_in_mitranet"])}')

with open(r'C:\mitranet\docs\webui\WEBUI-SOURCE-INVENTORY.json', 'w') as out:
    json.dump(inventory, out, indent=2)
