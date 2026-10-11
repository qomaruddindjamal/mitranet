import re
import json

with open('pfsense_dashboard_real.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Extract aside#pf-sidebar
m = re.search(r'<aside id="pf-sidebar".*?</aside>', html, re.DOTALL)
if not m:
    print("Could not find aside#pf-sidebar")
    exit(1)

sidebar_html = m.group(0)

# Extract items
# Structure is:
# <li class="sidebar-item ...">
#   direct link: <a href="/url" class="sidebar-link direct-link" title="Title"><i ...></i><span>Title</span></a>
#   or flyout menu:
#   <a href="#sub-xxx" class="sidebar-link" ...><i ...></i><span>Section</span></a>
#   <div id="sub-xxx" class="sidebar-flyout">...<ul class="sidebar-submenu"><li><a href="..." ...>Name</a></li>...</ul></div>

items = re.findall(r'<li class="sidebar-item\s*[^"]*">(.*?)</li>\s*(?=<li class="sidebar-item|<ul class="sidebar-menu"|</ul>)', sidebar_html, re.DOTALL)
print(f"Total sidebar items found: {len(items)}")

sidebar_data = []

for it in items:
    # Check if direct link
    direct_m = re.search(r'<a\s+href="([^"]+)"\s+class="sidebar-link direct-link"[^>]*title="([^"]+)"', it)
    if direct_m:
        url = direct_m.group(1)
        name = direct_m.group(2)
        icon_m = re.search(r'<i\s+class="([^"]+)"', it)
        icon = icon_m.group(1) if icon_m else ''
        sidebar_data.append({
            'type': 'direct',
            'name': name,
            'url': url,
            'icon': icon
        })
        continue

    # Flyout
    flyout_head = re.search(r'<div class="flyout-header">.*?<span>(.*?)</span>', it, re.DOTALL)
    if flyout_head:
        sec_name = flyout_head.group(1).strip()
        icon_m = re.search(r'<a href="#sub-[^"]*".*?<i\s+class="([^"]+)"', it, re.DOTALL)
        icon = icon_m.group(1) if icon_m else ''
        
        submenu_items = []
        for a in re.findall(r'<a\s+href="([^"]+)"\s+class="navlnk"[^>]*>(.*?)</a>', it, re.DOTALL):
            sub_url = a[0]
            sub_name = re.sub(r'<[^>]+>', '', a[1]).strip()
            submenu_items.append({
                'name': sub_name,
                'url': sub_url
            })
        
        sidebar_data.append({
            'type': 'menu',
            'name': sec_name,
            'icon': icon,
            'submenus': submenu_items
        })

print("\n=== COMPLETE PFSENSE 2.9 LIVE SIDEBAR AUDIT ===")
for entry in sidebar_data:
    if entry['type'] == 'direct':
        print(f"[*] DIRECT: {entry['name']} -> {entry['url']} ({entry['icon']})")
    else:
        print(f"[*] MENU: {entry['name']} ({len(entry['submenus'])} items) ({entry['icon']})")
        for sub in entry['submenus']:
            print(f"    |-- {sub['name']} -> {sub['url']}")

with open('pfsense_sidebar_complete_audit.json', 'w', encoding='utf-8') as f:
    json.dump(sidebar_data, f, indent=2)

print("\nSaved pfsense_sidebar_complete_audit.json")
