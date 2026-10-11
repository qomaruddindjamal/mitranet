import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

# Run PHP directly from CLI with simulated session
php_code = """
session_start();
$_SESSION['username'] = 'admin';
$_SESSION['csrf_token'] = 'dummy';
$_SERVER['DOCUMENT_ROOT'] = '/mitranet/web';
$_SERVER['REQUEST_URI'] = '/interfaces/interfaces.php?tab={tab}';
$_GET['tab'] = '{tab}';
ob_start();
include '/mitranet/web/interfaces/interfaces.php';
$html = ob_get_clean();
echo "TAB: {tab}\\n";
echo "Length: " . strlen($html) . "\\n";
echo "Has check icon: " . (strpos($html, 'fa-check text-success') !== false ? 'YES' : 'NO') . "\\n";
echo "Has minus icon: " . (strpos($html, 'fa-minus text-muted') !== false ? 'YES' : 'NO') . "\\n";
foreach (['vxlan100', 'gre-vps', 'ipip-vps', 'eoip-vps'] as $if) {
    if (strpos($html, $if) !== false) {
        echo "Found interface: $if\\n";
    }
}
echo "\\n";
"""

for tab in ['interface', 'vxlan', 'gre', 'iptunnel', 'eoip']:
    code = php_code.replace('{tab}', tab)
    stdin, stdout, stderr = ssh.exec_command(f"php -r {repr(code)}")
    print(stdout.read().decode())
    err = stderr.read().decode()
    if err:
        print("STDERR:", err)

ssh.close()
