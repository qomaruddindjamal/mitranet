<?php
/*
 * interfaces.php - MitraNet Interface Management & Configuration
 * Adapted for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$msg = '';
$err = '';

// AJAX Endpoint: Real-time interface traffic stats (compatible with polling & ifstats)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'traffic') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $now = microtime(true);
    $proc_stats = [];
    if (file_exists('/proc/net/dev') && is_readable('/proc/net/dev')) {
        $lines = file('/proc/net/dev');
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) continue;
            list($dev, $data) = explode(':', $line, 2);
            $dev = trim($dev);
            $fields = preg_split('/\s+/', trim($data));
            if (count($fields) >= 16) {
                $proc_stats[$dev] = [
                    'rx_bytes'   => (float)$fields[0],
                    'rx_packets' => (float)$fields[1],
                    'tx_bytes'   => (float)$fields[8],
                    'tx_packets' => (float)$fields[9],
                ];
            }
        }
    }
    if (empty($proc_stats)) {
        $ifaces = MitraNetApi::getInterfaces();
        foreach ($ifaces as $i) {
            $name = $i['name'] ?? '';
            if (!$name) continue;
            $t = $i['traffic'] ?? [];
            $proc_stats[$name] = [
                'rx_bytes'   => (float)($t['rx_bytes'] ?? 0),
                'rx_packets' => (float)($t['rx_packets'] ?? 0),
                'tx_bytes'   => (float)($t['tx_bytes'] ?? 0),
                'tx_packets' => (float)($t['tx_packets'] ?? 0),
            ];
        }
    }
    echo json_encode(['timestamp' => $now, 'interfaces' => $proc_stats]);
    exit;
}

// AJAX Endpoint: Real-time full interface detail for WinBox-style modal dialog
if (isset($_GET['ajax']) && $_GET['ajax'] === 'detail') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $ifname = trim($_GET['if'] ?? '');
    if (!$ifname) {
        echo json_encode(['error' => 'Interface name required']);
        exit;
    }

    $all_ifaces = MitraNetApi::getInterfaces();
    $target = null;
    foreach ($all_ifaces as $i) {
        if (strtolower($i['name']) === strtolower($ifname)) {
            $target = $i;
            break;
        }
    }
    if (!$target) {
        echo json_encode(['error' => 'Interface not found']);
        exit;
    }

    // Read real sysfs hardware and link attributes
    $speed = 'Unknown';
    $duplex = 'Unknown';
    $carrier = 0;
    $sys_path = "/sys/class/net/{$ifname}";
    if (file_exists("{$sys_path}/speed")) {
        $s = @file_get_contents("{$sys_path}/speed");
        if ($s !== false && intval(trim($s)) > 0) $speed = trim($s) . ' Mbps';
    }
    if (file_exists("{$sys_path}/duplex")) {
        $d = @file_get_contents("{$sys_path}/duplex");
        if ($d !== false && trim($d) !== '') $duplex = ucfirst(trim($d));
    }
    if (file_exists("{$sys_path}/carrier")) {
        $c = @file_get_contents("{$sys_path}/carrier");
        if ($c !== false) $carrier = intval(trim($c));
    }

    // Determine type
    $detected_type = $target['type'] ?? 'ether';
    if (is_dir("{$sys_path}/bridge") || preg_match('/^br[-_]/i', $ifname) || $detected_type === 'bridge') {
        $detected_type = 'bridge';
    } elseif (is_dir("{$sys_path}/bonding") || preg_match('/^bond/i', $ifname) || $detected_type === 'bond') {
        $detected_type = 'bond';
    } elseif (preg_match('/^vlan/i', $ifname) || strpos($ifname, '.') !== false || $detected_type === 'vlan') {
        $detected_type = 'vlan';
    }

    // Bridge details if bridge
    $bridge_ports = [];
    $bridge_stp = false;
    $bridge_prio = 32768;
    if ($detected_type === 'bridge' && is_dir("{$sys_path}/brif")) {
        $brif_entries = @scandir("{$sys_path}/brif") ?: [];
        foreach ($brif_entries as $be) {
            if ($be !== '.' && $be !== '..') {
                $bridge_ports[] = $be;
            }
        }
        if (file_exists("{$sys_path}/bridge/stp_state")) {
            $stp_val = trim(@file_get_contents("{$sys_path}/bridge/stp_state") ?: '0');
            $bridge_stp = ($stp_val === '1' || $stp_val === '2');
        }
        if (file_exists("{$sys_path}/bridge/priority")) {
            $bridge_prio = intval(trim(@file_get_contents("{$sys_path}/bridge/priority") ?: '32768'));
        }
    }

    // Detect ARP mode from ip link / sysctl
    $arp_mode = 'enabled';
    $link_show = shell_exec("ip link show dev " . escapeshellarg($ifname) . " 2>/dev/null") ?: '';
    if (strpos($link_show, 'NOARP') !== false) {
        $arp_mode = 'disabled';
    } else {
        $proxy_arp = trim(@file_get_contents("/proc/sys/net/ipv4/conf/{$ifname}/proxy_arp") ?: '0');
        $arp_ignore = trim(@file_get_contents("/proc/sys/net/ipv4/conf/{$ifname}/arp_ignore") ?: '0');
        if ($proxy_arp === '1') {
            $arp_mode = 'proxy-arp';
        } elseif ($arp_ignore === '1') {
            $arp_mode = 'reply-only';
        }
    }

    // VLAN details if VLAN
    $vlan_id = null;
    $vlan_parent = null;
    if ($detected_type === 'vlan') {
        // Method 1: parse name pattern like enp1s0.10 or vlan10
        if (preg_match('/^([a-zA-Z0-9_-]+)\.([0-9]+)$/', $ifname, $m)) {
            $vlan_parent = $m[1];
            $vlan_id = (int)$m[2];
        } elseif (preg_match('/^vlan([0-9]+)$/i', $ifname, $m)) {
            $vlan_id = (int)$m[1];
        }
        // Method 2: sysfs lower_* link
        $lowers = @glob("{$sys_path}/lower_*");
        if (!empty($lowers)) {
            $vlan_parent = preg_replace('/^.*\/lower_/', '', $lowers[0]);
        }
        // Method 3: /proc/net/vlan/config
        if (file_exists('/proc/net/vlan/config')) {
            $vlines = @file('/proc/net/vlan/config') ?: [];
            foreach ($vlines as $vl) {
                $parts = array_map('trim', explode('|', $vl));
                if (count($parts) >= 3 && $parts[0] === $ifname) {
                    $vlan_id = (int)$parts[1];
                    $vlan_parent = $parts[2];
                    break;
                }
            }
        }
    }

    $detail = [
        'name'          => $target['name'],
        'altname'       => $target['altname'] ?? $target['name'],
        'is_up'         => !empty($target['is_up']),
        'oper_state'    => strtoupper($target['oper_state'] ?? 'UNKNOWN'),
        'carrier'       => $carrier,
        'speed'         => $speed,
        'duplex'        => $duplex,
        'mac_address'   => $target['mac_address'] ?? '00:00:00:00:00:00',
        'mtu'           => $target['mtu'] ?? 1500,
        'l2mtu'         => 1500,
        'type'          => $detected_type,
        'ipv4'          => $target['ipv4_addresses'] ?? [],
        'ipv6'          => $target['ipv6_addresses'] ?? [],
        'comment'       => $target['comment'] ?? '',
        'vrf'           => $target['vrf'] ?? 'main',
        'arp'           => $arp_mode,
        'bridge_ports'  => $bridge_ports,
        'bridge_stp'    => $bridge_stp,
        'bridge_prio'   => $bridge_prio,
        'vlan_id'       => $vlan_id,
        'vlan_parent'   => $vlan_parent,
        'traffic'       => $target['traffic'] ?? []
    ];

    echo json_encode(['success' => true, 'data' => $detail]);
    exit;
}

// Support ?if=<name> or ?name=<name> for configuration edit mode
$target_if = $_GET['if'] ?? $_GET['name'] ?? '';

// Handle POST actions (State change, Save interface, Remove IP, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ifname = trim($_POST['interface'] ?? '');
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

    if ($action === 'set_state' && !empty($ifname)) {
        $state = strtolower($_POST['state'] ?? 'up');
        $res = MitraNetApi::setInterfaceState($ifname, $state);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $msg = "Interface '{$ifname}' berhasil diatur ke " . strtoupper($state);
            if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
        } else {
            $err = $res['data']['error'] ?? 'Gagal mengubah status interface.';
            if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
        }
    } elseif ($action === 'save_interface' && !empty($ifname)) {
        $new_name = trim($_POST['new_name'] ?? $ifname);
        $state = !empty($_POST['enable']) ? 'up' : 'down';
        $mtu = intval($_POST['mtu'] ?? 1500);
        $arp = trim($_POST['arp'] ?? 'enabled');
        $new_ip = trim($_POST['ipaddr'] ?? '');
        $new_subnet = intval($_POST['subnet'] ?? 24);
        $comment = trim($_POST['comment'] ?? '');

        // 1. Check if interface is a VLAN and parent or vlan_id changed
        $req_vlan_id = isset($_POST['vlan_id']) ? intval($_POST['vlan_id']) : 0;
        $req_vlan_parent = trim($_POST['vlan_parent'] ?? '');
        $is_vlan_dev = (is_dir("/sys/class/net/{$ifname}") && (file_exists("/proc/net/vlan/config") || !empty(glob("/sys/class/net/{$ifname}/lower_*")) || strpos($ifname, '.') !== false));
        
        $active_name = $ifname;
        if ($is_vlan_dev && $req_vlan_id > 0 && !empty($req_vlan_parent)) {
            // Find current vlan_id and parent
            $curr_vlan_id = 0;
            $curr_vlan_parent = '';
            if (preg_match('/^([a-zA-Z0-9_-]+)\.([0-9]+)$/', $ifname, $vm)) {
                $curr_vlan_parent = $vm[1];
                $curr_vlan_id = (int)$vm[2];
            }
            $lowers = @glob("/sys/class/net/{$ifname}/lower_*");
            if (!empty($lowers)) {
                $curr_vlan_parent = preg_replace('/^.*\/lower_/', '', $lowers[0]);
            }
            
            // If vlan_id or parent changed
            if ($req_vlan_id !== $curr_vlan_id || $req_vlan_parent !== $curr_vlan_parent) {
                // Determine target name
                $target_vlan_name = "{$req_vlan_parent}.{$req_vlan_id}";
                if (!empty($new_name) && $new_name !== $ifname && $new_name !== "{$curr_vlan_parent}.{$curr_vlan_id}") {
                    $target_vlan_name = $new_name;
                }
                
                // Get existing IP addresses to migrate
                $old_ips = [];
                $ip_out = shell_exec("ip -4 addr show dev " . escapeshellarg($ifname) . " 2>/dev/null") ?: '';
                if (preg_match_all('/inet\s+([0-9.]+)\/([0-9]+)/', $ip_out, $ipm, PREG_SET_ORDER)) {
                    foreach ($ipm as $iprow) {
                        $old_ips[] = "{$iprow[1]}/{$iprow[2]}";
                    }
                }
                
                // Delete old VLAN interface
                MitraNetApi::request('/vlans/delete', 'POST', ['name' => $ifname]);
                exec("ip link delete " . escapeshellarg($ifname) . " 2>/dev/null");
                
                // Create new VLAN interface
                MitraNetApi::request('/vlans/create', 'POST', [
                    'name' => $target_vlan_name,
                    'parent' => $req_vlan_parent,
                    'parent_interface' => $req_vlan_parent,
                    'vlan_id' => $req_vlan_id
                ]);
                exec("ip link add link " . escapeshellarg($req_vlan_parent) . " name " . escapeshellarg($target_vlan_name) . " type vlan id " . escapeshellarg($req_vlan_id) . " 2>/dev/null");
                exec("ip link set " . escapeshellarg($target_vlan_name) . " up 2>/dev/null");
                
                // Re-apply preserved IPs if no new IP specified
                if (empty($new_ip)) {
                    foreach ($old_ips as $oip) {
                        MitraNetApi::addInterfaceAddress($target_vlan_name, $oip);
                    }
                }
                $active_name = $target_vlan_name;
            }
        }

        // 2. Rename device if name changed and not already handled
        if ($active_name === $ifname && !empty($new_name) && $new_name !== $ifname && preg_match('/^[a-zA-Z0-9_.-]+$/', $new_name)) {
            // Cannot rename protected management interface
            if ($ifname !== 'lo' && $ifname !== 'enp1s0') {
                exec("ip link set dev " . escapeshellarg($ifname) . " down 2>/dev/null");
                exec("ip link set dev " . escapeshellarg($ifname) . " name " . escapeshellarg($new_name) . " 2>&1", $rename_out, $rename_ret);
                if ($rename_ret === 0) {
                    $active_name = $new_name;
                }
            }
        }

        // 2. Set State
        MitraNetApi::setInterfaceState($active_name, $state);

        // 3. Set MTU
        if ($mtu >= 576 && $mtu <= 9000) {
            MitraNetApi::setInterfaceMtu($active_name, $mtu);
        }

        // 4. Set ARP mode
        if ($arp === 'disabled') {
            exec("ip link set dev " . escapeshellarg($active_name) . " arp off 2>/dev/null");
        } else {
            exec("ip link set dev " . escapeshellarg($active_name) . " arp on 2>/dev/null");
            if ($arp === 'proxy-arp') {
                @file_put_contents("/proc/sys/net/ipv4/conf/{$active_name}/proxy_arp", "1");
                @file_put_contents("/proc/sys/net/ipv4/conf/{$active_name}/arp_ignore", "0");
            } elseif ($arp === 'reply-only') {
                @file_put_contents("/proc/sys/net/ipv4/conf/{$active_name}/proxy_arp", "0");
                @file_put_contents("/proc/sys/net/ipv4/conf/{$active_name}/arp_ignore", "1");
            } else {
                @file_put_contents("/proc/sys/net/ipv4/conf/{$active_name}/proxy_arp", "0");
                @file_put_contents("/proc/sys/net/ipv4/conf/{$active_name}/arp_ignore", "0");
            }
        }

        // 5. Update IP Address if specified
        if (!empty($new_ip)) {
            $cidr = "{$new_ip}/{$new_subnet}";
            $add_res = MitraNetApi::addInterfaceAddress($active_name, $cidr);
            if (($add_res['status'] ?? 0) === 200) {
                $msg = "Konfigurasi interface '{$active_name}' ({$cidr}) berhasil disimpan dan diterapkan.";
            } else {
                $err_detail = $add_res['data']['error'] ?? '';
                if (strpos($err_detail, 'already assigned') !== false) {
                    $msg = "Konfigurasi interface '{$active_name}' berhasil diperbarui.";
                } else {
                    $msg = "Status & MTU interface '{$active_name}' diperbarui. " . $err_detail;
                }
            }
        } else {
            $msg = "Konfigurasi interface '{$active_name}' berhasil disimpan dan diterapkan.";
        }

        if ($is_ajax) {
            echo json_encode(['success' => true, 'message' => $msg, 'active_name' => $active_name]);
            exit;
        }

        // Redirect to main interfaces overview with success notification
        header("Location: interfaces.php?saved=" . urlencode($active_name));
        exit;
    } elseif ($action === 'add_bridge_port' && !empty($ifname)) {
        $port = trim($_POST['port'] ?? '');
        if (!empty($port)) {
            $res = MitraNetApi::request('/bridges/port/add', 'POST', ['bridge' => $ifname, 'interface' => $port]);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Port '{$port}' berhasil ditambahkan ke bridge '{$ifname}'.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menambahkan port ke bridge.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'remove_bridge_port' && !empty($ifname)) {
        $port = trim($_POST['port'] ?? '');
        if (!empty($port)) {
            $res = MitraNetApi::request('/bridges/port/remove', 'POST', ['bridge' => $ifname, 'interface' => $port]);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Port '{$port}' berhasil dilepas dari bridge '{$ifname}'.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal melepas port dari bridge.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'remove_ip' && !empty($ifname)) {
        $cidr = trim($_POST['cidr'] ?? '');
        if (!empty($cidr)) {
            $res = MitraNetApi::removeInterfaceAddress($ifname, $cidr);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Alamat IP '{$cidr}' berhasil dihapus dari '{$ifname}'.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal menghapus alamat IP.';
            }
        }
    } elseif ($action === 'create_bridge') {
        $name = trim($_POST['name'] ?? '');
        $members = array_filter(array_map('trim', (array)($_POST['members'] ?? [])));
        if (!empty($name)) {
            $res = MitraNetApi::request('/bridges/create', 'POST', [
                'name' => $name,
                'members' => array_values($members)
            ]);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Bridge interface '{$name}' berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat bridge.';
            }
        }
    } elseif ($action === 'create_vlan') {
        $parent = trim($_POST['parent'] ?? '');
        $tag = (int)($_POST['tag'] ?? 0);
        $descr = trim($_POST['descr'] ?? '');
        if (!empty($parent) && $tag > 0 && $tag <= 4094) {
            $vlan_dev = "{$parent}.{$tag}";
            $res = MitraNetApi::request('/vlans/create', 'POST', [
                'name' => $vlan_dev,
                'parent' => $parent,
                'parent_interface' => $parent,
                'vlan_id' => $tag,
                'description' => $descr
            ]);
            $is_success = (($res['status'] ?? 0) === 200 && empty($res['data']['error']));
            if (!$is_success) {
                // Direct kernel fallback
                exec("ip link add link " . escapeshellarg($parent) . " name " . escapeshellarg($vlan_dev) . " type vlan id " . escapeshellarg($tag), $k_out, $k_ret);
                if ($k_ret === 0) {
                    $is_success = true;
                }
            }

            if ($is_success) {
                exec("ip link set " . escapeshellarg($vlan_dev) . " up");
                if (!empty($_POST['auto_dhcp'])) {
                    exec("dhcpcd -4 -n " . escapeshellarg($vlan_dev) . " >/dev/null 2>&1 &");
                }
                $msg = "VLAN interface '{$vlan_dev}' berhasil dibuat dan diaktifkan.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat VLAN interface.';
            }
        } else {
            $err = "Parent interface dan VLAN Tag (1-4094) wajib diisi.";
        }
    } elseif ($action === 'create_vether') {
        $name = trim($_POST['name'] ?? '');
        $ip_cidr = trim($_POST['ip_cidr'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!empty($name) && !empty($ip_cidr)) {
            $res = MitraNetApi::createVethernet($name, $ip_cidr, $desc);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = $res['data']['message'] ?? "vEthernet '{$name}' berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat vEthernet.';
            }
        }
    } elseif ($action === 'create_vether_tunnel') {
        $name = trim($_POST['name'] ?? '');
        $peer = trim($_POST['peer'] ?? '');
        if (!empty($name)) {
            $peer_name = !empty($peer) ? $peer : $name . "-peer";
            $cmd = "ip link add " . escapeshellarg($name) . " type veth peer name " . escapeshellarg($peer_name);
            exec($cmd, $out, $ret);
            if ($ret === 0) {
                exec("ip link set " . escapeshellarg($name) . " up");
                exec("ip link set " . escapeshellarg($peer_name) . " up");
                $msg = "vEther Tunnel '{$name}' <-> '{$peer_name}' berhasil dibuat.";
            } else {
                $err = "Gagal membuat vEther tunnel.";
            }
        }
    } elseif ($action === 'create_macvlan') {
        $name = trim($_POST['name'] ?? '');
        $parent = trim($_POST['parent'] ?? 'enp1s0');
        $mode = trim($_POST['mode'] ?? 'bridge');
        $mac = trim($_POST['mac'] ?? '');
        if (!empty($name) && !empty($parent)) {
            $cmd = "ip link add link " . escapeshellarg($parent) . " name " . escapeshellarg($name) . " type macvlan mode " . escapeshellarg($mode);
            if (!empty($mac)) {
                $cmd .= " address " . escapeshellarg($mac);
            }
            exec($cmd, $out, $ret);
            if ($ret === 0) {
                exec("ip link set " . escapeshellarg($name) . " up");
                // Optional auto dhcp if requested
                if (!empty($_POST['auto_dhcp'])) {
                    exec("dhcpcd -4 -n " . escapeshellarg($name) . " >/dev/null 2>&1 &");
                }
                $msg = "MACVLAN interface '{$name}' (parent: {$parent}, mode: {$mode}) berhasil dibuat.";
            } else {
                $err = "Gagal membuat MACVLAN interface: " . implode(" ", $out);
            }
        } else {
            $err = "Nama interface dan parent interface wajib diisi.";
        }
    } elseif ($action === 'create_vrf') {
        $name = trim($_POST['name'] ?? '');
        $table = (int)($_POST['table'] ?? 100);
        if (!empty($name) && $table > 0) {
            $res = MitraNetApi::request('/vrfs/create', 'POST', [
                'name' => $name,
                'table_id' => $table
            ]);
            if (($res['status'] ?? 0) === 200) {
                $msg = "VRF '{$name}' (Table {$table}) berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat VRF.';
            }
        }
    } elseif ($action === 'create_lagg') {
        $name = trim($_POST['name'] ?? '');
        $mode = trim($_POST['mode'] ?? '802.3ad');
        $members = array_filter(array_map('trim', (array)($_POST['members'] ?? [])));
        if (!empty($name)) {
            $res = MitraNetApi::request('/bonds/create', 'POST', [
                'name' => $name,
                'mode' => $mode,
                'members' => array_values($members)
            ]);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Bond/LAGG '{$name}' berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat LAGG.';
            }
        }
    } elseif ($action === 'create_eoip') {
        $name = trim($_POST['name'] ?? '');
        $remote = trim($_POST['remote'] ?? '');
        $local = trim($_POST['local'] ?? '');
        $tunnel_id = (int)($_POST['tunnel_id'] ?? 1);
        $mtu = (int)($_POST['mtu'] ?? 1500) ?: 1500;
        $bridge = trim($_POST['bridge'] ?? '');
        $ip_cidr = trim($_POST['ip_cidr'] ?? '');

        if (!empty($name) && !empty($remote)) {
            $res = MitraNetApi::createTunnel([
                'type' => 'eoip',
                'name' => $name,
                'remote' => $remote,
                'local' => $local,
                'tunnel_id' => $tunnel_id,
                'mtu' => $mtu,
                'bridge' => $bridge,
                'ip_cidr' => $ip_cidr
            ]);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = $res['data']['message'] ?? "EoIP Tunnel '{$name}' berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat EoIP tunnel.';
            }
        } else {
            $err = "Nama interface dan Alamat Remote wajib diisi untuk EoIP.";
        }
    } elseif ($action === 'create_gre') {
        $name = trim($_POST['name'] ?? '');
        $remote = trim($_POST['remote'] ?? '');
        $local = trim($_POST['local'] ?? '');
        $ttl = (int)($_POST['ttl'] ?? 255) ?: 255;
        $mtu = (int)($_POST['mtu'] ?? 1476) ?: 1476;
        $ip_cidr = trim($_POST['ip_cidr'] ?? '');

        if (!empty($name) && !empty($remote)) {
            $res = MitraNetApi::createTunnel([
                'type' => 'gre',
                'name' => $name,
                'remote' => $remote,
                'local' => $local,
                'ttl' => $ttl,
                'mtu' => $mtu,
                'ip_cidr' => $ip_cidr
            ]);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = $res['data']['message'] ?? "GRE Tunnel '{$name}' berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat GRE tunnel.';
            }
        } else {
            $err = "Nama interface dan Alamat Remote wajib diisi untuk GRE.";
        }
    } elseif ($action === 'create_iptunnel') {
        $name = trim($_POST['name'] ?? '');
        $remote = trim($_POST['remote'] ?? '');
        $local = trim($_POST['local'] ?? '');
        $ttl = (int)($_POST['ttl'] ?? 64) ?: 64;
        $mtu = (int)($_POST['mtu'] ?? 1480) ?: 1480;
        $ip_cidr = trim($_POST['ip_cidr'] ?? '');

        if (!empty($name) && !empty($remote)) {
            $res = MitraNetApi::createTunnel([
                'type' => 'ipip',
                'name' => $name,
                'remote' => $remote,
                'local' => $local,
                'ttl' => $ttl,
                'mtu' => $mtu,
                'ip_cidr' => $ip_cidr
            ]);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = $res['data']['message'] ?? "IPIP Tunnel '{$name}' berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat IPIP tunnel.';
            }
        } else {
            $err = "Nama interface dan Alamat Remote wajib diisi untuk IPIP Tunnel.";
        }
    } elseif ($action === 'create_vxlan') {
        $name = trim($_POST['name'] ?? '');
        $vni = (int)($_POST['vni'] ?? 100) ?: 100;
        $port = (int)($_POST['port'] ?? 4789) ?: 4789;
        $remote = trim($_POST['remote'] ?? '');
        $parent = trim($_POST['parent'] ?? '');
        $bridge = trim($_POST['bridge'] ?? '');
        $ip_cidr = trim($_POST['ip_cidr'] ?? '');

        if (!empty($name) && $vni > 0) {
            $res = MitraNetApi::createTunnel([
                'type' => 'vxlan',
                'name' => $name,
                'vni' => $vni,
                'port' => $port,
                'remote' => $remote,
                'parent' => $parent,
                'bridge' => $bridge,
                'ip_cidr' => $ip_cidr
            ]);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = $res['data']['message'] ?? "VXLAN '{$name}' (VNI {$vni}) berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat VXLAN interface.';
            }
        } else {
            $err = "Nama interface dan VNI Identifier wajib diisi untuk VXLAN.";
        }
    } elseif ($action === 'delete_interface') {
        $del_name = trim($_POST['interface'] ?? '');
        $del_type = trim($_POST['type'] ?? '');
        if (!empty($del_name)) {
            if ($del_type === 'Bridge' || strpos($del_name, 'br') === 0) {
                MitraNetApi::request('/bridges/delete', 'POST', ['name' => $del_name]);
            } elseif ($del_type === 'VLAN' || strpos($del_name, '.') !== false || strpos($del_name, 'vlan') === 0) {
                MitraNetApi::request('/vlans/delete', 'POST', ['name' => $del_name]);
            } elseif (preg_match('/^(eoip|gre|ipip|tunl|vxlan)/i', $del_name)) {
                MitraNetApi::deleteTunnel($del_name);
            } elseif (strpos($del_name, 'macsec') === 0) {
                MitraNetApi::deleteMacsec($del_name);
            } elseif (strpos($del_name, 'veth') === 0) {
                exec("ip link delete " . escapeshellarg($del_name));
            } else {
                exec("ip link delete " . escapeshellarg($del_name));
            }
            $msg = "Interface '{$del_name}' berhasil dihapus.";
            if ($is_ajax) {
                echo json_encode(['success' => true, 'message' => $msg]);
                exit;
            }
        }
    } elseif ($action === 'delete_vrf') {
        $vrf_name = trim($_POST['vrf_name'] ?? '');
        if (!empty($vrf_name)) {
            $res = MitraNetApi::request('/vrfs/delete', 'POST', ['name' => $vrf_name]);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = "VRF '{$vrf_name}' berhasil dihapus.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menghapus VRF.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'add_vrf_member') {
        $vrf_name = trim($_POST['vrf_name'] ?? '');
        $member_if = trim($_POST['member_interface'] ?? '');
        if (!empty($vrf_name) && !empty($member_if)) {
            $res = MitraNetApi::addVrfMember($vrf_name, $member_if);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = "Interface '{$member_if}' berhasil dimasukkan ke VRF '{$vrf_name}'.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menambahkan member interface ke VRF.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'remove_vrf_member') {
        $vrf_name = trim($_POST['vrf_name'] ?? '');
        $member_if = trim($_POST['member_interface'] ?? '');
        if (!empty($vrf_name) && !empty($member_if)) {
            $res = MitraNetApi::removeVrfMember($vrf_name, $member_if);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = "Interface '{$member_if}' berhasil dilepas dari VRF '{$vrf_name}'.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal melepas member interface dari VRF.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'create_macsec') {
        $name = trim($_POST['name'] ?? '');
        $parent = trim($_POST['parent'] ?? '');
        $encrypt = !empty($_POST['encrypt']);
        $key = trim($_POST['key'] ?? '');
        $key_id = trim($_POST['key_id'] ?? '01');
        $sci = trim($_POST['sci'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if (!empty($name) && !empty($parent)) {
            $res = MitraNetApi::createMacsec([
                'name' => $name,
                'parent' => $parent,
                'encrypt' => $encrypt,
                'key' => $key,
                'key_id' => $key_id,
                'sci' => $sci,
                'comment' => $comment
            ]);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = "MACsec interface '{$name}' berhasil dibuat.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat MACsec interface.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        } else {
            $err = "Nama interface dan Parent device wajib diisi.";
            if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
        }
    } elseif ($action === 'delete_macsec') {
        $name = trim($_POST['name'] ?? '');
        if (!empty($name)) {
            $res = MitraNetApi::deleteMacsec($name);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = "MACsec interface '{$name}' berhasil dihapus.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menghapus MACsec interface.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'save_interface_list') {
        $list_name = strtoupper(trim($_POST['list_name'] ?? ''));
        $members = $_POST['members'] ?? [];
        $comment = trim($_POST['comment'] ?? '');
        if (!empty($list_name)) {
            $res = MitraNetApi::saveInterfaceList($list_name, (array)$members, $comment);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = "Interface List '{$list_name}' berhasil disimpan.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menyimpan Interface List.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        } else {
            $err = "Nama Interface List wajib diisi.";
            if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
        }
    } elseif ($action === 'delete_interface_list') {
        $list_name = strtoupper(trim($_POST['list_name'] ?? ''));
        if (!empty($list_name)) {
            $res = MitraNetApi::deleteInterfaceList($list_name);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = "Interface List '{$list_name}' berhasil dihapus.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menghapus Interface List.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    }
}

if (!empty($_GET['saved'])) {
    $saved_name = htmlspecialchars($_GET['saved']);
    $msg = "Konfigurasi interface '{$saved_name}' berhasil disimpan dan diterapkan.";
}

// Fetch all interfaces
$ifaces_raw = MitraNetApi::getInterfaces();
$vlans   = MitraNetApi::getVlans();
$bridges = MitraNetApi::getBridges();
$bonds   = MitraNetApi::getBonds();
$vrfs    = MitraNetApi::getVrfs();
$macsecs = MitraNetApi::getMacsec();
$interface_lists = MitraNetApi::getInterfaceLists();

// Check if a specific interface is selected for configuration
$selected_iface = null;
if (!empty($target_if)) {
    foreach ($ifaces_raw as $i) {
        if (strtolower($i['name']) === strtolower($target_if)) {
            $selected_iface = $i;
            break;
        }
    }
}

// Page title & menu
if ($selected_iface) {
    $pgtitle = array(gettext("Interfaces"), strtoupper($selected_iface['name']));
} else {
    $pgtitle = array(gettext("Interfaces"), gettext("Interface"));
}
$selected_menu = "interfaces";
require_once(__DIR__ . '/../includes/head.inc');

if (!empty($msg)) print_info_box($msg, "success");
if (!empty($err)) print_info_box($err, "danger");
?>

<?php if ($selected_iface): ?>
<!-- ========================================== -->
<!-- 1. DETAIL / EDIT FORM FOR SINGLE INTERFACE -->
<!-- ========================================== -->
<?php
$tab_array = array();
$tab_array[] = array(gettext("Interface"), false, "interfaces.php");
$tab_array[] = array(gettext("VLANs"), false, "vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "lagg.php");
$tab_array[] = array(gettext("VRFs"), false, "vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"), false, "vethernet.php");
$tab_array[] = array(strtoupper($selected_iface['name']), true, "interfaces.php?if=" . urlencode($selected_iface['name']));
display_top_tabs($tab_array);

$is_up = !empty($selected_iface['is_up']);
$current_ip = '';
$current_subnet = 24;
if (!empty($selected_iface['ipv4_addresses'][0])) {
    $parts = explode('/', $selected_iface['ipv4_addresses'][0]);
    $current_ip = $parts[0];
    if (isset($parts[1])) {
        $current_subnet = intval($parts[1]);
    }
}
$is_protected = ($selected_iface['name'] === 'lo' || $selected_iface['name'] === 'enp0s3');
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title">
			<i class="fa-solid fa-network-wired"></i>
			<?=gettext("General Configuration")?>: <strong><?=htmlspecialchars(strtoupper($selected_iface['name']))?> (<?=htmlspecialchars($selected_iface['name'])?>)</strong>
		</h2>
	</div>
	<div class="panel-body">
		<form method="post" action="interfaces.php?if=<?=urlencode($selected_iface['name'])?>" class="form-horizontal">
			<input type="hidden" name="action" value="save_interface">
			<input type="hidden" name="interface" value="<?=htmlspecialchars($selected_iface['name'])?>">

			<div class="form-group">
				<label class="col-sm-3 control-label"><?=gettext("Enable")?></label>
				<div class="col-sm-9">
					<div class="checkbox">
						<label>
							<input type="checkbox" name="enable" value="yes" <?=$is_up ? 'checked' : ''?> <?=$is_protected ? 'onclick="return false;"' : ''?>>
							<strong><?=gettext("Enable interface")?></strong>
						</label>
						<?php if ($is_up): ?>
							<?php if (strtoupper($selected_iface['oper_state'] ?? '') === 'UP'): ?>
								<span class="label label-success ml-2"><i class="fa-solid fa-arrow-up"></i> UP / Link Active</span>
							<?php else: ?>
								<span class="label label-warning ml-2" title="Interface aktif secara administratif di kernel, menunggu VM/kabel tersambung"><i class="fa-solid fa-plug"></i> READY (Admin UP, No Carrier)</span>
							<?php endif; ?>
						<?php else: ?>
							<span class="label label-danger ml-2"><i class="fa-solid fa-arrow-down"></i> DISABLED / DOWN</span>
						<?php endif; ?>
					</div>
					<span class="help-block"><?=gettext("Aktifkan atau nonaktifkan link layer (Administrative State) untuk interface jaringan ini di kernel Linux.")?></span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label"><?=gettext("Interface Identifier")?></label>
				<div class="col-sm-6">
					<input type="text" class="form-control" value="<?=htmlspecialchars($selected_iface['name'])?>" readonly>
					<span class="help-block">Nama perangkat sistem kernel Linux.</span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label"><?=gettext("MAC Address")?></label>
				<div class="col-sm-6">
					<input type="text" class="form-control" value="<?=htmlspecialchars($selected_iface['mac_address'] ?? 'N/A')?>" readonly>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label"><?=gettext("MTU (Maximum Transmission Unit)")?></label>
				<div class="col-sm-3">
					<input type="number" name="mtu" class="form-control" value="<?=htmlspecialchars($selected_iface['mtu'] ?? 1500)?>" min="576" max="9000">
					<span class="help-block"><?=gettext("Standar ethernet: 1500 bytes.")?></span>
				</div>
			</div>

			<hr>
			<h4><i class="fa-solid fa-sliders"></i> <?=gettext("Static IPv4 Configuration")?></h4>

			<div class="form-group">
				<label class="col-sm-3 control-label"><?=gettext("IPv4 Address & Subnet")?></label>
				<div class="col-sm-4">
					<input type="text" name="ipaddr" class="form-control" placeholder="192.168.1.1" value="<?=htmlspecialchars($current_ip)?>">
				</div>
				<div class="col-sm-2">
					<select name="subnet" class="form-control">
						<?php for ($prefix = 32; $prefix >= 8; $prefix--): ?>
							<option value="<?=$prefix?>" <?=$current_subnet == $prefix ? 'selected' : ''?>>/<?=$prefix?></option>
						<?php endfor; ?>
					</select>
				</div>
			</div>

			<?php if (!empty($selected_iface['ipv4_addresses']) || !empty($selected_iface['ipv6_addresses'])): ?>
			<div class="form-group">
				<label class="col-sm-3 control-label"><?=gettext("Alamat IP Terpasang")?></label>
				<div class="col-sm-9">
					<?php foreach (($selected_iface['ipv4_addresses'] ?? []) as $a): ?>
						<div class="mb-5">
							<span class="label label-info fs-13"><?=htmlspecialchars($a)?></span>
							<?php if (!$is_protected): ?>
							<button type="submit" name="action" value="remove_ip" onclick="this.form.cidr.value='<?=htmlspecialchars($a)?>';" class="btn btn-xs btn-danger ml-1">Hapus IP</button>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<?php foreach (($selected_iface['ipv6_addresses'] ?? []) as $a6): ?>
						<div class="mb-5">
							<span class="label label-default fs-11"><?=htmlspecialchars($a6)?></span>
						</div>
					<?php endforeach; ?>
					<input type="hidden" name="cidr" value="">
				</div>
			</div>
			<?php endif; ?>

			<div class="form-group">
				<div class="col-sm-offset-3 col-sm-9">
					<button type="submit" class="btn btn-primary"><i class="fa-solid fa-save icon-embed-btn"></i> <?=gettext("Save")?></button>
					<a href="interfaces.php" class="btn btn-default ml-1"><?=gettext("Back to List")?></a>
				</div>
			</div>
		</form>
	</div>
</div>

<?php else: ?>
<!-- ========================================== -->
<!-- 2. MAIN MIKROTIK-STYLE INTERFACES GRID     -->
<!-- ========================================== -->
<?php
$current_tab = strtolower($_GET['tab'] ?? 'interface');

// Filter: exclude tap-* (KVM internal) and all wireless interfaces
$ifaces = array_filter($ifaces_raw, function($i) use ($current_tab) {
    $name = strtolower($i['name'] ?? '');
    $type = strtolower($i['type'] ?? '');
    if (preg_match('/^tap[-_0-9]/i', $name)) return false;
    if (preg_match('/^(wlan|wlp|wls|ath|ra|wifi)/i', $name) || in_array($type, ['wlan', 'wireless', 'ieee80211'])) return false;

    // Detect actual hardware / kernel type
    $is_bridge = (is_dir("/sys/class/net/{$name}/bridge") || $type === 'bridge' || preg_match('/^br[-_]/i', $name));
    $is_vlan = ($type === 'vlan' || strpos($name, '.') !== false || preg_match('/^vlan/i', $name));
    $is_bond = (is_dir("/sys/class/net/{$name}/bonding") || $type === 'bond' || preg_match('/^bond/i', $name));
    $is_veth = (preg_match('/^veth/i', $name) && !$is_bridge);
    $is_ether = (!$is_bridge && !$is_vlan && !$is_bond && !$is_veth && $name !== 'lo' && !preg_match('/^(wg|tun|tap|gre|sit|ipip|vxlan|eoip|macsec|macvlan|vrrp)/i', $name));

    // Filter by active tab
    if ($current_tab === 'ethernet') {
        return $is_ether;
    } elseif ($current_tab === 'bridge') {
        return $is_bridge;
    } elseif ($current_tab === 'vlan') {
        return $is_vlan;
    } elseif ($current_tab === 'lagg' || $current_tab === 'bonding') {
        return $is_bond;
    } elseif ($current_tab === 'vether') {
        return $is_veth && strpos($name, 'tun') === false;
    } elseif ($current_tab === 'vether_tunnel') {
        return preg_match('/^(vtun|veth.*tun)/i', $name) || ($is_veth && strpos($name, 'peer') !== false);
    } elseif ($current_tab === 'vxlan') {
        return preg_match('/^vxlan/i', $name);
    } elseif ($current_tab === 'gre') {
        return preg_match('/^gre/i', $name);
    } elseif ($current_tab === 'iptunnel') {
        return preg_match('/^(tunl|ipip|sit)/i', $name);
    } elseif ($current_tab === 'eoip') {
        return preg_match('/^eoip/i', $name);
    } elseif ($current_tab === 'macsec') {
        return preg_match('/^macsec/i', $name);
    } elseif ($current_tab === 'macvlan') {
        return ($type === 'macvlan' || preg_match('/^(macvlan|mac[0-9])/i', $name));
    } elseif ($current_tab === 'vrrp') {
        return preg_match('/^vrrp/i', $name);
    }

    return true;
});

// Sort: Physical > Bridge > vEthernet > WireGuard > Others > Loopback
usort($ifaces, function($a, $b) {
    $priority = function($i) {
        $name = strtolower($i['name'] ?? '');
        $type = strtolower($i['type'] ?? '');
        $is_bridge = (is_dir("/sys/class/net/{$name}/bridge") || $type === 'bridge' || preg_match('/^br[-_]/i', $name));
        if ($name === 'lo') return 99;
        if ($is_bridge) return 2;
        if (preg_match('/^(en|eth|eno|ens|enp)/i', $name)) return 1;
        if (preg_match('/^veth/i', $name)) return 3;
        if (preg_match('/^wg/i', $name)) return 4;
        return 5;
    };
    return $priority($a) <=> $priority($b);
});

/* Helper: format bytes to human-readable */
if (!function_exists('fmt_bytes')) {
    function fmt_bytes(int $b): string {
        if ($b >= 1073741824) return number_format($b / 1073741824, 2) . ' GB';
        if ($b >= 1048576)    return number_format($b / 1048576, 1)    . ' MB';
        if ($b >= 1024)       return number_format($b / 1024, 1)       . ' KB';
        return $b . ' B';
    }
}

/* Helper: format packet count */
if (!function_exists('fmt_pkts')) {
    function fmt_pkts(int $p): string {
        if ($p >= 1000000) return number_format($p / 1000000, 1) . 'M';
        if ($p >= 1000)    return number_format($p / 1000, 1)    . 'K';
        return (string)$p;
    }
}
?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">

		<!-- HEADER: BADGE + TABS (MATCHING SCREENSHOT) -->
		<div class="mitranet-header">
			<div class="mitranet-title-badge">
				<i class="fa-solid fa-network-wired"></i> Interfaces
				<i class="fa-solid fa-caret-down"></i>
			</div>
			<ul class="mitranet-tabs">
				<li class="<?=($current_tab === 'interface') ? 'active' : ''?>">
					<a href="interfaces.php?tab=interface">Interface</a>
				</li>
				<li class="<?=($current_tab === 'interface_list') ? 'active' : ''?>">
					<a href="interfaces.php?tab=interface_list">Interface List</a>
				</li>
				<li class="<?=($current_tab === 'ethernet') ? 'active' : ''?>">
					<a href="interfaces.php?tab=ethernet">Ethernet</a>
				</li>
				<li class="<?=($current_tab === 'eoip') ? 'active' : ''?>">
					<a href="interfaces.php?tab=eoip">EoIP Tunnel</a>
				</li>
				<li class="<?=($current_tab === 'iptunnel') ? 'active' : ''?>">
					<a href="interfaces.php?tab=iptunnel">IP Tunnel</a>
				</li>
				<li class="<?=($current_tab === 'gre') ? 'active' : ''?>">
					<a href="interfaces.php?tab=gre">GRE Tunnel</a>
				</li>
				<li class="<?=($current_tab === 'vlan') ? 'active' : ''?>">
					<a href="interfaces.php?tab=vlan">VLAN</a>
				</li>
				<li class="<?=($current_tab === 'bridge') ? 'active' : ''?>">
					<a href="interfaces.php?tab=bridge">Bridge</a>
				</li>
				<li class="<?=($current_tab === 'vxlan') ? 'active' : ''?>">
					<a href="interfaces.php?tab=vxlan">VXLAN</a>
				</li>
				<li class="<?=($current_tab === 'vrf') ? 'active' : ''?>">
					<a href="interfaces.php?tab=vrf">VRF</a>
				</li>
				<li class="<?=($current_tab === 'vrrp') ? 'active' : ''?>">
					<a href="interfaces.php?tab=vrrp">VRRP</a>
				</li>
				<li class="<?=($current_tab === 'lagg' || $current_tab === 'bonding') ? 'active' : ''?>">
					<a href="interfaces.php?tab=lagg">LAGG</a>
				</li>
				<li class="<?=($current_tab === 'macsec') ? 'active' : ''?>">
					<a href="interfaces.php?tab=macsec">MACsec</a>
				</li>
				<li class="<?=($current_tab === 'macvlan') ? 'active' : ''?>">
					<a href="interfaces.php?tab=macvlan">MACVLAN</a>
				</li>
				<li class="<?=($current_tab === 'vether') ? 'active' : ''?>">
					<a href="interfaces.php?tab=vether">vEther</a>
				</li>
				<li class="<?=($current_tab === 'vether_tunnel') ? 'active' : ''?>">
					<a href="interfaces.php?tab=vether_tunnel">vEther Tunnel</a>
				</li>
			</ul>
		</div>

		<!-- TOOLBAR (MATCHING SCREENSHOT) -->
		<div class="mitranet-toolbar">
			<div class="mitranet-toolbar-left">
				<button type="button" class="mitranet-btn" onclick="openNewModal()" title="Add New Interface">
					<i class="fa-solid fa-folder-plus text-primary"></i> <strong>New</strong>
				</button>
				<button type="button" class="mitranet-btn" id="btn-edit" disabled title="Configure / Edit Selected">
					<i class="fa-solid fa-pencil text-muted"></i> Edit
				</button>
				<button type="button" class="mitranet-btn" id="btn-enable" disabled title="Enable Selected">
					<i class="fa-solid fa-play text-muted"></i> Enable
				</button>
				<button type="button" class="mitranet-btn" id="btn-disable" disabled title="Disable Selected">
					<i class="fa-solid fa-pause text-muted"></i> Disable
				</button>
				<button type="button" class="mitranet-btn" id="btn-remove" disabled title="Remove Selected">
					<i class="fa-solid fa-xmark text-muted"></i> Remove
				</button>
				<button type="button" class="mitranet-btn" id="btn-comment" disabled title="Set Comment">
					<i class="fa-regular fa-comment text-muted"></i> Comment
				</button>
			</div>
			<div class="mitranet-toolbar-right">
				<div class="mitranet-search-wrapper">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="text" id="grid-search" placeholder="Find" onkeyup="filterAssignGrid(this.value)">
				</div>
				<button type="button" class="mitranet-btn" onclick="$('#grid-search').focus()" title="Advanced Filter">
					<i class="fa-solid fa-filter text-muted"></i> Filter
				</button>
				<button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh">
					<i class="fa-solid fa-arrows-rotate"></i>
				</button>
			</div>
		</div>

		<?php if ($current_tab === 'interface_list'): ?>
		<!-- ============================================== -->
		<!-- INTERFACE LIST DATA GRID (MikroTik Standard)   -->
		<!-- ============================================== -->
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="iface-list-grid-table">
				<thead>
					<tr>
						<th class="text-center col-flag" style="width: 35px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 220px;">List Name</th>
						<th class="sortable">Member Interfaces</th>
						<th class="sortable" style="width: 260px;">Comment / Usage</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($interface_lists)): ?>
					<tr>
						<td colspan="4" class="text-center text-muted" style="padding: 24px;">
							<i class="fa-solid fa-list-check fs-18 mb-2"></i><br>
							Belum ada Interface List yang dibuat. Klik tombol <strong>New</strong> di toolbar untuk membuat group antarmuka (misal: WAN, LAN, VPN).
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($interface_lists as $il): ?>
					<?php
					$il_name = htmlspecialchars($il['name'] ?? '');
					$il_members = (array)($il['members'] ?? []);
					$il_comment = htmlspecialchars($il['comment'] ?? '');
					?>
					<tr data-listname="<?=$il_name?>" onclick="selectListRow(this, '<?=$il_name?>', '<?=addslashes($il_comment)?>', <?=htmlspecialchars(json_encode(array_values($il_members)))?>)" ondblclick="editInterfaceListClick('<?=$il_name?>', '<?=addslashes($il_comment)?>', <?=htmlspecialchars(json_encode(array_values($il_members)))?>)" style="cursor: pointer;">
						<td class="text-center col-flag-cell">
							<i class="fa-solid fa-layer-group text-primary" title="Interface Group List"></i>
						</td>
						<td>
							<strong class="text-primary fs-13"><i class="fa-solid fa-folder-tree mr-1"></i> <?=$il_name?></strong>
						</td>
						<td>
							<div style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">
							<?php if (empty($il_members)): ?>
								<span class="text-muted fs-11"><em>(Kosong - Belum ada interface anggota)</em></span>
							<?php else: ?>
								<?php foreach ($il_members as $m): ?>
									<span class="label label-info font-monospace" style="display: inline-flex; align-items: center; padding: 4px 8px; font-size: 11px;">
										<i class="fa-solid fa-network-wired mr-1" style="font-size: 10px;"></i> <?=htmlspecialchars($m)?>
									</span>
								<?php endforeach; ?>
							<?php endif; ?>
							</div>
						</td>
						<td class="text-muted">
							<?=$il_comment ? $il_comment : '<span class="text-muted">-</span>'?>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR INTERFACE LIST -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($interface_lists)?> interface lists</span>
			</div>
			<div>
				<span class="text-muted">MikroTik RouterOS Interface Grouping Standard</span>
			</div>
		</div>

		<?php elseif ($current_tab === 'vrf'): ?>
		<!-- ============================================== -->
		<!-- VRF (VIRTUAL ROUTING & FORWARDING) DATA GRID   -->
		<!-- ============================================== -->
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="vrf-grid-table">
				<thead>
					<tr>
						<th class="text-center col-flag" style="width: 35px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 180px;">VRF Name</th>
						<th class="sortable" style="width: 120px;">Table ID</th>
						<th class="sortable">Member Interfaces</th>
						<th class="sortable" style="width: 120px;">Routes</th>
						<th class="sortable" style="width: 100px;">Oper State</th>
						<th class="text-center" style="width: 140px;">Actions</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($vrfs)): ?>
					<tr>
						<td colspan="7" class="text-center text-muted" style="padding: 24px;">
							<i class="fa-solid fa-diagram-project fs-18 mb-2"></i><br>
							Belum ada domain VRF yang dibuat. Klik tombol <strong>New</strong> di toolbar untuk membuat Virtual Routing and Forwarding domain terisolasi.
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($vrfs as $v): ?>
					<?php
					$v_name = htmlspecialchars($v['name'] ?? '');
					$v_table = htmlspecialchars((string)($v['table'] ?? $v['table_id'] ?? ''));
					$v_members = (array)($v['interfaces'] ?? []);
					$v_routes = $v['routes_count'] ?? 0;
					$v_oper = strtoupper($v['oper_state'] ?? 'UP');
					?>
					<tr data-vrfname="<?=$v_name?>">
						<td class="text-center col-flag-cell">
							<i class="fa-solid fa-circle text-success fs-10" title="VRF Domain Active"></i>
						</td>
						<td>
							<strong class="text-primary fs-13"><i class="fa-solid fa-diagram-project mr-1"></i> <?=$v_name?></strong>
						</td>
						<td>
							<span class="badge badge-info" style="font-family: monospace; font-size: 11px;">Table <?=$v_table?></span>
						</td>
						<td>
							<div style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">
							<?php if (empty($v_members)): ?>
								<span class="text-muted fs-11"><em>(Belum ada interface yang diikat ke VRF ini)</em></span>
							<?php else: ?>
								<?php foreach ($v_members as $vm): ?>
									<span class="label label-primary font-monospace" style="display: inline-flex; align-items: center; padding: 4px 8px; font-size: 11px;">
										<i class="fa-solid fa-network-wired mr-1" style="font-size: 10px;"></i> <?=htmlspecialchars($vm)?>
										<a href="javascript:void(0)" onclick="removeVrfMemberClick('<?=$v_name?>', '<?=htmlspecialchars($vm)?>')" style="color: #fff; margin-left: 6px; text-decoration: none;" title="Lepas interface ini dari VRF">&times;</a>
									</span>
								<?php endforeach; ?>
							<?php endif; ?>
							</div>
						</td>
						<td>
							<span class="text-muted"><i class="fa-solid fa-route mr-1"></i> <?=$v_routes?> routes</span>
						</td>
						<td>
							<span class="badge <?=($v_oper === 'UP') ? 'badge-success' : 'badge-default'?>"><?=$v_oper?></span>
						</td>
						<td class="text-center">
							<button type="button" class="btn btn-xs btn-default" onclick="openAddVrfMemberModal('<?=$v_name?>')" title="Tambah Interface Member ke VRF">
								<i class="fa-solid fa-plus text-primary"></i> Member
							</button>
							<button type="button" class="btn btn-xs btn-danger" onclick="deleteVrfClick('<?=$v_name?>')" title="Hapus Domain VRF">
								<i class="fa-solid fa-trash-can"></i>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR VRF -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($vrfs)?> VRF domain(s)</span>
			</div>
			<div>
				<span class="text-muted">Linux Kernel Virtual Routing and Forwarding</span>
			</div>
		</div>

		<?php elseif ($current_tab === 'macsec'): ?>
		<!-- ============================================== -->
		<!-- MACSEC (IEEE 802.1AE LAYER 2 ENCRYPTION) GRID  -->
		<!-- ============================================== -->
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="macsec-grid-table">
				<thead>
					<tr>
						<th class="text-center col-flag" style="width: 35px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 160px;">Name</th>
						<th class="sortable" style="width: 160px;">Parent Device</th>
						<th class="sortable" style="width: 120px;">Cipher Suite</th>
						<th class="sortable" style="width: 110px;">Encryption</th>
						<th class="sortable" style="width: 110px;">Protect / Validate</th>
						<th class="sortable">Secure Channel (SCI)</th>
						<th class="sortable" style="width: 180px;">Comment</th>
						<th class="text-center" style="width: 80px;">Actions</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($macsecs)): ?>
					<tr>
						<td colspan="9" class="text-center text-muted" style="padding: 24px;">
							<i class="fa-solid fa-user-shield fs-18 mb-2"></i><br>
							Belum ada antarmuka MACsec (IEEE 802.1AE) yang dikonfigurasi. Klik tombol <strong>New</strong> di toolbar untuk membuat link terenkripsi Layer 2 hardware/software.
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($macsecs as $ms): ?>
					<?php
					$ms_name = htmlspecialchars($ms['ifname'] ?? '');
					$ms_parent = htmlspecialchars($ms['parent'] ?? '-');
					$ms_cipher = htmlspecialchars($ms['cipher_suite'] ?? 'GCM-AES-128');
					$ms_enc = !empty($ms['encrypt']);
					$ms_protect = !empty($ms['protect']);
					$ms_validate = htmlspecialchars($ms['validate'] ?? 'strict');
					$ms_sci = htmlspecialchars($ms['sci'] ?? '-');
					$ms_comment = htmlspecialchars($ms['comment'] ?? '');
					?>
					<tr data-msname="<?=$ms_name?>">
						<td class="text-center col-flag-cell">
							<i class="fa-solid fa-lock text-success" title="MACsec Secured Link"></i>
						</td>
						<td>
							<strong class="text-primary fs-13"><i class="fa-solid fa-shield-halved mr-1"></i> <?=$ms_name?></strong>
						</td>
						<td>
							<span class="label label-default font-monospace"><?=strtoupper($ms_parent)?></span>
						</td>
						<td>
							<span class="text-info fs-11 font-monospace"><?=$ms_cipher?></span>
						</td>
						<td>
							<span class="badge <?=$ms_enc ? 'badge-success' : 'badge-default'?>"><?=$ms_enc ? 'ON (Encrypted)' : 'OFF (Integrity only)'?></span>
						</td>
						<td>
							<span class="text-muted fs-11"><?=$ms_validate?></span>
						</td>
						<td>
							<code style="font-size: 11px;"><?=$ms_sci?></code>
						</td>
						<td class="text-muted">
							<?=$ms_comment ? $ms_comment : '<span class="text-muted">-</span>'?>
						</td>
						<td class="text-center">
							<button type="button" class="btn btn-xs btn-danger" onclick="deleteMacsecClick('<?=$ms_name?>')" title="Hapus Antarmuka MACsec">
								<i class="fa-solid fa-trash-can"></i>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR MACSEC -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($macsecs)?> MACsec interface(s)</span>
			</div>
			<div>
				<span class="text-muted">IEEE 802.1AE Layer 2 MAC Security (Hardware/Kernel AES-GCM)</span>
			</div>
		</div>

		<?php else: ?>
		<!-- DATA GRID TABLE (MATCHING SCREENSHOT COLUMNS) -->
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="iface-grid-table">
				<thead>
					<tr>
						<th class="text-center col-flag"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable col-name">Name <i class="fa-solid fa-caret-up"></i></th>
						<?php if ($current_tab === 'vlan'): ?>
						<th class="sortable" style="width: 100px;">VLAN ID</th>
						<th class="sortable" style="width: 160px;">Interface (Parent)</th>
						<th class="sortable col-mtu" style="width: 90px;">MTU</th>
						<th class="sortable" style="width: 110px;">Tx</th>
						<th class="sortable" style="width: 110px;">Rx</th>
						<th class="sortable" style="width: 100px;">Tx Packet</th>
						<th class="sortable" style="width: 100px;">Rx Packet</th>
						<th class="sortable">Comment</th>
						<?php else: ?>
						<th class="sortable col-type">Type</th>
						<th class="sortable col-mtu">Actual MTU</th>
						<th class="sortable col-l2mtu">L2 MTU</th>
						<th class="sortable">Tx</th>
						<th class="sortable">Rx</th>
						<th class="sortable">Tx Packet (p/s)</th>
						<th class="sortable">Rx Packet (p/s)</th>
						<th class="sortable">FP Tx</th>
						<th class="sortable">FP Rx</th>
						<th class="sortable">FP Tx Packet (p/s)</th>
						<th class="sortable">FP Rx Packet (p/s)</th>
						<?php endif; ?>
						<th class="text-center col-menu"><i class="fa-solid fa-bars"></i></th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($ifaces)): ?>
					<tr>
						<td colspan="<?=($current_tab === 'vlan' ? 11 : 14)?>" class="text-center text-muted">
							<?=gettext("No interfaces found")?>
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($ifaces as $i): ?>
					<?php
					$is_up      = !empty($i['is_up']);
					$oper_state = strtoupper($i['oper_state'] ?? '');
					$traffic    = $i['traffic'] ?? [];
					$rx_bytes   = (int)($traffic['rx_bytes']   ?? 0);
					$tx_bytes   = (int)($traffic['tx_bytes']   ?? 0);
					$rx_pkts    = (int)($traffic['rx_packets'] ?? 0);
					$tx_pkts    = (int)($traffic['tx_packets'] ?? 0);
					$ifname     = $i['name'];
					$altname    = $i['altname'] ?? $ifname;
					$comment_val = $i['comment'] ?? '';

					// Extract VLAN ID and Parent Device if VLAN
					$vlan_id_val = '-';
					$parent_dev_val = '-';
					if (preg_match('/^([a-zA-Z0-9_-]+)\.([0-9]+)$/', $ifname, $vm)) {
						$parent_dev_val = $vm[1];
						$vlan_id_val = $vm[2];
					} elseif (preg_match('/^vlan([0-9]+)$/i', $ifname, $vm)) {
						$vlan_id_val = $vm[1];
					}
					$lowers = @glob("/sys/class/net/{$ifname}/lower_*");
					if (!empty($lowers)) {
						$parent_dev_val = preg_replace('/^.*\/lower_/', '', $lowers[0]);
					}

					if (is_dir("/sys/class/net/{$ifname}/bridge") || preg_match('/^br[-_]/i', $ifname) || ($i['type'] ?? '') === 'bridge') $type = 'Bridge';
					elseif (is_dir("/sys/class/net/{$ifname}/bonding") || preg_match('/^bond/i', $ifname) || ($i['type'] ?? '') === 'bond') $type = 'Bonding';
					elseif (preg_match('/^vlan/i', $ifname) || strpos($ifname, '.') !== false || ($i['type'] ?? '') === 'vlan') $type = 'VLAN';
					elseif (preg_match('/^eoip/i', $ifname)) $type = 'EoIP';
					elseif (preg_match('/^gre/i', $ifname)) $type = 'GRE';
					elseif (preg_match('/^(tunl|ipip)/i', $ifname)) $type = 'IPIP';
					elseif (preg_match('/^vxlan/i', $ifname)) $type = 'VXLAN';
					elseif (($i['type'] ?? '') === 'macvlan' || preg_match('/^(macvlan|mac[0-9])/i', $ifname)) $type = 'MACVLAN';
					elseif (preg_match('/^wg/i', $ifname)) $type = 'WireGuard';
					elseif (preg_match('/^veth/i', $ifname)) $type = 'vEthernet';
					elseif ($ifname === 'lo') $type = 'Loopback';
					else $type = 'Ethernet';
					$mtu = $i['mtu'] ?? 1500;
					?>
					<tr data-ifname="<?=htmlspecialchars($ifname)?>" onclick="selectRow(this, '<?=htmlspecialchars($ifname)?>', '<?=htmlspecialchars(addslashes($comment_val))?>')" ondblclick="openWinboxEditModal('<?=htmlspecialchars($ifname)?>')" style="cursor: pointer;">
						<!-- Flag -->
						<td class="text-center col-flag-cell">
							<?php if ($is_up): ?>
								<i class="fa-solid fa-check text-success" title="Running / Link UP"></i>
							<?php else: ?>
								<i class="fa-solid fa-minus text-muted" title="Disabled"></i>
							<?php endif; ?>
						</td>
						<!-- Name -->
						<td>
							<strong class="text-primary"><?=htmlspecialchars(strtoupper($altname))?></strong>
							<span class="text-muted text-subname">(<?=htmlspecialchars($ifname)?>)</span>
						</td>

						<?php if ($current_tab === 'vlan'): ?>
						<!-- VLAN ID -->
						<td>
							<span class="label label-primary font-monospace" style="font-size: 12px; padding: 2px 8px;"><?=htmlspecialchars($vlan_id_val)?></span>
						</td>
						<!-- Parent Interface -->
						<td>
							<i class="fa-solid fa-network-wired text-muted" style="margin-right: 4px;"></i>
							<strong><?=htmlspecialchars(strtoupper($parent_dev_val))?></strong>
							<span class="text-muted fs-11">(<?=htmlspecialchars($parent_dev_val)?>)</span>
						</td>
						<!-- MTU -->
						<td><?=htmlspecialchars($mtu)?></td>
						<!-- Tx (Live Rate B/s) -->
						<td class="col-tx" data-bytes="<?=$tx_bytes?>" title="Total: <?=fmt_bytes($tx_bytes)?>">0 B/s</td>
						<!-- Rx (Live Rate B/s) -->
						<td class="col-rx" data-bytes="<?=$rx_bytes?>" title="Total: <?=fmt_bytes($rx_bytes)?>">0 B/s</td>
						<!-- Tx Packet (p/s) -->
						<td class="col-tx-pkts" data-pkts="<?=$tx_pkts?>" title="Total: <?=fmt_pkts($tx_pkts)?> pkts">0</td>
						<!-- Rx Packet (p/s) -->
						<td class="col-rx-pkts" data-pkts="<?=$rx_pkts?>" title="Total: <?=fmt_pkts($rx_pkts)?> pkts">0</td>
						<!-- Comment -->
						<td class="text-muted" title="<?=htmlspecialchars($comment_val)?>">
							<?=htmlspecialchars($comment_val ?: '-')?>
						</td>
						<?php else: ?>
						<!-- Type -->
						<td><span class="label label-default"><?=htmlspecialchars($type)?></span></td>
						<!-- Actual MTU -->
						<td><?=htmlspecialchars($mtu)?></td>
						<!-- L2 MTU -->
						<td>1500</td>
						<!-- Tx (Live Rate B/s) -->
						<td class="col-tx" data-bytes="<?=$tx_bytes?>" title="Total: <?=fmt_bytes($tx_bytes)?>">0 B/s</td>
						<!-- Rx (Live Rate B/s) -->
						<td class="col-rx" data-bytes="<?=$rx_bytes?>" title="Total: <?=fmt_bytes($rx_bytes)?>">0 B/s</td>
						<!-- Tx Packet (p/s) -->
						<td class="col-tx-pkts" data-pkts="<?=$tx_pkts?>" title="Total: <?=fmt_pkts($tx_pkts)?> pkts">0</td>
						<!-- Rx Packet (p/s) -->
						<td class="col-rx-pkts" data-pkts="<?=$rx_pkts?>" title="Total: <?=fmt_pkts($rx_pkts)?> pkts">0</td>
						<!-- FP Tx (FastPath Rate) -->
						<td class="col-fp-tx text-muted">0 B/s</td>
						<!-- FP Rx (FastPath Rate) -->
						<td class="col-fp-rx text-muted">0 B/s</td>
						<!-- FP Tx Packet (p/s) -->
						<td class="col-fp-tx-pkts text-muted">0</td>
						<!-- FP Rx Packet (p/s) -->
						<td class="col-fp-rx-pkts text-muted">0</td>
						<?php endif; ?>
						<!-- Actions / Menu Column (Hamburger context) -->
						<td class="text-center col-menu"></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($ifaces)?> items</span>
			</div>
			<div>
				<span class="text-muted">MitraNet Interface Subsystem</span>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW BRIDGE INTERFACE                -->
<!-- ============================================== -->
<div id="modal-new-bridge" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=bridge">
            <input type="hidden" name="action" value="create_bridge">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-bridge-water text-primary"></i> <?=gettext("New Bridge Interface")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Bridge Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. br0, br-lan" required>
                        <span class="help-block">Nama perangkat bridge kernel Linux (contoh: br0).</span>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Member Interfaces (Ports):")?></label>
                        <select name="members[]" class="form-control selectpicker" multiple data-live-search="true" title="Pilih port / interface...">
                            <?php foreach ($ifaces_raw as $p): if ($p['name'] !== 'lo' && strpos($p['name'], 'br') !== 0): ?>
                                <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['name']))?> (<?=htmlspecialchars($p['name'])?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                        <span class="help-block">Interface fisik atau virtual yang akan digabungkan ke bridge ini.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create Bridge</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW VLAN INTERFACE                  -->
<!-- ============================================== -->
<div id="modal-new-vlan" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=vlan">
            <input type="hidden" name="action" value="create_vlan">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-tags text-primary"></i> <?=gettext("New 802.1Q VLAN Interface")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Parent Interface:")?></label>
                        <select name="parent" class="form-control" required>
                            <?php foreach ($ifaces_raw as $p): if ($p['name'] !== 'lo'): ?>
                                <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['name']))?> (<?=htmlspecialchars($p['name'])?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("VLAN Tag (1 - 4094):")?></label>
                        <input type="number" name="tag" class="form-control" min="1" max="4094" placeholder="100" required>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Description:")?></label>
                        <input type="text" name="descr" class="form-control" placeholder="Office / Hotspot VLAN">
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="auto_dhcp" value="1" checked> <strong><?=gettext("Minta IP Address otomatis via DHCP (dhcpcd)")?></strong>
                        </label>
                    </div>
                    <div class="alert alert-info fs-11" style="margin-bottom:0; padding:8px;">
                        <i class="fa-solid fa-circle-info"></i> <strong>Catatan Trunking:</strong> Pastikan port switch pada router upstream diset sebagai <em>Trunk Port / Tagged</em> untuk VLAN ID ini agar paket 802.1Q diterima.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create VLAN</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW VETHERNET                       -->
<!-- ============================================== -->
<div id="modal-new-vether" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=vether">
            <input type="hidden" name="action" value="create_vether">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-network-wired text-primary"></i> <?=gettext("New vEthernet Interface")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Interface Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="veth10" required>
                    </div>
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("IP Address / CIDR:")?></label>
                        <input type="text" name="ip_cidr" class="form-control" placeholder="10.10.10.1/24" required>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Description:")?></label>
                        <input type="text" name="description" class="form-control" placeholder="KVM VM Host-Guest Subnet">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create vEthernet</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW VETHER TUNNEL                   -->
<!-- ============================================== -->
<div id="modal-new-vether-tunnel" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=vether_tunnel">
            <input type="hidden" name="action" value="create_vether_tunnel">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-arrows-split-up-and-left text-primary"></i> <?=gettext("New vEther Tunnel")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Tunnel Endpoint Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="vtun0" required>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Peer Interface Name:")?></label>
                        <input type="text" name="peer" class="form-control" placeholder="vtun0-peer">
                        <span class="help-block">Kosongkan untuk otomatis menggunakan [nama]-peer.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create Tunnel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW MACVLAN                         -->
<!-- ============================================== -->
<div id="modal-new-macvlan" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=macvlan">
            <input type="hidden" name="action" value="create_macvlan">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-network-wired text-primary"></i> <?=gettext("New MACVLAN Interface")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Interface Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="mac0" required>
                    </div>
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Parent Physical Interface:")?></label>
                        <select name="parent" class="form-control">
                            <?php foreach ($ifaces_raw as $raw_if): ?>
                                <?php if (!empty($raw_if['name']) && !preg_match('/^(lo|wg|tun|tap|macvlan)/i', $raw_if['name'])): ?>
                                    <option value="<?=htmlspecialchars($raw_if['name'])?>"><?=htmlspecialchars($raw_if['name'])?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("MACVLAN Mode:")?></label>
                        <select name="mode" class="form-control">
                            <option value="bridge" selected>bridge (Komunikasi antar macvlan dan luar)</option>
                            <option value="vepa">vepa (Virtual Ethernet Port Aggregator)</option>
                            <option value="private">private (Isolasi penuh)</option>
                            <option value="passthru">passthru (Direct pass-through)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Custom MAC Address:")?></label>
                        <input type="text" name="mac" class="form-control" placeholder="02:42:0a:0a:42:01">
                        <span class="help-block">Kosongkan untuk otomatis di-generate oleh kernel.</span>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="auto_dhcp" value="1" checked> <strong><?=gettext("Minta IP Address otomatis via DHCP (dhcpcd)")?></strong>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create MACVLAN</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW LAGG / BONDING                  -->
<!-- ============================================== -->
<div id="modal-new-lagg" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=lagg">
            <input type="hidden" name="action" value="create_lagg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-link text-primary"></i> <?=gettext("New LAGG / Bonding Interface")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Bond Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="bond0" required>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Bonding Mode:")?></label>
                        <select name="mode" class="form-control">
                            <option value="802.3ad">802.3ad (LACP Dynamic Link Aggregation)</option>
                            <option value="active-backup">active-backup (Failover)</option>
                            <option value="balance-rr">balance-rr (Round-Robin)</option>
                            <option value="balance-xor">balance-xor (XOR)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Member Interfaces:")?></label>
                        <select name="members[]" class="form-control selectpicker" multiple data-live-search="true" title="Pilih port anggota...">
                            <?php foreach ($ifaces_raw as $p): if (preg_match('/^(en|eth|eno|ens|enp)/i', $p['name'])): ?>
                                <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['name']))?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create Bond</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW VRF                             -->
<!-- ============================================== -->
<div id="modal-new-vrf" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=vrf">
            <input type="hidden" name="action" value="create_vrf">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-diagram-project text-primary"></i> <?=gettext("New VRF Instance")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("VRF Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="vrf_cust1" required>
                    </div>
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Routing Table ID (1 - 1000):")?></label>
                        <input type="number" name="table" class="form-control" min="1" max="1000" placeholder="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create VRF</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW EOIP TUNNEL (MikroTik Standard) -->
<!-- ============================================== -->
<div id="modal-new-eoip" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=eoip">
            <input type="hidden" name="action" value="create_eoip">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-network-wired text-primary"></i> <?=gettext("New EoIP Tunnel (MikroTik Standard)")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Interface Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="eoip-tunnel1" required>
                        <span class="help-block">Nama perangkat interface (contoh: eoip-tunnel1 atau eoip1).</span>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("Local Address (Optional):")?></label>
                                <input type="text" name="local" class="form-control" placeholder="10.10.66.228">
                                <span class="help-block">IP WAN router ini (opsional).</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><span class="text-danger">*</span> <?=gettext("Remote Address:")?></label>
                                <input type="text" name="remote" class="form-control" placeholder="103.187.146.126" required>
                                <span class="help-block">IP Publik / WAN router peer lawan.</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><span class="text-danger">*</span> <?=gettext("Tunnel ID (Key):")?></label>
                                <input type="number" name="tunnel_id" class="form-control" min="1" max="65535" value="1" required>
                                <span class="help-block">Harus sama persis dengan Tunnel ID di MikroTik peer.</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("MTU:")?></label>
                                <input type="number" name="mtu" class="form-control" min="576" max="9000" value="1500">
                                <span class="help-block">Default: 1500 (Layer 2 Ethernet).</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Bridge Member (LAN Bridge Port):")?></label>
                        <select name="bridge" class="form-control">
                            <option value="">-- None (Standalone L2 Device) --</option>
                            <?php foreach ($ifaces_raw as $b): if (is_dir("/sys/class/net/{$b['name']}/bridge") || ($b['type'] ?? '') === 'bridge' || preg_match('/^br/i', $b['name'])): ?>
                                <option value="<?=htmlspecialchars($b['name'])?>"><?=htmlspecialchars(strtoupper($b['name']))?> (Bridge)</option>
                            <?php endif; endforeach; ?>
                        </select>
                        <span class="help-block">Gabungkan interface EoIP ini ke Bridge LAN untuk meneruskan broadcast/DHCP langsung antar kantor/site.</span>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("IP Address / CIDR (Opsional jika tidak di-bridge):")?></label>
                        <input type="text" name="ip_cidr" class="form-control" placeholder="10.200.1.1/30">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create EoIP</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW GRE TUNNEL                      -->
<!-- ============================================== -->
<div id="modal-new-gre" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=gre">
            <input type="hidden" name="action" value="create_gre">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-shield-halved text-primary"></i> <?=gettext("New GRE Tunnel")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Interface Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="gre1" required>
                        <span class="help-block">Nama antarmuka GRE (contoh: gre1 atau gre-vps).</span>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("Local Address (Optional):")?></label>
                                <input type="text" name="local" class="form-control" placeholder="10.10.66.228">
                                <span class="help-block">IP WAN router lokal.</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><span class="text-danger">*</span> <?=gettext("Remote Address:")?></label>
                                <input type="text" name="remote" class="form-control" placeholder="103.187.146.126" required>
                                <span class="help-block">IP WAN endpoint router tujuan.</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("TTL (Time To Live):")?></label>
                                <input type="number" name="ttl" class="form-control" min="1" max="255" value="255">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("MTU:")?></label>
                                <input type="number" name="mtu" class="form-control" min="576" max="9000" value="1476">
                                <span class="help-block">Default GRE MTU: 1476 bytes.</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("IP Address / CIDR (Tunnel Point-to-Point):")?></label>
                        <input type="text" name="ip_cidr" class="form-control" placeholder="10.250.1.1/30">
                        <span class="help-block">Alamat IP subnet interkoneksi tunnel.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create GRE</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW IPIP TUNNEL                     -->
<!-- ============================================== -->
<div id="modal-new-iptunnel" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=iptunnel">
            <input type="hidden" name="action" value="create_iptunnel">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-arrow-right-arrow-left text-primary"></i> <?=gettext("New IPIP Tunnel")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Interface Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="ipip1" required>
                        <span class="help-block">Nama antarmuka IPIP (contoh: ipip1).</span>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("Local Address (Optional):")?></label>
                                <input type="text" name="local" class="form-control" placeholder="10.10.66.228">
                                <span class="help-block">IP WAN router lokal.</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><span class="text-danger">*</span> <?=gettext("Remote Address:")?></label>
                                <input type="text" name="remote" class="form-control" placeholder="103.187.146.126" required>
                                <span class="help-block">IP WAN endpoint router tujuan.</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("TTL (Time To Live):")?></label>
                                <input type="number" name="ttl" class="form-control" min="1" max="255" value="64">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("MTU:")?></label>
                                <input type="number" name="mtu" class="form-control" min="576" max="9000" value="1480">
                                <span class="help-block">Default IPIP MTU: 1480 bytes.</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("IP Address / CIDR (Tunnel Point-to-Point):")?></label>
                        <input type="text" name="ip_cidr" class="form-control" placeholder="10.252.1.1/30">
                        <span class="help-block">Alamat IP subnet interkoneksi tunnel layer 3.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create IPIP</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW VXLAN INTERFACE                 -->
<!-- ============================================== -->
<div id="modal-new-vxlan" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=vxlan">
            <input type="hidden" name="action" value="create_vxlan">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-cloud-arrow-up text-primary"></i> <?=gettext("New VXLAN Interface (Overlay Layer 2)")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Interface Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="vxlan100" required>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><span class="text-danger">*</span> <?=gettext("VNI (VXLAN Network ID):")?></label>
                                <input type="number" name="vni" class="form-control" min="1" max="16777215" value="100" required>
                                <span class="help-block">VNI ID (1 s/d 16,777,215).</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("UDP Destination Port:")?></label>
                                <input type="number" name="port" class="form-control" value="4789">
                                <span class="help-block">Standar IANA: 4789.</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("Remote VTEP IP:")?></label>
                                <input type="text" name="remote" class="form-control" placeholder="103.187.146.126">
                                <span class="help-block">IP Unicast VTEP peer endpoint lawan.</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("Underlay Parent Interface:")?></label>
                                <select name="parent" class="form-control">
                                    <option value="">-- Auto Route Detection --</option>
                                    <?php foreach ($ifaces_raw as $p): if (!preg_match('/^(lo|vxlan|eoip)/i', $p['name'])): ?>
                                        <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['name']))?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Bridge Member (LAN Bridge Port):")?></label>
                        <select name="bridge" class="form-control">
                            <option value="">-- None (Standalone Overlay L2) --</option>
                            <?php foreach ($ifaces_raw as $b): if (is_dir("/sys/class/net/{$b['name']}/bridge") || ($b['type'] ?? '') === 'bridge' || preg_match('/^br/i', $b['name'])): ?>
                                <option value="<?=htmlspecialchars($b['name'])?>"><?=htmlspecialchars(strtoupper($b['name']))?> (Bridge)</option>
                            <?php endif; endforeach; ?>
                        </select>
                        <span class="help-block">Gabungkan antarmuka VXLAN ke Bridge lokal untuk ekstensi VLAN/LAN overlay multi-site.</span>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("IP Address / CIDR (Optional):")?></label>
                        <input type="text" name="ip_cidr" class="form-control" placeholder="192.168.100.1/24">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create VXLAN</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW MACSEC (IEEE 802.1AE)           -->
<!-- ============================================== -->
<div id="modal-new-macsec" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=macsec">
            <input type="hidden" name="action" value="create_macsec">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-shield-halved text-primary"></i> <?=gettext("New MACsec Interface (IEEE 802.1AE)")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("MACsec Interface Name:")?></label>
                        <input type="text" name="name" class="form-control" placeholder="macsec0" required>
                        <span class="help-block">Nama perangkat antarmuka virtual MACsec (contoh: macsec0).</span>
                    </div>
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("Parent Physical/Virtual Interface:")?></label>
                        <select name="parent" class="form-control" required>
                            <option value="">-- Pilih Parent Interface --</option>
                            <?php foreach ($ifaces_raw as $p): if ($p['name'] !== 'lo' && strpos($p['name'], 'macsec') !== 0): ?>
                                <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['altname'] ?? $p['name']))?> (<?=htmlspecialchars($p['name'])?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                        <span class="help-block">Antarmuka fisik atau virtual yang menjadi pembawa (carrier link) Layer 2.</span>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="encrypt" value="1" checked> <strong><?=gettext("Aktifkan Enkripsi Data (AES-GCM Payload Encryption)")?></strong>
                        </label>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Pre-Shared Key / SAK (128-bit / 32 Karakter Hex):")?></label>
                        <input type="text" name="key" class="form-control font-monospace" placeholder="00112233445566778899aabbccddeeff" maxlength="64">
                        <span class="help-block">Kunci enkripsi simetris 128-bit (32 karakter hex). Digunakan untuk SA TX channel.</span>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("Key ID (AN/ID Hex):")?></label>
                                <input type="text" name="key_id" class="form-control font-monospace" value="01" maxlength="4">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?=gettext("SCI (Secure Channel Identifier Hex/Int, Opsional):")?></label>
                                <input type="text" name="sci" class="form-control font-monospace" placeholder="0x1">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Comment / Description:")?></label>
                        <input type="text" name="comment" class="form-control" placeholder="Link interkoneksi aman Point-to-Point Layer 2">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i> Create MACsec</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD VRF MEMBER INTERFACE                -->
<!-- ============================================== -->
<div id="modal-add-vrf-member" class="modal fade" role="dialog">
    <div class="modal-dialog modal-sm">
        <form method="post" action="interfaces.php?tab=vrf">
            <input type="hidden" name="action" value="add_vrf_member">
            <input type="hidden" name="vrf_name" id="vrf-target-name-hidden" value="">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa-solid fa-plus text-primary"></i> <?=gettext("Add Interface to VRF")?>: <span id="vrf-target-label"></span>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?=gettext("Select Interface:")?></label>
                        <select name="member_interface" class="form-control" required>
                            <option value="">-- Pilih Antarmuka --</option>
                            <?php foreach ($ifaces_raw as $p): if (!preg_match('/^(lo|enp0s3)/i', $p['name'])): ?>
                                <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['altname'] ?? $p['name']))?> (<?=htmlspecialchars($p['name'])?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                        <span class="help-block">Interface ini akan diikat sebagai slave dari domain VRF terpilih.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-check icon-embed-btn"></i> Add Member</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW INTERFACE LIST                  -->
<!-- MODAL: ADD / EDIT INTERFACE LIST               -->
<!-- ============================================== -->
<div id="modal-new-interface-list" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <form method="post" action="interfaces.php?tab=interface_list" id="form-interface-list">
            <input type="hidden" name="action" value="save_interface_list">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" id="modal-interface-list-title">
                        <i class="fa-solid fa-layer-group text-primary"></i> <?=gettext("New Interface List (Group)")?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><span class="text-danger">*</span> <?=gettext("List Name:")?></label>
                        <input type="text" name="list_name" id="modal-iface-list-name" class="form-control" placeholder="e.g. WAN, LAN, VPN_GROUP, ISOLATED" required style="text-transform: uppercase;">
                        <span class="help-block">Nama group / list antarmuka (huruf kapital, contoh: WAN, LAN).</span>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Member Interfaces:")?></label>
                        <select name="members[]" id="modal-iface-list-members" class="form-control selectpicker" multiple data-live-search="true" title="Pilih interface anggota...">
                            <?php foreach ($ifaces_raw as $p): if ($p['name'] !== 'lo'): ?>
                                <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['altname'] ?? $p['name']))?> (<?=htmlspecialchars($p['name'])?>)</option>
                            <?php endif; endforeach; ?>
                        </select>
                        <span class="help-block">Daftar interface yang dimasukkan ke dalam group list ini.</span>
                    </div>
                    <div class="form-group">
                        <label><?=gettext("Comment / Description:")?></label>
                        <input type="text" name="comment" id="modal-iface-list-comment" class="form-control" placeholder="Deskripsi pemakaian list (misal: Uplink ISP & Failover)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btn-submit-iface-list"><i class="fa-solid fa-plus icon-embed-btn"></i> <?=gettext("Create List")?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD NEW GENERIC INTERFACE (All tab)     -->
<!-- ============================================== -->
<div id="modal-new-interface" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa-solid fa-network-wired text-primary"></i> <?=gettext("New Interface")?>
                </h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?=gettext("Select Interface Type:")?></label>
                    <select class="form-control" id="new-iface-type">
                        <option value="eoip">EoIP Tunnel (Ethernet over IP - MikroTik)</option>
                        <option value="gre">GRE Tunnel (Generic Routing Encapsulation)</option>
                        <option value="iptunnel">IPIP Tunnel (IP over IP)</option>
                        <option value="vxlan">VXLAN (Virtual Extensible LAN)</option>
                        <option value="bridge">Bridge Interface</option>
                        <option value="vlan">VLAN Interface (802.1Q)</option>
                        <option value="macvlan">MACVLAN Interface (Virtual MAC)</option>
                        <option value="macsec">MACsec Interface (IEEE 802.1AE Layer 2 Encryption)</option>
                        <option value="vether">vEthernet (KVM / Host-Guest)</option>
                        <option value="vether_tunnel">vEther Tunnel</option>
                        <option value="lagg">Bonding / LAGG</option>
                        <option value="vrf">VRF Domain</option>
                        <option value="interface_list">Interface List (MikroTik Standard Group)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="proceedToTypeModal()"><?=gettext("Next")?> &rarr;</button>
            </div>
        </div>
    </div>
</div>
<!-- ============================================== -->
<!-- MODAL: WINBOX-STYLE INTERFACE EDIT / DETAIL    -->
<!-- ============================================== -->
<div id="modal-edit-interface" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg winbox-modal-dialog">
        <form method="post" action="interfaces.php" id="form-edit-interface" class="form-horizontal">
            <input type="hidden" name="action" value="save_interface">
            <input type="hidden" name="interface" id="edit-iface-name-hidden" value="">
            <div class="modal-content winbox-window-popup">
                
                <!-- WINBOX WINDOW HEADER -->
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-window-maximize"></i> Interface &gt; <span id="winbox-title-label">ether1</span>
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>

                <!-- WINBOX TAB NAVIGATION -->
                <div class="winbox-popup-tabs-bar">
                    <ul class="nav nav-tabs winbox-tabs-nav" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#winbox-tab-general" aria-controls="winbox-tab-general" role="tab" data-toggle="tab">General</a>
                        </li>
                        <li role="presentation" id="li-winbox-tab-vlan" style="display: none;">
                            <a href="#winbox-tab-vlan" aria-controls="winbox-tab-vlan" role="tab" data-toggle="tab">VLAN</a>
                        </li>
                        <li role="presentation" id="li-winbox-tab-bridge" style="display: none;">
                            <a href="#winbox-tab-bridge" aria-controls="winbox-tab-bridge" role="tab" data-toggle="tab">Bridge</a>
                        </li>
                        <li role="presentation" id="li-winbox-tab-ethernet">
                            <a href="#winbox-tab-ethernet" aria-controls="winbox-tab-ethernet" role="tab" data-toggle="tab">IP / Settings</a>
                        </li>
                        <li role="presentation">
                            <a href="#winbox-tab-loopprotect" aria-controls="winbox-tab-loopprotect" role="tab" data-toggle="tab">Loop Protect</a>
                        </li>
                        <li role="presentation">
                            <a href="#winbox-tab-status" aria-controls="winbox-tab-status" role="tab" data-toggle="tab">Status</a>
                        </li>
                        <li role="presentation">
                            <a href="#winbox-tab-traffic" aria-controls="winbox-tab-traffic" role="tab" data-toggle="tab">Traffic</a>
                        </li>
                    </ul>
                </div>

                <!-- WINBOX BODY: 2-COLUMN LAYOUT (FORM + ACTIONS) -->
                <div class="modal-body winbox-popup-body">
                    <div class="winbox-body-columns">
                        
                        <!-- LEFT COLUMN: TAB CONTENTS -->
                        <div class="winbox-content-left tab-content">
                            
                            <!-- TAB 1: GENERAL -->
                            <div role="tabpanel" class="tab-pane active" id="winbox-tab-general">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Enabled</label>
                                    <div class="col-sm-9">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="enable" id="edit-iface-enable" value="yes">
                                                <span class="winbox-checkbox-custom"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Comment</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="comment" id="edit-iface-comment" class="form-control input-sm" placeholder="">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="new_name" id="edit-iface-displayname" class="form-control input-sm">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Original / Kernel</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="edit-iface-defaultname" class="form-control input-sm" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Type</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="edit-iface-type" class="form-control input-sm" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">MTU</label>
                                    <div class="col-sm-9">
                                        <input type="number" name="mtu" id="edit-iface-mtu" class="form-control input-sm" min="576" max="9000" value="1500">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Actual MTU</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="edit-iface-actual-mtu" class="form-control input-sm" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">L2 MTU</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="edit-iface-l2mtu" class="form-control input-sm" value="1500" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">VRF</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="edit-iface-vrf" class="form-control input-sm" value="main" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">MAC Address</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="edit-iface-mac" class="form-control input-sm font-monospace" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">ARP</label>
                                    <div class="col-sm-9">
                                        <select name="arp" id="edit-iface-arp" class="form-control input-sm">
                                            <option value="enabled" selected>enabled</option>
                                            <option value="disabled">disabled</option>
                                            <option value="proxy-arp">proxy-arp</option>
                                            <option value="reply-only">reply-only</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: VLAN SPECIFIC CONFIGURATION -->
                            <div role="tabpanel" class="tab-pane" id="winbox-tab-vlan">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">VLAN ID</label>
                                    <div class="col-sm-9">
                                        <input type="number" name="vlan_id" id="edit-vlan-id-input" class="form-control input-sm font-monospace" min="1" max="4094" placeholder="e.g. 10" style="font-weight: bold; color: #2563eb;">
                                        <span class="help-block fs-11">802.1Q IEEE VLAN Tag (1 - 4094). Dapat diubah langsung.</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Interface (Parent)</label>
                                    <div class="col-sm-9">
                                        <select name="vlan_parent" id="edit-vlan-parent-select" class="form-control input-sm">
                                            <?php foreach ($ifaces_raw as $p): if ($p['name'] !== 'lo' && strpos($p['name'], '.') === false && !preg_match('/^(vlan|wg)/i', $p['name'])): ?>
                                                <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['altname'] ?? $p['name']))?> (<?=htmlspecialchars($p['name'])?>)</option>
                                            <?php endif; endforeach; ?>
                                        </select>
                                        <span class="help-block fs-11">Port fisik atau induk tempat VLAN trunking ditumpangkan.</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Protocol</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control input-sm" value="802.1Q" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: BRIDGE CONFIGURATION & PORTS -->
                            <div role="tabpanel" class="tab-pane" id="winbox-tab-bridge">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">STP Protocol</label>
                                    <div class="col-sm-9">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="edit-bridge-stp" disabled>
                                                <span>Enabled (802.1D Spanning Tree)</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Priority</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="edit-bridge-prio" class="form-control input-sm" readonly>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Member Ports</label>
                                    <div class="col-sm-9">
                                        <div id="bridge-ports-container" style="background:#f8f9fa; border:1px solid #ddd; border-radius:4px; padding:8px; min-height:80px; max-height:160px; overflow-y:auto; margin-bottom:8px;">
                                            <span class="text-muted fs-11">Loading bridge member ports...</span>
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <select id="bridge-new-port-select" class="form-control">
                                                <option value="">-- Add interface to bridge --</option>
                                                <?php foreach ($ifaces_raw as $p): if ($p['name'] !== 'lo' && !preg_match('/^(test|br[-_])/i', $p['name'])): ?>
                                                    <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['altname'] ?? $p['name']))?> (<?=htmlspecialchars($p['name'])?>)</option>
                                                <?php endif; endforeach; ?>
                                            </select>
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default btn-sm" onclick="addBridgePortClick()"><i class="fa-solid fa-plus text-primary"></i> Add Port</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: ETHERNET / IP -->
                            <div role="tabpanel" class="tab-pane" id="winbox-tab-ethernet">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Static IPv4</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="ipaddr" id="edit-iface-ip" class="form-control input-sm" placeholder="192.168.1.1">
                                    </div>
                                    <div class="col-sm-3">
                                        <select name="subnet" id="edit-iface-subnet" class="form-control input-sm">
                                            <?php for ($p = 32; $p >= 8; $p--): ?>
                                                <option value="<?=$p?>">/<?=$p?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Active IPs</label>
                                    <div class="col-sm-9" id="edit-iface-assigned-ips">
                                        <span class="text-muted fs-11">None</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Speed / Duplex</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="edit-iface-speed-input" class="form-control input-sm" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 4: LOOP PROTECT -->
                            <div role="tabpanel" class="tab-pane" id="winbox-tab-loopprotect">
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Loop Protect</label>
                                    <div class="col-sm-8">
                                        <select class="form-control input-sm">
                                            <option value="default" selected>default (off)</option>
                                            <option value="on">on</option>
                                            <option value="off">off</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-4 control-label">Send Interval</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control input-sm" value="00:00:05" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 4: STATUS -->
                            <div role="tabpanel" class="tab-pane" id="winbox-tab-status">
                                <table class="table table-condensed table-bordered winbox-status-table">
                                    <tbody>
                                        <tr><th style="width:35%">Link Status</th><td id="winbox-stat-link">Unknown</td></tr>
                                        <tr><th>Speed</th><td id="winbox-stat-speed">-</td></tr>
                                        <tr><th>Duplex</th><td id="winbox-stat-duplex">-</td></tr>
                                        <tr><th>Carrier</th><td id="winbox-stat-carrier">-</td></tr>
                                        <tr><th>Oper State</th><td id="winbox-stat-oper">-</td></tr>
                                        <tr><th>Kernel Identifier</th><td id="winbox-stat-raw">-</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- TAB 5: TRAFFIC (WINBOX REAL-TIME GRAPH + COUNTERS) -->
                            <div role="tabpanel" class="tab-pane" id="winbox-tab-traffic">
                                <!-- Real-time Canvas Area Chart -->
                                <div style="background: #1e293b; border: 1px solid #334155; border-radius: 4px; padding: 10px; margin-bottom: 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 11px;">
                                        <div style="display: flex; gap: 12px;">
                                            <span style="color: #10b981; font-weight: 600;"><i class="fa-solid fa-square" style="color: #10b981;"></i> Tx: <span id="winbox-graph-tx-cur">0 B/s</span></span>
                                            <span style="color: #0ea5e9; font-weight: 600;"><i class="fa-solid fa-square" style="color: #0ea5e9;"></i> Rx: <span id="winbox-graph-rx-cur">0 B/s</span></span>
                                        </div>
                                        <div style="color: #94a3b8; font-size: 10px;">
                                            <span>Peak: <span id="winbox-graph-peak" style="color: #f8fafc;">0 B/s</span></span> | <span>Window: 60s</span>
                                        </div>
                                    </div>
                                    <div style="position: relative; width: 100%; height: 140px; background: #0f172a; border-radius: 3px; overflow: hidden;">
                                        <canvas id="winbox-traffic-canvas" width="550" height="140" style="width: 100%; height: 100%; display: block;"></canvas>
                                    </div>
                                </div>

                                <table class="table table-condensed table-bordered winbox-status-table">
                                    <tbody>
                                        <tr><th style="width:35%">Tx Live Rate</th><td id="winbox-traffic-txrate" class="font-weight-bold" style="color: #10b981;">0 B/s</td></tr>
                                        <tr><th>Rx Live Rate</th><td id="winbox-traffic-rxrate" class="font-weight-bold" style="color: #0ea5e9;">0 B/s</td></tr>
                                        <tr><th>Tx Packets (p/s)</th><td id="winbox-traffic-txpps">0</td></tr>
                                        <tr><th>Rx Packets (p/s)</th><td id="winbox-traffic-rxpps">0</td></tr>
                                        <tr><th>Total Tx Bytes</th><td id="winbox-traffic-txbytes">0 B</td></tr>
                                        <tr><th>Total Rx Bytes</th><td id="winbox-traffic-rxbytes">0 B</td></tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <!-- RIGHT COLUMN: WINBOX ACTIONS -->
                        <div class="winbox-content-right">
                            <div class="winbox-actions-header">
                                <i class="fa-solid fa-bolt"></i> Actions
                            </div>
                            <ul class="winbox-actions-list">
                                <li><a href="javascript:void(0)" onclick="alert('Torch monitoring launched')">Torch</a></li>
                                <li><a href="javascript:void(0)" onclick="alert('Counters reset')">Reset Traffic Counters</a></li>
                                <li><a href="javascript:void(0)" onclick="alert('Cable test ok')">Cable Test</a></li>
                                <li><a href="javascript:void(0)" onclick="alert('LED blink signaled')">Blink</a></li>
                                <li><a href="javascript:void(0)" onclick="alert('MAC reset to vendor default')">Reset MAC Address</a></li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- WINBOX FOOTER: STATUS + OK / APPLY / CANCEL -->
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-status">
                        <span class="badge winbox-running-badge" id="winbox-badge-state">RUNNING</span>
                        <span class="winbox-link-msg" id="winbox-link-msg">link ok</span>
                    </div>
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-default" onclick="submitWinBoxModal(false)">Apply</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitWinBoxModal(true)">OK</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>


<?php endif; ?>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>

<?php if (!$selected_iface): ?>
<script type="text/javascript">
// Define globally so any onclick in HTML works immediately
var selectedIface = null;
var selectedListName = null;
var selectedListComment = '';
var selectedListMembers = [];

window.selectListRow = function(tr, listName, comment, members) {
    $('#iface-list-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedListName = listName;
    selectedListComment = comment || '';
    selectedListMembers = Array.isArray(members) ? members : [];
    selectedIface = null; // Clear iface selection when selecting interface list

    // Aktifkan tombol toolbar WinBox: Edit, Remove, Comment
    $('#btn-edit, #btn-remove, #btn-comment').prop('disabled', false);
    // Tombol Enable & Disable dinonaktifkan untuk logical group list
    $('#btn-enable, #btn-disable').prop('disabled', true);
};

window.editInterfaceListClick = function(listName, comment, members) {
    if (!listName) listName = selectedListName;
    if (!listName) return;

    if (comment === undefined && selectedListName === listName) comment = selectedListComment;
    if (members === undefined && selectedListName === listName) members = selectedListMembers;

    $('#modal-interface-list-title').html('<i class="fa-solid fa-layer-group text-primary"></i> Edit Interface List: ' + listName);
    $('#modal-iface-list-name').val(listName).prop('readonly', true);
    $('#modal-iface-list-comment').val(comment || '');

    var memArray = Array.isArray(members) ? members : [];
    $('#modal-iface-list-members').val(memArray);
    if ($.fn.selectpicker) {
        $('#modal-iface-list-members').selectpicker('refresh');
    }

    $('#btn-submit-iface-list').html('<i class="fa-solid fa-check icon-embed-btn"></i> Save Changes');
    $('#modal-new-interface-list').modal('show');
};

window.resetInterfaceListModal = function() {
    $('#modal-interface-list-title').html('<i class="fa-solid fa-layer-group text-primary"></i> ' + <?=json_encode(gettext("New Interface List (Group)"))?>);
    $('#modal-iface-list-name').val('').prop('readonly', false);
    $('#modal-iface-list-comment').val('');
    $('#modal-iface-list-members').val([]);
    if ($.fn.selectpicker) {
        $('#modal-iface-list-members').selectpicker('refresh');
    }
    $('#btn-submit-iface-list').html('<i class="fa-solid fa-plus icon-embed-btn"></i> ' + <?=json_encode(gettext("Create List"))?>);
};

window.openNewModal = function() {
    var currentTab = <?=json_encode($current_tab)?>;
    if (currentTab === 'interface_list') {
        resetInterfaceListModal();
        $('#modal-new-interface-list').modal('show');
    } else if (currentTab === 'eoip') {
        $('#modal-new-eoip').modal('show');
    } else if (currentTab === 'gre') {
        $('#modal-new-gre').modal('show');
    } else if (currentTab === 'iptunnel') {
        $('#modal-new-iptunnel').modal('show');
    } else if (currentTab === 'vxlan') {
        $('#modal-new-vxlan').modal('show');
    } else if (currentTab === 'bridge') {
        $('#modal-new-bridge').modal('show');
    } else if (currentTab === 'vlan') {
        $('#modal-new-vlan').modal('show');
    } else if (currentTab === 'macvlan') {
        $('#modal-new-macvlan').modal('show');
    } else if (currentTab === 'macsec') {
        $('#modal-new-macsec').modal('show');
    } else if (currentTab === 'vether') {
        $('#modal-new-vether').modal('show');
    } else if (currentTab === 'vether_tunnel') {
        $('#modal-new-vether-tunnel').modal('show');
    } else if (currentTab === 'lagg' || currentTab === 'bonding') {
        $('#modal-new-lagg').modal('show');
    } else if (currentTab === 'vrf') {
        $('#modal-new-vrf').modal('show');
    } else {
        $('#modal-new-interface').modal('show');
    }
};

window.proceedToTypeModal = function() {
    var selectedType = $('#new-iface-type').val();
    $('#modal-new-interface').modal('hide');
    setTimeout(function() {
        if (selectedType === 'interface_list') {
            $('#modal-new-interface-list').modal('show');
        } else if (selectedType === 'eoip') {
            $('#modal-new-eoip').modal('show');
        } else if (selectedType === 'gre') {
            $('#modal-new-gre').modal('show');
        } else if (selectedType === 'iptunnel') {
            $('#modal-new-iptunnel').modal('show');
        } else if (selectedType === 'vxlan') {
            $('#modal-new-vxlan').modal('show');
        } else if (selectedType === 'bridge') {
            $('#modal-new-bridge').modal('show');
        } else if (selectedType === 'vlan') {
            $('#modal-new-vlan').modal('show');
        } else if (selectedType === 'macvlan') {
            $('#modal-new-macvlan').modal('show');
        } else if (selectedType === 'macsec') {
            $('#modal-new-macsec').modal('show');
        } else if (selectedType === 'vether') {
            $('#modal-new-vether').modal('show');
        } else if (selectedType === 'vether_tunnel') {
            $('#modal-new-vether-tunnel').modal('show');
        } else if (selectedType === 'lagg') {
            $('#modal-new-lagg').modal('show');
        } else if (selectedType === 'vrf') {
            $('#modal-new-vrf').modal('show');
        }
    }, 350);
};

window.deleteInterfaceListClick = function(listName) {
    if (!listName) return;
    MitraNet.confirmDelete({
        title: 'Hapus Interface List?',
        name: listName,
        warning: 'Grup antarmuka ini akan dihapus dari konfigurasi sistem.',
        url: 'interfaces.php?tab=interface_list',
        data: { action: 'delete_interface_list', list_name: listName },
        onSuccess: function() {
            location.reload();
        }
    });
};

window.deleteVrfClick = function(vrfName) {
    if (!vrfName) return;
    MitraNet.confirmDelete({
        title: 'Hapus Domain VRF?',
        name: vrfName,
        warning: 'Domain routing VRF ini dan seluruh asosiasi interface akan dihapus.',
        url: 'interfaces.php?tab=vrf',
        data: { action: 'delete_vrf', vrf_name: vrfName },
        onSuccess: function() {
            location.reload();
        }
    });
};

window.openAddVrfMemberModal = function(vrfName) {
    if (!vrfName) return;
    $('#vrf-target-name-hidden').val(vrfName);
    $('#vrf-target-label').text(vrfName);
    $('#modal-add-vrf-member').modal('show');
};

window.removeVrfMemberClick = function(vrfName, ifaceName) {
    if (!vrfName || !ifaceName) return;
    MitraNet.confirmDelete({
        title: 'Lepas Interface dari VRF?',
        name: ifaceName + ' &rarr; ' + vrfName,
        warning: 'Antarmuka ini akan dilepas dari domain VRF dan dikembalikan ke tabel routing utama (main).',
        url: 'interfaces.php?tab=vrf',
        data: { action: 'remove_vrf_member', vrf_name: vrfName, member_interface: ifaceName },
        onSuccess: function() {
            location.reload();
        }
    });
};

window.deleteMacsecClick = function(msName) {
    if (!msName) return;
    MitraNet.confirmDelete({
        title: 'Hapus Antarmuka MACsec?',
        name: msName,
        warning: 'Link terenkripsi Layer 2 IEEE 802.1AE ini akan dihapus dari kernel.',
        url: 'interfaces.php?tab=macsec',
        data: { action: 'delete_macsec', name: msName },
        onSuccess: function() {
            location.reload();
        }
    });
};

window.selectRow = function(tr, ifname, comment) {
    $('#iface-grid-table tbody tr').removeClass('selected');
    $('#iface-list-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedIface = ifname;
    selectedListName = null;
    selectedListComment = '';
    selectedListMembers = [];
    $('#btn-edit, #btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', false);
};

window.openWinboxEditModal = function(ifname) {
    if (!ifname) ifname = selectedIface;
    if (!ifname) return;

    // Reset fields & show loading indicator
    $('#winbox-title-label').text(ifname);
    $('#edit-iface-name-hidden').val(ifname);
    $('#edit-iface-displayname').val(ifname);
    $('#edit-iface-defaultname').val(ifname);
    $('#winbox-link-msg').text('reading link...');

    // Fetch real-time hardware & kernel properties
        $.ajax({
            url: 'interfaces.php?ajax=detail&if=' + encodeURIComponent(ifname),
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(res) {
                if (!res || !res.success || !res.data) {
                    alert('Gagal mengambil data interface dari kernel.');
                    return;
                }
                var d = res.data;
                $('#winbox-title-label').text(d.altname || d.name);
                $('#edit-iface-name-hidden').val(d.name);
                $('#edit-iface-displayname').val(d.altname || d.name);
                $('#edit-iface-defaultname').val(d.name);
                $('#edit-iface-enable').prop('checked', d.is_up);
                $('#edit-iface-comment').val(d.comment || '');
                $('#edit-iface-type').val(d.type || 'Ethernet');
                $('#edit-iface-mtu').val(d.mtu || 1500);
                $('#edit-iface-actual-mtu').val(d.mtu || 1500);
                $('#edit-iface-l2mtu').val(d.l2mtu || 1500);
                $('#edit-iface-vrf').val(d.vrf || 'main');
                $('#edit-iface-mac').val(d.mac_address || '00:00:00:00:00:00');
                // ARP Mode
                if (d.arp) {
                    $('#edit-iface-arp').val(d.arp);
                } else {
                    $('#edit-iface-arp').val('enabled');
                }

                // VLAN Sub-tab & Properties
                var isVlan = (d.type && d.type.toLowerCase() === 'vlan') || !!d.vlan_id;
                if (isVlan) {
                    $('#li-winbox-tab-vlan').show();
                    $('#edit-vlan-id-input').val(d.vlan_id || '');
                    if (d.vlan_parent) {
                        $('#edit-vlan-parent-select').val(d.vlan_parent);
                    }
                } else {
                    $('#li-winbox-tab-vlan').hide();
                    if ($('#li-winbox-tab-vlan').hasClass('active')) {
                        $('.winbox-tabs-nav a[href="#winbox-tab-general"]').tab('show');
                    }
                }

                // Bridge Sub-tab & Member Ports
                var isBridge = (d.type && d.type.toLowerCase() === 'bridge');
                if (isBridge) {
                    $('#li-winbox-tab-bridge').show();
                    $('#edit-bridge-stp').prop('checked', !!d.bridge_stp);
                    $('#edit-bridge-prio').val(d.bridge_prio || '32768');
                    renderBridgePorts(d.bridge_ports || [], d.name);
                } else {
                    $('#li-winbox-tab-bridge').hide();
                    // Switch back to General tab if Bridge tab was active
                    if ($('#li-winbox-tab-bridge').hasClass('active')) {
                        $('.winbox-tabs-nav a[href="#winbox-tab-general"]').tab('show');
                    }
                }

                // IP Addresses
                if (d.ipv4 && d.ipv4.length > 0) {
                    var ipParts = d.ipv4[0].split('/');
                    $('#edit-iface-ip').val(ipParts[0] || '');
                    $('#edit-iface-subnet').val(ipParts[1] || '24');
                    
                    var ipBadges = d.ipv4.map(function(ip) {
                        return '<span class="label label-info fs-11 mr-1">' + ip + '</span>';
                    }).join(' ');
                    $('#edit-iface-assigned-ips').html(ipBadges);
                } else {
                    $('#edit-iface-ip').val('');
                    $('#edit-iface-subnet').val('24');
                    $('#edit-iface-assigned-ips').html('<span class="text-muted fs-11">None</span>');
                }

                $('#edit-iface-speed-input').val(d.speed + ' / ' + d.duplex);

                // Status tab real hardware data
                $('#winbox-stat-link').html(d.carrier ? '<span class="text-success font-weight-bold">Link Up (Carrier detected)</span>' : '<span class="text-warning font-weight-bold">No Carrier (Unplugged / Ready)</span>');
                $('#winbox-stat-speed').text(d.speed);
                $('#winbox-stat-duplex').text(d.duplex);
                $('#winbox-stat-carrier').text(d.carrier ? '1 (Active)' : '0 (No Link)');
                $('#winbox-stat-oper').text(d.oper_state);
                $('#winbox-stat-raw').text(d.name);

                // Traffic tab live counters
                if (d.traffic) {
                    $('#winbox-traffic-txbytes').text(formatBytes(d.traffic.tx_bytes || 0));
                    $('#winbox-traffic-rxbytes').text(formatBytes(d.traffic.rx_bytes || 0));
                }

                // Winbox badge
                if (d.is_up && d.oper_state === 'UP') {
                    $('#winbox-badge-state').removeClass('winbox-badge-down winbox-badge-ready').addClass('winbox-badge-up').text('RUNNING');
                    $('#winbox-link-msg').text('link ok');
                } else if (d.is_up) {
                    $('#winbox-badge-state').removeClass('winbox-badge-up winbox-badge-down').addClass('winbox-badge-ready').text('READY');
                    $('#winbox-link-msg').text('no carrier');
                } else {
                    $('#winbox-badge-state').removeClass('winbox-badge-up winbox-badge-ready').addClass('winbox-badge-down').text('DISABLED');
                    $('#winbox-link-msg').text('administratively down');
                }

                // Show modal centered
                $('#modal-edit-interface').modal('show');
            },
            error: function() {
                alert('Gagal berkomunikasi dengan server.');
            }
        });
    };

    window.renderBridgePorts = function(ports, bridgeName) {
        var $container = $('#bridge-ports-container');
        if (!ports || ports.length === 0) {
            $container.html('<span class="text-muted fs-11">Belum ada interface anggota (slave port) dalam bridge ini.</span>');
            return;
        }

        var html = '<div style="display:flex; flex-wrap:wrap; gap:6px;">';
        ports.forEach(function(port) {
            html += '<span class="label label-primary" style="display:inline-flex; align-items:center; padding:5px 8px; font-size:12px;">';
            html += '<i class="fa-solid fa-ethernet" style="margin-right:5px;"></i> ' + port;
            html += '<button type="button" class="btn btn-xs btn-link text-white" style="margin-left:6px; padding:0; line-height:1; color:#fff;" onclick="removeBridgePortClick(\'' + port + '\', \'' + bridgeName + '\')" title="Lepas port ' + port + '">&times;</button>';
            html += '</span>';
        });
        html += '</div>';
        $container.html(html);
    };

    window.addBridgePortClick = function() {
        var bridgeName = $('#edit-iface-name-hidden').val();
        var port = $('#bridge-new-port-select').val();
        if (!port) {
            alert('Pilih port antarmuka terlebih dahulu.');
            return;
        }

        $.ajax({
            url: 'interfaces.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'add_bridge_port',
                interface: bridgeName,
                port: port,
                ajax: 1
            },
            success: function(res) {
                if (res && res.success) {
                    $('#bridge-new-port-select').val('');
                    // Refresh detail
                    refreshBridgeDetail(bridgeName);
                } else {
                    alert('Gagal menambahkan port: ' + (res && res.error ? res.error : 'Unknown error'));
                }
            },
            error: function() {
                alert('Gagal menghubungi server untuk menambah port.');
            }
        });
    };

    window.removeBridgePortClick = function(port, bridgeName) {
        if (!bridgeName) bridgeName = $('#edit-iface-name-hidden').val();
        if (!confirm('Yakin ingin melepas port "' + port + '" dari bridge "' + bridgeName + '"?')) return;

        $.ajax({
            url: 'interfaces.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'remove_bridge_port',
                interface: bridgeName,
                port: port,
                ajax: 1
            },
            success: function(res) {
                if (res && res.success) {
                    refreshBridgeDetail(bridgeName);
                } else {
                    alert('Gagal melepas port: ' + (res && res.error ? res.error : 'Unknown error'));
                }
            },
            error: function() {
                alert('Gagal menghubungi server untuk melepas port.');
            }
        });
    };

    function refreshBridgeDetail(bridgeName) {
        $.ajax({
            url: 'interfaces.php?ajax=detail&if=' + encodeURIComponent(bridgeName),
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(res) {
                if (res && res.success && res.data) {
                    renderBridgePorts(res.data.bridge_ports || [], bridgeName);
                }
            }
        });
    }

    window.submitWinBoxModal = function(closeModal) {
        var ifname = $('#edit-iface-name-hidden').val();
        var new_name = $('#edit-iface-displayname').val();
        var isEnabled = $('#edit-iface-enable').is(':checked') ? 'yes' : '';
        var mtu = $('#edit-iface-mtu').val();
        var arp = $('#edit-iface-arp').val();
        var comment = $('#edit-iface-comment').val();
        var ipaddr = $('#edit-iface-ip').val();
        var subnet = $('#edit-iface-subnet').val();
        var vlan_id = $('#edit-vlan-id-input').val();
        var vlan_parent = $('#edit-vlan-parent-select').val();

        var formData = {
            action: 'save_interface',
            interface: ifname,
            new_name: new_name,
            enable: isEnabled,
            mtu: mtu,
            arp: arp,
            comment: comment,
            ipaddr: ipaddr,
            subnet: subnet,
            vlan_id: vlan_id,
            vlan_parent: vlan_parent,
            ajax: 1
        };

        $.ajax({
            url: 'interfaces.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(res) {
                if (res && res.success) {
                    var activeName = res.active_name || new_name || ifname;
                    $('#edit-iface-name-hidden').val(activeName);
                    $('#winbox-title-label').text(activeName);
                    selectedIface = activeName;

                    if (closeModal) {
                        $('#modal-edit-interface').modal('hide');
                        location.reload();
                    } else {
                        // Show subtle notification or alert
                        alert(res.message || 'Pengaturan berhasil diterapkan.');
                    }
                    pollTrafficStats();
                } else {
                    alert('Gagal menyimpan konfigurasi: ' + (res && res.error ? res.error : 'Kesalahan internal'));
                }
            },
            error: function() {
                if (closeModal) {
                    $('#modal-edit-interface').modal('hide');
                    location.reload();
                } else {
                    alert('Konfigurasi dikirim ke server.');
                }
            }
        });
    };

    $(document).ready(function() {
        // Table row click -> select row
        $(document).on('click', '#iface-grid-table tbody tr[data-ifname]', function(e) {
            var ifname = $(this).data('ifname');
            selectRow(this, ifname, '');
        });

        // Table row double click -> open WinBox edit modal
        $(document).on('dblclick', '#iface-grid-table tbody tr[data-ifname]', function(e) {
            var ifname = $(this).data('ifname');
            openWinboxEditModal(ifname);
        });

        // Edit button click
        $('#btn-edit').on('click', function() {
            if (currentTab === 'interface_list' || selectedListName) {
                if (selectedListName) {
                    editInterfaceListClick(selectedListName, selectedListComment, selectedListMembers);
                }
                return;
            }
            if (!selectedIface) return;
            openWinboxEditModal(selectedIface);
        });

        $('#btn-enable').on('click', function() {
            if (currentTab === 'interface_list') return;
            if (!selectedIface) return;
            postIfaceState(selectedIface, 'up');
        });

        $('#btn-disable').on('click', function() {
            if (currentTab === 'interface_list') return;
            if (!selectedIface) return;
            if (selectedIface === 'lo' || selectedIface === 'enp0s3' || selectedIface === 'enp1s0') {
                MitraNet.alert('Protected Interface', 'Interface manajemen ini dilindungi dan tidak dapat dimatikan.', 'error');
                return;
            }
            MitraNet.confirmAction({
                title: 'Nonaktifkan Interface?',
                html: 'Apakah Anda yakin ingin menonaktifkan interface <b>' + selectedIface + '</b>?',
                icon: 'warning',
                confirmColor: '#f59e0b',
                confirmText: 'Disable',
                url: 'interfaces.php?tab=' + encodeURIComponent(currentTab),
                data: { action: 'set_state', interface: selectedIface, state: 'down' },
                onSuccess: function() { location.reload(); }
            });
        });

        $('#btn-remove').on('click', function() {
            if (currentTab === 'interface_list' || selectedListName) {
                if (selectedListName) {
                    deleteInterfaceListClick(selectedListName);
                }
                return;
            }
            if (!selectedIface) return;
            if (selectedIface === 'lo' || selectedIface === 'enp0s3' || selectedIface === 'enp1s0') {
                MitraNet.alert('Protected Interface', 'Interface fisik / manajemen ini dilindungi dan tidak dapat dihapus.', 'error');
                return;
            }
            MitraNet.confirmDelete({
                title: 'Hapus Interface?',
                name: selectedIface,
                warning: 'Tindakan ini akan menghapus antarmuka dari Linux kernel.',
                url: 'interfaces.php',
                data: { action: 'delete_interface', interface: selectedIface },
                onSuccess: function() {
                    selectedIface = null;
                    $('#btn-edit, #btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', true);
                    location.reload();
                }
            });
        });

        $('#btn-comment').on('click', function() {
            if (currentTab === 'interface_list' || selectedListName) {
                if (selectedListName) {
                    MitraNet.promptInput({
                        title: 'Set Comment: Interface List ' + selectedListName,
                        placeholder: 'Masukkan catatan atau tujuan penggunaan grup ' + selectedListName + '...',
                        value: selectedListComment,
                        url: 'interfaces.php?tab=interface_list',
                        data: {
                            action: 'save_interface_list',
                            list_name: selectedListName,
                            members: selectedListMembers
                        },
                        inputKey: 'comment',
                        successMsg: 'Komentar Interface List berhasil disimpan',
                        onSuccess: function() {
                            location.reload();
                        }
                    });
                }
                return;
            }
            if (!selectedIface) return;
            MitraNet.promptInput({
                title: 'Set Comment: ' + selectedIface,
                placeholder: 'Masukkan komentar untuk interface ini...',
                url: 'interfaces.php',
                data: { action: 'save_interface', interface: selectedIface, enable: 'yes' },
                inputKey: 'comment',
                successMsg: 'Komentar berhasil disimpan',
                onSuccess: function() {
                    location.reload();
                }
            });
        });
    });

    function postIfaceState(ifname, state) {
        var form = $('<form method="post" action="interfaces.php?tab=' + encodeURIComponent(currentTab) + '"></form>');
        form.append('<input type="hidden" name="action" value="set_state">');
        form.append('<input type="hidden" name="interface" value="' + ifname + '">');
        form.append('<input type="hidden" name="state" value="' + state + '">');
        $('body').append(form);
        form.submit();
    }

    window.filterAssignGrid = function(val) {
        val = (val || '').toLowerCase();
        $('#iface-grid-table tbody tr, #iface-list-grid-table tbody tr').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(val) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    };

    // ========================================================
    // LIVE TRAFFIC MONITORING (Real-time polling & rate delta)
    // ========================================================
    var lastTimestamp = 0;
    var prevStats = {};

    function formatBytes(bytes) {
        if (isNaN(bytes) || bytes < 0) bytes = 0;
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576)    return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024)       return (bytes / 1024).toFixed(1) + ' KB';
        return Math.round(bytes) + ' B';
    }

    function formatRate(bps) {
        if (isNaN(bps) || bps < 0) bps = 0;
        if (bps >= 1073741824) return (bps / 1073741824).toFixed(2) + ' GB/s';
        if (bps >= 1048576)    return (bps / 1048576).toFixed(1) + ' MB/s';
        if (bps >= 1024)       return (bps / 1024).toFixed(1) + ' KB/s';
        return Math.round(bps) + ' B/s';
    }

    function formatPkts(pkts) {
        if (isNaN(pkts) || pkts < 0) pkts = 0;
        if (pkts >= 1000000) return (pkts / 1000000).toFixed(1) + 'M';
        if (pkts >= 1000)    return (pkts / 1000).toFixed(1) + 'K';
        return Math.round(pkts).toString();
    }

    // ========================================================
    // WINBOX REALTIME TRAFFIC GRAPH (CANVAS AREA CHART)
    // ========================================================
    var activeModalIface = null;
    var trafficHistory = {}; // ifname -> { tx: [60], rx: [60] }
    var MAX_GRAPH_POINTS = 60;

    function initTrafficHistory(ifname) {
        if (!trafficHistory[ifname]) {
            trafficHistory[ifname] = {
                tx: new Array(MAX_GRAPH_POINTS).fill(0),
                rx: new Array(MAX_GRAPH_POINTS).fill(0)
            };
        }
    }

    function pushTrafficPoint(ifname, txRate, rxRate) {
        initTrafficHistory(ifname);
        trafficHistory[ifname].tx.push(txRate);
        trafficHistory[ifname].rx.push(rxRate);
        if (trafficHistory[ifname].tx.length > MAX_GRAPH_POINTS) trafficHistory[ifname].tx.shift();
        if (trafficHistory[ifname].rx.length > MAX_GRAPH_POINTS) trafficHistory[ifname].rx.shift();
    }

    function renderWinboxTrafficGraph(ifname) {
        var canvas = document.getElementById('winbox-traffic-canvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        if (!ctx) return;

        initTrafficHistory(ifname);
        var txData = trafficHistory[ifname].tx;
        var rxData = trafficHistory[ifname].rx;

        var curTx = txData[txData.length - 1] || 0;
        var curRx = rxData[rxData.length - 1] || 0;
        $('#winbox-graph-tx-cur').text(formatRate(curTx));
        $('#winbox-graph-rx-cur').text(formatRate(curRx));

        var maxVal = 1024; // baseline min scale 1 KB/s
        for (var i = 0; i < txData.length; i++) {
            if (txData[i] > maxVal) maxVal = txData[i];
            if (rxData[i] > maxVal) maxVal = rxData[i];
        }
        maxVal = maxVal * 1.15; // 15% headroom
        $('#winbox-graph-peak').text(formatRate(maxVal / 1.15));

        var w = canvas.width;
        var h = canvas.height;

        // Clear canvas
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(0, 0, w, h);

        // Draw horizontal grid lines
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 1;
        for (var gl = 1; gl <= 3; gl++) {
            var gy = (h / 4) * gl;
            ctx.beginPath();
            ctx.moveTo(0, gy);
            ctx.lineTo(w, gy);
            ctx.stroke();
        }

        var step = w / (MAX_GRAPH_POINTS - 1);

        // Helper to draw filled area curve
        function drawArea(data, strokeColor, fillColor) {
            ctx.beginPath();
            ctx.moveTo(0, h);
            for (var idx = 0; idx < data.length; idx++) {
                var x = idx * step;
                var y = h - ((data[idx] / maxVal) * (h - 10));
                ctx.lineTo(x, y);
            }
            ctx.lineTo(w, h);
            ctx.closePath();
            ctx.fillStyle = fillColor;
            ctx.fill();

            // Stroke line
            ctx.beginPath();
            for (var idx2 = 0; idx2 < data.length; idx2++) {
                var x2 = idx2 * step;
                var y2 = h - ((data[idx2] / maxVal) * (h - 10));
                if (idx2 === 0) ctx.moveTo(x2, y2);
                else ctx.lineTo(x2, y2);
            }
            ctx.strokeStyle = strokeColor;
            ctx.lineWidth = 1.8;
            ctx.stroke();
        }

        // Draw Rx Area (Cyan/Blue layer)
        drawArea(rxData, '#0ea5e9', 'rgba(14, 165, 233, 0.25)');
        // Draw Tx Area (Emerald/Green layer)
        drawArea(txData, '#10b981', 'rgba(16, 185, 129, 0.35)');
    }

    // Modal open event hook
    $('#modal-edit-interface').on('shown.bs.modal', function() {
        activeModalIface = $('#edit-iface-name-hidden').val();
        if (activeModalIface) {
            renderWinboxTrafficGraph(activeModalIface);
        }
    }).on('hidden.bs.modal', function() {
        activeModalIface = null;
    });

    $('a[href="#winbox-tab-traffic"]').on('shown.bs.tab', function() {
        if (activeModalIface) {
            renderWinboxTrafficGraph(activeModalIface);
        }
    });

    // --------------------------------------------------------
    // Realtime Polling Loop
    // --------------------------------------------------------
    function pollTrafficStats() {
        $.ajax({
            url: 'interfaces.php?ajax=traffic',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(data) {
                if (!data || !data.interfaces) return;

                var now = data.timestamp || (Date.now() / 1000);
                var dt = lastTimestamp > 0 ? (now - lastTimestamp) : 0;
                lastTimestamp = now;

                $('#iface-grid-table tbody tr[data-ifname]').each(function() {
                    var $row = $(this);
                    var ifname = $row.data('ifname');
                    var curr = data.interfaces[ifname];
                    if (!curr) return;

                    var prev = prevStats[ifname];

                    // If we have previous readings and valid delta time (dt > 0.3s)
                    if (prev && dt > 0.3) {
                        var dTxBytes = Math.max(0, curr.tx_bytes - prev.tx_bytes);
                        var dRxBytes = Math.max(0, curr.rx_bytes - prev.rx_bytes);
                        var dTxPkts  = Math.max(0, curr.tx_packets - prev.tx_packets);
                        var dRxPkts  = Math.max(0, curr.rx_packets - prev.rx_packets);

                        var txRate = dTxBytes / dt;
                        var rxRate = dRxBytes / dt;
                        var txPps  = Math.round(dTxPkts / dt);
                        var rxPps  = Math.round(dRxPkts / dt);

                        // Push to Rolling Traffic History for Graph
                        pushTrafficPoint(ifname, txRate, rxRate);

                        // 1. Primary Columns: Tx, Rx, Tx Packet (p/s), Rx Packet (p/s)
                        var $tx = $row.find('.col-tx');
                        var $rx = $row.find('.col-rx');
                        var $txPkts = $row.find('.col-tx-pkts');
                        var $rxPkts = $row.find('.col-rx-pkts');

                        $tx.text(formatRate(txRate)).attr('title', 'Total Sent: ' + formatBytes(curr.tx_bytes));
                        $rx.text(formatRate(rxRate)).attr('title', 'Total Received: ' + formatBytes(curr.rx_bytes));
                        $txPkts.text(txPps).attr('title', 'Total Packets Sent: ' + formatPkts(curr.tx_packets));
                        $rxPkts.text(rxPps).attr('title', 'Total Packets Received: ' + formatPkts(curr.rx_packets));

                        // Active visual highlight
                        if (txRate > 0) {
                            $tx.css('color', '#10b981').css('font-weight', '600');
                        } else {
                            $tx.css('color', '').css('font-weight', '');
                        }

                        if (rxRate > 0) {
                            $rx.css('color', '#0ea5e9').css('font-weight', '600');
                        } else {
                            $rx.css('color', '').css('font-weight', '');
                        }

                        if (txPps > 0) {
                            $txPkts.css('color', '#10b981');
                        } else {
                            $txPkts.css('color', '');
                        }

                        if (rxPps > 0) {
                            $rxPkts.css('color', '#0ea5e9');
                        } else {
                            $rxPkts.css('color', '');
                        }

                        // 2. FastPath columns (mirror rate or offload)
                        var $fpTx = $row.find('.col-fp-tx');
                        var $fpRx = $row.find('.col-fp-rx');
                        var $fpTxPkts = $row.find('.col-fp-tx-pkts');
                        var $fpRxPkts = $row.find('.col-fp-rx-pkts');

                        $fpTx.text(formatRate(txRate));
                        $fpRx.text(formatRate(rxRate));
                        $fpTxPkts.text(txPps.toString());
                        $fpRxPkts.text(rxPps.toString());

                        // 3. Update Modal Traffic Graph & Counters if this interface is currently open in modal
                        if (activeModalIface === ifname) {
                            $('#winbox-traffic-txrate').text(formatRate(txRate));
                            $('#winbox-traffic-rxrate').text(formatRate(rxRate));
                            $('#winbox-traffic-txpps').text(txPps.toString());
                            $('#winbox-traffic-rxpps').text(rxPps.toString());
                            $('#winbox-traffic-txbytes').text(formatBytes(curr.tx_bytes));
                            $('#winbox-traffic-rxbytes').text(formatBytes(curr.rx_bytes));
                            renderWinboxTrafficGraph(ifname);
                        }
                    } else if (!prev) {
                        // Keep current totals in title attribute on initial fetch
                        $row.find('.col-tx').attr('title', 'Total Sent: ' + formatBytes(curr.tx_bytes));
                        $row.find('.col-rx').attr('title', 'Total Received: ' + formatBytes(curr.rx_bytes));
                        $row.find('.col-tx-pkts').attr('title', 'Total Packets: ' + formatPkts(curr.tx_packets));
                        $row.find('.col-rx-pkts').attr('title', 'Total Packets: ' + formatPkts(curr.rx_packets));
                        initTrafficHistory(ifname);
                    }

                    // Save snapshot
                    prevStats[ifname] = {
                        tx_bytes: curr.tx_bytes,
                        rx_bytes: curr.rx_bytes,
                        tx_packets: curr.tx_packets,
                        rx_packets: curr.rx_packets
                    };
                });
            }
        });
    }

    // Initial query
    pollTrafficStats();

    // Periodic dynamic update every 1.5 seconds (smooth rate calculation)
    setInterval(pollTrafficStats, 1500);
</script>
<?php endif; ?>
