import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet', timeout=5)

sftp = ssh.open_sftp()
with sftp.file('/tmp/test_type_detect.php', 'w') as f:
    f.write("""<?php
function getSysfsNetType($name) {
    if (is_dir("/sys/class/net/{$name}/bridge")) return 'bridge';
    if (is_dir("/sys/class/net/{$name}/bonding")) return 'bond';
    if (file_exists("/sys/class/net/{$name}/uevent")) {
        $uevent = file_get_contents("/sys/class/net/{$name}/uevent");
        if (preg_match('/DEVTYPE=(.+)/i', $uevent, $m)) {
            $devtype = strtolower(trim($m[1]));
            if ($devtype === 'bridge') return 'bridge';
            if ($devtype === 'vlan') return 'vlan';
            if ($devtype === 'bond') return 'bond';
        }
    }
    return 'ether';
}

echo 'test: ' . getSysfsNetType('test') . "\\n";
echo 'veth0: ' . getSysfsNetType('veth0') . "\\n";
echo 'enp1s0: ' . getSysfsNetType('enp1s0') . "\\n";
""")
sftp.close()

stdin, stdout, stderr = ssh.exec_command('php /tmp/test_type_detect.php')
print(stdout.read().decode('utf-8'))
ssh.close()
