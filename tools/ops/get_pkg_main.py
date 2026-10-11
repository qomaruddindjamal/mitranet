import re

with open('pfsense_pkg_installed_real.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Let's find where the body content starts
m = re.search(r'<div id="maincontent".*?(?=</footer>)', html, re.DOTALL)
if not m:
    m = re.search(r'<div class="panel panel-default".*?(?=</footer>)', html, re.DOTALL)

if m:
    main_snippet = m.group(0)
    print("Main snippet:\n", main_snippet[:4000])
else:
    print("Searching for any panel...")
    for p in re.findall(r'<div class="panel[^"]*".*?</div>\s*</div>', html, re.DOTALL):
        print("Panel:\n", p[:1000])
