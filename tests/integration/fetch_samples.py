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
resp2 = opener.open(req)

for page in ['/system.php', '/system_advanced_admin.php', '/interfaces_assign.php', '/firewall_rules.php', '/status_interfaces.php']:
    try:
        r = opener.open('https://10.10.66.47' + page)
        c = r.read().decode('utf-8')
        name = page.replace('/', '_').replace('.php', '')
        with open(f'c:/mitranet/pfsense{name}.html', 'w', encoding='utf-8') as f:
            f.write(c)
        print('Fetched', page, 'len:', len(c))
    except Exception as e:
        print('Error fetching', page, e)
