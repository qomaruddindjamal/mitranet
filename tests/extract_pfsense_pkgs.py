import urllib.request
import re
import base64
import ssl

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

auth_str = base64.b64encode(b'admin:pfsense').decode('ascii')
req = urllib.request.Request('https://10.10.66.47/pkg_mgr_installed.php', headers={'Authorization': f'Basic {auth_str}'})
with urllib.request.urlopen(req, timeout=5, context=ctx) as resp:
    html = resp.read().decode('utf-8', errors='ignore')

with open('pfsense_pkg_installed_live.html', 'w', encoding='utf-8') as f:
    f.write(html)

print("Saved pfsense_pkg_installed_live.html, size:", len(html))

# Look for table content
clean_text = re.sub(r'<script.*?</script>', '', html, flags=re.DOTALL)
clean_text = re.sub(r'<style.*?</style>', '', clean_text, flags=re.DOTALL)

for tr in re.findall(r'<tr[^>]*>(.*?)</tr>', clean_text, flags=re.DOTALL):
    cells = re.findall(r'<td[^>]*>(.*?)</td>', tr, flags=re.DOTALL)
    if cells:
        clean_cells = [re.sub(r'<[^>]+>', '', c).strip() for c in cells]
        clean_cells = [' '.join(c.split()) for c in clean_cells if c]
        if clean_cells:
            print("ROW:", " | ".join(clean_cells))
