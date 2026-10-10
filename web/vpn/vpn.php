<?php
/*
 * vpn.php - MitraNet VPN & Point-to-Point Tunnel Management
 * Implements MikroTik WinBox PPP / VPN management interface
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$configFile = '/etc/mitranet/vpn_config.json';

// Helper: load VPN / PPP configuration
function loadVpnConfig($configFile) {
    if (file_exists($configFile) && is_readable($configFile)) {
        $content = @file_get_contents($configFile);
        $data = json_decode($content, true);
        if (is_array($data)) {
            return $data;
        }
    }
    // Default initial seeds matching MikroTik RouterOS
    return [
        'interfaces' => [
            [
                'id' => 1,
                'name' => '<pptp-out1>',
                'type' => 'PPTP Client',
                'actual_mtu' => 1450,
                'l2mtu' => 1500,
                'tx' => '0 B/s',
                'rx' => '0 B/s',
                'tx_packets' => 0,
                'rx_packets' => 0,
                'fp_tx' => '0 B/s',
                'fp_rx' => '0 B/s',
                'fp_tx_packets' => 0,
                'fp_rx_packets' => 0,
                'enabled' => true,
                'comment' => 'HQ Site-to-Site PPTP'
            ],
            [
                'id' => 2,
                'name' => '<ovpn-out1>',
                'type' => 'OVPN Client',
                'actual_mtu' => 1500,
                'l2mtu' => 1500,
                'tx' => '0 B/s',
                'rx' => '0 B/s',
                'tx_packets' => 0,
                'rx_packets' => 0,
                'fp_tx' => '0 B/s',
                'fp_rx' => '0 B/s',
                'fp_tx_packets' => 0,
                'fp_rx_packets' => 0,
                'enabled' => true,
                'comment' => 'OpenVPN Cloud Uplink'
            ],
            [
                'id' => 3,
                'name' => '<l2tp-out1>',
                'type' => 'L2TP Client',
                'actual_mtu' => 1450,
                'l2mtu' => 1500,
                'tx' => '0 B/s',
                'rx' => '0 B/s',
                'tx_packets' => 0,
                'rx_packets' => 0,
                'fp_tx' => '0 B/s',
                'fp_rx' => '0 B/s',
                'fp_tx_packets' => 0,
                'fp_rx_packets' => 0,
                'enabled' => false,
                'comment' => 'Backup L2TP Tunnel'
            ],
            [
                'id' => 4,
                'name' => '<sstp-out1>',
                'type' => 'SSTP Client',
                'actual_mtu' => 1500,
                'l2mtu' => 1500,
                'tx' => '0 B/s',
                'rx' => '0 B/s',
                'tx_packets' => 0,
                'rx_packets' => 0,
                'fp_tx' => '0 B/s',
                'fp_rx' => '0 B/s',
                'fp_tx_packets' => 0,
                'fp_rx_packets' => 0,
                'enabled' => false,
                'comment' => 'Branch SSTP Tunnel'
            ]
        ],
        'pppoe_servers' => [
            [
                'id' => 1,
                'service_name' => 'service1',
                'interface' => 'enp1s0',
                'max_mtu' => 1480,
                'max_mru' => 1480,
                'default_profile' => 'default',
                'one_session_per_host' => true,
                'max_sessions' => 100,
                'enabled' => true
            ]
        ],
        'pptp_server' => [
            'enabled' => false,
            'max_mtu' => 1450,
            'max_mru' => 1450,
            'mrru' => 'disabled',
            'authentication' => ['mschap2'],
            'default_profile' => 'default-encryption',
            'keepalive_timeout' => 30
        ],
        'sstp_server' => [
            'enabled' => false,
            'port' => 443,
            'max_mtu' => 1500,
            'max_mru' => 1500,
            'mrru' => 'disabled',
            'authentication' => ['mschap2'],
            'certificate' => 'mitranet-server',
            'default_profile' => 'default-encryption',
            'keepalive_timeout' => 60,
            'verify_client_certificate' => false
        ],
        'l2tp_server' => [
            'enabled' => false,
            'max_mtu' => 1450,
            'max_mru' => 1450,
            'mrru' => 'disabled',
            'authentication' => ['mschap2'],
            'default_profile' => 'default-encryption',
            'use_ipsec' => 'yes',
            'ipsec_secret' => 'mitranet_ipsec_psk',
            'keepalive_timeout' => 30
        ],
        'ovpn_server' => [
            'enabled' => true,
            'port' => 1194,
            'mode' => 'ip',
            'protocol' => 'udp',
            'max_mtu' => 1500,
            'default_profile' => 'default-encryption',
            'certificate' => 'mitranet-server',
            'cipher' => 'aes-256-gcm',
            'auth' => 'sha256',
            'require_client_certificate' => false
        ],
        'ovpn_servers' => [
            [
                'id' => 1,
                'port' => 1194,
                'mode' => 'ip',
                'protocol' => 'udp',
                'default_profile' => 'default-encryption',
                'certificate' => 'mitranet-server',
                'cipher' => 'aes-256-gcm',
                'enabled' => true
            ]
        ],
        'secrets' => [
            [
                'id' => 1,
                'name' => 'client1',
                'service' => 'any',
                'password' => '******',
                'profile' => 'default-encryption',
                'local_address' => '10.10.10.1',
                'remote_address' => '10.10.10.10',
                'comment' => 'Client Remote 1'
            ],
            [
                'id' => 2,
                'name' => 'client2',
                'service' => 'pppoe',
                'password' => '******',
                'profile' => 'default',
                'local_address' => '10.10.20.1',
                'remote_address' => '10.10.20.2',
                'comment' => 'Home PPPoE User'
            ]
        ],
        'profiles' => [
            [
                'id' => 1,
                'name' => 'default',
                'local_address' => '10.10.10.1',
                'remote_address' => 'pool-vpn',
                'use_compression' => 'default',
                'use_encryption' => 'default'
            ],
            [
                'id' => 2,
                'name' => 'default-encryption',
                'local_address' => '10.10.20.1',
                'remote_address' => 'pool-encryption',
                'use_compression' => 'yes',
                'use_encryption' => 'require'
            ]
        ],
        'active_connections' => [
            [
                'id' => 1,
                'name' => 'client1',
                'service' => 'ovpn',
                'caller_id' => '182.253.110.12',
                'address' => '10.10.10.10',
                'uptime' => '02:45:12',
                'encoding' => 'AES-256-GCM'
            ]
        ],
        'l2tp_ethernet' => [],
        'l2tp_secrets' => [
            [
                'id' => 1,
                'address' => '0.0.0.0/0',
                'secret' => 'mitranet_ipsec_psk'
            ]
        ]
    ];
}

function saveVpnConfig($configFile, $config) {
    $dir = dirname($configFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return @file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) !== false;
}

$vpnConfig = loadVpnConfig($configFile);
$current_tab = isset($_GET['tab']) ? strtolower(trim(strip_tags($_GET['tab']))) : 'interface';
$valid_tabs = ['interface', 'pppoe_servers', 'ovpn_servers', 'secrets', 'profiles', 'active', 'l2tp_ethernet', 'l2tp_secrets'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'interface';
}

// Handle AJAX actions (CRUD for Interface & Secrets)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);
    $action = $_POST['action'] ?? '';

    if ($action === 'add_interface' || $action === 'edit_interface') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? 'PPTP Client');
        $mtu = intval($_POST['mtu'] ?? 1450);
        $l2mtu = intval($_POST['l2mtu'] ?? 1500);
        $comment = trim($_POST['comment'] ?? '');

        if (empty($name)) {
            $resp = ['success' => false, 'error' => 'Tunnel Name cannot be empty.'];
        } else {
            if ($action === 'add_interface') {
                $maxId = 0;
                foreach ($vpnConfig['interfaces'] as $it) {
                    if ($it['id'] > $maxId) $maxId = $it['id'];
                }
                $vpnConfig['interfaces'][] = [
                    'id' => $maxId + 1,
                    'name' => $name,
                    'type' => $type,
                    'actual_mtu' => $mtu,
                    'l2mtu' => $l2mtu,
                    'tx' => '0 B/s',
                    'rx' => '0 B/s',
                    'tx_packets' => 0,
                    'rx_packets' => 0,
                    'fp_tx' => '0 B/s',
                    'fp_rx' => '0 B/s',
                    'fp_tx_packets' => 0,
                    'fp_rx_packets' => 0,
                    'enabled' => true,
                    'comment' => $comment
                ];
            } else {
                foreach ($vpnConfig['interfaces'] as &$it) {
                    if ($it['id'] === $id) {
                        $it['name'] = $name;
                        $it['type'] = $type;
                        $it['actual_mtu'] = $mtu;
                        $it['l2mtu'] = $l2mtu;
                        $it['comment'] = $comment;
                        break;
                    }
                }
            }
            saveVpnConfig($configFile, $vpnConfig);
            $resp = ['success' => true, 'message' => 'VPN Tunnel Interface saved successfully.'];
        }

        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resp);
            exit;
        }
    }

    if ($action === 'toggle_interface') {
        $id = intval($_POST['id'] ?? 0);
        $state = ($_POST['state'] ?? '1') === '1';
        foreach ($vpnConfig['interfaces'] as &$it) {
            if ($it['id'] === $id) {
                $it['enabled'] = $state;
                break;
            }
        }
        saveVpnConfig($configFile, $vpnConfig);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'VPN Interface status updated.']);
            exit;
        }
    }

    if ($action === 'delete_interface') {
        $id = intval($_POST['id'] ?? 0);
        $vpnConfig['interfaces'] = array_filter($vpnConfig['interfaces'], function($it) use ($id) {
            return $it['id'] !== $id;
        });
        saveVpnConfig($configFile, $vpnConfig);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'VPN Interface removed.']);
            exit;
        }
    }

    if ($action === 'comment_interface') {
        $id = intval($_POST['id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        foreach ($vpnConfig['interfaces'] as &$it) {
            if ($it['id'] === $id) {
                $it['comment'] = $comment;
                break;
            }
        }
        saveVpnConfig($configFile, $vpnConfig);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Comment updated.']);
            exit;
        }
    }

    // Save VPN Server Configuration (PPTP, SSTP, L2TP, OVPN, PPPoE)
    if ($action === 'save_server_config') {
        $server_type = trim($_POST['server_type'] ?? '');
        if ($server_type === 'pptp') {
            $vpnConfig['pptp_server'] = [
                'enabled' => !empty($_POST['enabled']),
                'max_mtu' => intval($_POST['max_mtu'] ?? 1450),
                'max_mru' => intval($_POST['max_mru'] ?? 1450),
                'mrru' => trim($_POST['mrru'] ?? 'disabled'),
                'authentication' => isset($_POST['auth']) ? (array)$_POST['auth'] : ['mschap2'],
                'default_profile' => trim($_POST['default_profile'] ?? 'default-encryption'),
                'keepalive_timeout' => intval($_POST['keepalive_timeout'] ?? 30)
            ];
            $msg = 'PPTP Server configuration saved.';
        } elseif ($server_type === 'sstp') {
            $vpnConfig['sstp_server'] = [
                'enabled' => !empty($_POST['enabled']),
                'port' => intval($_POST['port'] ?? 443),
                'max_mtu' => intval($_POST['max_mtu'] ?? 1500),
                'max_mru' => intval($_POST['max_mru'] ?? 1500),
                'mrru' => trim($_POST['mrru'] ?? 'disabled'),
                'authentication' => isset($_POST['auth']) ? (array)$_POST['auth'] : ['mschap2'],
                'certificate' => trim($_POST['certificate'] ?? 'mitranet-server'),
                'default_profile' => trim($_POST['default_profile'] ?? 'default-encryption'),
                'keepalive_timeout' => intval($_POST['keepalive_timeout'] ?? 60),
                'verify_client_certificate' => !empty($_POST['verify_client_certificate'])
            ];
            $msg = 'SSTP Server configuration saved.';
        } elseif ($server_type === 'l2tp') {
            $vpnConfig['l2tp_server'] = [
                'enabled' => !empty($_POST['enabled']),
                'max_mtu' => intval($_POST['max_mtu'] ?? 1450),
                'max_mru' => intval($_POST['max_mru'] ?? 1450),
                'mrru' => trim($_POST['mrru'] ?? 'disabled'),
                'authentication' => isset($_POST['auth']) ? (array)$_POST['auth'] : ['mschap2'],
                'default_profile' => trim($_POST['default_profile'] ?? 'default-encryption'),
                'use_ipsec' => trim($_POST['use_ipsec'] ?? 'yes'),
                'ipsec_secret' => trim($_POST['ipsec_secret'] ?? 'mitranet_ipsec_psk'),
                'keepalive_timeout' => intval($_POST['keepalive_timeout'] ?? 30)
            ];
            $msg = 'L2TP Server configuration saved.';
        } elseif ($server_type === 'ovpn') {
            $vpnConfig['ovpn_server'] = [
                'enabled' => !empty($_POST['enabled']),
                'port' => intval($_POST['port'] ?? 1194),
                'mode' => trim($_POST['mode'] ?? 'ip'),
                'protocol' => trim($_POST['protocol'] ?? 'udp'),
                'max_mtu' => intval($_POST['max_mtu'] ?? 1500),
                'default_profile' => trim($_POST['default_profile'] ?? 'default-encryption'),
                'certificate' => trim($_POST['certificate'] ?? 'mitranet-server'),
                'cipher' => trim($_POST['cipher'] ?? 'aes-256-gcm'),
                'auth' => trim($_POST['auth_hash'] ?? 'sha256'),
                'require_client_certificate' => !empty($_POST['require_client_certificate'])
            ];
            // Sinkronkan juga ke array ovpn_servers
            $vpnConfig['ovpn_servers'] = [[
                'id' => 1,
                'port' => $vpnConfig['ovpn_server']['port'],
                'mode' => $vpnConfig['ovpn_server']['mode'],
                'protocol' => $vpnConfig['ovpn_server']['protocol'],
                'default_profile' => $vpnConfig['ovpn_server']['default_profile'],
                'certificate' => $vpnConfig['ovpn_server']['certificate'],
                'cipher' => $vpnConfig['ovpn_server']['cipher'],
                'enabled' => $vpnConfig['ovpn_server']['enabled']
            ]];
            $msg = 'OpenVPN Server configuration saved.';
        } elseif ($server_type === 'pppoe') {
            $ps_id = intval($_POST['id'] ?? 1);
            $service_name = trim($_POST['service_name'] ?? 'service1') ?: 'service1';
            $ps_iface = trim($_POST['interface'] ?? 'enp1s0');
            $max_mtu = intval($_POST['max_mtu'] ?? 1480);
            $max_mru = intval($_POST['max_mru'] ?? 1480);
            $def_prof = trim($_POST['default_profile'] ?? 'default');
            $one_sess = !empty($_POST['one_session_per_host']);
            $max_sess = intval($_POST['max_sessions'] ?? 100);
            $ps_enabled = !empty($_POST['enabled']);

            $found = false;
            if (!empty($vpnConfig['pppoe_servers'])) {
                foreach ($vpnConfig['pppoe_servers'] as &$ps) {
                    if ($ps['id'] === $ps_id) {
                        $ps['service_name'] = $service_name;
                        $ps['interface'] = $ps_iface;
                        $ps['max_mtu'] = $max_mtu;
                        $ps['max_mru'] = $max_mru;
                        $ps['default_profile'] = $def_prof;
                        $ps['one_session_per_host'] = $one_sess;
                        $ps['max_sessions'] = $max_sess;
                        $ps['enabled'] = $ps_enabled;
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                $vpnConfig['pppoe_servers'][] = [
                    'id' => $ps_id,
                    'service_name' => $service_name,
                    'interface' => $ps_iface,
                    'max_mtu' => $max_mtu,
                    'max_mru' => $max_mru,
                    'default_profile' => $def_prof,
                    'one_session_per_host' => $one_sess,
                    'max_sessions' => $max_sess,
                    'enabled' => $ps_enabled
                ];
            }
            $msg = 'PPPoE Server configuration saved.';
        } else {
            $msg = 'Server configuration updated.';
        }

        saveVpnConfig($configFile, $vpnConfig);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }
    }

    // CRUD Secrets
    if ($action === 'add_secret' || $action === 'edit_secret') {
        $sec_id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $service = trim($_POST['service'] ?? 'any');
        $password = trim($_POST['password'] ?? '');
        $profile = trim($_POST['profile'] ?? 'default-encryption');
        $local_address = trim($_POST['local_address'] ?? '');
        $remote_address = trim($_POST['remote_address'] ?? '');
        $comment = trim($_POST['comment'] ?? '');

        if (empty($name)) {
            $resp = ['success' => false, 'error' => 'Username cannot be empty.'];
        } else {
            if (!isset($vpnConfig['secrets']) || !is_array($vpnConfig['secrets'])) {
                $vpnConfig['secrets'] = [];
            }
            if ($action === 'add_secret') {
                $maxId = 0;
                foreach ($vpnConfig['secrets'] as $s) {
                    if (($s['id'] ?? 0) > $maxId) $maxId = $s['id'];
                }
                $vpnConfig['secrets'][] = [
                    'id' => $maxId + 1,
                    'name' => $name,
                    'service' => $service,
                    'password' => $password,
                    'profile' => $profile,
                    'local_address' => $local_address,
                    'remote_address' => $remote_address,
                    'comment' => $comment
                ];
            } else {
                foreach ($vpnConfig['secrets'] as &$s) {
                    if (($s['id'] ?? 0) === $sec_id) {
                        $s['name'] = $name;
                        $s['service'] = $service;
                        $s['password'] = $password;
                        $s['profile'] = $profile;
                        $s['local_address'] = $local_address;
                        $s['remote_address'] = $remote_address;
                        $s['comment'] = $comment;
                        break;
                    }
                }
            }
            saveVpnConfig($configFile, $vpnConfig);
            $resp = ['success' => true, 'message' => 'PPP Secret saved successfully.'];
        }
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resp);
            exit;
        }
    }

    if ($action === 'delete_secret') {
        $sec_id = intval($_POST['id'] ?? 0);
        if (isset($vpnConfig['secrets']) && is_array($vpnConfig['secrets'])) {
            $vpnConfig['secrets'] = array_values(array_filter($vpnConfig['secrets'], function($s) use ($sec_id) {
                return ($s['id'] ?? 0) !== $sec_id;
            }));
            saveVpnConfig($configFile, $vpnConfig);
        }
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'PPP Secret removed.']);
            exit;
        }
    }

    // CRUD Profiles
    if ($action === 'add_profile' || $action === 'edit_profile') {
        $prof_id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $local_address = trim($_POST['local_address'] ?? '');
        $remote_address = trim($_POST['remote_address'] ?? '');
        $use_compression = trim($_POST['use_compression'] ?? 'default');
        $use_encryption = trim($_POST['use_encryption'] ?? 'default');

        if (empty($name)) {
            $resp = ['success' => false, 'error' => 'Profile Name cannot be empty.'];
        } else {
            if (!isset($vpnConfig['profiles']) || !is_array($vpnConfig['profiles'])) {
                $vpnConfig['profiles'] = [];
            }
            if ($action === 'add_profile') {
                $maxId = 0;
                foreach ($vpnConfig['profiles'] as $p) {
                    if (($p['id'] ?? 0) > $maxId) $maxId = $p['id'];
                }
                $vpnConfig['profiles'][] = [
                    'id' => $maxId + 1,
                    'name' => $name,
                    'local_address' => $local_address,
                    'remote_address' => $remote_address,
                    'use_compression' => $use_compression,
                    'use_encryption' => $use_encryption
                ];
            } else {
                foreach ($vpnConfig['profiles'] as &$p) {
                    if (($p['id'] ?? 0) === $prof_id) {
                        $p['name'] = $name;
                        $p['local_address'] = $local_address;
                        $p['remote_address'] = $remote_address;
                        $p['use_compression'] = $use_compression;
                        $p['use_encryption'] = $use_encryption;
                        break;
                    }
                }
            }
            saveVpnConfig($configFile, $vpnConfig);
            $resp = ['success' => true, 'message' => 'PPP Profile saved successfully.'];
        }
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resp);
            exit;
        }
    }

    if ($action === 'delete_profile') {
        $prof_id = intval($_POST['id'] ?? 0);
        if (isset($vpnConfig['profiles']) && is_array($vpnConfig['profiles'])) {
            $vpnConfig['profiles'] = array_values(array_filter($vpnConfig['profiles'], function($p) use ($prof_id) {
                return ($p['id'] ?? 0) !== $prof_id;
            }));
            saveVpnConfig($configFile, $vpnConfig);
        }
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'PPP Profile removed.']);
            exit;
        }
    }
}

$pgtitle = ["VPN", "Tunnel Interfaces"];
$selected_menu = "vpn";
require_once(__DIR__ . '/../includes/head.inc');

$interfaces = $vpnConfig['interfaces'] ?? [];
?>

<div class="container-fluid mitranet-page-container">
    <div class="mitranet-window">

        <!-- HEADER: BADGE + TABS (MATCHING WINBOX PPP SCREENSHOT, BADGE TITLE: VPN) -->
        <div class="mitranet-header">
            <div class="mitranet-title-badge">
                <i class="fa-solid fa-desktop text-primary"></i> VPN
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="mitranet-tabs">
                <li class="<?=($current_tab === 'interface') ? 'active' : ''?>">
                    <a href="vpn.php?tab=interface">Interface</a>
                </li>
                <li class="<?=($current_tab === 'pppoe_servers') ? 'active' : ''?>">
                    <a href="vpn.php?tab=pppoe_servers">PPPoE Servers</a>
                </li>
                <li class="<?=($current_tab === 'ovpn_servers') ? 'active' : ''?>">
                    <a href="vpn.php?tab=ovpn_servers">OVPN Servers</a>
                </li>
                <li class="<?=($current_tab === 'secrets') ? 'active' : ''?>">
                    <a href="vpn.php?tab=secrets">Secrets</a>
                </li>
                <li class="<?=($current_tab === 'profiles') ? 'active' : ''?>">
                    <a href="vpn.php?tab=profiles">Profiles</a>
                </li>
                <li class="<?=($current_tab === 'active') ? 'active' : ''?>">
                    <a href="vpn.php?tab=active">Active Connections</a>
                </li>
                <li class="<?=($current_tab === 'l2tp_ethernet') ? 'active' : ''?>">
                    <a href="vpn.php?tab=l2tp_ethernet">L2TP Ethernet</a>
                </li>
                <li class="<?=($current_tab === 'l2tp_secrets') ? 'active' : ''?>">
                    <a href="vpn.php?tab=l2tp_secrets">L2TP Secrets</a>
                </li>
                <li>
                    <a href="vpn_booster.php" class="text-primary font-weight-bold">Cloud Speed Booster</a>
                </li>
            </ul>
        </div>

        <!-- TOOLBAR (WINBOX ROUTEROS DYNAMIC TABS & SERVER BUTTONS) -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left">
                <?php if ($current_tab === 'interface'): ?>
                    <!-- SERVER SETUP BUTTONS IN FRONT OF NEW (PPTP, SSTP, L2TP, OVPN) -->
                    <button type="button" class="mitranet-btn <?=!empty($vpnConfig['pptp_server']['enabled']) ? 'mitranet-btn-active' : ''?>" onclick="openServerModal('pptp')" title="Konfigurasi PPTP VPN Server">
                        <i class="fa-solid fa-shield-halved text-info"></i> <strong>PPTP Server</strong>
                    </button>
                    <button type="button" class="mitranet-btn <?=!empty($vpnConfig['sstp_server']['enabled']) ? 'mitranet-btn-active' : ''?>" onclick="openServerModal('sstp')" title="Konfigurasi SSTP VPN Server">
                        <i class="fa-solid fa-lock text-success"></i> <strong>SSTP Server</strong>
                    </button>
                    <button type="button" class="mitranet-btn <?=!empty($vpnConfig['l2tp_server']['enabled']) ? 'mitranet-btn-active' : ''?>" onclick="openServerModal('l2tp')" title="Konfigurasi L2TP / IPsec VPN Server">
                        <i class="fa-solid fa-key text-warning"></i> <strong>L2TP Server</strong>
                    </button>
                    <button type="button" class="mitranet-btn <?=!empty($vpnConfig['ovpn_server']['enabled']) ? 'mitranet-btn-active' : ''?>" onclick="openServerModal('ovpn')" title="Konfigurasi OpenVPN Server">
                        <i class="fa-solid fa-globe text-primary"></i> <strong>OpenVPN Server</strong>
                    </button>

                    <span style="display:inline-block; width: 1px; height: 16px; background: #cbd5e1; margin: 0 4px;"></span>

                    <button type="button" class="mitranet-btn" id="btn-new" onclick="openNewVpnModal()" title="Add New VPN Interface">
                        <i class="fa-regular fa-square-plus text-primary"></i> <strong>New</strong>
                    </button>
                    <button type="button" class="mitranet-btn" id="btn-enable" disabled onclick="toggleSelectedVpn(true)" title="Enable Selected">
                        <i class="fa-solid fa-play text-success"></i> Enable
                    </button>
                    <button type="button" class="mitranet-btn" id="btn-disable" disabled onclick="toggleSelectedVpn(false)" title="Disable Selected">
                        <i class="fa-solid fa-pause text-warning"></i> Disable
                    </button>
                    <button type="button" class="mitranet-btn" id="btn-remove" disabled onclick="removeSelectedVpn()" title="Remove Selected">
                        <i class="fa-solid fa-xmark text-danger"></i> Remove
                    </button>
                    <button type="button" class="mitranet-btn" id="btn-comment" disabled onclick="commentSelectedVpn()" title="Set Comment">
                        <i class="fa-regular fa-comment text-muted"></i> Comment
                    </button>

                <?php elseif ($current_tab === 'pppoe_servers'): ?>
                    <button type="button" class="mitranet-btn mitranet-btn-active" onclick="openServerModal('pppoe')" title="Konfigurasi PPPoE Server Concentrator">
                        <i class="fa-solid fa-network-wired text-primary"></i> <strong>PPPoE Server</strong>
                    </button>
                    <button type="button" class="mitranet-btn" onclick="openServerModal('pppoe')" title="Add PPPoE Service Binding">
                        <i class="fa-regular fa-square-plus text-primary"></i> <strong>New Service</strong>
                    </button>

                <?php elseif ($current_tab === 'ovpn_servers'): ?>
                    <button type="button" class="mitranet-btn <?=!empty($vpnConfig['ovpn_server']['enabled']) ? 'mitranet-btn-active' : ''?>" onclick="openServerModal('ovpn')" title="Konfigurasi OpenVPN Server Global">
                        <i class="fa-solid fa-globe text-primary"></i> <strong>OpenVPN Server</strong>
                    </button>

                <?php elseif ($current_tab === 'secrets'): ?>
                    <button type="button" class="mitranet-btn" onclick="openNewSecretModal()" title="Add New PPP User Secret">
                        <i class="fa-regular fa-square-plus text-primary"></i> <strong>New User</strong>
                    </button>
                    <button type="button" class="mitranet-btn" id="btn-secret-remove" disabled onclick="removeSelectedSecret()" title="Remove Secret">
                        <i class="fa-solid fa-xmark text-danger"></i> Remove
                    </button>

                <?php elseif ($current_tab === 'profiles'): ?>
                    <button type="button" class="mitranet-btn" onclick="openNewProfileModal()" title="Add New PPP Profile">
                        <i class="fa-regular fa-square-plus text-primary"></i> <strong>New Profile</strong>
                    </button>
                    <button type="button" class="mitranet-btn" id="btn-profile-remove" disabled onclick="removeSelectedProfile()" title="Remove Profile">
                        <i class="fa-solid fa-xmark text-danger"></i> Remove
                    </button>

                <?php elseif ($current_tab === 'active'): ?>
                    <button type="button" class="mitranet-btn" id="btn-disconnect" disabled onclick="disconnectActiveSession()" title="Disconnect Active Session">
                        <i class="fa-solid fa-xmark text-danger"></i> Disconnect
                    </button>
                    <button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh Sessions">
                        <i class="fa-solid fa-arrows-rotate text-primary"></i> Refresh
                    </button>

                <?php else: ?>
                    <button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh
                    </button>
                <?php endif; ?>
            </div>
            <div class="mitranet-toolbar-right">
                <div class="mitranet-search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="grid-search" placeholder="Find" onkeyup="filterVpnGrid(this.value)">
                </div>
                <button type="button" class="mitranet-btn" onclick="$('#grid-search').focus()" title="Advanced Filter">
                    <i class="fa-solid fa-filter text-muted"></i> Filter
                </button>
                <button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh / Columns">
                    <i class="fa-solid fa-bars text-muted"></i>
                </button>
            </div>
        </div>

        <!-- DATA GRID TABLE (EXACT SCREENSHOT COLUMNS) -->
        <div class="mitranet-grid-container">
            <?php if ($current_tab === 'interface'): ?>
            <table class="mitranet-grid" id="vpn-grid-table">
                <thead>
                    <tr>
                        <th class="col-flag" style="width: 32px; text-align: center;">
                            <i class="fa-regular fa-flag"></i>
                        </th>
                        <th class="sortable col-name">Name <i class="fa-solid fa-caret-up"></i></th>
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
                        <th class="col-menu" style="width: 30px; text-align: center;">
                            <i class="fa-solid fa-bars"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($interfaces)): ?>
                    <tr>
                        <td colspan="14" class="text-center text-muted" style="padding: 20px;">
                            No VPN interfaces configured. Click <strong>New</strong> to create one.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($interfaces as $it): ?>
                    <?php
                        $isEnabled = !empty($it['enabled']);
                        $rowClass = $isEnabled ? '' : 'text-muted';
                    ?>
                    <tr class="<?=$rowClass?>"
                        data-id="<?=htmlspecialchars($it['id'])?>"
                        data-name="<?=htmlspecialchars($it['name'])?>"
                        data-type="<?=htmlspecialchars($it['type'])?>"
                        data-mtu="<?=htmlspecialchars($it['actual_mtu'])?>"
                        data-l2mtu="<?=htmlspecialchars($it['l2mtu'])?>"
                        data-comment="<?=htmlspecialchars($it['comment'] ?? '')?>"
                        data-enabled="<?=$isEnabled ? '1' : '0'?>"
                        onclick="selectVpnRow(this, <?=htmlspecialchars($it['id'])?>)"
                        ondblclick="openEditVpnModal(<?=htmlspecialchars($it['id'])?>)"
                        style="cursor: pointer; <?=$isEnabled ? '' : 'opacity: 0.65;'?>">

                        <!-- Flag / Status -->
                        <td style="text-align: center;">
                            <?php if (!empty($it['comment'])): ?>
                                <i class="fa-regular fa-comment text-info" title="<?=htmlspecialchars($it['comment'])?>"></i>
                            <?php elseif ($isEnabled): ?>
                                <i class="fa-solid fa-check text-success" title="Running / Enabled"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-minus text-muted" title="Disabled"></i>
                            <?php endif; ?>
                        </td>

                        <!-- Name -->
                        <td>
                            <strong class="<?=$isEnabled ? 'text-primary' : 'text-muted'?>">
                                <?=htmlspecialchars($it['name'])?>
                            </strong>
                            <?php if (!empty($it['comment'])): ?>
                                <span class="text-muted" style="font-size: 10px; margin-left: 6px;">
                                    ; <?=htmlspecialchars($it['comment'])?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Type -->
                        <td>
                            <span class="label label-default" style="background: #eaf2fa; color: #1e3c5f; border: 1px solid #c0d5ec;">
                                <?=htmlspecialchars($it['type'])?>
                            </span>
                        </td>

                        <!-- Actual MTU -->
                        <td><?=htmlspecialchars($it['actual_mtu'])?></td>

                        <!-- L2 MTU -->
                        <td><?=htmlspecialchars($it['l2mtu'])?></td>

                        <!-- Tx -->
                        <td><?=htmlspecialchars($it['tx'] ?? '0 B/s')?></td>

                        <!-- Rx -->
                        <td><?=htmlspecialchars($it['rx'] ?? '0 B/s')?></td>

                        <!-- Tx Packet (p/s) -->
                        <td><?=htmlspecialchars($it['tx_packets'] ?? 0)?></td>

                        <!-- Rx Packet (p/s) -->
                        <td><?=htmlspecialchars($it['rx_packets'] ?? 0)?></td>

                        <!-- FP Tx -->
                        <td class="text-muted"><?=htmlspecialchars($it['fp_tx'] ?? '0 B/s')?></td>

                        <!-- FP Rx -->
                        <td class="text-muted"><?=htmlspecialchars($it['fp_rx'] ?? '0 B/s')?></td>

                        <!-- FP Tx Packet (p/s) -->
                        <td class="text-muted"><?=htmlspecialchars($it['fp_tx_packets'] ?? 0)?></td>

                        <!-- FP Rx Packet (p/s) -->
                        <td class="text-muted"><?=htmlspecialchars($it['fp_rx_packets'] ?? 0)?></td>

                        <!-- Menu Context icon -->
                        <td style="text-align: center; color: #8faecf;">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'pppoe_servers'): ?>
            <!-- PPPoE Servers Tab -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"><i class="fa-regular fa-flag"></i></th>
                        <th>Service Name</th>
                        <th>Interface</th>
                        <th>Max MTU</th>
                        <th>Max MRU</th>
                        <th>Default Profile</th>
                        <th>One Session Per Host</th>
                        <th>Max Sessions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($vpnConfig['pppoe_servers'] ?? []) as $ps): ?>
                    <tr>
                        <td style="text-align: center;"><i class="fa-solid fa-check text-success"></i></td>
                        <td><strong><?=htmlspecialchars($ps['service_name'])?></strong></td>
                        <td><?=htmlspecialchars($ps['interface'])?></td>
                        <td><?=htmlspecialchars($ps['max_mtu'])?></td>
                        <td><?=htmlspecialchars($ps['max_mru'])?></td>
                        <td><?=htmlspecialchars($ps['default_profile'])?></td>
                        <td><?=!empty($ps['one_session_per_host']) ? 'yes' : 'no'?></td>
                        <td><?=htmlspecialchars($ps['max_sessions'])?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'ovpn_servers'): ?>
            <!-- OVPN Servers Tab -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"><i class="fa-regular fa-flag"></i></th>
                        <th>Port</th>
                        <th>Mode</th>
                        <th>Protocol</th>
                        <th>Default Profile</th>
                        <th>Certificate</th>
                        <th>Cipher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($vpnConfig['ovpn_servers'] ?? []) as $os): ?>
                    <tr>
                        <td style="text-align: center;"><i class="fa-solid fa-check text-success"></i></td>
                        <td><strong><?=htmlspecialchars($os['port'])?></strong></td>
                        <td><?=htmlspecialchars($os['mode'])?></td>
                        <td><span class="label label-info"><?=strtoupper($os['protocol'])?></span></td>
                        <td><?=htmlspecialchars($os['default_profile'])?></td>
                        <td><?=htmlspecialchars($os['certificate'])?></td>
                        <td><?=htmlspecialchars($os['cipher'])?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'secrets'): ?>
            <!-- Secrets Tab -->
            <table class="mitranet-grid" id="secrets-grid-table">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"><i class="fa-regular fa-flag"></i></th>
                        <th>Name</th>
                        <th>Service</th>
                        <th>Password</th>
                        <th>Profile</th>
                        <th>Local Address</th>
                        <th>Remote Address</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vpnConfig['secrets'])): ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 20px;">No secrets configured. Click <strong>New User</strong> to add one.</td></tr>
                    <?php else: foreach ($vpnConfig['secrets'] as $sec): ?>
                    <tr data-id="<?=htmlspecialchars($sec['id'])?>"
                        data-name="<?=htmlspecialchars($sec['name'])?>"
                        data-service="<?=htmlspecialchars($sec['service'])?>"
                        data-password="<?=htmlspecialchars($sec['password'])?>"
                        data-profile="<?=htmlspecialchars($sec['profile'])?>"
                        data-local="<?=htmlspecialchars($sec['local_address'] ?? '')?>"
                        data-remote="<?=htmlspecialchars($sec['remote_address'] ?? '')?>"
                        data-comment="<?=htmlspecialchars($sec['comment'] ?? '')?>"
                        onclick="selectSecretRow(this, <?=htmlspecialchars($sec['id'])?>)"
                        ondblclick="openEditSecretModal(<?=htmlspecialchars($sec['id'])?>)"
                        style="cursor: pointer;">
                        <td style="text-align: center;"><i class="fa-solid fa-key text-warning"></i></td>
                        <td><strong><?=htmlspecialchars($sec['name'])?></strong></td>
                        <td><span class="label label-default"><?=htmlspecialchars($sec['service'])?></span></td>
                        <td><code><?=htmlspecialchars($sec['password'])?></code></td>
                        <td><?=htmlspecialchars($sec['profile'])?></td>
                        <td><?=htmlspecialchars($sec['local_address'])?></td>
                        <td><?=htmlspecialchars($sec['remote_address'])?></td>
                        <td class="text-muted"><?=htmlspecialchars($sec['comment'] ?? '')?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'profiles'): ?>
            <!-- Profiles Tab -->
            <table class="mitranet-grid" id="profiles-grid-table">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"><i class="fa-regular fa-flag"></i></th>
                        <th>Name</th>
                        <th>Local Address</th>
                        <th>Remote Address</th>
                        <th>Use Compression</th>
                        <th>Use Encryption</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vpnConfig['profiles'])): ?>
                        <tr><td colspan="6" class="text-center text-muted" style="padding: 20px;">No profiles configured. Click <strong>New Profile</strong> to add one.</td></tr>
                    <?php else: foreach ($vpnConfig['profiles'] as $pr): ?>
                    <tr data-id="<?=htmlspecialchars($pr['id'])?>"
                        data-name="<?=htmlspecialchars($pr['name'])?>"
                        data-local="<?=htmlspecialchars($pr['local_address'] ?? '')?>"
                        data-remote="<?=htmlspecialchars($pr['remote_address'] ?? '')?>"
                        data-compression="<?=htmlspecialchars($pr['use_compression'] ?? 'default')?>"
                        data-encryption="<?=htmlspecialchars($pr['use_encryption'] ?? 'default')?>"
                        onclick="selectProfileRow(this, <?=htmlspecialchars($pr['id'])?>)"
                        ondblclick="openEditProfileModal(<?=htmlspecialchars($pr['id'])?>)"
                        style="cursor: pointer;">
                        <td style="text-align: center;"><i class="fa-solid fa-check text-success"></i></td>
                        <td><strong><?=htmlspecialchars($pr['name'])?></strong></td>
                        <td><?=htmlspecialchars($pr['local_address'])?></td>
                        <td><?=htmlspecialchars($pr['remote_address'])?></td>
                        <td><?=htmlspecialchars($pr['use_compression'])?></td>
                        <td><?=htmlspecialchars($pr['use_encryption'])?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'active'): ?>
            <!-- Active Connections Tab -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"><i class="fa-regular fa-flag"></i></th>
                        <th>User</th>
                        <th>Service</th>
                        <th>Caller ID</th>
                        <th>Assigned IP</th>
                        <th>Uptime</th>
                        <th>Encoding</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vpnConfig['active_connections'])): ?>
                        <tr><td colspan="7" class="text-center text-muted" style="padding:20px;">No active VPN client connections.</td></tr>
                    <?php else: foreach ($vpnConfig['active_connections'] as $ac): ?>
                    <tr>
                        <td style="text-align: center;"><i class="fa-solid fa-circle text-success" style="font-size: 7px;"></i></td>
                        <td><strong><?=htmlspecialchars($ac['name'])?></strong></td>
                        <td><span class="label label-primary"><?=htmlspecialchars($ac['service'])?></span></td>
                        <td><code><?=htmlspecialchars($ac['caller_id'])?></code></td>
                        <td><code><?=htmlspecialchars($ac['address'])?></code></td>
                        <td><?=htmlspecialchars($ac['uptime'])?></td>
                        <td><?=htmlspecialchars($ac['encoding'])?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'l2tp_ethernet'): ?>
            <!-- L2TP Ethernet Tab -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"><i class="fa-regular fa-flag"></i></th>
                        <th>Name</th>
                        <th>Remote Session ID</th>
                        <th>Remote Tunnel ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="text-center text-muted" style="padding:20px;">No L2TP Ethernet pseudo-wire interfaces.</td></tr>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'l2tp_secrets'): ?>
            <!-- L2TP Secrets Tab -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th style="width: 32px; text-align: center;"><i class="fa-regular fa-flag"></i></th>
                        <th>Address Subnet</th>
                        <th>IPsec Secret / PSK</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($vpnConfig['l2tp_secrets'] ?? []) as $ls): ?>
                    <tr>
                        <td style="text-align: center;"><i class="fa-solid fa-shield text-info"></i></td>
                        <td><code><?=htmlspecialchars($ls['address'])?></code></td>
                        <td><code><?=htmlspecialchars($ls['secret'])?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- STATUSBAR FOOTER (MATCHING WINBOX) -->
        <div class="mitranet-statusbar">
            <div>
                <strong><?=count($interfaces)?></strong> items at <?=htmlspecialchars($current_tab)?>
            </div>
            <div class="text-muted">
                Linux VPN Core: <span class="text-success"><i class="fa-solid fa-circle-check"></i> PPP / OVPN / L2TP / SSTP RUNNING</span>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL DIALOG: NEW / EDIT VPN TUNNEL INTERFACE           -->
<!-- ============================================================== -->
<div id="modal-vpn-interface" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg winbox-modal-dialog">
        <form id="form-vpn-interface" class="form-horizontal">
            <input type="hidden" name="action" id="vpn-action" value="add_interface">
            <input type="hidden" name="id" id="vpn-id" value="0">
            <div class="modal-content winbox-window-popup">

                <!-- WINBOX WINDOW HEADER -->
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-desktop text-primary"></i>
                        <span id="vpn-modal-title">New Interface</span>
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>

                <!-- WINBOX TABS BAR -->
                <div class="winbox-popup-tabs-bar">
                    <ul class="nav nav-tabs winbox-tabs-nav" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#vpntab-general" role="tab" data-toggle="tab">General</a>
                        </li>
                        <li role="presentation">
                            <a href="#vpntab-dialout" role="tab" data-toggle="tab">Dial Out</a>
                        </li>
                        <li role="presentation">
                            <a href="#vpntab-status" role="tab" data-toggle="tab">Status</a>
                        </li>
                    </ul>
                </div>

                <!-- WINBOX BODY: 2-COLUMN (FORM + ACTIONS) -->
                <div class="modal-body winbox-popup-body">
                    <div class="winbox-body-columns">

                        <!-- LEFT: FORM FIELDS -->
                        <div class="winbox-content-left tab-content">

                            <!-- TAB 1: GENERAL -->
                            <div role="tabpanel" class="tab-pane active" id="vpntab-general">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="name" id="vpn-name" class="form-control input-sm" placeholder="<pptp-out1>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Type</label>
                                    <div class="col-sm-9">
                                        <select name="type" id="vpn-type" class="form-control input-sm">
                                            <option value="PPTP Client">PPTP Client</option>
                                            <option value="OVPN Client">OVPN Client</option>
                                            <option value="L2TP Client">L2TP Client</option>
                                            <option value="SSTP Client">SSTP Client</option>
                                            <option value="PPPoE Client">PPPoE Client</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Max MTU</label>
                                    <div class="col-sm-9">
                                        <input type="number" name="mtu" id="vpn-mtu" class="form-control input-sm" value="1450" min="576" max="1500">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Max MRU / L2MTU</label>
                                    <div class="col-sm-9">
                                        <input type="number" name="l2mtu" id="vpn-l2mtu" class="form-control input-sm" value="1500" min="576" max="1500">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Comment</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="comment" id="vpn-comment" class="form-control input-sm" placeholder="Tunnel description...">
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: DIAL OUT -->
                            <div role="tabpanel" class="tab-pane" id="vpntab-dialout">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Connect To</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control input-sm" placeholder="vpn.example.com or IP">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">User</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control input-sm" placeholder="vpnuser">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control input-sm" placeholder="••••••••">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Profile</label>
                                    <div class="col-sm-9">
                                        <select class="form-control input-sm">
                                            <option value="default">default</option>
                                            <option value="default-encryption" selected>default-encryption</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: STATUS -->
                            <div role="tabpanel" class="tab-pane" id="vpntab-status">
                                <table class="table table-condensed table-striped" style="font-size: 11px;">
                                    <tbody>
                                        <tr><th style="width: 35%;">Status</th><td><span class="text-success font-weight-bold">connected</span></td></tr>
                                        <tr><th>Uptime</th><td>02:15:34</td></tr>
                                        <tr><th>Encoding</th><td>MPPE128 / AES-GCM</td></tr>
                                        <tr><th>Local Address</th><td>10.10.10.1</td></tr>
                                        <tr><th>Remote Address</th><td>10.10.10.2</td></tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <!-- RIGHT: ACTIONS COLUMN (WINBOX STYLE) -->
                        <div class="winbox-content-right">
                            <div class="winbox-actions-header">
                                <i class="fa-solid fa-bolt"></i> Actions
                            </div>
                            <ul class="winbox-actions-list">
                                <li><a href="javascript:void(0)" onclick="submitVpnForm(true)">OK</a></li>
                                <li><a href="javascript:void(0)" onclick="$('#modal-vpn-interface').modal('hide')">Cancel</a></li>
                                <li><a href="javascript:void(0)" onclick="submitVpnForm(false)">Apply</a></li>
                                <li><a href="javascript:void(0)" onclick="$('#form-vpn-interface')[0].reset()">Reset</a></li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- WINBOX FOOTER -->
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-status">
                        <span class="badge winbox-running-badge" style="background:#27ae60;">READY</span>
                        <span class="winbox-link-msg">VPN Tunnel Engine Active</span>
                    </div>
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-default" onclick="submitVpnForm(false)">Apply</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitVpnForm(true)">OK</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: PPTP SERVER SETUP                                -->
<!-- ============================================================== -->
<div id="modal-server-pptp" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog" style="max-width: 520px;">
        <form id="form-server-pptp" class="form-horizontal">
            <input type="hidden" name="action" value="save_server_config">
            <input type="hidden" name="server_type" value="pptp">
            <div class="modal-content winbox-window-popup">
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-shield-halved text-info"></i> PPTP Server
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body winbox-popup-body" style="padding: 15px 20px;">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Enabled</label>
                        <div class="col-sm-8">
                            <label style="font-weight: normal; margin-top: 5px;">
                                <input type="checkbox" name="enabled" value="1" <?=!empty($vpnConfig['pptp_server']['enabled']) ? 'checked' : ''?>>
                                Enable PPTP Server
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Max MTU</label>
                        <div class="col-sm-8">
                            <input type="number" name="max_mtu" class="form-control input-sm font-monospace" value="<?=$vpnConfig['pptp_server']['max_mtu'] ?? 1450?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Max MRU</label>
                        <div class="col-sm-8">
                            <input type="number" name="max_mru" class="form-control input-sm font-monospace" value="<?=$vpnConfig['pptp_server']['max_mru'] ?? 1450?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">MRRU</label>
                        <div class="col-sm-8">
                            <select name="mrru" class="form-control input-sm">
                                <option value="disabled" selected>disabled</option>
                                <option value="1500">1500</option>
                                <option value="1600">1600</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Default Profile</label>
                        <div class="col-sm-8">
                            <select name="default_profile" class="form-control input-sm">
                                <?php foreach (($vpnConfig['profiles'] ?? []) as $p): ?>
                                    <option value="<?=htmlspecialchars($p['name'])?>" <?=($vpnConfig['pptp_server']['default_profile'] ?? '') === $p['name'] ? 'selected' : ''?>><?=htmlspecialchars($p['name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Authentication</label>
                        <div class="col-sm-8">
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="mschap2" checked> MS-CHAPv2</label>
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="mschap1"> MS-CHAPv1</label>
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="chap"> CHAP</label>
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="pap"> PAP</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Keepalive Timeout</label>
                        <div class="col-sm-8">
                            <input type="number" name="keepalive_timeout" class="form-control input-sm" value="<?=$vpnConfig['pptp_server']['keepalive_timeout'] ?? 30?>">
                        </div>
                    </div>
                </div>
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitServerForm('pptp', true)">OK</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: SSTP SERVER SETUP                                -->
<!-- ============================================================== -->
<div id="modal-server-sstp" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog" style="max-width: 520px;">
        <form id="form-server-sstp" class="form-horizontal">
            <input type="hidden" name="action" value="save_server_config">
            <input type="hidden" name="server_type" value="sstp">
            <div class="modal-content winbox-window-popup">
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-lock text-success"></i> SSTP Server
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body winbox-popup-body" style="padding: 15px 20px;">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Enabled</label>
                        <div class="col-sm-8">
                            <label style="font-weight: normal; margin-top: 5px;">
                                <input type="checkbox" name="enabled" value="1" <?=!empty($vpnConfig['sstp_server']['enabled']) ? 'checked' : ''?>>
                                Enable SSTP Server
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Port</label>
                        <div class="col-sm-8">
                            <input type="number" name="port" class="form-control input-sm font-monospace" value="<?=$vpnConfig['sstp_server']['port'] ?? 443?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Certificate</label>
                        <div class="col-sm-8">
                            <select name="certificate" class="form-control input-sm">
                                <option value="mitranet-server" selected>mitranet-server (Auto SSL Certificate)</option>
                                <option value="none">none</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Max MTU / MRU</label>
                        <div class="col-sm-4">
                            <input type="number" name="max_mtu" class="form-control input-sm font-monospace" value="<?=$vpnConfig['sstp_server']['max_mtu'] ?? 1500?>" placeholder="MTU">
                        </div>
                        <div class="col-sm-4">
                            <input type="number" name="max_mru" class="form-control input-sm font-monospace" value="<?=$vpnConfig['sstp_server']['max_mru'] ?? 1500?>" placeholder="MRU">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Default Profile</label>
                        <div class="col-sm-8">
                            <select name="default_profile" class="form-control input-sm">
                                <?php foreach (($vpnConfig['profiles'] ?? []) as $p): ?>
                                    <option value="<?=htmlspecialchars($p['name'])?>" <?=($vpnConfig['sstp_server']['default_profile'] ?? '') === $p['name'] ? 'selected' : ''?>><?=htmlspecialchars($p['name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Authentication</label>
                        <div class="col-sm-8">
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="mschap2" checked> MS-CHAPv2</label>
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="pap"> PAP</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Verify Client Cert</label>
                        <div class="col-sm-8">
                            <label style="font-weight: normal; margin-top: 5px;">
                                <input type="checkbox" name="verify_client_certificate" value="1" <?=!empty($vpnConfig['sstp_server']['verify_client_certificate']) ? 'checked' : ''?>>
                                Require Client Certificate
                            </label>
                        </div>
                    </div>
                </div>
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitServerForm('sstp', true)">OK</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: L2TP SERVER SETUP                                -->
<!-- ============================================================== -->
<div id="modal-server-l2tp" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog" style="max-width: 520px;">
        <form id="form-server-l2tp" class="form-horizontal">
            <input type="hidden" name="action" value="save_server_config">
            <input type="hidden" name="server_type" value="l2tp">
            <div class="modal-content winbox-window-popup">
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-key text-warning"></i> L2TP Server
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body winbox-popup-body" style="padding: 15px 20px;">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Enabled</label>
                        <div class="col-sm-8">
                            <label style="font-weight: normal; margin-top: 5px;">
                                <input type="checkbox" name="enabled" value="1" <?=!empty($vpnConfig['l2tp_server']['enabled']) ? 'checked' : ''?>>
                                Enable L2TP Server
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Max MTU / MRU</label>
                        <div class="col-sm-4">
                            <input type="number" name="max_mtu" class="form-control input-sm font-monospace" value="<?=$vpnConfig['l2tp_server']['max_mtu'] ?? 1450?>">
                        </div>
                        <div class="col-sm-4">
                            <input type="number" name="max_mru" class="form-control input-sm font-monospace" value="<?=$vpnConfig['l2tp_server']['max_mru'] ?? 1450?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Default Profile</label>
                        <div class="col-sm-8">
                            <select name="default_profile" class="form-control input-sm">
                                <?php foreach (($vpnConfig['profiles'] ?? []) as $p): ?>
                                    <option value="<?=htmlspecialchars($p['name'])?>" <?=($vpnConfig['l2tp_server']['default_profile'] ?? '') === $p['name'] ? 'selected' : ''?>><?=htmlspecialchars($p['name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Use IPsec</label>
                        <div class="col-sm-8">
                            <select name="use_ipsec" class="form-control input-sm">
                                <option value="yes" <?=($vpnConfig['l2tp_server']['use_ipsec'] ?? 'yes') === 'yes' ? 'selected' : ''?>>yes</option>
                                <option value="require" <?=($vpnConfig['l2tp_server']['use_ipsec'] ?? '') === 'require' ? 'selected' : ''?>>require</option>
                                <option value="no" <?=($vpnConfig['l2tp_server']['use_ipsec'] ?? '') === 'no' ? 'selected' : ''?>>no</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">IPsec Secret / PSK</label>
                        <div class="col-sm-8">
                            <input type="text" name="ipsec_secret" class="form-control input-sm font-monospace" value="<?=htmlspecialchars($vpnConfig['l2tp_server']['ipsec_secret'] ?? 'mitranet_ipsec_psk')?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Authentication</label>
                        <div class="col-sm-8">
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="mschap2" checked> MS-CHAPv2</label>
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="chap"> CHAP</label>
                            <label class="checkbox-inline" style="font-size: 11px;"><input type="checkbox" name="auth[]" value="pap"> PAP</label>
                        </div>
                    </div>
                </div>
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitServerForm('l2tp', true)">OK</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: OPENVPN SERVER SETUP                             -->
<!-- ============================================================== -->
<div id="modal-server-ovpn" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog" style="max-width: 520px;">
        <form id="form-server-ovpn" class="form-horizontal">
            <input type="hidden" name="action" value="save_server_config">
            <input type="hidden" name="server_type" value="ovpn">
            <div class="modal-content winbox-window-popup">
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-globe text-primary"></i> OpenVPN Server
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body winbox-popup-body" style="padding: 15px 20px;">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Enabled</label>
                        <div class="col-sm-8">
                            <label style="font-weight: normal; margin-top: 5px;">
                                <input type="checkbox" name="enabled" value="1" <?=!empty($vpnConfig['ovpn_server']['enabled']) ? 'checked' : ''?>>
                                Enable OpenVPN Server
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Port</label>
                        <div class="col-sm-8">
                            <input type="number" name="port" class="form-control input-sm font-monospace" value="<?=$vpnConfig['ovpn_server']['port'] ?? 1194?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Protocol</label>
                        <div class="col-sm-8">
                            <select name="protocol" class="form-control input-sm">
                                <option value="udp" <?=($vpnConfig['ovpn_server']['protocol'] ?? 'udp') === 'udp' ? 'selected' : ''?>>UDP (Fastest)</option>
                                <option value="tcp" <?=($vpnConfig['ovpn_server']['protocol'] ?? '') === 'tcp' ? 'selected' : ''?>>TCP (Reliable / Firewall Bypass)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Mode</label>
                        <div class="col-sm-8">
                            <select name="mode" class="form-control input-sm">
                                <option value="ip" selected>ip (TUN - Routed)</option>
                                <option value="ethernet">ethernet (TAP - Bridged)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Default Profile</label>
                        <div class="col-sm-8">
                            <select name="default_profile" class="form-control input-sm">
                                <?php foreach (($vpnConfig['profiles'] ?? []) as $p): ?>
                                    <option value="<?=htmlspecialchars($p['name'])?>" <?=($vpnConfig['ovpn_server']['default_profile'] ?? '') === $p['name'] ? 'selected' : ''?>><?=htmlspecialchars($p['name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Certificate</label>
                        <div class="col-sm-8">
                            <select name="certificate" class="form-control input-sm">
                                <option value="mitranet-server" selected>mitranet-server</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Cipher</label>
                        <div class="col-sm-8">
                            <select name="cipher" class="form-control input-sm">
                                <option value="aes-256-gcm" <?=($vpnConfig['ovpn_server']['cipher'] ?? '') === 'aes-256-gcm' ? 'selected' : ''?>>AES-256-GCM (Hardware Accelerated)</option>
                                <option value="aes-128-gcm" <?=($vpnConfig['ovpn_server']['cipher'] ?? '') === 'aes-128-gcm' ? 'selected' : ''?>>AES-128-GCM</option>
                                <option value="chacha20-poly1305" <?=($vpnConfig['ovpn_server']['cipher'] ?? '') === 'chacha20-poly1305' ? 'selected' : ''?>>CHACHA20-POLY1305</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitServerForm('ovpn', true)">OK</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: PPPoE SERVER SETUP                               -->
<!-- ============================================================== -->
<div id="modal-server-pppoe" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog" style="max-width: 520px;">
        <form id="form-server-pppoe" class="form-horizontal">
            <input type="hidden" name="action" value="save_server_config">
            <input type="hidden" name="server_type" value="pppoe">
            <input type="hidden" name="id" value="1">
            <div class="modal-content winbox-window-popup">
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-network-wired text-primary"></i> PPPoE Server Service
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body winbox-popup-body" style="padding: 15px 20px;">
                    <?php $ps_item = ($vpnConfig['pppoe_servers'][0] ?? ['service_name' => 'service1', 'interface' => 'enp1s0', 'max_mtu' => 1480, 'max_mru' => 1480, 'default_profile' => 'default', 'one_session_per_host' => true, 'max_sessions' => 100, 'enabled' => true]); ?>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Service Name</label>
                        <div class="col-sm-8">
                            <input type="text" name="service_name" class="form-control input-sm" value="<?=htmlspecialchars($ps_item['service_name'] ?? 'service1')?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Interface</label>
                        <div class="col-sm-8">
                            <select name="interface" class="form-control input-sm">
                                <option value="enp1s0" <?=($ps_item['interface'] ?? '') === 'enp1s0' ? 'selected' : ''?>>enp1s0 (Physical LAN)</option>
                                <option value="veth0" <?=($ps_item['interface'] ?? '') === 'veth0' ? 'selected' : ''?>>veth0</option>
                                <option value="wlan0" <?=($ps_item['interface'] ?? '') === 'wlan0' ? 'selected' : ''?>>wlan0</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Max MTU / MRU</label>
                        <div class="col-sm-4">
                            <input type="number" name="max_mtu" class="form-control input-sm font-monospace" value="<?=$ps_item['max_mtu'] ?? 1480?>">
                        </div>
                        <div class="col-sm-4">
                            <input type="number" name="max_mru" class="form-control input-sm font-monospace" value="<?=$ps_item['max_mru'] ?? 1480?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Default Profile</label>
                        <div class="col-sm-8">
                            <select name="default_profile" class="form-control input-sm">
                                <?php foreach (($vpnConfig['profiles'] ?? []) as $p): ?>
                                    <option value="<?=htmlspecialchars($p['name'])?>" <?=($ps_item['default_profile'] ?? '') === $p['name'] ? 'selected' : ''?>><?=htmlspecialchars($p['name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">One Session / Host</label>
                        <div class="col-sm-8">
                            <label style="font-weight: normal; margin-top: 5px;">
                                <input type="checkbox" name="one_session_per_host" value="1" <?=!empty($ps_item['one_session_per_host']) ? 'checked' : ''?>>
                                Only 1 session per host
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Max Sessions</label>
                        <div class="col-sm-8">
                            <input type="number" name="max_sessions" class="form-control input-sm font-monospace" value="<?=$ps_item['max_sessions'] ?? 100?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Enabled</label>
                        <div class="col-sm-8">
                            <label style="font-weight: normal; margin-top: 5px;">
                                <input type="checkbox" name="enabled" value="1" <?=!empty($ps_item['enabled']) ? 'checked' : ''?>>
                                Enable PPPoE Service
                            </label>
                        </div>
                    </div>
                </div>
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitServerForm('pppoe', true)">OK</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: PPP SECRET (NEW / EDIT USER)                     -->
<!-- ============================================================== -->
<div id="modal-ppp-secret" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog" style="max-width: 520px;">
        <form id="form-ppp-secret" class="form-horizontal">
            <input type="hidden" name="action" id="secret-action" value="add_secret">
            <input type="hidden" name="id" id="secret-id" value="0">
            <div class="modal-content winbox-window-popup">
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-key text-warning"></i> <span id="secret-modal-title">New PPP Secret</span>
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body winbox-popup-body" style="padding: 15px 20px;">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Name</label>
                        <div class="col-sm-8">
                            <input type="text" name="name" id="secret-name" class="form-control input-sm" placeholder="username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Password</label>
                        <div class="col-sm-8">
                            <input type="text" name="password" id="secret-password" class="form-control input-sm font-monospace" placeholder="password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Service</label>
                        <div class="col-sm-8">
                            <select name="service" id="secret-service" class="form-control input-sm">
                                <option value="any">any</option>
                                <option value="pppoe">pppoe</option>
                                <option value="pptp">pptp</option>
                                <option value="sstp">sstp</option>
                                <option value="l2tp">l2tp</option>
                                <option value="ovpn">ovpn</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Profile</label>
                        <div class="col-sm-8">
                            <select name="profile" id="secret-profile" class="form-control input-sm">
                                <?php foreach (($vpnConfig['profiles'] ?? []) as $p): ?>
                                    <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars($p['name'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Local Address</label>
                        <div class="col-sm-8">
                            <input type="text" name="local_address" id="secret-local-address" class="form-control input-sm font-monospace" placeholder="10.10.10.1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Remote Address</label>
                        <div class="col-sm-8">
                            <input type="text" name="remote_address" id="secret-remote-address" class="form-control input-sm font-monospace" placeholder="10.10.10.10">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Comment</label>
                        <div class="col-sm-8">
                            <input type="text" name="comment" id="secret-comment" class="form-control input-sm" placeholder="User comment...">
                        </div>
                    </div>
                </div>
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitSecretForm(true)">OK</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: PPP PROFILE (NEW / EDIT PROFILE)                -->
<!-- ============================================================== -->
<div id="modal-ppp-profile" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog" style="max-width: 520px;">
        <form id="form-ppp-profile" class="form-horizontal">
            <input type="hidden" name="action" id="profile-action" value="add_profile">
            <input type="hidden" name="id" id="profile-id" value="0">
            <div class="modal-content winbox-window-popup">
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-list-check text-primary"></i> <span id="profile-modal-title">New PPP Profile</span>
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body winbox-popup-body" style="padding: 15px 20px;">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Name</label>
                        <div class="col-sm-8">
                            <input type="text" name="name" id="profile-name" class="form-control input-sm" placeholder="profile1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Local Address</label>
                        <div class="col-sm-8">
                            <input type="text" name="local_address" id="profile-local-address" class="form-control input-sm font-monospace" placeholder="10.10.20.1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Remote Address</label>
                        <div class="col-sm-8">
                            <input type="text" name="remote_address" id="profile-remote-address" class="form-control input-sm font-monospace" placeholder="pool-vpn">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Use Compression</label>
                        <div class="col-sm-8">
                            <select name="use_compression" id="profile-compression" class="form-control input-sm">
                                <option value="default">default</option>
                                <option value="yes">yes</option>
                                <option value="no">no</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Use Encryption</label>
                        <div class="col-sm-8">
                            <select name="use_encryption" id="profile-encryption" class="form-control input-sm">
                                <option value="default">default</option>
                                <option value="require">require</option>
                                <option value="yes">yes</option>
                                <option value="no">no</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitProfileForm(true)">OK</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>

<script type="text/javascript">
var selectedVpnId = null;
var selectedVpnRowData = null;

function selectVpnRow(tr, id) {
    $('#vpn-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedVpnId = id;

    selectedVpnRowData = {
        id: id,
        name: $(tr).data('name'),
        type: $(tr).data('type'),
        mtu: $(tr).data('mtu'),
        l2mtu: $(tr).data('l2mtu'),
        comment: $(tr).data('comment'),
        enabled: $(tr).data('enabled') == 1
    };

    // Enable toolbar buttons
    $('#btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', false);
}

function openNewVpnModal() {
    $('#vpn-action').val('add_interface');
    $('#vpn-id').val('0');
    $('#vpn-modal-title').text('New Interface');
    $('#vpn-name').val('<tunnel-' + (Math.floor(Math.random() * 900) + 100) + '>');
    $('#vpn-type').val('PPTP Client');
    $('#vpn-mtu').val('1450');
    $('#vpn-l2mtu').val('1500');
    $('#vpn-comment').val('');
    $('#modal-vpn-interface').modal('show');
}

function openEditVpnModal(id) {
    var $tr = $('#vpn-grid-table tbody tr[data-id="' + id + '"]');
    if ($tr.length === 0) return;

    $('#vpn-action').val('edit_interface');
    $('#vpn-id').val(id);
    var name = $tr.data('name');
    $('#vpn-modal-title').text('Interface <' + name + '>');
    $('#vpn-name').val(name);
    $('#vpn-type').val($tr.data('type'));
    $('#vpn-mtu').val($tr.data('mtu'));
    $('#vpn-l2mtu').val($tr.data('l2mtu'));
    $('#vpn-comment').val($tr.data('comment'));
    $('#modal-vpn-interface').modal('show');
}

function submitVpnForm(closeModal) {
    var name = $('#vpn-name').val().trim();
    if (!name) {
        MitraNet.toast('error', 'Interface Name cannot be empty');
        return;
    }

    var formData = $('#form-vpn-interface').serialize() + '&ajax=1';

    $.ajax({
        url: 'vpn.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res && res.success) {
                MitraNet.toast('success', res.message || 'VPN Interface saved');
                if (closeModal) {
                    $('#modal-vpn-interface').modal('hide');
                    location.reload();
                } else {
                    $('#vpn-action').val('edit_interface');
                }
            } else {
                MitraNet.toast('error', (res && res.error) ? res.error : 'Failed saving interface');
            }
        },
        error: function() {
            MitraNet.toast('error', 'Error communicating with server');
        }
    });
}

function toggleSelectedVpn(state) {
    if (!selectedVpnId) return;
    $.ajax({
        url: 'vpn.php',
        type: 'POST',
        data: {
            action: 'toggle_interface',
            id: selectedVpnId,
            state: state ? '1' : '0',
            ajax: 1
        },
        dataType: 'json',
        success: function(res) {
            MitraNet.toast('success', state ? 'Interface Enabled' : 'Interface Disabled');
            location.reload();
        }
    });
}

function removeSelectedVpn() {
    if (!selectedVpnId || !selectedVpnRowData) return;
    MitraNet.confirmDelete({
        title: 'Remove VPN Interface?',
        name: selectedVpnRowData.name,
        warning: 'This will terminate and remove the tunnel interface.',
        url: 'vpn.php',
        data: {
            action: 'delete_interface',
            id: selectedVpnId,
            ajax: 1
        },
        onSuccess: function() {
            selectedVpnId = null;
            $('#btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', true);
            location.reload();
        }
    });
}

function commentSelectedVpn() {
    if (!selectedVpnId || !selectedVpnRowData) return;
    MitraNet.promptInput({
        title: 'Set Comment',
        inputLabel: 'Comment for ' + selectedVpnRowData.name + ':',
        inputValue: selectedVpnRowData.comment || '',
        placeholder: 'e.g. Tunnel uplink connection',
        confirmText: 'Save Comment',
        url: 'vpn.php',
        data: {
            action: 'comment_interface',
            id: selectedVpnId,
            ajax: 1
        },
        inputParam: 'comment',
        onSuccess: function() {
            location.reload();
        }
    });
}

function filterVpnGrid(query) {
    query = (query || '').toLowerCase().trim();
    $('#vpn-grid-table tbody tr, #secrets-grid-table tbody tr, #profiles-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(query) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// =========================================================
// VPN SERVER MODALS (PPTP, SSTP, L2TP, OVPN, PPPoE)
// =========================================================
function openServerModal(type) {
    $('#modal-server-' + type).modal('show');
}

function submitServerForm(type, closeModal) {
    var form = $('#form-server-' + type);
    var formData = form.serialize() + '&ajax=1';

    $.ajax({
        url: 'vpn.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res && res.success) {
                MitraNet.toast('success', res.message || 'Server configuration saved');
                if (closeModal) {
                    $('#modal-server-' + type).modal('hide');
                    setTimeout(function() { location.reload(); }, 600);
                }
            } else {
                MitraNet.toast('error', (res && res.error) ? res.error : 'Failed to save configuration');
            }
        },
        error: function() {
            MitraNet.toast('error', 'Error communicating with server');
        }
    });
}

// =========================================================
// PPP SECRETS HANDLERS
// =========================================================
var selectedSecretId = null;
var selectedSecretName = '';

function selectSecretRow(tr, id) {
    $('#secrets-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedSecretId = id;
    selectedSecretName = $(tr).data('name') || '';
    $('#btn-secret-remove').prop('disabled', false);
}

function openNewSecretModal() {
    $('#secret-action').val('add_secret');
    $('#secret-id').val('0');
    $('#secret-modal-title').text('New PPP Secret');
    $('#secret-name').val('');
    $('#secret-password').val('');
    $('#secret-service').val('any');
    $('#secret-local-address').val('');
    $('#secret-remote-address').val('');
    $('#secret-comment').val('');
    $('#modal-ppp-secret').modal('show');
}

function openEditSecretModal(id) {
    var $tr = $('#secrets-grid-table tbody tr[data-id="' + id + '"]');
    if ($tr.length === 0) return;

    $('#secret-action').val('edit_secret');
    $('#secret-id').val(id);
    var name = $tr.data('name');
    $('#secret-modal-title').text('Secret <' + name + '>');
    $('#secret-name').val(name);
    $('#secret-password').val($tr.data('password') || '');
    $('#secret-service').val($tr.data('service') || 'any');
    $('#secret-profile').val($tr.data('profile') || 'default');
    $('#secret-local-address').val($tr.data('local') || '');
    $('#secret-remote-address').val($tr.data('remote') || '');
    $('#secret-comment').val($tr.data('comment') || '');
    $('#modal-ppp-secret').modal('show');
}

function submitSecretForm(closeModal) {
    var name = $('#secret-name').val().trim();
    if (!name) {
        MitraNet.toast('error', 'Username cannot be empty');
        return;
    }
    var formData = $('#form-ppp-secret').serialize() + '&ajax=1';
    $.ajax({
        url: 'vpn.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res && res.success) {
                MitraNet.toast('success', res.message || 'PPP Secret saved');
                if (closeModal) {
                    $('#modal-ppp-secret').modal('hide');
                    setTimeout(function() { location.reload(); }, 600);
                }
            } else {
                MitraNet.toast('error', (res && res.error) ? res.error : 'Failed to save secret');
            }
        },
        error: function() {
            MitraNet.toast('error', 'Error communicating with server');
        }
    });
}

function removeSelectedSecret() {
    if (!selectedSecretId) return;
    MitraNet.confirmDelete({
        title: 'Remove PPP Secret?',
        name: selectedSecretName,
        warning: 'This will remove the user authentication secret.',
        url: 'vpn.php',
        data: {
            action: 'delete_secret',
            id: selectedSecretId,
            ajax: 1
        },
        onSuccess: function() {
            selectedSecretId = null;
            $('#btn-secret-remove').prop('disabled', true);
            location.reload();
        }
    });
}

// =========================================================
// PPP PROFILES HANDLERS
// =========================================================
var selectedProfileId = null;
var selectedProfileName = '';

function selectProfileRow(tr, id) {
    $('#profiles-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedProfileId = id;
    selectedProfileName = $(tr).data('name') || '';
    $('#btn-profile-remove').prop('disabled', false);
}

function openNewProfileModal() {
    $('#profile-action').val('add_profile');
    $('#profile-id').val('0');
    $('#profile-modal-title').text('New PPP Profile');
    $('#profile-name').val('');
    $('#profile-local-address').val('');
    $('#profile-remote-address').val('');
    $('#profile-compression').val('default');
    $('#profile-encryption').val('default');
    $('#modal-ppp-profile').modal('show');
}

function openEditProfileModal(id) {
    var $tr = $('#profiles-grid-table tbody tr[data-id="' + id + '"]');
    if ($tr.length === 0) return;

    $('#profile-action').val('edit_profile');
    $('#profile-id').val(id);
    var name = $tr.data('name');
    $('#profile-modal-title').text('Profile <' + name + '>');
    $('#profile-name').val(name);
    $('#profile-local-address').val($tr.data('local') || '');
    $('#profile-remote-address').val($tr.data('remote') || '');
    $('#profile-compression').val($tr.data('compression') || 'default');
    $('#profile-encryption').val($tr.data('encryption') || 'default');
    $('#modal-ppp-profile').modal('show');
}

function submitProfileForm(closeModal) {
    var name = $('#profile-name').val().trim();
    if (!name) {
        MitraNet.toast('error', 'Profile Name cannot be empty');
        return;
    }
    var formData = $('#form-ppp-profile').serialize() + '&ajax=1';
    $.ajax({
        url: 'vpn.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res && res.success) {
                MitraNet.toast('success', res.message || 'PPP Profile saved');
                if (closeModal) {
                    $('#modal-ppp-profile').modal('hide');
                    setTimeout(function() { location.reload(); }, 600);
                }
            } else {
                MitraNet.toast('error', (res && res.error) ? res.error : 'Failed to save profile');
            }
        },
        error: function() {
            MitraNet.toast('error', 'Error communicating with server');
        }
    });
}

function removeSelectedProfile() {
    if (!selectedProfileId) return;
    MitraNet.confirmDelete({
        title: 'Remove PPP Profile?',
        name: selectedProfileName,
        warning: 'This will remove the PPP Profile configuration.',
        url: 'vpn.php',
        data: {
            action: 'delete_profile',
            id: selectedProfileId,
            ajax: 1
        },
        onSuccess: function() {
            selectedProfileId = null;
            $('#btn-profile-remove').prop('disabled', true);
            location.reload();
        }
    });
}

function disconnectActiveSession() {
    MitraNet.toast('info', 'Active session disconnected.');
    setTimeout(function() { location.reload(); }, 600);
}
</script>
