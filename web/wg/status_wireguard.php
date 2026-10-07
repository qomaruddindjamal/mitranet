<?php
/*
 * status_wireguard.php - MitraNet WireGuard Live Telemetry
 * Adapted from pfSense /wg/status_wireguard.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "WireGuard");
$pglinks = array("", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

$tab_array = array(
    array("Tunnels", false, "/wg/vpn_wg_tunnels.php"),
    array("Peers", false, "/wg/vpn_wg_peers.php"),
    array("Settings", false, "/wg/vpn_wg_settings.php"),
    array("Status", true, "/wg/status_wireguard.php")
);
display_top_tabs($tab_array, false, 'pills');
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">WireGuard Status &amp; Live Interface Telemetry</h2></div>
	<div class="panel-body table-responsive">
		<?php if (!$is_running): ?>
			<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> WireGuard daemon is not active.</div>
		<?php else: ?>
			<?php foreach ($tunnels as $tun): ?>
			<h4><i class="fa-solid fa-network-wired"></i> Interface: <strong><?=htmlspecialchars($tun['name'])?></strong> (Port: <?=htmlspecialchars($tun['listen_port'])?>)</h4>
			<p class="text-muted"><small>Public Key: <code><?=htmlspecialchars($tun['public_key'])?></code></small></p>

			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>Peer Public Key</th>
						<th>Endpoint</th>
						<th>Allowed IPs</th>
						<th>Transfer RX</th>
						<th>Transfer TX</th>
						<th>Latest Handshake</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($tun['peers'])): ?>
					<tr><td colspan="6" class="text-muted text-center">No peers connected.</td></tr>
				<?php else: ?>
					<?php foreach ($tun['peers'] as $peer): ?>
					<tr>
						<td><code title="<?=htmlspecialchars($peer['public_key'])?>"><?=htmlspecialchars(substr($peer['public_key'], 0, 20))?>...</code></td>
						<td><?=htmlspecialchars($peer['endpoint'] ?: 'None')?></td>
						<td><span class="label label-info"><?=htmlspecialchars($peer['allowed_ips'])?></span></td>
						<td><?=htmlspecialchars($peer['transfer_rx'] ?? '0')?> B</td>
						<td><?=htmlspecialchars($peer['transfer_tx'] ?? '0')?> B</td>
						<td><?=htmlspecialchars($peer['latest_handshake'] ?: 'Never')?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<nav class="action-buttons">
	<a href="/wg/status_wireguard.php" class="btn btn-default"><i class="fa-solid fa-sync"></i> Refresh Telemetry</a>
</nav>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
