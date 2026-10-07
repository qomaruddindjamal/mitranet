<?php
/*
 * vpn_wg_tunnels.php - MitraNet WireGuard Tunnels
 * Adapted from pfSense /wg/vpn_wg_tunnels.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("VPN", "WireGuard", "Tunnels");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
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
	<div class="pull-left"><?=htmlspecialchars($savemsg)?></div>
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
	<div class="pull-left"><i class="fa-solid fa-check-circle"></i> WireGuard kernel service is running (in-tree Linux kernel module active).</div>
	<form method="post" class="pull-right" style="margin: 0;">
		<input type="hidden" name="act" value="restart">
		<button type="submit" class="btn btn-xs btn-warning"><i class="fa-solid fa-arrows-rotate"></i> Restart</button>
		<input type="hidden" name="act" value="stop">
		<button type="submit" class="btn btn-xs btn-danger" style="margin-left: 5px;"><i class="fa-solid fa-stop"></i> Stop</button>
	</form>
</div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">WireGuard Tunnels</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>Name</th>
					<th>Listen Port</th>
					<th>Public Key</th>
					<th>Status</th>
					<th>Peers Count</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php if (empty($tunnels)): ?>
				<tr><td colspan="6" class="text-center text-muted">No WireGuard tunnels configured.</td></tr>
			<?php else: ?>
				<?php foreach ($tunnels as $tun): ?>
				<tr>
					<td><strong><?=htmlspecialchars($tun['name'])?></strong></td>
					<td><code><?=htmlspecialchars($tun['listen_port'])?></code></td>
					<td><code title="<?=htmlspecialchars($tun['public_key'])?>"><?=htmlspecialchars(substr($tun['public_key'], 0, 24))?>...</code></td>
					<td>
						<?php if ($is_running): ?>
							<span class="label label-success"><i class="fa-solid fa-check"></i> ACTIVE</span>
						<?php else: ?>
							<span class="label label-danger"><i class="fa-solid fa-ban"></i> STOPPED</span>
						<?php endif; ?>
					</td>
					<td><span class="badge bg-primary"><?=count($tun['peers'] ?? [])?></span></td>
					<td>
						<a class="fa-solid fa-eye" title="View Peers" href="/wg/vpn_wg_peers.php"></a>
						<a class="fa-solid fa-sliders" title="Settings" href="/wg/vpn_wg_settings.php"></a>
						<a class="fa-solid fa-chart-line" title="Live Status" href="/wg/status_wireguard.php"></a>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<nav class="action-buttons">
	<a href="/wg/vpn_wg_settings.php" class="btn btn-info btn-sm"><i class="fa-solid fa-cog icon-embed-btn"></i> Tunnel Settings</a>
	<a href="/wg/status_wireguard.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-chart-line icon-embed-btn"></i> Live Tunnel Telemetry</a>
</nav>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
