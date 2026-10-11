import requests
import re
import urllib3
urllib3.disable_warnings()

s = requests.Session()
s.verify = False

base = 'https://10.10.66.47'
r1 = s.get(base + '/')
m = re.search(r'var\s+csrfMagicToken\s*=\s*"([^"]+)"', r1.text)
csrf = m.group(1) if m else ''

data = {
    '__csrf_magic': csrf,
    'usernamefld': 'admin',
    'passwordfld': 'pfsense',
    'login': 'Sign In'
}

s.post(base + '/index.php', data=data)

# Query /pkg_mgr.php with AJAX
r_page = s.get(base + '/pkg_mgr.php')
m2 = re.search(r'var\s+csrfMagicToken\s*=\s*"([^"]+)"', r_page.text)
csrf2 = m2.group(1) if m2 else ''

r_ajax = s.post(base + '/pkg_mgr.php', data={'__csrf_magic': csrf2, 'ajax': 'ajax'})
print("Available pkgs AJAX response status:", r_ajax.status_code, "Length:", len(r_ajax.text))
print("Available pkgs content snippet:\n", r_ajax.text[:1000])

with open('pfsense_pkg_available_ajax.html', 'w', encoding='utf-8') as f:
    f.write(r_ajax.text)
