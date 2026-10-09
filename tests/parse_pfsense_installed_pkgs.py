import re

with open('pfsense_pkg_installed_real.html', 'r', encoding='utf-8') as f:
    html = f.read()

print("File size:", len(html))

# Extract the table
m = re.search(r'<table class="table table-striped table-hover.*?</table', html, re.DOTALL)
if m:
    table_html = m.group(0)
    rows = re.findall(r'<tr[^>]*>(.*?)</tr>', table_html, re.DOTALL)
    print(f"Found {len(rows)} table rows in pkg_mgr_installed:")
    for r in rows:
        tds = re.findall(r'<td[^>]*>(.*?)</td>', r, re.DOTALL)
        if tds:
            clean = [re.sub(r'<[^>]+>', ' ', td).strip() for td in tds]
            clean = [' '.join(c.split()) for c in clean]
            print(" | ".join(clean))
else:
    print("No standard table found. Checking content snippet:")
    print(html[10000:15000])
