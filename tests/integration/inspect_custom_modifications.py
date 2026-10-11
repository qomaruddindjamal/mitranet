import urllib.request
import base64
import ssl
import re

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

auth = base64.b64encode(b'admin:pfsense').decode()
headers = {'Authorization': f'Basic {auth}'}

custom_pages = [
    'tools_speedtest.php',
    'services_virtual.php',
    'diag_terminal.php',
    'interfaces_wifi.php',
    'vpn_xray.php',
    'wg/vpn_wg_tunnels.php'
]

for p in custom_pages:
    url = f"https://10.10.66.47/{p}"
    try:
        req = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(req, timeout=5, context=ctx) as r:
            body = r.read().decode('utf-8', errors='ignore')
            print(f"\n=== PAGE: {p} ({len(body)} bytes) ===")
            # Look for scripts, forms, ajax endpoints
            ajax_endpoints = re.findall(r'url:\s*[\'"]([^\'"]+)[\'"]', body)
            forms = re.findall(r'<form[^>]*action=[\'"]([^\'"]*)[\'"][^>]*method=[\'"]([^\'"]*)[\'"]', body)
            buttons = re.findall(r'<button[^>]*name=[\'"]([^\'"]+)[\'"][^>]*value=[\'"]([^\'"]*)[\'"]', body)
            print("  AJAX calls:", set(ajax_endpoints))
            print("  Forms:", forms[:3])
            print("  Buttons:", buttons[:5])
    except Exception as e:
        print(f"Error {p}: {e}")
