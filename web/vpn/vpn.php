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
                    <a href="vpn_booster.php" class="text-primary font-weight-bold"><i class="fa-solid fa-bolt"></i> Cloud Speed Booster</a>
                </li>
            </ul>
        </div>

        <!-- TOOLBAR (MATCHING EXACT SCREENSHOT) -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left">
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
            <table class="mitranet-grid">
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
                    <?php foreach (($vpnConfig['secrets'] ?? []) as $sec): ?>
                    <tr>
                        <td style="text-align: center;"><i class="fa-solid fa-key text-warning"></i></td>
                        <td><strong><?=htmlspecialchars($sec['name'])?></strong></td>
                        <td><span class="label label-default"><?=htmlspecialchars($sec['service'])?></span></td>
                        <td><code><?=htmlspecialchars($sec['password'])?></code></td>
                        <td><?=htmlspecialchars($sec['profile'])?></td>
                        <td><?=htmlspecialchars($sec['local_address'])?></td>
                        <td><?=htmlspecialchars($sec['remote_address'])?></td>
                        <td class="text-muted"><?=htmlspecialchars($sec['comment'] ?? '')?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'profiles'): ?>
            <!-- Profiles Tab -->
            <table class="mitranet-grid">
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
                    <?php foreach (($vpnConfig['profiles'] ?? []) as $pr): ?>
                    <tr>
                        <td style="text-align: center;"><i class="fa-solid fa-check text-success"></i></td>
                        <td><strong><?=htmlspecialchars($pr['name'])?></strong></td>
                        <td><?=htmlspecialchars($pr['local_address'])?></td>
                        <td><?=htmlspecialchars($pr['remote_address'])?></td>
                        <td><?=htmlspecialchars($pr['use_compression'])?></td>
                        <td><?=htmlspecialchars($pr['use_encryption'])?></td>
                    </tr>
                    <?php endforeach; ?>
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
    $('#vpn-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(query) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}
</script>
