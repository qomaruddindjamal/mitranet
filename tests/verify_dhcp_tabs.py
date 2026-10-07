import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('192.168.56.101', port=22, username='root', password='mitranet')

php_script = """<?php
require_once '/mitranet/web/includes/api.inc';
$logged_in = MitraNetApi::login('admin', 'mitranet');

$all_interfaces = MitraNetApi::getInterfaces();
$all_bridges = MitraNetApi::getBridges();
$all_vethernets = MitraNetApi::getVethernets();
$all_vlans = MitraNetApi::getVlans();
$gateways = MitraNetApi::getGateways();

$wan_ifaces = [];
foreach ($gateways as $gw) {
    if (!empty($gw['default']) && !empty($gw['interface'])) {
        $wan_ifaces[] = $gw['interface'];
    }
}
if (empty($wan_ifaces)) {
    $wan_ifaces = ['enp0s3'];
}

$eligible_ifaces = [];

// 1. Physical LAN (Non-WAN)
foreach ($all_interfaces as $it) {
    $n = $it['name'];
    if ($n === 'lo' || in_array($n, $wan_ifaces) || str_starts_with($n, 'veth') || str_starts_with($n, 'tap') || str_contains($n, '.') || str_starts_with($n, 'vlan') || str_starts_with($n, 'wg') || str_starts_with($n, 'tun') || str_starts_with($n, 'ppp') || str_starts_with($n, 'sit') || str_starts_with($n, 'gre')) {
        continue;
    }
    $type = $it['type'] ?? '';
    if (!in_array($type, ['ether', 'wlan', ''])) {
        continue;
    }
    $is_br = false;
    foreach ($all_bridges as $br) {
        if ($br['name'] === $n) { $is_br = true; break; }
    }
    if ($is_br) continue;
    $eligible_ifaces[$n] = ['label' => "LAN: {$n}", 'type' => 'Physical LAN'];
}

// 2. Bridges
foreach ($all_bridges as $br) {
    $eligible_ifaces[$br['name']] = ['label' => "Bridge: {$br['name']}", 'type' => 'Bridge'];
}

// 3. vEthernet
foreach ($all_vethernets as $ve) {
    $eligible_ifaces[$ve['name']] = ['label' => "vEthernet: {$ve['name']}", 'type' => 'vEthernet'];
}

// 4. VLANs
foreach ($all_vlans as $vl) {
    $vl_name = $vl['name'] ?? '';
    if ($vl_name && !isset($eligible_ifaces[$vl_name]) && !in_array($vl_name, $wan_ifaces)) {
        $eligible_ifaces[$vl_name] = ['label' => "VLAN: {$vl_name}", 'type' => 'VLAN'];
    }
}

echo "=== TAB ORDER IN DHCP SERVER ===\\n";
$first = array_key_first($eligible_ifaces);
$idx = 1;
foreach ($eligible_ifaces as $k => $v) {
    $status = ($k === $first) ? "[TAB #1 - DEFAULT OPEN]" : "[TAB #{$idx}]";
    echo "Tab {$idx}: " . str_pad($k, 10) . " | Type: " . str_pad($v['type'], 15) . " | Label: " . str_pad($v['label'], 18) . " | {$status}\\n";
    $idx++;
}
"""

sftp = ssh.open_sftp()
with sftp.file('/tmp/test_tabs.php', 'w') as f:
    f.write(php_script)
sftp.close()

stdin, stdout, stderr = ssh.exec_command("php /tmp/test_tabs.php")
print(stdout.read().decode('utf-8'))
print(stderr.read().decode('utf-8'))
ssh.exec_command("rm -f /tmp/test_tabs.php")
ssh.close()
