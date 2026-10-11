
import os as _os, sys as _sys
_d = _os.path.dirname(_os.path.abspath(__file__))
while not _os.path.exists(_os.path.join(_d, "mitranet_secrets.py")):
    _p = _os.path.dirname(_d)
    if _p == _d: break
    _d = _p
_sys.path.insert(0, _d)
from mitranet_secrets import WEBUI_PASS, WEBUI_USER

import requests

def test():
    s = requests.Session()
    r_login = s.post('http://192.168.56.101:8443/login.php', data={'username': WEBUI_USER, 'password': WEBUI_PASS}, allow_redirects=True)
    print('LOGIN CODE:', r_login.status_code)

    r_api = s.get('http://192.168.56.101:8443/api/v1/services/kvm')
    print('API STATUS:', r_api.status_code)
    vms = r_api.json().get('vms', [])
    for vm in vms:
        if vm.get('id') == 'aapanel':
            print('AAPANEL CREDS IN KVM API:', vm.get('aapanel_creds'))

    r_page = s.get('http://192.168.56.101:8443/services_virtual.php?act=manage_aapanel')
    html = r_page.text
    print('Page status:', r_page.status_code)
    print('Card Header present:', 'Kredensial & Akses Masuk aaPanel' in html)
    print('URL input present:', 'aapanel_url_input' in html)
    print('Username input present:', 'aapanel_user_input' in html)
    print('Password input present:', 'aapanel_pass_input' in html)
    print('mitranet123 in html:', 'mitranet123' in html)
    print('mitranet username in html:', 'value="mitranet"' in html)

if __name__ == '__main__':
    test()
