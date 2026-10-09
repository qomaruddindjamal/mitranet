import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('10.10.66.228', username='root', password='mitranet')

sftp = ssh.open_sftp()
with sftp.file('/tmp/test_wifi.php', 'w') as f:
    f.write("""<?php
function getRealWirelessInterfaces() {
    $devices = [];
    $iw_out = @shell_exec('iw dev 2>/dev/null');
    $current_phy = null;
    if ($iw_out) {
        foreach (explode("\\n", $iw_out) as $line) {
            $line = trim($line);
            if (strpos($line, 'phy#') === 0) {
                $current_phy = $line;
            } elseif (preg_match('/^Interface\\s+(\\S+)/', $line, $m)) {
                $devices[$m[1]] = [
                    'name' => $m[1],
                    'phy'  => $current_phy
                ];
            }
        }
    }

    // Check sysfs for any missed wireless interfaces
    foreach (glob('/sys/class/net/*') as $p) {
        $name = basename($p);
        if (is_dir("$p/wireless") || is_dir("$p/phy80211")) {
            if (!isset($devices[$name])) {
                $devices[$name] = ['name' => $name, 'phy' => null];
            }
        }
    }

    foreach ($devices as $name => &$d) {
        $info = @shell_exec('iw dev ' . escapeshellarg($name) . ' info 2>/dev/null');
        $d['mode'] = 'managed';
        $d['channel'] = 'auto';
        $d['frequency'] = 'auto';
        $d['txpower'] = '-';
        if ($info) {
            foreach (explode("\\n", $info) as $l) {
                $l = trim($l);
                if (preg_match('/^type\\s+(.+)/', $l, $m)) {
                    $d['mode'] = $m[1];
                } elseif (preg_match('/^addr\\s+(.+)/', $l, $m)) {
                    $d['mac_address'] = $m[1];
                } elseif (preg_match('/^channel\\s+(\\d+)\\s*\\((\\d+)\\s*MHz\\)/i', $l, $m)) {
                    $d['channel'] = $m[1];
                    $d['frequency'] = $m[2] . ' MHz';
                } elseif (preg_match('/^txpower\\s+(.+)/', $l, $m)) {
                    $d['txpower'] = $m[1];
                }
            }
        }

        // MAC fallback
        if (empty($d['mac_address']) && file_exists("/sys/class/net/$name/address")) {
            $d['mac_address'] = trim(file_get_contents("/sys/class/net/$name/address"));
        }

        // MTU
        $d['mtu'] = file_exists("/sys/class/net/$name/mtu") ? intval(trim(file_get_contents("/sys/class/net/$name/mtu"))) : 1500;

        // Flags & operstate
        $flags = file_exists("/sys/class/net/$name/flags") ? hexdec(trim(file_get_contents("/sys/class/net/$name/flags"))) : 0;
        $d['is_up'] = (bool)($flags & 1);
        $d['oper_state'] = file_exists("/sys/class/net/$name/operstate") ? strtoupper(trim(file_get_contents("/sys/class/net/$name/operstate"))) : 'DOWN';

        // Check bands from phy
        $phy_idx = !empty($d['phy']) ? preg_replace('/[^0-9]/', '', $d['phy']) : '0';
        $phy_out = @shell_exec('iw phy phy' . intval($phy_idx) . ' info 2>/dev/null');
        $bands = [];
        if ($phy_out) {
            if (strpos($phy_out, 'Band 1:') !== false) $bands[] = '2.4GHz';
            if (strpos($phy_out, 'Band 2:') !== false) $bands[] = '5GHz';
        }
        $d['band'] = !empty($bands) ? implode(' / ', $bands) : '2.4GHz';

        // Check Link status / SSID
        $link = @shell_exec('iw dev ' . escapeshellarg($name) . ' link 2>/dev/null');
        if ($link && strpos($link, 'Not connected') === false) {
            if (preg_match('/SSID:\\s*(.+)/', $link, $m)) {
                $d['ssid'] = trim($m[1]);
            } else {
                $d['ssid'] = 'Connected';
            }
            if (preg_match('/Connected to\\s+([0-9a-f:]{17})/i', $link, $m)) {
                $d['bssid'] = $m[1];
            }
            if (preg_match('/freq:\\s*(\\d+)/', $link, $m)) {
                $d['frequency'] = $m[1] . ' MHz';
            }
            if (preg_match('/signal:\\s*([-\\d]+ dBm)/', $link, $m)) {
                $d['signal'] = $m[1];
            }
            $d['link_connected'] = true;
        } else {
            $d['ssid'] = 'None';
            $d['bssid'] = '-';
            $d['signal'] = '-';
            $d['link_connected'] = false;
        }
    }
    return array_values($devices);
}

echo json_encode(getRealWirelessInterfaces(), JSON_PRETTY_PRINT) . "\\n";
""")
sftp.close()

stdin, stdout, stderr = ssh.exec_command("php /tmp/test_wifi.php")
print("STDOUT:\\n", stdout.read().decode('utf-8'))
print("STDERR:\\n", stderr.read().decode('utf-8'))
ssh.close()
