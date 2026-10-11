import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

for url in [
    'http://127.0.0.1:8000/interfaces/interfaces.php?tab=interface',
    'http://127.0.0.1:8000/interfaces/interfaces.php?tab=vxlan',
    'http://127.0.0.1:8000/interfaces/interfaces.php?tab=gre',
    'http://127.0.0.1:8000/interfaces/interfaces.php?tab=iptunnel',
    'http://127.0.0.1:8000/interfaces/interfaces.php?tab=eoip'
]:
    stdin, stdout, stderr = ssh.exec_command(f'curl -i -s "{url}"')
    html = stdout.read().decode()
    print(f'{url} -> status line: {html.splitlines()[:2]}')
    has_check = 'fa-check text-success' in html
    has_minus = 'fa-minus text-muted' in html
    print(f'{url} -> len: {len(html)}, check: {has_check}, minus: {has_minus}')
    for iface in ['vxlan100', 'gre-vps', 'ipip-vps', 'eoip-vps']:
        if iface in html:
            print(f'   found {iface}')

ssh.close()
