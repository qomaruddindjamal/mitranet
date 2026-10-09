import json

with open('pfsense_sidebar_complete_audit.json', 'r', encoding='utf-8') as f:
    d = json.load(f)

if isinstance(d, list):
    for cat in d:
        if cat.get('title') == 'System':
            sys_subs = cat.get('submenus', [])
            print(f"Total submenus in System: {len(sys_subs)}")
            for s in sys_subs:
                print(f"{s['title']:28} -> {s['url']}")
elif isinstance(d, dict):
    sys_subs = d.get('System', {}).get('submenus', [])
    print(f"Total submenus in System: {len(sys_subs)}")
    for s in sys_subs:
        print(f"{s['title']:28} -> {s['url']}")

