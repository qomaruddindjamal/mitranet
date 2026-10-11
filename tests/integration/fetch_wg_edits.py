import urllib.request
import urllib.parse
import http.cookiejar
import re

import ssl

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

class CustomHTTPSHandler(urllib.request.HTTPSHandler):
    def __init__(self):
        super().__init__(context=ctx)

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), CustomHTTPSHandler())

resp = opener.open('https://10.10.66.47/')
html = resp.read().decode('utf-8', errors='ignore')
m = re.search(r'name=["\']__csrf_magic["\'].*?value=["\']([^"\']+)["\']', html)
csrf = m.group(1) if m else ''

post_data = urllib.parse.urlencode({
    '__csrf_magic': csrf,
    'usernamefld': 'admin',
    'passwordfld': 'pfsense',
    'login': 'Sign In'
}).encode('utf-8')

opener.open(urllib.request.Request('https://10.10.66.47/', data=post_data))

targets = [
    '/wg/vpn_wg_tunnels_edit.php?tun=tun_wg0',
    '/wg/vpn_wg_peers_edit.php?peer=0',
    '/wg/vpn_wg_tunnels_edit.php',
    '/wg/vpn_wg_peers_edit.php'
]

for p in targets:
    try:
        res = opener.open('https://10.10.66.47' + p)
        content = res.read().decode('utf-8', errors='ignore')
        clean_name = p.replace('/', '_').replace('.php', '').replace('?', '_').replace('=', '_').strip('_')
        with open('c:/mitranet/' + clean_name + '.html', 'w', encoding='utf-8') as f:
            f.write(content)
        print('Saved', clean_name, 'len:', len(content))
    except Exception as e:
        print('Error on', p, e)
