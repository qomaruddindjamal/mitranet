import requests
import re
import urllib3
urllib3.disable_warnings()

s = requests.Session()
s.verify = False

base = 'https://10.10.66.47'
r1 = s.get(base + '/login.php')
m = re.search(r'name=[\'"]__csrf_magic[\'"]\s+value=[\'"]([^\'"]+)[\'"]', r1.text)
csrf = m.group(1) if m else ''
print("Found CSRF magic:", csrf[:30] if csrf else 'NONE')

login_data = {
    '__csrf_magic': csrf,
    'usernamefld': 'admin',
    'passwordfld': 'pfsense',
    'login': 'Sign In'
}

r2 = s.post(base + '/login.php', data=login_data, allow_redirects=True)
print("Login result status:", r2.status_code, "URL:", r2.url)

if 'Sign In' in r2.text and 'passwordfld' in r2.text:
    print("LOGIN FAILED!")
else:
    print("LOGIN SUCCESSFUL!")
    with open('pfsense_authenticated_dashboard.html', 'w', encoding='utf-8') as f:
        f.write(r2.text)

    # 1. Fetch Installed Packages
    r_pkg = s.get(base + '/pkg_mgr_installed.php')
    with open('pfsense_authenticated_pkg_installed.html', 'w', encoding='utf-8') as f:
        f.write(r_pkg.text)
    print("Saved pkg_mgr_installed.html")

    # 2. Extract entire pfSense navigation menus and sub-menus
    # Find all dropdowns or nav elements
    nav_matches = re.findall(r'<li[^>]*class=[\'"][^\'"]*dropdown[^\'"]*[\'"][^>]*>.*?<a[^>]*>(.*?)</a>.*?<ul class=[\'"]dropdown-menu[\'"]>(.*?)</ul>', r2.text, re.DOTALL)
    print(f"\n=== EXTRACTED MENU STRUCTURE ({len(nav_matches)} Main Menus) ===")
    menu_dict = {}
    for menu_title, submenu_html in nav_matches:
        clean_title = re.sub(r'<[^>]+>', '', menu_title).strip()
        items = re.findall(r'<a\s+href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)</a>', submenu_html, re.DOTALL)
        clean_items = [(re.sub(r'<[^>]+>', '', name).strip(), href) for href, name in items if name.strip()]
        menu_dict[clean_title] = clean_items
        print(f"\n[{clean_title}] ({len(clean_items)} submenus)")
        for name, href in clean_items:
            print(f"  ├── {name} -> {href}")

    import json
    with open('pfsense_menus_extracted.json', 'w', encoding='utf-8') as f:
        json.dump(menu_dict, f, indent=2)

    # Extract packages from pkg_mgr_installed
    print("\n=== EXTRACTED INSTALLED PACKAGES ===")
    for tr in re.findall(r'<tr[^>]*>(.*?)</tr>', r_pkg.text, re.DOTALL):
        tds = re.findall(r'<td[^>]*>(.*?)</td>', tr, re.DOTALL)
        if tds:
            clean_tds = [re.sub(r'<[^>]+>', '', c).strip() for c in tds]
            clean_tds = [' '.join(c.split()) for c in clean_tds if c]
            if clean_tds:
                print("PKG ROW:", " | ".join(clean_tds))
