<?php
/*
 * status_wireguard.php - MitraNet WireGuard Live Telemetry
 * Faithful port from pfSense /wg/status_wireguard.php
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
	<div class="panel-heading"><h2 class="panel-title">WireGuard Status</h2></div>
	<div class="table-responsive panel-body">
		<table class="table table-hover table-striped table-condensed tree table-overflow-visible">
			<thead>
				<tr>
					<th>Tunnel</th>
					<th>Description</th>
					<th>Peers</th>
					<th>Public Key</th>
					<th>Address / Assignment</th>
					<th>MTU</th>
					<th>Listen Port</th>
					<th>RX</th>
					<th>TX</th>
				</tr>
			</thead>
			<tbody>
			<?php if (empty($tunnels) || !$is_running): ?>
				<tr>
					<td colspan="9">
						<div class="alert alert-warning clearfix" role="alert">
							<div class="pull-left">No WireGuard status information is available.</div>
						</div>
					</td>
				</tr>
			<?php else: ?>
				<?php foreach ($tunnels as $tun): 
					$t_name = $tun['name'] ?? 'wg0';
					$t_desc = $tun['description'] ?: 'WireGuard Tunnel';
					$t_addr = !empty($tun['address']) ? $tun['address'] : ($tun['interface'] ?? strtoupper($t_name));
					$t_mtu = !empty($tun['mtu']) ? htmlspecialchars($tun['mtu']) : '1420';
					$rx_bytes = floatval($tun['transfer_rx'] ?? 0);
					$tx_bytes = floatval($tun['transfer_tx'] ?? 0);
					$rx_fmt = $rx_bytes > 1048576 ? number_format($rx_bytes / 1048576, 2) . ' MiB' : number_format($rx_bytes / 1024, 2) . ' KiB';
					$tx_fmt = $tx_bytes > 1048576 ? number_format($tx_bytes / 1048576, 2) . ' MiB' : number_format($tx_bytes / 1024, 2) . ' KiB';
				?>
				<tr>
					<td><strong><?=htmlspecialchars($t_name)?></strong></td>
					<td><?=htmlspecialchars($t_desc)?></td>
					<td><?=count($tun['peers'] ?? [])?></td>
					<td class="pubkey" title="<?=htmlspecialchars($tun['public_key'] ?? '')?>"><?=htmlspecialchars(substr($tun['public_key'] ?? '', 0, 16))?><?=strlen($tun['public_key'] ?? '') > 16 ? '...' : ''?></td>
					<td><code><?=htmlspecialchars($t_addr)?></code></td>
					<td><?=$t_mtu?></td>
					<td><?=htmlspecialchars($tun['listen_port'] ?? '51820')?></td>
					<td><span class="label label-info"><?=$rx_fmt?></span></td>
					<td><span class="label label-info"><?=$tx_fmt?></span></td>
				</tr>
				<tr class="treegrid-parent-<?=htmlspecialchars($t_name)?>">
					<td class="td-bold">Peers</td>
					<td class="contains-table" colspan="8">
						<table class="table table-hover table-striped table-condensed">
							<thead>
								<tr>
									<th>Description</th>
									<th>Public Key</th>
									<th>Endpoint</th>
									<th>Allowed IPs</th>
									<th>Latest Handshake</th>
									<th>Transfer RX</th>
									<th>Transfer TX</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($tun['peers'] as $peer): ?>
								<tr>
									<td><strong><?=htmlspecialchars($peer['description'] ?? 'Peer')?></strong></td>
									<td class="pubkey" title="<?=htmlspecialchars($peer['public_key'])?>"><?=htmlspecialchars(substr($peer['public_key'], 0, 16))?>...</td>
									<td><code><?=htmlspecialchars($peer['endpoint'] ?: 'Dynamic')?></code></td>
									<td><?=htmlspecialchars($peer['allowed_ips'])?></td>
									<td>
										<?php if (!empty($peer['latest_handshake']) && intval($peer['latest_handshake']) > 0): ?>
											<span class="text-success"><i class="fa-solid fa-clock"></i> Active (connected)</span>
										<?php else: ?>
											<span class="text-muted">Never connected</span>
										<?php endif; ?>
									</td>
									<td><?=htmlspecialchars(number_format(floatval($peer['transfer_rx'] ?? 0) / 1024, 2))?> KiB</td>
									<td><?=htmlspecialchars(number_format(floatval($peer['transfer_tx'] ?? 0) / 1024, 2))?> KiB</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
