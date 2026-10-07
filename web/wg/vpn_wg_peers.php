<?php
/*
 * vpn_wg_peers.php - MitraNet WireGuard Peers
 * Faithful port from pfSense /wg/vpn_wg_peers.php
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = $_GET['savemsg'] ?? '';
$err_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'delete') {
    $tun = $_POST['tun'] ?? 'wg0';
    $pubkey = $_POST['pubkey'] ?? '';
    if (!empty($pubkey)) {
        $res = MitraNetApi::deleteWireGuardPeer($tun, $pubkey);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            $savemsg = "Peer deleted successfully.";
        } else {
            $err_msg = $res['data']['error'] ?? 'Failed to delete peer.';
        }
    }
}

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

$pgtitle = array("VPN", "WireGuard", "Peers");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$tab_array = array(
    array("Tunnels", false, "/wg/vpn_wg_tunnels.php"),
    array("Peers", true, "/wg/vpn_wg_peers.php"),
    array("Settings", false, "/wg/vpn_wg_settings.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
display_top_tabs($tab_array, false, 'pills');
?>

<?php if ($savemsg): ?>
<div class="alert alert-success clearfix" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<div class="pull-left"><i class="fa-solid fa-check"></i> <?=htmlspecialchars($savemsg)?></div>
</div>
<?php endif; ?>
<?php if ($err_msg): ?>
<div class="alert alert-danger clearfix" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<div class="pull-left"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($err_msg)?></div>
</div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa-solid fa-users"></i> Configured WireGuard Peers (Server Clients &amp; Remote Endpoints)</h2></div>
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
					<th style="text-align: right;">Actions</th>
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
						<td><code><?=htmlspecialchars(($peer['endpoint'] && $peer['endpoint'] !== '(none)') ? $peer['endpoint'] : 'Dynamic')?></code></td>
						<td><?=htmlspecialchars(($peer['latest_handshake'] && $peer['latest_handshake'] !== '0') ? $peer['latest_handshake'] : 'Never')?></td>
						<td>
							<small>
								RX: <?=htmlspecialchars($peer['transfer_rx'] ?? '0')?> B | 
								TX: <?=htmlspecialchars($peer['transfer_tx'] ?? '0')?> B
							</small>
						</td>
						<td style="text-align: right;">
							<a class="btn btn-xs btn-info" title="Edit Peer" href="/wg/vpn_wg_peers_edit.php?peer=<?=urlencode($peer['public_key'])?>&tun=<?=htmlspecialchars($tun['name'])?>"><i class="fa-solid fa-pencil"></i></a>
							<form method="post" style="display: inline-block;">
								<input type="hidden" name="act" value="delete">
								<input type="hidden" name="tun" value="<?=htmlspecialchars($tun['name'])?>">
								<input type="hidden" name="pubkey" value="<?=htmlspecialchars($peer['public_key'])?>">
								<button type="submit" class="btn btn-xs btn-danger" title="Delete Peer" onclick="return confirm('Hapus peer WireGuard ini?');"><i class="fa-solid fa-trash"></i></button>
							</form>
						</td>
					</tr>
					<?php
				}
			}
			if (!$has_peers) {
				echo '<tr><td colspan="7" class="text-center text-muted">Belum ada peer WireGuard terkonfigurasi. Klik "Add Peer" untuk menambahkan client atau uplink VPS.</td></tr>';
			}
			?>
			</tbody>
		</table>
	</div>
</div>

<nav class="action-buttons">
    <a href="/wg/vpn_wg_peers_edit.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus icon-embed-btn"></i> Add Peer
    </a>
</nav>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
