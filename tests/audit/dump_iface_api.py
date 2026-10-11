import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = ssh.open_sftp()
with sftp.file('/tmp/dump_ifaces.php', 'w') as f:
    f.write("""<?php
require_once '/var/www/mitranet/includes/api.inc';
$res = MitraNetApi::getInterfaces();
foreach ($res as $i) {
    if ($i['name'] === 'test' || $i['name'] === 'veth0') {
        echo json_encode($i, JSON_PRETTY_PRINT) . "\\n";
    }
}
""")
sftp.close()

stdin, stdout, stderr = ssh.exec_command('php /tmp/dump_ifaces.php')
print(stdout.read().decode('utf-8'))
ssh.close()
