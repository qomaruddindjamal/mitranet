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
$vlans   = MitraNetApi::getVlans();
$bridges = MitraNetApi::getBridges();
$bonds   = MitraNetApi::getBonds();

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

$tab_array   = array();
$tab_array[] = array(gettext("Interface Assignments"), true,  "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"),                 false, "interfaces_vlan.php");
$tab_array[] = array(gettext("Bridges"),               false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"),                 false, "interfaces_lagg.php");
$tab_array[] = array(gettext("VRFs"),                  false, "interfaces_vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"),       false, "interfaces_vethernet.php");
display_top_tabs($tab_array);

if (!empty($msg)) print_info_box($msg, "success");
if (!empty($err)) print_info_box($err, "danger");
?>

<div class="container-fluid mitranet-page-container">
	<div class="panel-heading">
		<h2 class="panel-title">
			<i class="fa-solid fa-network-wired text-primary"></i>
			<?=gettext("Interface Assignments")?>
		</h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed iface-assign-table">
				<thead>
					<tr>
						<!-- Flag -->
						<th class="iface-col-flag" title="Link / Carrier State">
							<i class="fa-solid fa-circle-dot"></i>
						</th>
						<!-- Name -->
						<th class="iface-col-name">
							<?=gettext("Name")?>
						</th>
						<!-- Type -->
						<th class="iface-col-type">
							<?=gettext("Type")?>
						</th>
						<!-- MAC -->
						<th class="iface-col-mac">
							<?=gettext("MAC Address")?>
						</th>
						<!-- MTU -->
						<th class="iface-col-mtu">
							<?=gettext("MTU")?>
						</th>
						<!-- IPv4 -->
						<th class="iface-col-ip">
							<i class="fa-solid fa-4 text-success" title="IPv4"></i>
							IPv4
						</th>
						<!-- IPv6 -->
						<th class="iface-col-ip">
							<i class="fa-solid fa-6 text-info" title="IPv6"></i>
							IPv6
						</th>
						<!-- TX -->
						<th class="iface-col-traffic">
							<i class="fa-solid fa-arrow-up text-warning" title="Transmit Bytes"></i>
							TX
						</th>
						<!-- RX -->
						<th class="iface-col-traffic">
							<i class="fa-solid fa-arrow-down text-success" title="Receive Bytes"></i>
							RX
						</th>
						<!-- TX Packets -->
						<th class="iface-col-pkts">
							<i class="fa-solid fa-arrow-up text-warning" title="TX Packets"></i>
							TX Pkts
						</th>
						<!-- RX Packets -->
						<th class="iface-col-pkts">
							<i class="fa-solid fa-arrow-down text-success" title="RX Packets"></i>
							RX Pkts
						</th>
						<!-- Actions -->
						<th class="iface-col-actions">
							<?=gettext("Actions")?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($ifaces)): ?>
					<tr>
						<td colspan="12" class="text-center text-muted">
							<?=gettext("No interfaces found")?>
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($ifaces as $i): ?>
					<?php
					$is_up      = !empty($i['is_up']);
					$oper_state = strtoupper($i['oper_state'] ?? '');
					$ipv4s      = $i['ipv4_addresses'] ?? [];
					$ipv6s      = $i['ipv6_addresses'] ?? [];
					$traffic    = $i['traffic'] ?? [];
					$rx_bytes   = (int)($traffic['rx_bytes']   ?? 0);
					$tx_bytes   = (int)($traffic['tx_bytes']   ?? 0);
					$rx_pkts    = (int)($traffic['rx_packets'] ?? 0);
					$tx_pkts    = (int)($traffic['tx_packets'] ?? 0);
					$is_protected = ($i['name'] === 'lo' || $i['name'] === 'enp0s3');
					?>
					<tr class="iface-row <?=$is_up ? 'iface-row-up' : 'iface-row-down'?>">
						<!-- Flag / Status -->
						<td class="iface-col-flag">
							<?php if ($is_up && $oper_state === 'UP'): ?>
								<span class="iface-flag iface-flag-up" title="Link UP / Carrier Present">
									<i class="fa-solid fa-circle"></i>
								</span>
							<?php elseif ($is_up): ?>
								<span class="iface-flag iface-flag-nocarrier" title="Admin UP — No Carrier">
									<i class="fa-solid fa-circle-half-stroke"></i>
								</span>
							<?php else: ?>
								<span class="iface-flag iface-flag-down" title="DOWN">
									<i class="fa-regular fa-circle"></i>
								</span>
							<?php endif; ?>
						</td>
						<!-- Name -->
						<td class="iface-col-name">
							<a href="interfaces.php?if=<?=urlencode($i['name'])?>" class="iface-name-link">
								<i class="fa-solid <?=!empty($i['is_sfp']) ? 'fa-bolt text-warning' : 'fa-ethernet text-primary'?>"></i>
								<?php if (!empty($i['altname'])): ?>
									<strong><?=htmlspecialchars(strtoupper($i['altname']))?></strong>
									<small class="text-muted">&nbsp;<?=htmlspecialchars($i['name'])?></small>
									<?php if (!empty($i['is_sfp'])): ?>
										<span class="badge badge-sfp">SFP</span>
									<?php endif; ?>
								<?php else: ?>
									<strong><?=htmlspecialchars(strtoupper($i['name']))?></strong>
								<?php endif; ?>
							</a>
						</td>
						<!-- Type -->
						<td class="iface-col-type">
							<span class="label label-default">
								<?=htmlspecialchars($i['type'] ?? 'ether')?>
							</span>
						</td>
						<!-- MAC Address -->
						<td class="iface-col-mac">
							<code class="iface-mac">
								<?=htmlspecialchars($i['mac_address'] ?? '--')?>
							</code>
						</td>
						<!-- MTU -->
						<td class="iface-col-mtu">
							<?=htmlspecialchars($i['mtu'] ?? 1500)?>
						</td>
						<!-- IPv4 -->
						<td class="iface-col-ip">
							<?php if (empty($ipv4s)): ?>
								<span class="text-muted">—</span>
							<?php else: ?>
								<?php foreach ($ipv4s as $ip4): ?>
									<span class="label label-success iface-ip-label">
										<?=htmlspecialchars($ip4)?>
									</span>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
						<!-- IPv6 -->
						<td class="iface-col-ip">
							<?php if (empty($ipv6s)): ?>
								<span class="text-muted">—</span>
							<?php else: ?>
								<?php foreach ($ipv6s as $ip6): ?>
									<span class="label label-info iface-ip-label">
										<?=htmlspecialchars($ip6)?>
									</span>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
						<!-- TX Bytes -->
						<td class="iface-col-traffic iface-traffic-cell">
							<span class="badge badge-traffic badge-traffic-tx">
								<?=fmt_bytes($tx_bytes)?>
							</span>
						</td>
						<!-- RX Bytes -->
						<td class="iface-col-traffic iface-traffic-cell">
							<span class="badge badge-traffic">
								<?=fmt_bytes($rx_bytes)?>
							</span>
						</td>
						<!-- TX Packets -->
						<td class="iface-col-pkts iface-traffic-cell">
							<?php if ($tx_pkts > 0): ?>
								<span class="badge badge-pkts badge-pkts-tx">
									<?=fmt_pkts($tx_pkts)?>
								</span>
							<?php else: ?>
								<span class="text-muted">—</span>
							<?php endif; ?>
						</td>
						<!-- RX Packets -->
						<td class="iface-col-pkts iface-traffic-cell">
							<?php if ($rx_pkts > 0): ?>
								<span class="badge badge-pkts">
									<?=fmt_pkts($rx_pkts)?>
								</span>
							<?php else: ?>
								<span class="text-muted">—</span>
							<?php endif; ?>
						</td>
						<!-- Actions -->
						<td class="iface-col-actions">
							<form method="post" action="interfaces_assign.php" class="form-inline-action">
								<input type="hidden" name="action" value="set_state">
								<input type="hidden" name="interface" value="<?=htmlspecialchars($i['name'])?>">
								<?php if ($is_up): ?>
									<input type="hidden" name="state" value="down">
									<button type="submit"
										class="btn btn-xs btn-danger"
										<?=$is_protected ? 'disabled title="Protected management interface"' : ''?>>
										<i class="fa-solid fa-arrow-down"></i>
										Down
									</button>
								<?php else: ?>
									<input type="hidden" name="state" value="up">
									<button type="submit" class="btn btn-xs btn-success">
										<i class="fa-solid fa-arrow-up"></i>
										Up
									</button>
								<?php endif; ?>
							</form>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
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
    gettext("VLAN interfaces must be created on the VLANs tab before they can be assigned."),
    'info', false
);
?>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
