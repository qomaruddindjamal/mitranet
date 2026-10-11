import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = ssh.open_sftp()
php_script = """<?php
session_start();
$_SESSION["username"] = "admin";
$_SESSION["csrf_token"] = "dummy";
$_SERVER["DOCUMENT_ROOT"] = "/mitranet/web";
$tabs = ["interface", "vxlan", "gre", "iptunnel", "eoip"];
foreach ($tabs as $t) {
    $_GET["tab"] = $t;
    ob_start();
    include "/mitranet/web/interfaces/interfaces.php";
    $out = ob_get_clean();
    echo "=== TAB: $t ===\\n";
    echo "Length: " . strlen($out) . "\\n";
    echo "Has check: " . (strpos($out, "fa-check text-success") !== false ? "YES" : "NO") . "\\n";
    echo "Has minus: " . (strpos($out, "fa-minus text-muted") !== false ? "YES" : "NO") . "\\n";
    foreach (["vxlan100", "gre-vps", "ipip-vps", "eoip-vps"] as $ifn) {
        if (strpos($out, $ifn) !== false) {
            echo "  Visible: $ifn\\n";
        }
    }
}
"""

with sftp.file('/tmp/test_render.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = ssh.exec_command('php /tmp/test_render.php')
print(stdout.read().decode())
err = stderr.read().decode()
if err:
    print('ERR:', err)
ssh.close()
