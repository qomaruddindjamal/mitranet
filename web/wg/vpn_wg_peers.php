<?php
/*
 * vpn_wg_peers.php - MitraNet WireGuard Peers
 * Faithful port from pfSense /wg/vpn_wg_peers.php
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = $_GET['savemsg'] ?? '';
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['act']) && $_GET['act'] === 'delete') {
        $tun = $_POST['tun'] ?? 'tun_wg0';
        $pubkey = $_POST['peer'] ?? '';
        $res = MitraNetApi::deleteWireGuardPeer($tun, $pubkey);
        if (!empty($res['data']['success'])) {
            $savemsg = "WireGuard peer deleted successfully.";
        }
    }
}

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

$all_peers = [];
foreach ($tunnels as $t) {
    foreach ($t['peers'] ?? [] as $idx => $p) {
        $p['tunnel_name'] = $t['name'];
        $p['idx'] = $idx;
        $all_peers[] = $p;
    }
}

$pgtitle = array("VPN", "WireGuard", "Peers");
$pglinks = array("", "/wg/vpn_wg_peers.php", "@self");
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

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Peers</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>Description</th>
					<th>Public key</th>
					<th>Tunnel</th>
					<th>Allowed IPs</th>
					<th>Endpoint : Port</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php if (empty($all_peers)): ?>
				<tr><td colspan="6" class="text-center text-muted">No WireGuard peers configured.</td></tr>
			<?php else: ?>
				<?php foreach ($all_peers as $p): ?>
				<tr ondblclick="document.location='vpn_wg_peers_edit.php?peer=<?=htmlspecialchars($p['idx'])?>';">
					<td><strong><?=htmlspecialchars($p['description'] ?? 'Peer')?></strong></td>
					<td class="pubkey" style="cursor: pointer;" title="<?=htmlspecialchars($p['public_key'])?>">
						<?=htmlspecialchars(substr($p['public_key'], 0, 16))?>...
					</td>
					<td><code>tun_wg0</code></td>
					<td><?=htmlspecialchars($p['allowed_ips'])?></td>
					<td><?=htmlspecialchars($p['endpoint'] ?: 'Dynamic')?></td>
					<td style="cursor: pointer;">
						<a class="fa-solid fa-pencil" href="/wg/vpn_wg_peers_edit.php?peer=<?=htmlspecialchars($p['idx'])?>" title="Edit Peer"></a>
						<a class="fa-solid fa-ban" href="?act=toggle&amp;peer=<?=htmlspecialchars($p['idx'])?>" title="Disable peer"></a>
						<a class="fa-solid fa-trash-can text-danger" href="?act=delete&amp;peer=<?=htmlspecialchars($p['idx'])?>" title="Delete Peer"></a>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<nav class="action-buttons">
    <a href="/wg/vpn_wg_peers_edit.php" class="btn btn-success btn-sm">
        <i class="fa-solid fa-plus icon-embed-btn"></i> Add Peer
    </a>
</nav>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
