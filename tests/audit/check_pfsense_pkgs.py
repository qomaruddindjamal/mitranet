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
m = re.search(r'name=[\'"]__csrf_magic[\'"]\s+value=[\'"]([^\'"]+)[\'"]', html)
csrf = m.group(1) if m else ''

post_data = urllib.parse.urlencode({
    '__csrf_magic': csrf,
    'usernamefld': 'admin',
    'passwordfld': 'pfsense',
    'login': 'Sign In'
}).encode('utf-8')

req = urllib.request.Request('https://10.10.66.47/', data=post_data, headers={'User-Agent': 'Mozilla/5.0'})
opener.open(req)

for url in [
    '/pkg_mgr_installed.php',
    '/wg/vpn_wg_tunnels.php',
    '/wg/vpn_wg_peers.php',
    '/wg/vpn_wg_settings.php',
    '/status_wireguard.php',
    '/vpn_xray.php'
]:
    try:
        r = opener.open('https://10.10.66.47' + url)
        content = r.read().decode('utf-8')
        print(url, 'Status:', r.status, 'len:', len(content), 'Title:', re.findall(r'<title>(.*?)</title>', content))
        clean_name = url.replace('/', '_').replace('.php', '')
        with open(f'c:/mitranet/pfsense{clean_name}.html', 'w', encoding='utf-8') as f:
            f.write(content)
    except Exception as e:
        print(url, 'ERR:', e)
