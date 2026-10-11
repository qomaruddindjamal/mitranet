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

# 1. GET login page
resp = opener.open('https://10.10.66.47/')
html = resp.read().decode('utf-8')
m = re.search(r'name=[\'"]__csrf_magic[\'"]\s+value=[\'"]([^\'"]+)[\'"]', html)
csrf = m.group(1) if m else ''
print('CSRF token found:', bool(csrf))

# 2. POST login
post_data = urllib.parse.urlencode({
    '__csrf_magic': csrf,
    'usernamefld': 'admin',
    'passwordfld': 'pfsense',
    'login': 'Sign In'
}).encode('utf-8')

req = urllib.request.Request('https://10.10.66.47/', data=post_data, headers={'User-Agent': 'Mozilla/5.0'})
resp2 = opener.open(req)
html2 = resp2.read().decode('utf-8')
print('After login URL:', resp2.geturl())
print('Has dashboard title:', 'Dashboard' in html2)
with open('c:/mitranet/pfsense_dashboard_live.html', 'w', encoding='utf-8') as f:
    f.write(html2)
print('Saved live dashboard to c:/mitranet/pfsense_dashboard_live.html')
