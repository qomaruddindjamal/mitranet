import requests
import re
import json
import urllib3
urllib3.disable_warnings()

s = requests.Session()
s.verify = False

base = 'https://10.10.66.47'
r1 = s.get(base + '/')
m = re.search(r'var\s+csrfMagicToken\s*=\s*"([^"]+)"', r1.text)
csrf = m.group(1) if m else ''
print("Found CSRF Token:", csrf)

data = {
    '__csrf_magic': csrf,
    'usernamefld': 'admin',
    'passwordfld': 'pfsense',
    'login': 'Sign In'
}

r2 = s.post(base + '/index.php', data=data)
print("Login status:", r2.status_code, "URL:", r2.url)

if 'passwordfld' in r2.text and 'Sign In' in r2.text:
    print("FAILED TO LOG IN")
else:
    print("LOGGED IN! Page size:", len(r2.text))
    with open('pfsense_dashboard_real.html', 'w', encoding='utf-8') as f:
        f.write(r2.text)

    # Fetch pkg_mgr_installed.php
    r_pkg = s.get(base + '/pkg_mgr_installed.php')
    with open('pfsense_pkg_installed_real.html', 'w', encoding='utf-8') as f:
        f.write(r_pkg.text)
    print("Fetched pkg_mgr_installed.php, size:", len(r_pkg.text))

    # Fetch pkg_mgr.php (Available packages)
    r_pkg_avail = s.get(base + '/pkg_mgr.php')
    with open('pfsense_pkg_avail_real.html', 'w', encoding='utf-8') as f:
        f.write(r_pkg_avail.text)
    print("Fetched pkg_mgr.php, size:", len(r_pkg_avail.text))

    # Parse Navigation Menus
    # In pfSense 2.9, look for navbar / sidebar
    # Let's extract all nav elements
    menus = {}
    dropdowns = re.findall(r'<li[^>]*class="[^"]*dropdown[^"]*"[^>]*>(.*?)</li>', r2.text, re.DOTALL)
    for dd in dropdowns:
        title_m = re.search(r'<a[^>]*class="[^"]*dropdown-toggle[^"]*"[^>]*>(.*?)</a>', dd, re.DOTALL)
        if title_m:
            title = re.sub(r'<[^>]+>', '', title_m.group(1)).strip()
            links = re.findall(r'<a[^>]*href="([^"]+)"[^>]*>(.*?)</a>', dd, re.DOTALL)
            clean_links = []
            for href, name in links:
                cname = re.sub(r'<[^>]+>', '', name).strip()
                if cname and href != '#' and not 'dropdown-toggle' in href:
                    clean_links.append({'name': cname, 'url': href})
            if title and clean_links:
                menus[title] = clean_links

    # If sidebar or other menu structure
    if not menus:
        links_all = re.findall(r'<a[^>]*href="([^"]+)"[^>]*>(.*?)</a>', r2.text, re.DOTALL)
        print("Total links on dashboard:", len(links_all))

    with open('pfsense_menus_audited.json', 'w', encoding='utf-8') as f:
        json.dump(menus, f, indent=2)

    print("\nAudited Menus:")
    for m, items in menus.items():
        print(f"[{m}] -> {len(items)} items")
        for it in items:
            print(f"   - {it['name']}: {it['url']}")
