import re
import json

with open('pfsense_dashboard_real.html', 'r', encoding='utf-8') as f:
    html = f.read()

print("HTML size:", len(html))

# Look for sidebar or nav menus
# In pfSense 2.9, it uses <nav ...> or <aside ...> or navbar
navs = re.findall(r'<nav[^>]*>.*?</nav>', html, re.DOTALL)
print("Found nav tags:", len(navs))

asides = re.findall(r'<aside[^>]*>.*?</aside>', html, re.DOTALL)
print("Found aside tags:", len(asides))

# Print header/navigation excerpt
m = re.search(r'(<nav.*?</nav>)', html, re.DOTALL)
if m:
    print("Nav 1 snippet:\n", m.group(1)[:2000])

if asides:
    print("Aside snippet:\n", asides[0][:2000])

# Look for menu items: System, Interfaces, Firewall, Services, VPN, Status, Diagnostics
for section in ['System', 'Interfaces', 'Firewall', 'Services', 'VPN', 'Status', 'Diagnostics']:
    matches = re.findall(rf'<li[^>]*>.*?{section}.*?</li>', html, re.IGNORECASE | re.DOTALL)
    print(f"Matches for {section}: {len(matches)}")
    if matches:
        print(f"Sample {section}:", matches[0][:300])
