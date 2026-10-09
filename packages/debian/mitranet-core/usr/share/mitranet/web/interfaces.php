<?php
/*
 * interfaces.php - MitraNet Interface Details & Configuration Editor
 * Adapted from pfSense interfaces.php for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$msg = '';
$err = '';

// Support both ?if=<name> and ?name=<name>
$target_if = $_GET['if'] ?? $_GET['name'] ?? '';

// Handle POST actions
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

        // Redirect to main Interface Assignments page upon save
        header("Location: interfaces_assign.php?saved=" . urlencode($ifname));
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

// Fetch all interfaces
$ifaces = MitraNetApi::getInterfaces();

// Find single interface if selected
$selected_iface = null;
if (!empty($target_if)) {
    foreach ($ifaces as $i) {
        if (strtolower($i['name']) === strtolower($target_if)) {
            $selected_iface = $i;
            break;
        }
    }
}

if ($selected_iface) {
    $pgtitle = array(gettext("Interfaces"), strtoupper($selected_iface['name']));
} else {
    $pgtitle = array(gettext("Interfaces"), gettext("Interface Assignments"));
}
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), empty($selected_iface), "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "interfaces_lagg.php");
$tab_array[] = array(gettext("VRFs"), false, "interfaces_vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"), false, "interfaces_vethernet.php");
if ($selected_iface) {
    $tab_array[] = array(strtoupper($selected_iface['name']), true, "interfaces.php?if=" . urlencode($selected_iface['name']));
}
display_top_tabs($tab_array);

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<?php if ($selected_iface): ?>
<!-- SINGLE INTERFACE EDIT FORM (pfSense layout) -->
<?php
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
					<a href="interfaces_assign.php" class="btn btn-default ml-1"><?=gettext("Back to List")?></a>
				</div>
			</div>
		</form>
	</div>
</div>

<?php else: ?>
<!-- ALL INTERFACES OVERVIEW TABLE -->
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Interface Details & Configuration")?></h2></div>
	<div class="panel-body">
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr>
					<th>Interface</th>
					<th>Link State</th>
					<th>Type</th>
					<th>MAC Address</th>
					<th>MTU</th>
					<th>IP Addresses</th>
					<th>Traffic (RX/TX)</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($ifaces)): ?>
					<tr><td colspan="8" class="text-center text-muted">No interfaces found</td></tr>
				<?php else: foreach ($ifaces as $i): ?>
					<tr>
						<td>
							<a href="interfaces.php?if=<?=urlencode($i['name'])?>">
								<strong><i class="fa-solid fa-network-wired"></i> <?=htmlspecialchars(strtoupper($i['name']))?> (<?=htmlspecialchars($i['name'])?>)</strong>
							</a>
						</td>
						<td>
							<?php if (!empty($i['is_up'])): ?>
								<?php if (strtoupper($i['oper_state'] ?? '') === 'UP'): ?>
									<span class="label label-success" title="Link Active"><i class="fa-solid fa-arrow-up"></i> UP</span>
								<?php else: ?>
									<span class="label label-warning" title="Admin UP, No Carrier / Virtual"><i class="fa-solid fa-plug"></i> NO-CARRIER</span>
								<?php endif; ?>
							<?php else: ?>
								<span class="label label-danger"><i class="fa-solid fa-arrow-down"></i> DOWN</span>
							<?php endif; ?>
						</td>
						<td><span class="label label-default"><?=htmlspecialchars($i['type'] ?? 'ether')?></span></td>
						<td><code><?=htmlspecialchars($i['mac_address'] ?? '--')?></code></td>
						<td><?=htmlspecialchars($i['mtu'] ?? 1500)?></td>
						<td>
							<?php
							$ips = array_merge($i['ipv4_addresses'] ?? [], $i['ipv6_addresses'] ?? []);
							if (empty($ips)) {
								echo '<span class="text-muted">None</span>';
							} else {
								foreach ($ips as $ip_item) {
									echo '<span class="label label-info mr-1">' . htmlspecialchars($ip_item) . '</span>';
								}
							}
							?>
						</td>
						<td>
							<small>RX: <?=number_format(($i['traffic']['rx_bytes'] ?? 0)/1024, 1)?> KB</small><br>
							<small>TX: <?=number_format(($i['traffic']['tx_bytes'] ?? 0)/1024, 1)?> KB</small>
						</td>
						<td>
							<a href="interfaces.php?if=<?=urlencode($i['name'])?>" class="btn btn-xs btn-primary" title="<?=gettext('Configure Interface')?>">
								<i class="fa-solid fa-pencil"></i> Edit
							</a>
							<form method="post" action="interfaces.php" class="form-inline-action ml-1">
								<input type="hidden" name="action" value="set_state">
								<input type="hidden" name="interface" value="<?=htmlspecialchars($i['name'])?>">
								<?php if (!empty($i['is_up'])): ?>
									<input type="hidden" name="state" value="down">
									<button type="submit" class="btn btn-xs btn-danger" <?=($i['name']==='lo' || $i['name']==='enp0s3')?'disabled title="Protected management interface"':''?>>Down</button>
								<?php else: ?>
									<input type="hidden" name="state" value="up">
									<button type="submit" class="btn btn-xs btn-success">Up</button>
								<?php endif; ?>
							</form>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
<?php endif; ?>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
