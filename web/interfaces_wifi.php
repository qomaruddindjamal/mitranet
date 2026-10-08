<?php
/*
 * interfaces_wifi.php - MitraNet Wireless / WiFi Management
 * Designed with modern RouterOS / WinBox / pfSense Tabbed Interface
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Interfaces"), gettext("Wireless"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$all_ifaces = MitraNetApi::getInterfaces();

// Filter for genuine wireless interfaces:
// 1. type == 'wlan'
// 2. or name starts with wlan, wlp, wls, ath, ra, wifi
// 3. or sysfs wireless directory exists
$wifi_interfaces = array_filter($all_ifaces, function($i) {
    $name = strtolower($i['name'] ?? '');
    $type = strtolower($i['type'] ?? '');
    if ($type === 'wlan' || in_array($type, ['wireless', 'ieee80211'])) {
        return true;
    }
    if (preg_match('/^(wlan|wlp|wls|ath|ra|wifi)/i', $name)) {
        return true;
    }
    if (@is_dir("/sys/class/net/{$name}/wireless") || @is_dir("/sys/class/net/{$name}/phy80211")) {
        return true;
    }
    return false;
});

// Re-index array
$wifi_interfaces = array_values($wifi_interfaces);
$has_wifi = !empty($wifi_interfaces);

$current_tab = $_GET['tab'] ?? 'wifi';
?>

<style>
/* WinBox / RouterOS exact tab & toolbar styling */
.winbox-window {
    background: #d8e5f2;
    border: 1px solid #7ba0cd;
    border-radius: 4px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    overflow: hidden;
}

/* Header bar with WiFi title and Tabs */
.winbox-header {
    background: #c3d9ef;
    background: linear-gradient(to bottom, #dbe8f5 0%, #c4dbf0 100%);
    border-bottom: 1px solid #9cb8d9;
    padding: 4px 6px 0 6px;
    display: flex;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 2px;
}

.winbox-title-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eaf2fa;
    border: 1px solid #7ea3cf;
    border-bottom: none;
    padding: 5px 10px;
    font-weight: 700;
    color: #1e395b;
    font-size: 12px;
    border-top-left-radius: 3px;
    border-top-right-radius: 3px;
    margin-right: 6px;
}
.winbox-title-badge i {
    color: #0275d8;
}

/* Winbox Tabs */
.winbox-tabs {
    display: flex;
    flex-wrap: wrap;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 1px;
}

.winbox-tabs li a {
    display: inline-block;
    padding: 4px 9px;
    font-size: 11px;
    color: #2c496e;
    text-decoration: none;
    border: 1px solid transparent;
    border-bottom: none;
    border-top-left-radius: 3px;
    border-top-right-radius: 3px;
    white-space: nowrap;
    transition: background 0.15s;
}

.winbox-tabs li a:hover {
    background: #e4edf7;
    color: #0b315b;
}

.winbox-tabs li.active a {
    background: #ffffff;
    border-color: #8faecf;
    border-bottom: 1px solid #ffffff;
    margin-bottom: -1px;
    font-weight: 700;
    color: #0c335e;
    box-shadow: 0 -1px 2px rgba(0,0,0,0.04);
}

/* Action Toolbar (New, Enable, Disable, Remove, Find, Filter) */
.winbox-toolbar {
    background: #eef4f9;
    background: linear-gradient(to bottom, #f6f9fc 0%, #e5eef6 100%);
    border-top: 1px solid #ffffff;
    border-bottom: 1px solid #abc1da;
    padding: 4px 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
}

.winbox-toolbar-left, .winbox-toolbar-right {
    display: flex;
    align-items: center;
    gap: 4px;
}

.winbox-btn {
    background: #f7fafc;
    border: 1px solid #9cb5cf;
    border-radius: 3px;
    padding: 2px 8px;
    font-size: 11px;
    color: #233e5c;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    box-shadow: 0 1px 1px rgba(0,0,0,0.05);
}
.winbox-btn:hover:not(:disabled) {
    background: #ffffff;
    border-color: #648fb8;
    color: #002244;
}
.winbox-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    background: #f0f3f6;
    border-color: #c5d4e2;
    color: #8c9ba9;
}

.winbox-btn-active {
    background: #d4e5f7;
    border-color: #4b84bf;
    font-weight: 600;
}

/* Data Grid Table */
.winbox-grid-container {
    background: #ffffff;
    overflow-x: auto;
    min-height: 240px;
}

.winbox-grid {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    color: #212529;
}

.winbox-grid th {
    background: #e2ecf5;
    background: linear-gradient(to bottom, #e9f2fa 0%, #d8e5f2 100%);
    border-right: 1px solid #b7cde3;
    border-bottom: 1px solid #a6bed7;
    padding: 4px 6px;
    font-weight: 600;
    color: #1e3c5f;
    white-space: nowrap;
    text-align: left;
    user-select: none;
}
.winbox-grid th.sortable:hover {
    background: #d3e4f4;
    cursor: pointer;
}
.winbox-grid th:last-child {
    border-right: none;
}

.winbox-grid td {
    border-right: 1px solid #e1eaf2;
    border-bottom: 1px solid #e8eff6;
    padding: 4px 6px;
    white-space: nowrap;
}
.winbox-grid td:last-child {
    border-right: none;
}

.winbox-grid tbody tr:hover {
    background-color: #edf4fb;
}
.winbox-grid tbody tr.selected {
    background-color: #cde2f8 !important;
}

.winbox-empty-row {
    padding: 50px 20px;
    text-align: center;
    color: #64748b;
    background: #fafcfe;
}
.winbox-empty-row i {
    font-size: 32px;
    color: #94a3b8;
    margin-bottom: 10px;
    display: block;
}

/* Status Bar */
.winbox-statusbar {
    background: #e3edf6;
    border-top: 1px solid #abc1da;
    padding: 3px 8px;
    font-size: 11px;
    color: #335174;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>

<div class="container-fluid" style="padding-top: 10px;">

    <!-- WINBOX / ROUTEROS EXACT INTERFACE CONTAINER -->
    <div class="winbox-window">

        <!-- HEADER: TITLE + EXACT TABS -->
        <div class="winbox-header">
            <div class="winbox-title-badge">
                <i class="fa-solid fa-wifi"></i> WiFi
                <i class="fa-solid fa-caret-down" style="font-size: 9px; color: #555;"></i>
            </div>
            <ul class="winbox-tabs">
                <li class="<?=($current_tab === 'wifi') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=wifi">WiFi</a>
                </li>
                <li class="<?=($current_tab === 'network') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=network">Network</a>
                </li>
                <li class="<?=($current_tab === 'configuration') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=configuration">Configuration</a>
                </li>
                <li class="<?=($current_tab === 'channel') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=channel">Channel</a>
                </li>
                <li class="<?=($current_tab === 'security') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=security">Security</a>
                </li>
                <li class="<?=($current_tab === 'aaa') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=aaa">AAA</a>
                </li>
                <li class="<?=($current_tab === 'datapath') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=datapath">Datapath</a>
                </li>
                <li class="<?=($current_tab === 'interworking') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=interworking">Interworking</a>
                </li>
                <li class="<?=($current_tab === 'steering') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=steering">Steering</a>
                </li>
                <li class="<?=($current_tab === 'registration') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=registration">Registration</a>
                </li>
                <li class="<?=($current_tab === 'access_list') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=access_list">Access List</a>
                </li>
                <li class="<?=($current_tab === 'provisioning') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=provisioning">Provisioning</a>
                </li>
                <li class="<?=($current_tab === 'radios') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=radios">Radios</a>
                </li>
                <li class="<?=($current_tab === 'remote_cap') ? 'active' : ''?>">
                    <a href="interfaces_wifi.php?tab=remote_cap">Remote CAP</a>
                </li>
            </ul>
        </div>

        <!-- TOOLBAR: NEW, ENABLE, DISABLE, REMOVE, COMMENT, FIND, FILTER -->
        <div class="winbox-toolbar">
            <div class="winbox-toolbar-left">
                <button type="button" class="winbox-btn" onclick="openNewModal()" title="Add New Interface">
                    <i class="fa-solid fa-folder-plus text-primary"></i> <strong>New</strong>
                </button>
                <button type="button" class="winbox-btn" id="btn-enable" disabled title="Enable Selected">
                    <i class="fa-solid fa-play text-muted"></i> Enable
                </button>
                <button type="button" class="winbox-btn" id="btn-disable" disabled title="Disable Selected">
                    <i class="fa-solid fa-pause text-muted"></i> Disable
                </button>
                <button type="button" class="winbox-btn" id="btn-remove" disabled title="Remove Selected">
                    <i class="fa-solid fa-xmark text-muted"></i> Remove
                </button>
                <button type="button" class="winbox-btn" id="btn-comment" disabled title="Set Comment">
                    <i class="fa-regular fa-comment text-muted"></i> Comment
                </button>
            </div>
            <div class="winbox-toolbar-right">
                <div style="position: relative; display: inline-flex; align-items: center;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 6px; font-size: 10px; color: #738a9c;"></i>
                    <input type="text" id="grid-search" placeholder="Find" onkeyup="filterGrid(this.value)" 
                           style="padding: 2px 5px 2px 22px; font-size: 11px; height: 22px; border: 1px solid #9cb5cf; border-radius: 3px; width: 140px;">
                </div>
                <button type="button" class="winbox-btn" onclick="toggleFilter()" title="Advanced Filter">
                    <i class="fa-solid fa-filter text-muted"></i> Filter
                </button>
                <button type="button" class="winbox-btn" onclick="location.reload()" title="Refresh">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>

        <!-- DATA GRID TABLE (EXACT MATCH TO SCREENSHOT COLUMNS) -->
        <div class="winbox-grid-container">
            <table class="winbox-grid" id="wifi-grid-table">
                <thead>
                    <tr>
                        <th style="width: 24px; text-align: center;"><i class="fa-regular fa-flag"></i></th>
                        <th class="sortable" style="min-width: 140px;">Name <i class="fa-solid fa-caret-up" style="font-size: 9px; color: #555;"></i></th>
                        <th class="sortable" style="min-width: 90px;">Type</th>
                        <th class="sortable" style="min-width: 80px;">Actual MTU</th>
                        <th class="sortable" style="min-width: 70px;">L2 MTU</th>
                        <th class="sortable" style="min-width: 60px;">ARP</th>
                        <th class="sortable" style="min-width: 60px;">CAP</th>
                        <th class="sortable" style="min-width: 90px;">Mode</th>
                        <th class="sortable" style="min-width: 120px;">SSID</th>
                        <th class="sortable" style="min-width: 90px;">Band</th>
                        <th class="sortable" style="min-width: 90px;">Channel ...</th>
                        <th class="sortable" style="min-width: 90px;">Frequency</th>
                        <th class="sortable" style="min-width: 100px;">Passphrase</th>
                        <th class="sortable" style="min-width: 110px;">Multi Passph...</th>
                        <th class="sortable" style="min-width: 110px;">Current Chan...</th>
                        <th class="sortable" style="min-width: 70px;">Tx</th>
                        <th style="width: 20px; text-align: center;"><i class="fa-solid fa-bars"></i></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$has_wifi): ?>
                    <!-- NO GENUINE WIFI ADAPTER DETECTED - CLEAN ZERO INTERFACE STATE -->
                    <tr>
                        <td colspan="17" class="winbox-empty-row">
                            <i class="fa-solid fa-wifi-slash"></i>
                            <div style="font-weight: 600; font-size: 13px; color: #475569; margin-bottom: 4px;">
                                Tidak Ada Antarmuka Wireless (Wi-Fi) yang Terdeteksi
                            </div>
                            <div style="font-size: 11px; color: #64748b; max-width: 580px; margin: 0 auto 12px auto;">
                                Saat ini tidak ada perangkat atau adapter Wi-Fi (PCIe/USB) yang terpasang pada appliance MitraNet.
                                Hubungkan adapter nirkabel yang didukung untuk menambahkan antarmuka secara otomatis (contoh: <code>wlan1-2.4</code>, <code>wlan2-5.8</code>).
                            </div>
                            <div style="display: inline-flex; gap: 8px;">
                                <button type="button" class="winbox-btn" onclick="location.reload();">
                                    <i class="fa-solid fa-rotate"></i> Pindai Ulang Perangkat
                                </button>
                                <a href="interfaces_assign.php" class="winbox-btn" style="text-decoration: none;">
                                    <i class="fa-solid fa-network-wired"></i> Buka Interface Assignments
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <!-- GENUINE WIFI INTERFACES POPULATED DYNAMICALLY -->
                    <?php foreach ($wifi_interfaces as $idx => $w): 
                        $w_name = $w['altname'] ?? $w['name'];
                        $is_up = !empty($w['is_up']);
                        $band = "2.4GHz / 5GHz";
                        if (strpos($w_name, '2.4') !== false) $band = "2.4GHz-b/g/n/ax";
                        elseif (strpos($w_name, '5.8') !== false || strpos($w_name, '5') !== false) $band = "5GHz-a/n/ac/ax";
                    ?>
                    <tr onclick="selectRow(this, '<?=htmlspecialchars($w['name'])?>')">
                        <td style="text-align: center;">
                            <?php if ($is_up): ?>
                                <i class="fa-solid fa-check text-success" title="Running"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-minus text-muted" title="Disabled"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?=htmlspecialchars(strtoupper($w_name))?></strong>
                            <span class="text-muted" style="font-size: 10px;">(<?=htmlspecialchars($w['name'])?>)</span>
                        </td>
                        <td><span class="badge" style="background: #2563eb; font-size: 10px; font-weight: normal;"><?=htmlspecialchars($w['type'] ?? 'wlan')?></span></td>
                        <td><?=htmlspecialchars($w['mtu'] ?? 1500)?></td>
                        <td>1500</td>
                        <td>enabled</td>
                        <td>no</td>
                        <td>ap-bridge</td>
                        <td><em>MitraNet-<?=htmlspecialchars(substr($w['mac_address'] ?? 'WLAN', -5))?></em></td>
                        <td><?=htmlspecialchars($band)?></td>
                        <td>auto</td>
                        <td>auto</td>
                        <td>••••••••</td>
                        <td>no</td>
                        <td><?=htmlspecialchars($is_up ? '2412/20/gn' : 'disabled')?></td>
                        <td>100</td>
                        <td style="text-align: center;"><i class="fa-solid fa-ellipsis-vertical text-muted"></i></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- WINBOX STATUS BAR -->
        <div class="winbox-statusbar">
            <div>
                <span><strong>Total:</strong> <?=count($wifi_interfaces)?> items</span>
                <?php if ($has_wifi): ?>
                    <span style="margin-left: 12px; color: #16a34a;"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> Wireless Hardware Ready</span>
                <?php else: ?>
                    <span style="margin-left: 12px; color: #64748b;"><i class="fa-solid fa-circle" style="font-size: 8px;"></i> No Wireless Interfaces Installed</span>
                <?php endif; ?>
            </div>
            <div>
                <span class="text-muted">MitraNet Wireless Management Subsystem</span>
            </div>
        </div>

    </div>

</div>

<!-- MODAL: ADD / NEW INTERFACE CONFIGURATION -->
<div id="modal-new-wifi" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius: 4px;">
            <div class="modal-header" style="background: #e6eef6; border-bottom: 1px solid #b7cde3; padding: 10px 15px;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="font-size: 14px; font-weight: 700; color: #1e3c5f;">
                    <i class="fa-solid fa-wifi text-primary"></i> New Wireless Interface
                </h4>
            </div>
            <div class="modal-body" style="font-size: 12px;">
                <?php if (!$has_wifi): ?>
                    <div class="alert alert-warning" style="margin-bottom: 0;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <strong>Tidak Ada Radio Fisik:</strong> Tidak ada adapter Wi-Fi fisik yang dapat dikonfigurasi saat ini. Silakan sambungkan USB Wi-Fi adapter atau kartu PCIe nirkabel ke mesin ini.
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label>Hardware Interface / Master Radio:</label>
                        <select class="form-control input-sm">
                            <?php foreach ($wifi_interfaces as $w): ?>
                                <option value="<?=htmlspecialchars($w['name'])?>"><?=htmlspecialchars(strtoupper($w['altname'] ?? $w['name']))?> (<?=htmlspecialchars($w['name'])?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mode:</label>
                        <select class="form-control input-sm">
                            <option value="ap">Access Point (AP Mode)</option>
                            <option value="station">Station (Client / Connect to AP)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>SSID Name:</label>
                        <input type="text" class="form-control input-sm" placeholder="MitraNet-WiFi">
                    </div>
                    <div class="form-group">
                        <label>WPA2/WPA3 Passphrase:</label>
                        <input type="password" class="form-control input-sm" placeholder="Minimal 8 karakter">
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer" style="background: #f7fafc; padding: 8px 15px;">
                <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                <?php if ($has_wifi): ?>
                    <button type="button" class="btn btn-sm btn-primary">Apply &amp; Create</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function openNewModal() {
    $('#modal-new-wifi').modal('show');
}

function selectRow(tr, ifname) {
    $('#wifi-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    $('#btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', false);
}

function filterGrid(val) {
    val = (val || '').toLowerCase();
    $('#wifi-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(val) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

function toggleFilter() {
    var inp = $('#grid-search');
    inp.focus();
}
</script>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
