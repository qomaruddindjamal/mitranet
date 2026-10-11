import json

with open('pfsense_sidebar_complete_audit.json', 'r') as f:
    sidebar = json.load(f)

print("="*70)
print("AUDIT STATUS: PFSENSE 2.9 LIVE MENUS vs MITRANET RINJANI WEBUI")
print("="*70)

for item in sidebar:
    if item['type'] == 'direct':
        print(f"\n[DIRECT ITEM] {item['name']} -> {item['url']}")
    else:
        print(f"\n[MENU: {item['name']}] ({len(item['submenus'])} sub-menus)")
        for sub in item['submenus']:
            print(f"   {sub['name']:<28} -> {sub['url']}")
