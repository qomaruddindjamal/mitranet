import urllib.request, ssl, re

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

req = urllib.request.Request('http://192.168.56.101:8443/api/v1/bridges', headers={'Authorization': 'Bearer mitranet_secure_token'})
try:
    with urllib.request.urlopen(req) as resp:
        print('API Bridges:', resp.read().decode())
except Exception as e:
    print('API Bridges err:', e)

try:
    with urllib.request.urlopen('http://192.168.56.101:8443/interfaces_bridge.php') as resp:
        html = resp.read().decode()
        if 'No bridges configured' in html:
            print('Web: No bridges configured found (veth0 excluded successfully!)')
        else:
            print('Web: Bridges table has items')

        selects = re.findall(r'<option value="([^"]+)">([^<]+)</option>', html)
        print('Options in select:', selects)
except Exception as e:
    print('Web err:', e)
