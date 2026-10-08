<?php
/*
 * interfaces_wifi.php - MitraNet Wireless Interfaces
 * Adapted from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Interfaces"), gettext("Wireless"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
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

$current_tab = $_GET['tab'] ?? 'overview';

// Navigation pills
$tab_array = array();
$tab_array[] = array(gettext("Overview & Status"), ($current_tab === 'overview'), "interfaces_wifi.php?tab=overview");
$tab_array[] = array(gettext("Scan & Connect (Client)"), ($current_tab === 'scan'), "interfaces_wifi.php?tab=scan");
$tab_array[] = array(gettext("Access Point (AP Mode)"), ($current_tab === 'ap'), "interfaces_wifi.php?tab=ap");
$tab_array[] = array(gettext("Hardware & Radios"), ($current_tab === 'detect'), "interfaces_wifi.php?tab=detect");
display_top_tabs($tab_array);
?>

<style>
.wifi-card {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    background: #fff;
    border: 1px solid #e5e9ec;
}
.wifi-card .panel-heading {
    border-top-left-radius: 7px;
    border-top-right-radius: 7px;
    font-weight: 600;
    padding: 12px 18px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e9ec;
}
.wifi-signal-bar {
    height: 8px;
    border-radius: 4px;
    background: #e9ecef;
    overflow: hidden;
    margin-top: 6px;
}
.wifi-signal-fill {
    height: 100%;
    transition: width 0.3s ease;
}
.badge-wifi-connected {
    background-color: #28a745;
    color: #fff;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 12px;
}
.badge-wifi-disconnected {
    background-color: #6c757d;
    color: #fff;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 12px;
}
.badge-wifi-ap {
    background-color: #007bff;
    color: #fff;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 12px;
}
.iface-selector-bar {
    background: #edf2f7;
    border: 1px solid #e2e8f0;
    padding: 12px 18px;
    border-radius: 8px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.empty-wifi-state {
    padding: 35px 25px;
    text-align: center;
    background: #fdfdfe;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    margin-bottom: 20px;
}
.empty-wifi-state i.main-icon {
    font-size: 48px;
    color: #94a3b8;
    margin-bottom: 15px;
}
</style>

<div class="container-fluid" style="padding-top: 15px;">

<?php if (!$has_wifi): ?>
    <!-- CLEAN EMPTY STATE: NO WIRELESS HARDWARE PRESENT -->
    <div class="empty-wifi-state">
        <i class="fa-solid fa-wifi-slash main-icon"></i>
        <h3 style="font-weight: 700; color: #334155; margin-top: 0; margin-bottom: 8px;">
            Tidak Ada Perangkat Wireless (Wi-Fi) yang Terdeteksi
        </h3>
        <p style="color: #64748b; max-width: 650px; margin: 0 auto 18px auto; font-size: 14px; line-height: 1.6;">
            Sistem MitraNet Router tidak mendeteksi radio nirkabel fisik (PCIe Wi-Fi card atau USB Wi-Fi adapter) pada appliance ini. Semua antarmuka fisik yang tersedia saat ini adalah Ethernet/SFP berkabel.
        </p>
        <div style="display: inline-flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
            <button class="btn btn-default" onclick="location.reload();">
                <i class="fa-solid fa-arrows-rotate"></i> Pindai Ulang Perangkat
            </button>
            <a href="interfaces_assign.php" class="btn btn-primary">
                <i class="fa-solid fa-network-wired"></i> Buka Interface Assignments
            </a>
        </div>
    </div>

    <!-- HARDWARE DIAGNOSTICS & SYSTEM INFO -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default wifi-card">
                <div class="panel-heading">
                    <i class="fa-solid fa-circle-info text-info" style="margin-right: 8px;"></i>
                    Informasi Radio & Subsistem Kernel Wireless
                </div>
                <div class="panel-body">
                    <div class="alert alert-info" style="margin-bottom: 15px;">
                        <i class="fa-solid fa-circle-check"></i>
                        <strong>Subsistem Kernel Siap:</strong> Driver <code>cfg80211</code>, <code>mac80211</code>, <code>hostapd</code>, dan utilitas <code>iw</code> sudah aktif di dalam kernel. Cukup hubungkan modul Wi-Fi USB atau kartu PCIe Wi-Fi yang kompatibel dengan Linux (contoh: chipset Atheros, MediaTek, Realtek, atau Intel) untuk mengaktifkan fungsi Access Point (AP Mode) atau Client Station secara otomatis.
                    </div>

                    <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                        <tbody>
                            <tr>
                                <th style="width: 30%;">Status Perangkat Wireless</th>
                                <td><span class="label label-default">0 Perangkat Ditemukan</span></td>
                            </tr>
                            <tr>
                                <th>Driver Kernel 802.11</th>
                                <td><span class="label label-success">cfg80211 Aktif</span></td>
                            </tr>
                            <tr>
                                <th>Layanan AP Daemon</th>
                                <td><span class="label label-info">hostapd Siap</span></td>
                            </tr>
                            <tr>
                                <th>Format Penamaan Port Otomatis</th>
                                <td>
                                    <code>wlan1-2.4</code> (Frekuensi 2.4 GHz) &amp; 
                                    <code>wlan2-5.8</code> (Frekuensi 5.8 GHz / 5 GHz)
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- WIRELESS HARDWARE IS DETECTED -->
    <div class="iface-selector-bar">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <label style="margin: 0; font-weight: 600; font-size: 14px;">
                <i class="fa-solid fa-wifi text-primary"></i> Antarmuka Wireless Aktif:
            </label>
            <select id="select-active-iface" class="form-control" style="width: auto; min-width: 300px; font-weight: 600;" onchange="changeActiveInterface(this.value)">
                <?php foreach ($wifi_interfaces as $w): 
                    $disp = strtoupper($w['altname'] ?? $w['name']) . " (" . htmlspecialchars($w['name']) . ")";
                    if (!empty($w['port_label'])) {
                        $disp .= " - " . htmlspecialchars($w['port_label']);
                    }
                ?>
                    <option value="<?=htmlspecialchars($w['name'])?>"><?=htmlspecialchars($disp)?></option>
                <?php endforeach; ?>
            </select>
            <span id="iface-switch-msg" class="text-success" style="display: none; font-weight: 600;">
                <i class="fa-solid fa-circle-check"></i> Interface updated
            </span>
        </div>
        <div>
            <button class="btn btn-sm btn-info" onclick="location.reload();">
                <i class="fa-solid fa-rotate"></i> Pindai Ulang Perangkat
            </button>
        </div>
    </div>

    <!-- TAB 1: OVERVIEW & STATUS -->
    <div class="row">
        <div class="col-md-7">
            <div class="panel panel-default wifi-card">
                <div class="panel-heading">
                    <i class="fa-solid fa-wifi text-primary" style="margin-right: 8px;"></i>
                    Status Antarmuka Wireless
                    <button class="btn btn-xs btn-default pull-right" onclick="location.reload();">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh
                    </button>
                </div>
                <div class="panel-body">
                    <?php $first_w = $wifi_interfaces[0]; ?>
                    <table class="table table-striped table-hover table-wifi">
                        <tbody>
                            <tr>
                                <th style="width: 35%;">Port / Antarmuka</th>
                                <td>
                                    <strong><?=htmlspecialchars(strtoupper($first_w['altname'] ?? $first_w['name']))?></strong> 
                                    <code>(<?=htmlspecialchars($first_w['name'])?>)</code>
                                </td>
                            </tr>
                            <tr>
                                <th>Label Port</th>
                                <td><span class="label label-info"><?=htmlspecialchars($first_w['port_label'] ?? 'Wireless Port')?></span></td>
                            </tr>
                            <tr>
                                <th>Status Link</th>
                                <td>
                                    <?php if (!empty($first_w['is_up'])): ?>
                                        <span class="label label-success"><i class="fa-solid fa-arrow-up"></i> UP</span>
                                    <?php else: ?>
                                        <span class="label label-danger"><i class="fa-solid fa-arrow-down"></i> DOWN</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>MAC Address</th>
                                <td><code><?=htmlspecialchars($first_w['mac_address'] ?? '--')?></code></td>
                            </tr>
                            <tr>
                                <th>IP Address</th>
                                <td>
                                    <?php 
                                    $w_ips = array_merge($first_w['ipv4_addresses'] ?? [], $first_w['ipv6_addresses'] ?? []);
                                    echo !empty($w_ips) ? htmlspecialchars(implode(', ', $w_ips)) : '<span class="text-muted">None</span>';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>MTU</th>
                                <td><?=htmlspecialchars($first_w['mtu'] ?? 1500)?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="margin-top: 15px;">
                        <a href="interfaces_wifi.php?tab=scan" class="btn btn-primary">
                            <i class="fa-solid fa-satellite-dish"></i> Scan &amp; Connect Networks
                        </a>
                        <a href="interfaces_wifi.php?tab=ap" class="btn btn-info pull-right">
                            <i class="fa-solid fa-tower-broadcast"></i> Setup Access Point (AP)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="panel panel-default wifi-card">
                <div class="panel-heading">
                    <i class="fa-solid fa-microchip text-info" style="margin-right: 8px;"></i>
                    Daftar Radio Wireless Terpasang
                </div>
                <div class="panel-body">
                    <ul class="list-group">
                        <?php foreach ($wifi_interfaces as $idx => $w): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?=htmlspecialchars(strtoupper($w['altname'] ?? $w['name']))?></strong>
                                    <span class="text-muted">(<?=htmlspecialchars($w['name'])?>)</span>
                                </div>
                                <span class="badge" style="background-color: #007bff;"><?=htmlspecialchars($w['port_label'] ?? 'WLAN')?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

</div>

<script>
function changeActiveInterface(iface) {
    $('#iface-switch-msg').fadeIn().delay(1200).fadeOut();
}
</script>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
