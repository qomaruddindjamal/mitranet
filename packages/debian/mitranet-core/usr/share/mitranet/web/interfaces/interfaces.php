<?php
/*
 * interfaces.php - MitraNet Interface Management & Configuration
 * Adapted for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$msg = '';
$err = '';

// Support ?if=<name> or ?name=<name> for configuration edit mode
$target_if = $_GET['if'] ?? $_GET['name'] ?? '';

// Handle POST actions (State change, Save interface, Remove IP, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ifname = trim($_POST['interface'] ?? '');

    if ($action === 'set_state' && !empty($ifname)) {
        $state = strtolower($_POST['state'] ?? 'up');
        $res = MitraNetApi::setInterfaceState($ifname, $state);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $msg = "Interface '{$ifname}' berhasil diatur ke " . strtoupper($state);
        } else {
            $err = $res['data']['error'] ?? 'Gagal mengubah status interface.';
        }
    } elseif ($action === 'save_interface' && !empty($ifname)) {
        $state = !empty($_POST['enable']) ? 'up' : 'down';
        $mtu = intval($_POST['mtu'] ?? 1500);
        $new_ip = trim($_POST['ipaddr'] ?? '');
        $new_subnet = intval($_POST['subnet'] ?? 24);

        // 1. Set State
        MitraNetApi::setInterfaceState($ifname, $state);

        // 2. Set MTU
        if ($mtu >= 576 && $mtu <= 9000) {
            MitraNetApi::setInterfaceMtu($ifname, $mtu);
        }

        // 3. Update IP Address if specified
        if (!empty($new_ip)) {
            $cidr = "{$new_ip}/{$new_subnet}";
            $add_res = MitraNetApi::addInterfaceAddress($ifname, $cidr);
            if (($add_res['status'] ?? 0) === 200) {
                $msg = "Konfigurasi interface '{$ifname}' ({$cidr}) berhasil disimpan dan diterapkan.";
            } else {
                $err_detail = $add_res['data']['error'] ?? '';
                if (strpos($err_detail, 'already assigned') !== false) {
                    $msg = "Konfigurasi interface '{$ifname}' berhasil diperbarui.";
                } else {
                    $msg = "Status & MTU interface '{$ifname}' diperbarui. " . $err_detail;
                }
            }
        }

        // Redirect to main interfaces overview with success notification
        header("Location: interfaces.php?saved=" . urlencode($ifname));
        exit;
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
        $target_if = $ifname;
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
// Filter: exclude tap-* (KVM internal) and all wireless interfaces
$ifaces = array_filter($ifaces_raw, function($i) {
    $name = strtolower($i['name'] ?? '');
    $type = strtolower($i['type'] ?? '');
    if (preg_match('/^tap[-_0-9]/i', $name)) return false;
    if (preg_match('/^(wlan|wlp|wls|ath|ra|wifi)/i', $name) || in_array($type, ['wlan', 'wireless', 'ieee80211'])) return false;
    return true;
});

// Sort: Physical > Bridge > vEthernet > WireGuard > Others > Loopback
usort($ifaces, function($a, $b) {
    $priority = function($i) {
        $name = strtolower($i['name'] ?? '');
        $type = strtolower($i['type'] ?? '');
        if ($name === 'lo') return 99;
        if (preg_match('/^(en|eth|eno|ens|enp)/i', $name)) return 1;
        if (preg_match('/^br[-_]/i', $name) || $type === 'bridge') return 2;
        if (preg_match('/^veth/i', $name)) return 3;
        if (preg_match('/^wg/i', $name)) return 4;
        return 5;
    };
    return $priority($a) <=> $priority($b);
});

/* Helper: format bytes to human-readable */
function fmt_bytes(int $b): string {
    if ($b >= 1073741824) return number_format($b / 1073741824, 2) . ' GB';
    if ($b >= 1048576)    return number_format($b / 1048576, 1)    . ' MB';
    if ($b >= 1024)       return number_format($b / 1024, 1)       . ' KB';
    return $b . ' B';
}

/* Helper: format packet count */
function fmt_pkts(int $p): string {
    if ($p >= 1000000) return number_format($p / 1000000, 1) . 'M';
    if ($p >= 1000)    return number_format($p / 1000, 1)    . 'K';
    return (string)$p;
}

$current_tab = $_GET['tab'] ?? 'interface';
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
					<a href="vlan.php">VLAN</a>
				</li>
				<li class="<?=($current_tab === 'bridge') ? 'active' : ''?>">
					<a href="bridge.php">Bridge</a>
				</li>
				<li class="<?=($current_tab === 'vxlan') ? 'active' : ''?>">
					<a href="interfaces.php?tab=vxlan">VXLAN</a>
				</li>
				<li class="<?=($current_tab === 'vrrp') ? 'active' : ''?>">
					<a href="interfaces.php?tab=vrrp">VRRP</a>
				</li>
				<li class="<?=($current_tab === 'macsec') ? 'active' : ''?>">
					<a href="interfaces.php?tab=macsec">MACsec</a>
				</li>
				<li class="<?=($current_tab === 'macvlan') ? 'active' : ''?>">
					<a href="interfaces.php?tab=macvlan">MACVLAN</a>
				</li>
				<li class="<?=($current_tab === 'bonding') ? 'active' : ''?>">
					<a href="lagg.php">Bonding</a>
				</li>
				<li class="<?=($current_tab === 'vether') ? 'active' : ''?>">
					<a href="vethernet.php">vEther</a>
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

		<!-- DATA GRID TABLE (MATCHING SCREENSHOT COLUMNS) -->
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="iface-grid-table">
				<thead>
					<tr>
						<th class="text-center col-flag"><i class="fa-regular fa-flag"></i></th>
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
						<th class="text-center col-menu"><i class="fa-solid fa-bars"></i></th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($ifaces)): ?>
					<tr>
						<td colspan="14" class="text-center text-muted">
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
					$type       = $i['type'] ?? 'ether';
					if (preg_match('/^br[-_]/i', $ifname) || $type === 'bridge') $type = 'Bridge';
					elseif (preg_match('/^vlan/i', $ifname) || strpos($ifname, '.') !== false) $type = 'VLAN';
					elseif (preg_match('/^wg/i', $ifname)) $type = 'WireGuard';
					elseif (preg_match('/^bond/i', $ifname)) $type = 'Bonding';
					elseif ($ifname === 'lo') $type = 'Loopback';
					else $type = 'Ethernet';
					$mtu = $i['mtu'] ?? 1500;
					?>
					<tr onclick="selectRow(this, '<?=htmlspecialchars($ifname)?>', '<?=htmlspecialchars(addslashes($i['comment'] ?? ''))?>')" ondblclick="location.href='interfaces.php?if=<?=urlencode($ifname)?>'">
						<!-- Flag -->
						<td class="text-center">
							<?php if ($is_up && $oper_state === 'UP'): ?>
								<i class="fa-solid fa-check text-success" title="Running / Link UP"></i>
							<?php elseif ($is_up): ?>
								<i class="fa-solid fa-circle-half-stroke text-warning" title="No Carrier"></i>
							<?php else: ?>
								<i class="fa-solid fa-minus text-muted" title="Disabled"></i>
							<?php endif; ?>
						</td>
						<!-- Name -->
						<td>
							<strong><?=htmlspecialchars(strtoupper($altname))?></strong>
							<span class="text-muted text-subname">(<?=htmlspecialchars($ifname)?>)</span>
						</td>
						<!-- Type -->
						<td><span class="label label-default"><?=htmlspecialchars($type)?></span></td>
						<!-- Actual MTU -->
						<td><?=htmlspecialchars($mtu)?></td>
						<!-- L2 MTU -->
						<td>1500</td>
						<!-- Tx -->
						<td><?=fmt_bytes($tx_bytes)?></td>
						<!-- Rx -->
						<td><?=fmt_bytes($rx_bytes)?></td>
						<!-- Tx Packet (p/s) -->
						<td><?=fmt_pkts($tx_pkts)?></td>
						<!-- Rx Packet (p/s) -->
						<td><?=fmt_pkts($rx_pkts)?></td>
						<!-- FP Tx -->
						<td>0 B</td>
						<!-- FP Rx -->
						<td>0 B</td>
						<!-- FP Tx Packet (p/s) -->
						<td>0</td>
						<!-- FP Rx Packet (p/s) -->
						<td>0</td>
						<!-- Actions / Menu -->
						<td class="text-center">
							<a href="interfaces.php?if=<?=urlencode($ifname)?>" title="Configure <?=htmlspecialchars($ifname)?>">
								<i class="fa-solid fa-pencil text-primary"></i>
							</a>
						</td>
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

	</div>
</div>

<!-- MODAL: ADD NEW INTERFACE -->
<div id="modal-new-interface" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa-solid fa-network-wired text-primary"></i> New Interface
                </h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Interface Type:</label>
                    <select class="form-control" id="new-iface-type" onchange="onTypeChange(this.value)">
                        <option value="vlan">VLAN Interface</option>
                        <option value="bridge">Bridge Interface</option>
                        <option value="bonding">Bonding / LAGG</option>
                        <option value="vether">vEthernet (KVM / Host-Guest)</option>
                        <option value="vxlan">VXLAN Tunnel</option>
                        <option value="gre">GRE Tunnel</option>
                        <option value="iptunnel">IP Tunnel (IPIP)</option>
                        <option value="eoip">EoIP Tunnel</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Name / Identifier:</label>
                    <input type="text" class="form-control" id="new-iface-name" placeholder="e.g. vlan100, br0">
                </div>
                <div class="form-group" id="group-parent">
                    <label>Parent Interface:</label>
                    <select class="form-control" id="new-iface-parent">
                        <?php foreach ($ifaces as $p): ?>
                            <option value="<?=htmlspecialchars($p['name'])?>"><?=htmlspecialchars(strtoupper($p['name']))?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="createInterface()">Apply &amp; Create</button>
            </div>
        </div>
    </div>
</div>

<script>
var selectedIface = null;

function openNewModal() {
    $('#modal-new-interface').modal('show');
}

function onTypeChange(val) {
    if (val === 'vlan') {
        $('#group-parent').show();
    } else {
        $('#group-parent').hide();
    }
}

function selectRow(tr, ifname, comment) {
    $('#iface-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedIface = ifname;
    $('#btn-edit, #btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', false);
}

$('#btn-edit').on('click', function() {
    if (!selectedIface) return;
    location.href = 'interfaces.php?if=' + encodeURIComponent(selectedIface);
});

$('#btn-enable').on('click', function() {
    if (!selectedIface) return;
    postIfaceState(selectedIface, 'up');
});

$('#btn-disable').on('click', function() {
    if (!selectedIface) return;
    if (selectedIface === 'lo' || selectedIface === 'enp0s3') {
        alert('Interface manajemen ini dilindungi dan tidak dapat dimatikan.');
        return;
    }
    postIfaceState(selectedIface, 'down');
});

function postIfaceState(ifname, state) {
    var form = $('<form method="post" action="interfaces.php"></form>');
    form.append('<input type="hidden" name="action" value="set_state">');
    form.append('<input type="hidden" name="interface" value="' + ifname + '">');
    form.append('<input type="hidden" name="state" value="' + state + '">');
    $('body').append(form);
    form.submit();
}

function filterAssignGrid(val) {
    val = (val || '').toLowerCase();
    $('#iface-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(val) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

function createInterface() {
    var type = $('#new-iface-type').val();
    if (type === 'vlan') location.href = 'vlan.php';
    else if (type === 'bridge') location.href = 'bridge.php';
    else if (type === 'bonding') location.href = 'lagg.php';
    else if (type === 'vether') location.href = 'vethernet.php';
    else alert('Konfigurasi tipe ' + type + ' dapat dilakukan di tab masing-masing.');
}
</script>
<?php endif; ?>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
