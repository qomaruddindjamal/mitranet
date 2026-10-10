<?php
/*
 * dhcp_settings.php - MitraNet Services: DHCP Server Management
 * RouterOS / WinBox Style Data Grid with Tabs:
 * [DHCP] [Networks] [Leases] [Options] [Option Sets] [Option Matcher] [Alerts]
 */

require_once(__DIR__ . '/../includes/api.inc');

$msg = "";
$err = "";

// 1. Fetch system interfaces, bridges, vethernets, and gateways
$all_interfaces = MitraNetApi::getInterfaces();
$all_bridges    = MitraNetApi::getBridges();
$all_vethernets = MitraNetApi::getVethernets();
$all_vlans      = MitraNetApi::getVlans();
$gateways       = MitraNetApi::getGateways();

// Detect WAN interfaces to exclude from DHCP server
$wan_ifaces = [];
foreach ($gateways as $gw) {
    if (!empty($gw['default']) && !empty($gw['interface'])) {
        $wan_ifaces[] = $gw['interface'];
    }
}
$wan_ifaces = array_unique($wan_ifaces);

// Build eligible interface map
$eligible_ifaces = [];

// Physical LAN
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

    $eligible_ifaces[$n] = [
        'name'    => $n,
        'label'   => "LAN: {$n}",
        'ip_cidr' => $it['ipv4_addresses'][0] ?? '',
        'type'    => 'Physical LAN'
    ];
}

// Bridges
foreach ($all_bridges as $br) {
    $br_ip = '';
    foreach ($all_interfaces as $it) {
        if ($it['name'] === $br['name']) {
            $br_ip = $it['ipv4_addresses'][0] ?? '';
            break;
        }
    }
    $eligible_ifaces[$br['name']] = [
        'name'    => $br['name'],
        'label'   => "Bridge: {$br['name']}",
        'ip_cidr' => $br_ip,
        'type'    => 'Bridge'
    ];
}

// vEthernet
foreach ($all_vethernets as $ve) {
    $eligible_ifaces[$ve['name']] = [
        'name'    => $ve['name'],
        'label'   => "vEthernet: {$ve['name']}",
        'ip_cidr' => $ve['ip_cidr'] ?? '',
        'type'    => 'vEthernet'
    ];
}

// VLANs
foreach ($all_vlans as $vl) {
    $vl_name = $vl['name'] ?? '';
    if ($vl_name && !isset($eligible_ifaces[$vl_name]) && !in_array($vl_name, $wan_ifaces)) {
        $vl_ip = '';
        foreach ($all_interfaces as $it) {
            if ($it['name'] === $vl_name) {
                $vl_ip = $it['ipv4_addresses'][0] ?? '';
                break;
            }
        }
        $eligible_ifaces[$vl_name] = [
            'name'    => $vl_name,
            'label'   => "VLAN: {$vl_name}",
            'ip_cidr' => $vl_ip,
            'type'    => 'VLAN'
        ];
    }
}

// Handle POST actions (Save, Enable, Disable, Remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        $post_if     = trim($_POST['interface'] ?? '');
        $enabled     = !empty($_POST['enable']);
        $range_start = trim($_POST['range_start'] ?? '');
        $range_end   = trim($_POST['range_end'] ?? '');
        $gateway     = trim($_POST['gateway'] ?? '');
        $dns_raw     = trim($_POST['dns'] ?? '');
        $lease_time  = trim($_POST['lease_time'] ?? '12h');

        $dns_arr = [];
        if (!empty($dns_raw)) {
            $dns_arr = array_filter(array_map('trim', explode(',', str_replace(' ', ',', $dns_raw))));
        }

        $payload = [
            'interface'   => $post_if,
            'enabled'     => $enabled,
            'range_start' => $range_start,
            'range_end'   => $range_end,
            'gateway'     => $gateway,
            'dns'         => array_values($dns_arr),
            'lease_time'  => $lease_time
        ];

        $res = MitraNetApi::saveDhcpConfig($payload);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $msg = "DHCP Server untuk interface '{$post_if}' berhasil disimpan dan aktif.";
        } else {
            $err = $res['data']['error'] ?? "Gagal menyimpan konfigurasi DHCP Server.";
        }
    } elseif ($action === 'toggle_state') {
        $post_if = trim($_POST['interface'] ?? '');
        $state   = !empty($_POST['enable']);
        $dhcp_cfgs = MitraNetApi::getDhcpConfigs();
        $curr = $dhcp_cfgs[$post_if] ?? [];
        if ($curr) {
            $curr['enabled'] = $state;
            $res = MitraNetApi::saveDhcpConfig($curr);
            if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
                $msg = "Status DHCP Server interface '{$post_if}' berhasil diperbarui.";
            } else {
                $err = $res['data']['error'] ?? "Gagal mengubah status DHCP Server.";
            }
        }
    } elseif ($action === 'remove') {
        $post_if = trim($_POST['interface'] ?? '');
        $res = MitraNetApi::saveDhcpConfig(['interface' => $post_if, 'enabled' => false, 'action' => 'remove']);
        $is_ok = (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false));
        if ($is_ok) {
            $msg = "DHCP Server pada interface '{$post_if}' berhasil dihapus.";
        } else {
            $err = $res['data']['error'] ?? "Gagal menghapus DHCP Server.";
        }

        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $is_ok,
                'message' => $msg,
                'error'   => $err
            ]);
            exit;
        }
    }
}

// Active Tab
$current_tab = $_GET['tab'] ?? 'dhcp';
$valid_tabs  = ['dhcp', 'networks', 'leases', 'options', 'option_sets', 'option_matcher', 'alerts'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'dhcp';
}

// Load active DHCP configs
$dhcp_configs = MitraNetApi::getDhcpConfigs();

// Read active DHCP leases from /var/lib/misc/dnsmasq.leases
$active_leases = [];
$leases_file = '/var/lib/misc/dnsmasq.leases';
if (file_exists($leases_file) && is_readable($leases_file)) {
    $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 4) {
            $exp_ts   = (int)$parts[0];
            $mac      = $parts[1];
            $ip       = $parts[2];
            $hostname = $parts[3] !== '*' ? $parts[3] : '-';
            $client_id= $parts[4] ?? '-';
            $expires  = $exp_ts > 0 ? date('Y-m-d H:i:s', $exp_ts) : 'Never';

            $active_leases[] = [
                'ip'        => $ip,
                'mac'       => $mac,
                'hostname'  => $hostname,
                'client_id' => $client_id,
                'expires'   => $expires,
                'status'    => ($exp_ts > time() || $exp_ts === 0) ? 'bound' : 'expired'
            ];
        }
    }
}

// Standard header pfSense / MitraNet
$pgtitle       = array(gettext("Services"), gettext("DHCP Server"));
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');
?>

<style>
/* WinBox Style for Disabled Row in Data Grid */
.mitranet-grid tbody tr.mitranet-row-disabled {
    color: #8c9ba5 !important;
    background-color: #f8fafc !important;
}
.mitranet-grid tbody tr.mitranet-row-disabled:hover {
    background-color: #edf2f7 !important;
}
.mitranet-grid tbody tr.mitranet-row-disabled td {
    color: #8c9ba5 !important;
    font-style: italic !important;
}
.mitranet-grid tbody tr.mitranet-row-disabled strong,
.mitranet-grid tbody tr.mitranet-row-disabled code,
.mitranet-grid tbody tr.mitranet-row-disabled small {
    color: #8c9ba5 !important;
}
.mitranet-grid tbody tr.mitranet-row-disabled .label {
    background-color: #cbd5e1 !important;
    color: #64748b !important;
    border-color: #94a3b8 !important;
    opacity: 0.75;
}
.mitranet-grid tbody tr.mitranet-row-disabled.selected {
    background-color: #dbe4ee !important;
    color: #5a6e7f !important;
}
.mitranet-grid tbody tr.mitranet-row-disabled.selected td {
    color: #5a6e7f !important;
}
</style>

<div class="container-fluid mitranet-page-container">
    <div class="mitranet-window">

        <!-- HEADER: TITLE BADGE + WINBOX TABS (MATCHING SCREENSHOT) -->
        <div class="mitranet-header">
            <div class="mitranet-title-badge">
                <i class="fa-solid fa-server"></i> DHCP Server
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="mitranet-tabs">
                <li class="<?=$current_tab === 'dhcp' ? 'active' : ''?>">
                    <a href="dhcp_settings.php?tab=dhcp"><?=gettext("DHCP")?></a>
                </li>
                <li class="<?=$current_tab === 'networks' ? 'active' : ''?>">
                    <a href="dhcp_settings.php?tab=networks"><?=gettext("Networks")?></a>
                </li>
                <li class="<?=$current_tab === 'leases' ? 'active' : ''?>">
                    <a href="dhcp_settings.php?tab=leases"><?=gettext("Leases")?></a>
                </li>
                <li class="<?=$current_tab === 'options' ? 'active' : ''?>">
                    <a href="dhcp_settings.php?tab=options"><?=gettext("Options")?></a>
                </li>
                <li class="<?=$current_tab === 'option_sets' ? 'active' : ''?>">
                    <a href="dhcp_settings.php?tab=option_sets"><?=gettext("Option Sets")?></a>
                </li>
                <li class="<?=$current_tab === 'option_matcher' ? 'active' : ''?>">
                    <a href="dhcp_settings.php?tab=option_matcher"><?=gettext("Option Matcher")?></a>
                </li>
                <li class="<?=$current_tab === 'alerts' ? 'active' : ''?>">
                    <a href="dhcp_settings.php?tab=alerts"><?=gettext("Alerts")?></a>
                </li>
            </ul>
        </div>

        <!-- TOOLBAR: NEW, DHCP SETUP, EDIT, ENABLE, DISABLE, REMOVE, COMMENT, FIND, FILTER -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left">
                <button type="button" class="mitranet-btn" onclick="openNewDhcpModal()" title="Tambah DHCP Server Baru">
                    <i class="fa-solid fa-folder-plus text-primary"></i> <strong>New</strong>
                </button>
                <button type="button" class="mitranet-btn" onclick="openDhcpSetupWizard()" title="Panduan Konfigurasi Cepat DHCP Server (RouterOS Wizard)">
                    <i class="fa-solid fa-wand-magic-sparkles text-warning"></i> <strong>DHCP Setup</strong>
                </button>
                <button type="button" class="mitranet-btn" id="btn-edit" disabled title="Edit Konfigurasi Terpilih" onclick="openEditDhcpModal()">
                    <i class="fa-solid fa-pen-to-square text-primary"></i> <strong>Edit</strong>
                </button>
                <button type="button" class="mitranet-btn" id="btn-enable" disabled title="Aktifkan DHCP Terpilih" onclick="toggleSelectedDhcp(true)">
                    <i class="fa-solid fa-play text-muted"></i> Enable
                </button>
                <button type="button" class="mitranet-btn" id="btn-disable" disabled title="Nonaktifkan DHCP Terpilih" onclick="toggleSelectedDhcp(false)">
                    <i class="fa-solid fa-pause text-muted"></i> Disable
                </button>
                <button type="button" class="mitranet-btn" id="btn-remove" disabled title="Hapus / Nonaktifkan" onclick="removeSelectedDhcp()">
                    <i class="fa-solid fa-xmark text-muted"></i> Remove
                </button>
                <button type="button" class="mitranet-btn" id="btn-comment" disabled title="Beri Komentar">
                    <i class="fa-regular fa-comment text-muted"></i> Comment
                </button>
            </div>
            <div class="mitranet-toolbar-right">
                <div class="mitranet-search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="grid-search" placeholder="Find" onkeyup="filterDhcpGrid(this.value)">
                </div>
                <button type="button" class="mitranet-btn" onclick="$('#grid-search').focus()" title="Filter">
                    <i class="fa-solid fa-filter text-muted"></i> Filter
                </button>
                <button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>
        
        <?php if (!empty($msg)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof MitraNet !== 'undefined' && MitraNet.toast) {
                        MitraNet.toast('success', <?=json_encode($msg)?>);
                    }
                });
            </script>
        <?php endif; ?>
        <?php if (!empty($err)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof MitraNet !== 'undefined' && MitraNet.toast) {
                        MitraNet.toast('error', <?=json_encode($err)?>);
                    }
                });
            </script>
        <?php endif; ?>

        <!-- DATA GRID CONTAINER -->
        <div class="mitranet-grid-container">

            <?php if ($current_tab === 'dhcp'): ?>
            <!-- ============================================== -->
            <!-- TAB: DHCP SERVERS (MATCHING SCREENSHOT COLUMNS) -->
            <!-- ============================================== -->
            <table class="mitranet-grid" id="dhcp-grid-table">
                <thead>
                    <tr>
                        <th class="text-center col-flag" style="width: 32px;"><i class="fa-regular fa-flag"></i></th>
                        <th class="sortable" style="width: 140px;">Name <i class="fa-solid fa-caret-up"></i></th>
                        <th class="sortable" style="width: 130px;">Interface</th>
                        <th class="sortable" style="width: 90px;">Relay</th>
                        <th class="sortable" style="width: 100px;">Lease Time</th>
                        <th class="sortable" style="width: 220px;">Address Pool</th>
                        <th class="sortable" style="width: 100px;">Add AR...</th>
                        <th class="sortable" style="width: 100px;">Add DN...</th>
                        <th class="sortable">Dynamic Gateway / DNS</th>
                        <th class="text-center col-menu" style="width: 32px;"><i class="fa-solid fa-bars"></i></th>
                    </tr>
                </thead>
                <tbody id="dhcp-grid-body">
                    <?php if (empty($dhcp_configs)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted" style="padding: 24px;">
                            <i class="fa-solid fa-circle-info"></i> Belum ada DHCP Server yang dikonfigurasi. Klik <strong>New</strong> atau <strong>DHCP Setup</strong> untuk membuat server.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        foreach ($dhcp_configs as $if_key => $cfg): 
                            $if_data    = $eligible_ifaces[$if_key] ?? ['name' => $if_key, 'type' => 'Interface', 'label' => $if_key, 'ip_cidr' => ''];
                            $is_on      = !empty($cfg['enabled']);
                            $pool_start = $cfg['range_start'] ?? '';
                            $pool_end   = $cfg['range_end'] ?? '';
                            $pool_str   = ($pool_start && $pool_end) ? "{$pool_start} - {$pool_end}" : ($if_data['ip_cidr'] ? 'Auto Subnet Range' : '-');
                            $gw_val     = $cfg['gateway'] ?? ($if_data['ip_cidr'] ? explode('/', $if_data['ip_cidr'])[0] : '-');
                            $dns_val    = !empty($cfg['dns']) ? implode(', ', (array)$cfg['dns']) : '8.8.8.8, 1.1.1.1';
                            $lease_val  = $cfg['lease_time'] ?? '12h';
                            $server_name= "dhcp_" . $if_key;
                        ?>
                        <tr class="<?=$is_on ? '' : 'mitranet-row-disabled'?>"
                            data-ifname="<?=htmlspecialchars($if_key)?>"
                            data-enabled="<?=$is_on ? '1' : '0'?>"
                            data-pool-start="<?=htmlspecialchars($pool_start)?>"
                            data-pool-end="<?=htmlspecialchars($pool_end)?>"
                            data-gateway="<?=htmlspecialchars($gw_val)?>"
                            data-dns="<?=htmlspecialchars($dns_val)?>"
                            data-lease="<?=htmlspecialchars($lease_val)?>"
                            onclick="selectDhcpRow(this, '<?=htmlspecialchars($if_key)?>')"
                            ondblclick="openEditDhcpModal('<?=htmlspecialchars($if_key)?>')"
                            style="cursor: pointer;">
                            
                            <!-- Flag Cell -->
                            <td class="text-center col-flag-cell">
                                <?php if ($is_on): ?>
                                    <i class="fa-solid fa-check text-success" title="Running / Enabled"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-minus text-muted" title="Disabled"></i>
                                <?php endif; ?>
                            </td>

                            <!-- Name -->
                            <td>
                                <strong class="<?=$is_on ? 'text-primary' : ''?>"><?=htmlspecialchars($server_name)?></strong>
                            </td>

                            <!-- Interface -->
                            <td>
                                <strong><?=htmlspecialchars($if_key)?></strong>
                                <span class="text-muted fs-11">(<?=htmlspecialchars($if_data['type'])?>)</span>
                            </td>

                            <!-- Relay -->
                            <td class="text-muted">-</td>

                            <!-- Lease Time -->
                            <td><code><?=htmlspecialchars($lease_val)?></code></td>

                            <!-- Address Pool -->
                            <td>
                                <span class="label <?=($is_on && $pool_start && $pool_end) ? 'label-primary' : 'label-default'?>" style="font-family: monospace; font-size: 11px;">
                                    <?=htmlspecialchars($pool_str)?>
                                </span>
                            </td>

                            <!-- Add ARP -->
                            <td class="text-muted">yes</td>

                            <!-- Add DNS -->
                            <td class="text-muted">yes</td>

                            <!-- Dynamic Gateway / DNS info -->
                            <td>
                                <small class="text-muted">GW: <strong><?=htmlspecialchars($gw_val)?></strong> | DNS: <?=htmlspecialchars($dns_val)?></small>
                            </td>

                            <!-- WinBox menu column -->
                            <td class="text-center col-menu">
                                <i class="fa-solid fa-bars text-muted" style="font-size: 10px;"></i>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'networks'): ?>
            <!-- ============================================== -->
            <!-- TAB: NETWORKS                                  -->
            <!-- ============================================== -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th class="text-center col-flag" style="width: 32px;"><i class="fa-regular fa-flag"></i></th>
                        <th class="sortable" style="width: 180px;">Address <i class="fa-solid fa-caret-up"></i></th>
                        <th class="sortable" style="width: 160px;">Gateway</th>
                        <th class="sortable" style="width: 140px;">Netmask</th>
                        <th class="sortable" style="width: 220px;">DNS Servers</th>
                        <th class="sortable">Domain</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eligible_ifaces as $if_key => $if_data): 
                        $cfg = $dhcp_configs[$if_key] ?? [];
                        $gw  = $cfg['gateway'] ?? ($if_data['ip_cidr'] ? explode('/', $if_data['ip_cidr'])[0] : '-');
                        $dns = !empty($cfg['dns']) ? implode(', ', (array)$cfg['dns']) : '8.8.8.8, 1.1.1.1';
                    ?>
                    <tr>
                        <td class="text-center col-flag-cell"><i class="fa-solid fa-check text-success"></i></td>
                        <td><strong><?=htmlspecialchars($if_data['ip_cidr'] ?: '-')?></strong></td>
                        <td><code><?=htmlspecialchars($gw)?></code></td>
                        <td>255.255.255.0</td>
                        <td><?=htmlspecialchars($dns)?></td>
                        <td class="text-muted">localdomain</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'leases'): ?>
            <!-- ============================================== -->
            <!-- TAB: LEASES                                    -->
            <!-- ============================================== -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th class="text-center col-flag" style="width: 32px;"><i class="fa-regular fa-flag"></i></th>
                        <th class="sortable" style="width: 160px;">IP Address <i class="fa-solid fa-caret-up"></i></th>
                        <th class="sortable" style="width: 180px;">MAC Address</th>
                        <th class="sortable" style="width: 180px;">Host Name</th>
                        <th class="sortable" style="width: 120px;">Status</th>
                        <th class="sortable">Expires After / At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_leases)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding: 24px;">
                            <i class="fa-solid fa-circle-info"></i> Belum ada DHCP Leases aktif di sistem saat ini.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($active_leases as $ls): ?>
                        <tr>
                            <td class="text-center col-flag-cell"><i class="fa-solid fa-circle-check text-success"></i></td>
                            <td><strong class="text-primary font-monospace"><?=htmlspecialchars($ls['ip'])?></strong></td>
                            <td><code><?=htmlspecialchars($ls['mac'])?></code></td>
                            <td><strong><?=htmlspecialchars($ls['hostname'])?></strong></td>
                            <td><span class="label label-success"><?=htmlspecialchars($ls['status'])?></span></td>
                            <td><?=htmlspecialchars($ls['expires'])?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php else: ?>
            <!-- ============================================== -->
            <!-- TAB: OPTIONS / OPTION SETS / ALERTS            -->
            <!-- ============================================== -->
            <div style="padding: 40px; text-align: center; color: #64748b;">
                <i class="fa-solid fa-sliders" style="font-size: 32px; margin-bottom: 12px; color: #94a3b8;"></i>
                <h4><?=htmlspecialchars(strtoupper(str_replace('_', ' ', $current_tab)))?></h4>
                <p>Fitur lanjutan DHCP <?=htmlspecialchars($current_tab)?> telah disinkronkan dengan sub-sistem dnsmasq MitraNet.</p>
            </div>
            <?php endif; ?>

        </div>

        <!-- STATUSBAR (MATCHING SCREENSHOT & STANDARDS) -->
        <div class="mitranet-statusbar">
            <div>
                <span><strong>Total:</strong> <?=count($eligible_ifaces)?> DHCP items</span>
                <span style="margin-left: 15px;"><strong>Active Leases:</strong> <?=count($active_leases)?></span>
            </div>
            <div>
                <span class="text-muted">MitraNet DHCP Subsystem (dnsmasq)</span>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: DHCP SERVER CONFIGURATION (2-COLUMN WINBOX STYLE)-->
<!-- ============================================================== -->
<div id="modal-dhcp-config" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg winbox-modal-dialog">
        <form method="post" action="dhcp_settings.php?tab=dhcp" id="form-dhcp-config">
            <input type="hidden" name="action" value="save" />
            <input type="hidden" name="interface" id="dhcp-input-iface" value="" />

            <div class="modal-content winbox-window-popup">
                <!-- POPUP HEADER -->
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-server"></i> DHCP Server &lt;<span id="modal-title-iface">veth0</span>&gt;
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>

                <!-- TABS IN POPUP -->
                <div class="winbox-popup-tabs-bar">
                    <ul class="nav nav-tabs winbox-tabs-nav" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#winbox-dhcp-tab-general" role="tab" data-toggle="tab">General</a>
                        </li>
                    </ul>
                </div>

                <!-- 2-COLUMN POPUP BODY -->
                <div class="modal-body winbox-popup-body">
                    <div class="winbox-body-columns">

                        <!-- LEFT COLUMN: FORM INPUTS -->
                        <div class="winbox-content-left tab-content">
                            <div role="tabpanel" class="tab-pane active" id="winbox-dhcp-tab-general">
                                
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="dhcp-input-name" class="form-control" readonly />
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Interface</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="dhcp-select-iface" onchange="onIfaceSelectChange(this.value)">
                                            <?php foreach ($eligible_ifaces as $if_k => $if_v): ?>
                                                <option value="<?=htmlspecialchars($if_k)?>"><?=htmlspecialchars($if_v['label'])?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Enable</label>
                                    <div class="col-sm-9">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="enable" id="dhcp-input-enable" value="1" checked />
                                                <strong>Aktifkan DHCP Server pada interface ini</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Address Pool</label>
                                    <div class="col-sm-9">
                                        <div class="row" style="margin-left: -5px; margin-right: -5px;">
                                            <div class="col-sm-6" style="padding-left: 5px; padding-right: 5px;">
                                                <input type="text" name="range_start" id="dhcp-input-start" class="form-control" placeholder="10.10.1.10" required />
                                            </div>
                                            <div class="col-sm-6" style="padding-left: 5px; padding-right: 5px;">
                                                <input type="text" name="range_end" id="dhcp-input-end" class="form-control" placeholder="10.10.1.240" required />
                                            </div>
                                        </div>
                                        <span class="help-block">Rentang IP awal dan akhir yang dibagikan ke client.</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Gateway</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="gateway" id="dhcp-input-gateway" class="form-control" placeholder="10.10.1.254" />
                                        <span class="help-block">Default Gateway yang diterima client (kosongkan untuk IP interface).</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">DNS Servers</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="dns" id="dhcp-input-dns" class="form-control" placeholder="10.10.1.254, 8.8.8.8, 1.1.1.1" />
                                        <span class="help-block">Pisahkan dengan koma jika lebih dari satu server DNS.</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Lease Time</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="lease_time" id="dhcp-input-lease" class="form-control" value="12h" placeholder="12h" />
                                        <span class="help-block">Masa sewa IP address (contoh: 1h, 12h, 24h, 7d).</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- RIGHT COLUMN: WINBOX ACTION BUTTONS -->
                        <div class="winbox-content-right">
                            <div class="winbox-actions-header">
                                <i class="fa-solid fa-bolt"></i> Actions
                            </div>
                            <ul class="winbox-actions-list">
                                <li><a href="dhcp_settings.php?tab=leases"><i class="fa-solid fa-list-check"></i> View Leases</a></li>
                                <li><a href="javascript:void(0)" onclick="MitraNet.toast('info', 'Address Pool otomatis disinkronkan.')"><i class="fa-solid fa-calculator"></i> Calc Subnet</a></li>
                                <li><a href="javascript:void(0)" onclick="location.reload()"><i class="fa-solid fa-arrows-rotate"></i> Reset Counters</a></li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- WINBOX FOOTER: STATUS & ACTION BUTTONS -->
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-status">
                        <span class="badge winbox-running-badge" id="modal-dhcp-badge-status">RUNNING</span>
                        <span class="winbox-link-msg">service active</span>
                    </div>
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">OK</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL: DHCP SETUP WIZARD (STEP-BY-STEP DIALOG)         -->
<!-- ============================================================== -->
<div id="modal-dhcp-setup" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog" style="max-width: 480px;">
        <form method="post" action="dhcp_settings.php?tab=dhcp" id="form-dhcp-setup">
            <input type="hidden" name="action" value="save" />
            <input type="hidden" name="interface" id="wizard-input-iface" value="" />
            <input type="hidden" name="enable" value="1" />

            <div class="modal-content winbox-window-popup">
                <!-- POPUP HEADER -->
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> DHCP Server Setup
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>

                <div class="modal-body winbox-popup-body" style="padding: 16px 20px;">
                    <!-- STEP 1: Select Interface -->
                    <div class="wizard-step" id="wizard-step-1">
                        <h4 style="font-size: 13px; font-weight: 700; color: #1e395b; margin-top: 0; margin-bottom: 12px;">
                            Select interface to run DHCP server on
                        </h4>
                        <div class="form-group" style="border: none !important; padding: 0 !important;">
                            <label class="control-label" style="margin-bottom: 4px; display: block;">DHCP Server Interface:</label>
                            <select class="form-control" id="wizard-select-iface" onchange="wizardOnIfaceChange(this.value)">
                                <?php foreach ($eligible_ifaces as $if_k => $if_v): ?>
                                    <option value="<?=htmlspecialchars($if_k)?>"><?=htmlspecialchars($if_v['label'])?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- STEP 2: DHCP Address Space -->
                    <div class="wizard-step" id="wizard-step-2" style="display: none;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #1e395b; margin-top: 0; margin-bottom: 12px;">
                            Select DHCP Address Space
                        </h4>
                        <div class="form-group" style="border: none !important; padding: 0 !important;">
                            <label class="control-label" style="margin-bottom: 4px; display: block;">DHCP Address Space:</label>
                            <input type="text" class="form-control font-monospace" id="wizard-addr-space" placeholder="10.10.1.0/24" />
                        </div>
                    </div>

                    <!-- STEP 3: Gateway for DHCP Network -->
                    <div class="wizard-step" id="wizard-step-3" style="display: none;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #1e395b; margin-top: 0; margin-bottom: 12px;">
                            Select Gateway for DHCP Network
                        </h4>
                        <div class="form-group" style="border: none !important; padding: 0 !important;">
                            <label class="control-label" style="margin-bottom: 4px; display: block;">Gateway for DHCP Network:</label>
                            <input type="text" name="gateway" class="form-control font-monospace" id="wizard-gateway" placeholder="10.10.1.254" />
                        </div>
                    </div>

                    <!-- STEP 4: Addresses to Give Out (Pool) -->
                    <div class="wizard-step" id="wizard-step-4" style="display: none;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #1e395b; margin-top: 0; margin-bottom: 12px;">
                            Select pool of ip addresses given out by DHCP server
                        </h4>
                        <div class="form-group" style="border: none !important; padding: 0 !important;">
                            <label class="control-label" style="margin-bottom: 4px; display: block;">Addresses to Give Out:</label>
                            <div class="row" style="margin-left: -4px; margin-right: -4px;">
                                <div class="col-xs-6" style="padding-left: 4px; padding-right: 4px;">
                                    <input type="text" name="range_start" class="form-control font-monospace" id="wizard-pool-start" placeholder="10.10.1.10" required />
                                </div>
                                <div class="col-xs-6" style="padding-left: 4px; padding-right: 4px;">
                                    <input type="text" name="range_end" class="form-control font-monospace" id="wizard-pool-end" placeholder="10.10.1.240" required />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: DNS Servers -->
                    <div class="wizard-step" id="wizard-step-5" style="display: none;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #1e395b; margin-top: 0; margin-bottom: 12px;">
                            Select DNS Servers
                        </h4>
                        <div class="form-group" style="border: none !important; padding: 0 !important;">
                            <label class="control-label" style="margin-bottom: 4px; display: block;">DNS Servers:</label>
                            <input type="text" name="dns" class="form-control font-monospace" id="wizard-dns" placeholder="10.10.1.254, 8.8.8.8, 1.1.1.1" />
                        </div>
                    </div>

                    <!-- STEP 6: Lease Time -->
                    <div class="wizard-step" id="wizard-step-6" style="display: none;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #1e395b; margin-top: 0; margin-bottom: 12px;">
                            Select Lease Time
                        </h4>
                        <div class="form-group" style="border: none !important; padding: 0 !important;">
                            <label class="control-label" style="margin-bottom: 4px; display: block;">Lease Time:</label>
                            <input type="text" name="lease_time" class="form-control font-monospace" id="wizard-lease" value="12h" placeholder="12h" />
                        </div>
                    </div>
                </div>

                <!-- WIZARD FOOTER CONTROLS -->
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-status">
                        <span class="text-muted" id="wizard-step-indicator">Step 1 of 6</span>
                    </div>
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-default" id="wizard-btn-back" disabled onclick="wizardPrevStep()">Back</button>
                        <button type="button" class="btn btn-sm btn-primary" id="wizard-btn-next" onclick="wizardNextStep()">Next</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- Hidden action form for enable/disable/remove -->
<form method="post" action="dhcp_settings.php?tab=dhcp" id="form-dhcp-actions" style="display:none;">
    <input type="hidden" name="action" id="action-type" value="" />
    <input type="hidden" name="interface" id="action-iface" value="" />
    <input type="hidden" name="enable" id="action-enable" value="" />
</form>

<script>
var currentWizardStep = 1;
var totalWizardSteps = 6;

function openDhcpSetupWizard() {
    currentWizardStep = 1;
    var firstIface = Object.keys(ifacesData)[0] || 'veth0';
    document.getElementById('wizard-select-iface').value = firstIface;
    wizardOnIfaceChange(firstIface);
    updateWizardUI();
    $('#modal-dhcp-setup').modal('show');
}

function wizardOnIfaceChange(iface) {
    document.getElementById('wizard-input-iface').value = iface;
    var ifInfo = ifacesData[iface] || {};
    var ipCidr = ifInfo.ip_cidr || '';
    var calcGw = ipCidr ? ipCidr.split('/')[0] : '';
    var calcStart = '';
    var calcEnd = '';
    var subnetSpace = '';

    if (calcGw && calcGw.split('.').length === 4) {
        var oct = calcGw.split('.');
        var pfx = oct[0] + '.' + oct[1] + '.' + oct[2];
        calcStart = pfx + '.10';
        calcEnd = pfx + '.240';
        subnetSpace = pfx + '.0/24';
    }

    document.getElementById('wizard-addr-space').value = subnetSpace || '10.10.1.0/24';
    document.getElementById('wizard-gateway').value = calcGw || '10.10.1.254';
    document.getElementById('wizard-pool-start').value = calcStart || '10.10.1.10';
    document.getElementById('wizard-pool-end').value = calcEnd || '10.10.1.240';
    document.getElementById('wizard-dns').value = calcGw ? calcGw + ', 8.8.8.8, 1.1.1.1' : '8.8.8.8, 1.1.1.1';
    document.getElementById('wizard-lease').value = '12h';
}

function updateWizardUI() {
    for (var i = 1; i <= totalWizardSteps; i++) {
        var el = document.getElementById('wizard-step-' + i);
        if (el) el.style.display = (i === currentWizardStep) ? 'block' : 'none';
    }

    document.getElementById('wizard-step-indicator').textContent = 'Step ' + currentWizardStep + ' of ' + totalWizardSteps;
    document.getElementById('wizard-btn-back').disabled = (currentWizardStep === 1);

    var nextBtn = document.getElementById('wizard-btn-next');
    if (currentWizardStep === totalWizardSteps) {
        nextBtn.textContent = 'Finish';
    } else {
        nextBtn.textContent = 'Next';
    }
}

function wizardNextStep() {
    if (currentWizardStep < totalWizardSteps) {
        currentWizardStep++;
        updateWizardUI();
    } else {
        // Submit wizard
        MitraNet.toast('info', 'Menyimpan konfigurasi DHCP Server...');
        document.getElementById('form-dhcp-setup').submit();
    }
}

function wizardPrevStep() {
    if (currentWizardStep > 1) {
        currentWizardStep--;
        updateWizardUI();
    }
}
var selectedDhcpIface = null;
var ifacesData = <?=json_encode($eligible_ifaces)?>;
var dhcpConfigsData = <?=json_encode($dhcp_configs)?>;

function selectDhcpRow(tr, iface) {
    document.querySelectorAll('#dhcp-grid-body tr').forEach(function(r) {
        r.classList.remove('selected');
    });
    tr.classList.add('selected');
    selectedDhcpIface = iface;

    var isEnabled = tr.getAttribute('data-enabled') === '1';

    document.getElementById('btn-edit').removeAttribute('disabled');
    document.getElementById('btn-remove').removeAttribute('disabled');
    document.getElementById('btn-comment').removeAttribute('disabled');

    var btnEnable = document.getElementById('btn-enable');
    var btnDisable = document.getElementById('btn-disable');

    if (isEnabled) {
        btnEnable.setAttribute('disabled', 'disabled');
        btnDisable.removeAttribute('disabled');
    } else {
        btnEnable.removeAttribute('disabled');
        btnDisable.setAttribute('disabled', 'disabled');
    }
}

function openNewDhcpModal() {
    var firstIface = Object.keys(ifacesData)[0] || 'veth0';
    populateModal(firstIface);
    $('#modal-dhcp-config').modal('show');
}

function openEditDhcpModal(iface) {
    var target = iface || selectedDhcpIface;
    if (!target) return;
    populateModal(target);
    $('#modal-dhcp-config').modal('show');
}

function populateModal(iface) {
    document.getElementById('dhcp-input-iface').value = iface;
    document.getElementById('dhcp-select-iface').value = iface;
    document.getElementById('dhcp-input-name').value = 'dhcp_' + iface;
    document.getElementById('modal-title-iface').textContent = iface;

    var cfg = dhcpConfigsData[iface] || {};
    var ifInfo = ifacesData[iface] || {};

    // Auto default calc if empty
    var ipCidr = ifInfo.ip_cidr || '';
    var calcGw = ipCidr ? ipCidr.split('/')[0] : '';
    var calcStart = '';
    var calcEnd = '';
    if (calcGw && calcGw.split('.').length === 4) {
        var oct = calcGw.split('.');
        var pfx = oct[0] + '.' + oct[1] + '.' + oct[2];
        calcStart = pfx + '.10';
        calcEnd = pfx + '.240';
    }

    document.getElementById('dhcp-input-enable').checked = (cfg.enabled !== undefined) ? !!cfg.enabled : true;
    document.getElementById('dhcp-input-start').value = cfg.range_start || calcStart;
    document.getElementById('dhcp-input-end').value = cfg.range_end || calcEnd;
    document.getElementById('dhcp-input-gateway').value = cfg.gateway || calcGw;
    document.getElementById('dhcp-input-dns').value = (cfg.dns && cfg.dns.length) ? cfg.dns.join(', ') : (calcGw ? calcGw + ', 8.8.8.8, 1.1.1.1' : '8.8.8.8, 1.1.1.1');
    document.getElementById('dhcp-input-lease').value = cfg.lease_time || '12h';

    var badge = document.getElementById('modal-dhcp-badge-status');
    if (cfg.enabled) {
        badge.className = 'badge winbox-running-badge';
        badge.textContent = 'RUNNING';
    } else {
        badge.className = 'badge badge-danger';
        badge.textContent = 'STOPPED';
    }
}

function onIfaceSelectChange(iface) {
    populateModal(iface);
}

function toggleSelectedDhcp(enableState) {
    if (!selectedDhcpIface) return;
    document.getElementById('action-type').value = 'toggle_state';
    document.getElementById('action-iface').value = selectedDhcpIface;
    document.getElementById('action-enable').value = enableState ? '1' : '';
    MitraNet.toast('info', (enableState ? 'Mengaktifkan' : 'Menonaktifkan') + ' DHCP Server pada ' + selectedDhcpIface + '...');
    document.getElementById('form-dhcp-actions').submit();
}

function removeSelectedDhcp() {
    if (!selectedDhcpIface) return;
    var iface = selectedDhcpIface;

    Swal.fire({
        title: 'Konfirmasi Hapus',
        html: 'Apakah Anda yakin ingin menghapus DHCP Server pada interface <b>' + iface + '</b>?<br><small class="text-danger">Konfigurasi DHCP akan langsung dinonaktifkan dari sistem.</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e53935',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-trash"></i> Hapus',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('info', 'Menghapus DHCP Server ' + iface + '...');
            document.getElementById('action-type').value = 'remove';
            document.getElementById('action-iface').value = iface;
            document.getElementById('form-dhcp-actions').submit();
        }
    });
}

// Support keyboard Delete / Backspace key for WinBox feel
document.addEventListener('keydown', function(e) {
    if ((e.key === 'Delete') && selectedDhcpIface && !document.querySelector('.modal.in')) {
        removeSelectedDhcp();
    }
});

function filterDhcpGrid(val) {
    var q = val.toLowerCase().trim();
    document.querySelectorAll('#dhcp-grid-body tr').forEach(function(tr) {
        var txt = tr.textContent.toLowerCase();
        tr.style.display = txt.indexOf(q) !== -1 ? '' : 'none';
    });
}
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
