
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import WEBUI_PASS, WEBUI_USER

import requests

s = requests.Session()
r_login = s.post('http://192.168.56.101:8443/login.php', data={'username': WEBUI_USER, 'password': WEBUI_PASS})
print('Login status:', r_login.status_code)

r_post = s.post('http://192.168.56.101:8443/services_virtual.php', data={'act': 'stop', 'id': 'aapanel'})
print('Stop status:', r_post.status_code)
print('Response text:', r_post.text[:1000])
