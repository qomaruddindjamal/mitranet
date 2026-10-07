<?php
/*
 * vpn_wg_tunnels.php - MitraNet WireGuard Tunnels
 * Faithful port from pfSense /wg/vpn_wg_tunnels.php
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = $_GET['savemsg'] ?? '';
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if (in_array($act, ['start', 'stop', 'restart'])) {
        $res = MitraNetApi::controlWireGuard($act);
        if (!empty($res['data']['success'])) {
            $savemsg = "WireGuard service {$act} command executed successfully.";
        }
    }
}

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

$pgtitle = array("VPN", "WireGuard", "Tunnels");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$tab_array = array(
    array("Tunnels", true, "/wg/vpn_wg_tunnels.php"),
    array("Peers", false, "/wg/vpn_wg_peers.php"),
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

<?php if (!$is_running): ?>
<div class="alert alert-danger clearfix" role="alert">
	<div class="pull-left">The WireGuard service is not running.</div>
	<form method="post" class="pull-right" style="margin: 0;">
		<input type="hidden" name="act" value="start">
		<button type="submit" class="btn btn-xs btn-success"><i class="fa-solid fa-play"></i> Start Service</button>
	</form>
</div>
<?php else: ?>
<div class="alert alert-success clearfix" role="alert">
	<div class="pull-left"><i class="fa-solid fa-check-circle"></i> WireGuard service is running.</div>
	<form method="post" class="pull-right" style="margin: 0;">
		<input type="hidden" name="act" value="restart">
		<button type="submit" class="btn btn-xs btn-warning"><i class="fa-solid fa-arrows-rotate"></i> Restart</button>
		<input type="hidden" name="act" value="stop">
		<button type="submit" class="btn btn-xs btn-danger" style="margin-left: 5px;"><i class="fa-solid fa-stop"></i> Stop</button>
	</form>
</div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading">
        <h2 class="panel-title">Tunnels</h2>
    </div>
	<div class="panel-body table-responsive">
		<table class="table table-hover table-striped table-condensed tree">
			<thead>
				<tr>
					<th>Name</th>
					<th>Description</th>
					<th>Public Key</th>
					<th>Address / Assignment</th>
					<th>Listen Port</th>
					<th>Peers</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php if (empty($tunnels)): ?>
				<tr><td colspan="7" class="text-center text-muted">No WireGuard tunnels configured.</td></tr>
			<?php else: ?>
				<?php foreach ($tunnels as $tun): ?>
				<tr class="treegrid-tun_wg0">
					<td><strong>tun_wg0</strong></td>
					<td><?=htmlspecialchars($tun['description'] ?? 'Tunnel to MikroTik CHR VPS (103.93.162.168)')?></td>
					<td class="pubkey" style="cursor: pointer;" title="<?=htmlspecialchars($tun['public_key'])?>">
						<?=htmlspecialchars(substr($tun['public_key'], 0, 32))?>...
					</td>
					<td>
						<i class="fa-solid fa-sitemap" style="vertical-align: middle;"></i>
						<a href="/interfaces.php?if=opt1" style="padding-left: 3px">WGVPN (opt1)</a>
					</td>
					<td><?=htmlspecialchars($tun['listen_port'] ?? '51820')?></td>
					<td><?=count($tun['peers'] ?? [])?></td>
					<td>
						<a class="fa-solid fa-user-plus" href="/wg/vpn_wg_peers_edit.php?tun=tun_wg0" title="Add Peer"></a>
						<a class="fa-solid fa-pencil" href="/wg/vpn_wg_tunnels_edit.php?tun=tun_wg0" title="Edit Tunnel"></a>
						<a class="fa-solid fa-download" href="?act=download&amp;tun=tun_wg0" title="Download Configuration"></a>
						<a class="fa-solid fa-ban" href="?act=toggle&amp;tun=tun_wg0" title="Disable tunnel"></a>
						<a class="fa-solid fa-trash-can text-danger" href="?act=delete&amp;tun=tun_wg0" title="Delete Tunnel"></a>
					</td>
				</tr>
				<tr class="treegrid-parent-tun_wg0">
					<td style="font-weight: bold;">Peers</td>
					<td class="contains-table" colspan="6">
						<table class="table table-hover table-striped table-condensed">
							<thead>
								<tr>
									<th>Description</th>
									<th>Public Key</th>
									<th>Tunnel</th>
									<th>Allowed IPs</th>
									<th>Endpoint</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($tun['peers'] as $peer): ?>
								<tr>
									<td><?=htmlspecialchars($peer['description'] ?? 'WireGuard Peer')?></td>
									<td title="<?=htmlspecialchars($peer['public_key'])?>"><?=htmlspecialchars(substr($peer['public_key'], 0, 32))?>...</td>
									<td>tun_wg0</td>
									<td><?=htmlspecialchars($peer['allowed_ips'])?></td>
									<td><?=htmlspecialchars($peer['endpoint'] ?: 'Dynamic')?></td>
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

<nav class="action-buttons">
    <a href="/wg/vpn_wg_tunnels_edit.php" class="btn btn-success btn-sm">
        <i class="fa-solid fa-plus icon-embed-btn"></i> Add Tunnel
    </a>
</nav>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
