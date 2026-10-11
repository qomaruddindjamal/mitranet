import urllib.request
import urllib.parse
import http.cookiejar
import ssl
import re

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

class HTTPSHandler(urllib.request.HTTPSHandler):
    def __init__(self):
        super().__init__(context=ctx)

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), HTTPSHandler())

resp = opener.open('https://10.10.66.47/')
html = resp.read().decode('utf-8')
m = re.search(r'name=[\'"]__csrf_magic[\'"].*?value=[\'"]([^\'"]+)[\'"]', html)
csrf = m.group(1) if m else ''

post_data = urllib.parse.urlencode({
    '__csrf_magic': csrf,
    'usernamefld': 'admin',
    'passwordfld': 'pfsense',
    'login': 'Sign In'
}).encode('utf-8')

req = urllib.request.Request('https://10.10.66.47/', data=post_data, headers={'User-Agent': 'Mozilla/5.0'})
opener.open(req)

pages = [
    '/pkg_mgr_installed.php',
    '/pkg_mgr.php',
    '/pkg_mgr_install.php?id=firmware',
    '/vpn_xray.php',
    '/vpn_xray.php?tab=inbounds',
    '/vpn_xray.php?tab=routing',
    '/vpn_xray.php?tab=clients',
    '/vpn_xray.php?tab=config',
    '/vpn_xray.php?tab=logs',
    '/wg/vpn_wg_tunnels.php',
    '/wg/vpn_wg_peers.php',
    '/wg/vpn_wg_settings.php',
    '/wg/status_wireguard.php'
]

for p in pages:
    try:
        res = opener.open('https://10.10.66.47' + p)
        data = res.read().decode('utf-8')
        print(p, 'LEN:', len(data))
        clean_name = p.replace('/', '_').replace('.php', '').replace('?', '_').replace('=', '_')
        with open('c:/mitranet/pfsense' + clean_name + '.html', 'w', encoding='utf-8') as f:
            f.write(data)
    except Exception as e:
        print(p, 'ERR:', e)
