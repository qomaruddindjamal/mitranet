<?php
/*
 * interfaces_wifi.php - MitraNet Wireless / WiFi Management
 * Designed with modern MitraNet Enterprise Tabbed Interface
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Interfaces"), gettext("Wireless"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/../includes/head.inc');

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

<div class="container-fluid mitranet-page-container">

    <!-- MITRANET APPLIANCE WIRELESS CONTAINER -->
    <div class="mitranet-window">

        <!-- HEADER: TITLE + EXACT TABS -->
        <div class="mitranet-header">
            <div class="mitranet-title-badge">
                <i class="fa-solid fa-wifi"></i> WiFi
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="mitranet-tabs">
                <li class="<?=($current_tab === 'wifi') ? 'active' : ''?>">
                    <a href="wifi.php?tab=wifi">WiFi</a>
                </li>
                <li class="<?=($current_tab === 'network') ? 'active' : ''?>">
                    <a href="wifi.php?tab=network">Network</a>
                </li>
                <li class="<?=($current_tab === 'configuration') ? 'active' : ''?>">
                    <a href="wifi.php?tab=configuration">Configuration</a>
                </li>
                <li class="<?=($current_tab === 'channel') ? 'active' : ''?>">
                    <a href="wifi.php?tab=channel">Channel</a>
                </li>
                <li class="<?=($current_tab === 'security') ? 'active' : ''?>">
                    <a href="wifi.php?tab=security">Security</a>
                </li>
                <li class="<?=($current_tab === 'aaa') ? 'active' : ''?>">
                    <a href="wifi.php?tab=aaa">AAA</a>
                </li>
                <li class="<?=($current_tab === 'datapath') ? 'active' : ''?>">
                    <a href="wifi.php?tab=datapath">Datapath</a>
                </li>
                <li class="<?=($current_tab === 'interworking') ? 'active' : ''?>">
                    <a href="wifi.php?tab=interworking">Interworking</a>
                </li>
                <li class="<?=($current_tab === 'steering') ? 'active' : ''?>">
                    <a href="wifi.php?tab=steering">Steering</a>
                </li>
                <li class="<?=($current_tab === 'registration') ? 'active' : ''?>">
                    <a href="wifi.php?tab=registration">Registration</a>
                </li>
                <li class="<?=($current_tab === 'access_list') ? 'active' : ''?>">
                    <a href="wifi.php?tab=access_list">Access List</a>
                </li>
                <li class="<?=($current_tab === 'provisioning') ? 'active' : ''?>">
                    <a href="wifi.php?tab=provisioning">Provisioning</a>
                </li>
                <li class="<?=($current_tab === 'radios') ? 'active' : ''?>">
                    <a href="wifi.php?tab=radios">Radios</a>
                </li>
                <li class="<?=($current_tab === 'remote_cap') ? 'active' : ''?>">
                    <a href="wifi.php?tab=remote_cap">Remote CAP</a>
                </li>
            </ul>
        </div>

        <!-- TOOLBAR: NEW, ENABLE, DISABLE, REMOVE, COMMENT, FIND, FILTER -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left">
                <button type="button" class="mitranet-btn" onclick="openNewModal()" title="Add New Interface">
                    <i class="fa-solid fa-folder-plus text-primary"></i> <strong>New</strong>
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
                    <input type="text" id="grid-search" placeholder="Find" onkeyup="filterGrid(this.value)">
                </div>
                <button type="button" class="mitranet-btn" onclick="toggleFilter()" title="Advanced Filter">
                    <i class="fa-solid fa-filter text-muted"></i> Filter
                </button>
                <button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>

        <!-- DATA GRID TABLE (EXACT MATCH TO SCREENSHOT COLUMNS) -->
        <div class="mitranet-grid-container">
            <table class="mitranet-grid" id="wifi-grid-table">
                <thead>
                    <tr>
                        <th class="text-center col-flag"><i class="fa-regular fa-flag"></i></th>
                        <th class="sortable col-name">Name <i class="fa-solid fa-caret-up"></i></th>
                        <th class="sortable col-type">Type</th>
                        <th class="sortable col-mtu">Actual MTU</th>
                        <th class="sortable col-l2mtu">L2 MTU</th>
                        <th class="sortable col-arp">ARP</th>
                        <th class="sortable col-cap">CAP</th>
                        <th class="sortable col-mode">Mode</th>
                        <th class="sortable col-ssid">SSID</th>
                        <th class="sortable col-band">Band</th>
                        <th class="sortable col-ch">Channel ...</th>
                        <th class="sortable col-freq">Frequency</th>
                        <th class="sortable col-pass">Passphrase</th>
                        <th class="sortable col-mpass">Multi Passph...</th>
                        <th class="sortable col-cchan">Current Chan...</th>
                        <th class="sortable col-tx">Tx</th>
                        <th class="text-center col-menu"><i class="fa-solid fa-bars"></i></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($has_wifi): ?>
                    <!-- GENUINE WIFI INTERFACES POPULATED DYNAMICALLY -->
                    <?php foreach ($wifi_interfaces as $idx => $w): 
                        $w_name = $w['altname'] ?? $w['name'];
                        $is_up = !empty($w['is_up']);
                        $band = "2.4GHz / 5GHz";
                        if (strpos($w_name, '2.4') !== false) $band = "2.4GHz-b/g/n/ax";
                        elseif (strpos($w_name, '5.8') !== false || strpos($w_name, '5') !== false) $band = "5GHz-a/n/ac/ax";
                    ?>
                    <tr onclick="selectRow(this, '<?=htmlspecialchars($w['name'])?>')">
                        <td class="text-center">
                            <?php if ($is_up): ?>
                                <i class="fa-solid fa-check text-success" title="Running"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-minus text-muted" title="Disabled"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?=htmlspecialchars(strtoupper($w_name))?></strong>
                            <span class="text-muted text-subname">(<?=htmlspecialchars($w['name'])?>)</span>
                        </td>
                        <td><span class="badge badge-wlan"><?=htmlspecialchars($w['type'] ?? 'wlan')?></span></td>
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
                        <td class="text-center"><i class="fa-solid fa-ellipsis-vertical text-muted"></i></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- MITRANET STATUS BAR -->
        <div class="mitranet-statusbar">
            <div>
                <span><strong>Total:</strong> <?=count($wifi_interfaces)?> items</span>
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
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa-solid fa-wifi text-primary"></i> New Wireless Interface
                </h4>
            </div>
            <div class="modal-body">
                <?php if (!$has_wifi): ?>
                    <div class="alert alert-warning mb-0">
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
            <div class="modal-footer">
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

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
