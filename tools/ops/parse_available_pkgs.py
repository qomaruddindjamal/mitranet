import re
import json

with open('pfsense_pkg_available_ajax.html', 'r', encoding='utf-8') as f:
    html = f.read()

rows = re.findall(r'<tr>(.*?)</tr>', html, re.DOTALL)
print(f"Total package rows found: {len(rows)}")

pkgs = []
for r in rows:
    tds = re.findall(r'<td[^>]*>(.*?)</td>', r, re.DOTALL)
    if len(tds) >= 3:
        name = re.sub(r'<[^>]+>', '', tds[0]).strip()
        version = re.sub(r'<[^>]+>', '', tds[1]).strip()
        desc_full = re.sub(r'<[^>]+>', ' ', tds[2]).strip()
        desc_clean = ' '.join(desc_full.split())
        if name and version:
            pkgs.append({
                'name': name,
                'version': version,
                'description': desc_clean
            })

print(f"Parsed {len(pkgs)} packages:")
for p in pkgs:
    print(f" - {p['name']} (v{p['version']}): {p['description'][:70]}...")

with open('pfsense_all_available_packages.json', 'w', encoding='utf-8') as f:
    json.dump(pkgs, f, indent=2)

print("\nSaved pfsense_all_available_packages.json")
