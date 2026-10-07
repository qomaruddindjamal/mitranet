<?php
/*
 * vpn_wg_peers.php - MitraNet WireGuard Peers
 * Adapted from pfSense /wg/vpn_wg_peers.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("VPN", "WireGuard", "Peers");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

$tab_array = array(
    array("Tunnels", false, "/wg/vpn_wg_tunnels.php"),
    array("Peers", true, "/wg/vpn_wg_peers.php"),
    array("Settings", false, "/wg/vpn_wg_settings.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
display_top_tabs($tab_array, false, 'pills');
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Configured WireGuard Peers</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>Tunnel</th>
					<th>Public Key</th>
					<th>Allowed IPs</th>
					<th>Endpoint</th>
					<th>Latest Handshake</th>
					<th>Transfer</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$has_peers = false;
			foreach ($tunnels as $tun) {
				foreach ($tun['peers'] ?? [] as $peer) {
					$has_peers = true;
					?>
					<tr>
						<td><strong><?=htmlspecialchars($tun['name'])?></strong></td>
						<td><code title="<?=htmlspecialchars($peer['public_key'])?>"><?=htmlspecialchars(substr($peer['public_key'], 0, 24))?>...</code></td>
						<td><span class="label label-info"><?=htmlspecialchars($peer['allowed_ips'])?></span></td>
						<td><code><?=htmlspecialchars($peer['endpoint'] ?: 'Dynamic / Awaiting')?></code></td>
						<td><?=htmlspecialchars($peer['latest_handshake'] ?: 'Never')?></td>
						<td>
							<small>
								RX: <?=htmlspecialchars($peer['transfer_rx'] ?? '0')?> B | 
								TX: <?=htmlspecialchars($peer['transfer_tx'] ?? '0')?> B
							</small>
						</td>
					</tr>
					<?php
				}
			}
			if (!$has_peers) {
				echo '<tr><td colspan="6" class="text-center text-muted">No remote peers currently attached to active tunnels.</td></tr>';
			}
			?>
			</tbody>
		</table>
	</div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
