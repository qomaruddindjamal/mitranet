
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import WEBUI_PASS, WEBUI_USER

import urllib.request
import urllib.parse
import http.cookiejar
import re

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

# 1. Login to WebUI
login_data = urllib.parse.urlencode({'username': WEBUI_USER, 'password': WEBUI_PASS}).encode()
opener.open('http://127.0.0.1:8000/login.php', login_data)

# 2. Fetch MACVLAN tab
resp = opener.open('http://127.0.0.1:8000/interfaces/interfaces.php?tab=macvlan')
html = resp.read().decode('utf-8', errors='ignore')

matches = re.findall(r'<tr[^>]*data-ifname="([^"]+)"[^>]*>', html)
print("Grid Rows Detected in Tab MACVLAN:", matches)
if 'macvlan0' in html:
    print("SUCCESS: macvlan0 ditemukan dalam tabel WebUI!")
else:
    print("WARNING: macvlan0 TIDAK ditemukan dalam tabel WebUI.")
