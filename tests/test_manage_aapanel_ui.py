import requests

def test():
    s = requests.Session()
    r_login = s.post('http://192.168.56.101:8443/login.php', data={'username': 'admin', 'password': 'mitranet'}, allow_redirects=True)
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
