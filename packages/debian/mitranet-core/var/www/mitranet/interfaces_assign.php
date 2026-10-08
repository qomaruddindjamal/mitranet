<?php
/*
 * interfaces_assign.php - MitraNet Interface Assignments
 * Adapted from pfSense interfaces_assign.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Interfaces"), gettext("Interface Assignments"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

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
    }
}

if (!empty($_GET['saved'])) {
    $saved_name = htmlspecialchars($_GET['saved']);
    $msg = "Konfigurasi interface '{$saved_name}' berhasil disimpan dan diterapkan.";
}

$ifaces_raw = MitraNetApi::getInterfaces();
$vlans = MitraNetApi::getVlans();
$bridges = MitraNetApi::getBridges();
$bonds = MitraNetApi::getBonds();

// Filter out tap-* (internal KVM interfaces managed by QEMU/veth)
// AND filter out all Wireless/WiFi interfaces (so WiFi is strictly managed in /interfaces_wifi.php)
$ifaces = array_filter($ifaces_raw, function($i) {
    $name = strtolower($i['name'] ?? '');
    $type = strtolower($i['type'] ?? '');
    
    // Exclude internal KVM tap interfaces
    if (preg_match('/^tap[-_0-9]/i', $name)) {
        return false;
    }
    
    // Exclude all wireless / wifi devices (wlan*, wlp*, wls*, ath*, ra*, or type wlan/wireless)
    if (preg_match('/^(wlan|wlp|wls|ath|ra|wifi)/i', $name) || in_array($type, ['wlan', 'wireless', 'ieee80211'])) {
        return false;
    }
    
    return true;
});

// Sort by interface type priority
// Priority: 1. Physical (SFP+/10G/100G/Ethernet) > 2. Bridge > 3. vEthernet > 4. WireGuard > 5. Others > Loopback last
usort($ifaces, function($a, $b) {
    $priority = function($i) {
        $name = strtolower($i['name'] ?? '');
        $type = strtolower($i['type'] ?? '');
        if ($name === 'lo') return 99;                      // loopback last
        if (preg_match('/^(en|eth|eno|ens|enp)/i', $name)) return 1; // Physical SFP/Ethernet 1G/10G/100G
        if (preg_match('/^br[-_]/i', $name) || $type === 'bridge') return 2; // Bridge
        if (preg_match('/^veth/i', $name)) return 3;        // vEthernet
        if (preg_match('/^wg/i', $name)) return 4;          // WireGuard
        return 5;
    };
    return $priority($a) <=> $priority($b);
});

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), true, "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "interfaces_lagg.php");
$tab_array[] = array(gettext("VRFs"), false, "interfaces_vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"), false, "interfaces_vethernet.php");
display_top_tabs($tab_array);

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<div class="panel panel-default" style="margin-top: 0; border-top: none; border-radius: 0; box-shadow: none; border-color: #8faecf;">
	<div class="panel-heading" style="background: #eef4f9; border-bottom: 1px solid #c5d7e8; color: #1e395b; padding: 6px 12px;">
		<h2 class="panel-title" style="font-size: 12px; font-weight: 700;"><i class="fa-solid fa-network-wired text-primary"></i> <?=gettext("Interface Assignments")?></h2>
	</div>
	<div class="panel-body" style="padding: 0;">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed" style="margin-bottom: 0; font-size: 11px;">
				<thead>
					<tr style="background: linear-gradient(to bottom, #e9f2fa 0%, #d8e5f2 100%); color: #1e3c5f;">
						<th><?=gettext("Interface")?></th>
						<th><?=gettext("Link State")?></th>
						<th><?=gettext("Type")?></th>
						<th><?=gettext("MAC Address")?></th>
						<th><?=gettext("MTU")?></th>
						<th><?=gettext("IP Addresses")?></th>
						<th><?=gettext("Traffic (RX/TX)")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($ifaces)): ?>
					<tr><td colspan="8" class="text-center text-muted"><?=gettext("No interfaces found")?></td></tr>
				<?php else: foreach ($ifaces as $i): ?>
					<tr>
						<td>
							<a href="interfaces.php?if=<?=urlencode($i['name'])?>">
								<strong><i class="fa-solid <?=!empty($i['is_sfp']) ? 'fa-bolt text-warning' : 'fa-network-wired text-primary'?>"></i> 
								<?php if (!empty($i['altname'])): ?>
									<?=htmlspecialchars(strtoupper($i['altname']))?> <small class="text-muted">(<?=htmlspecialchars($i['name'])?>)</small>
									<?php if (!empty($i['is_sfp'])): ?>
										<span class="badge" style="background-color: #f39c12; font-size: 10px; margin-left: 4px;">SFP/Optical</span>
									<?php endif; ?>
								<?php else: ?>
									<?=htmlspecialchars(strtoupper($i['name']))?>
								<?php endif; ?>
								</strong>
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
									echo '<span class="label label-info" style="margin-right: 3px;">' . htmlspecialchars($ip_item) . '</span>';
								}
							}
							?>
						</td>
						<td>
							<small>RX: <?=number_format(($i['traffic']['rx_bytes'] ?? 0)/1024, 1)?> KB</small><br>
							<small>TX: <?=number_format(($i['traffic']['tx_bytes'] ?? 0)/1024, 1)?> KB</small>
						</td>
						<td>
							<form method="post" action="interfaces_assign.php" style="display:inline;">
								<input type="hidden" name="action" value="set_state">
								<input type="hidden" name="interface" value="<?=htmlspecialchars($i['name'])?>">
								<?php if (!empty($i['is_up'])): ?>
									<input type="hidden" name="state" value="down">
									<button type="submit" class="btn btn-xs btn-danger" <?=($i['name']==='lo' || $i['name']==='enp0s3')?'disabled title="Protected management interface"':''?>><i class="fa-solid fa-arrow-down"></i> Down</button>
								<?php else: ?>
									<input type="hidden" name="state" value="up">
									<button type="submit" class="btn btn-xs btn-success"><i class="fa-solid fa-arrow-up"></i> Up</button>
								<?php endif; ?>
							</form>
						</td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="interfaces_assign.php" role="button" class="btn btn-default btn-sm">
		<i class="fa-solid fa-rotate icon-embed-btn"></i>
		<?=gettext("Refresh")?>
	</a>
</nav>

<div class="infoblock">
<?php
print_info_box(
	gettext("Interfaces that are configured as members of a LAGG or Bridge interface will have their traffic managed by their respective virtual interfaces.") .
	'<br/><br/>' .
	gettext("VLAN interfaces must be created on the VLANs tab before they can be assigned."), 'info', false);
?>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
