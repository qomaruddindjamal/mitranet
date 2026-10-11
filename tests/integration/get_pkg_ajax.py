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

# Get /pkg_mgr_installed.php to get fresh csrf token
r_page = s.get(base + '/pkg_mgr_installed.php')
m2 = re.search(r'var\s+csrfMagicToken\s*=\s*"([^"]+)"', r_page.text)
csrf2 = m2.group(1) if m2 else ''
print("Fresh CSRF for pkg page:", csrf2[:30])

r_ajax = s.post(base + '/pkg_mgr_installed.php', data={'__csrf_magic': csrf2, 'ajax': 'ajax'})
print("AJAX response status:", r_ajax.status_code, "Length:", len(r_ajax.text))
print("AJAX content:\n", r_ajax.text)
