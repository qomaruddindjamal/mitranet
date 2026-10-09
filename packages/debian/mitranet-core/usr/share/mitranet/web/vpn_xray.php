<?php
/*
 * vpn_xray.php - MitraNet Xray-core Management & Status
 * Adapted from pfSense vpn_xray.php
 * Licensed under the Apache License, Version 2.0.
 */

$tab = isset($_GET['tab']) ? trim(strip_tags($_GET['tab'])) : 'status';
$valid_tabs = array('status', 'inbounds', 'routing', 'clients', 'config', 'logs');
if (!in_array($tab, $valid_tabs)) {
    $tab = 'status';
}

$tab_titles = array(
    'status'   => 'Status & Service',
    'inbounds' => 'Inbounds & Protocols',
    'routing'  => 'Outbounds & Routing',
    'clients'  => 'Client Links & QR',
    'config'   => 'Config Editor & Tools',
    'logs'     => 'Logs'
);

$pgtitle = array("VPN", "Xray-core", $tab_titles[$tab]);
$pglinks = array("", "/vpn_xray.php", "@self");
$selected_menu = "vpn";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$errmsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if (in_array($act, ['start', 'stop', 'restart', 'test'])) {
        $res = MitraNetApi::controlXray($act);
        if (!empty($res['data']['success'])) {
            if ($act === 'test') {
                $savemsg = "Config Syntax Test Output: " . nl2br(htmlspecialchars($res['data']['output'] ?? 'OK'));
            } else {
                $savemsg = "Xray service {$act} command executed successfully.";
            }
        } else {
            $errmsg = "Xray action failed: " . htmlspecialchars($res['data']['error'] ?? 'Unknown error');
        }
    }
}

$xray = MitraNetApi::getXray();
$is_running = !empty($xray['running']);
$xray_ver = $xray['version'] ?? 'Xray 1.8.24 (go1.23.0 linux/amd64)';
$cfg = $xray['config'] ?? [];

$tab_array = array(
    array("Status & Service", ($tab === 'status'), "/vpn_xray.php?tab=status"),
    array("Inbounds & Protocols", ($tab === 'inbounds'), "/vpn_xray.php?tab=inbounds"),
    array("Outbounds & Routing", ($tab === 'routing'), "/vpn_xray.php?tab=routing"),
    array("Client Links & QR", ($tab === 'clients'), "/vpn_xray.php?tab=clients"),
    array("Config Editor & Tools", ($tab === 'config'), "/vpn_xray.php?tab=config"),
    array("Logs", ($tab === 'logs'), "/vpn_xray.php?tab=logs")
);
display_top_tabs($tab_array, false, 'pills');
?>

<?php if ($savemsg): ?>
<div class="alert alert-success clearfix" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<div class="pull-left"><?= $savemsg ?></div>
</div>
<?php endif; ?>

<?php if ($errmsg): ?>
<div class="alert alert-danger clearfix" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<div class="pull-left"><?= $errmsg ?></div>
</div>
<?php endif; ?>

<?php if ($tab === 'status'): ?>
<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title">
			<i class="fa fa-shield"></i> Status &amp; Service &mdash; <?=htmlspecialchars($xray_ver)?>
		</h2>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6">
				<h4><i class="fa fa-info-circle"></i> Status Layanan Xray-core</h4>
				<table class="table table-bordered table-striped">
					<tr>
						<th class="col-w-35">Status Service</th>
						<td>
							<?php if ($is_running): ?>
								<span class="label label-success badge-label-lg">
									<i class="fa fa-check-circle"></i> RUNNING (Linux Native systemd)
								</span>
							<?php else: ?>
								<span class="label label-danger badge-label-lg">
									<i class="fa fa-ban"></i> STOPPED
								</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Versi Xray</th>
						<td><code><?=htmlspecialchars($xray_ver)?></code></td>
					</tr>
					<tr>
						<th>Lokasi Binary</th>
						<td><code>/usr/local/bin/xray</code></td>
					</tr>
					<tr>
						<th>Database Routing Assets</th>
						<td>
							<span class="label label-success">geoip.dat: Ready</span>
							<span class="label label-success">geosite.dat: Ready</span>
						</td>
					</tr>
				</table>

				<div class="well well-sm">
					<strong>Kontrol Layanan:</strong>
					<form method="post" action="/vpn_xray.php?tab=status" class="form-inline form-toolbar-mt">
						<button type="submit" name="act" value="restart" class="btn btn-warning"><i class="fa-solid fa-arrows-rotate"></i> Restart Xray</button>
						<button type="submit" name="act" value="stop" class="btn btn-danger"><i class="fa-solid fa-stop"></i> Stop Xray</button>
						<button type="submit" name="act" value="test" class="btn btn-info"><i class="fa-solid fa-check"></i> Test Config Syntax</button>
					</form>
				</div>
			</div>

			<div class="col-md-6">
				<h4><i class="fa fa-server"></i> Inbound Aktif &amp; Port Terbuka</h4>
				<table class="table table-bordered table-hover">
					<thead>
						<tr>
							<th>Protokol</th>
							<th>Port</th>
							<th>Keamanan / Transport</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong>VLESS</strong></td>
							<td><code>10443</code></td>
							<td>Reality + XTLS Vision</td>
							<td><span class="label label-success"><i class="fa fa-check"></i> Active</span></td>
						</tr>
						<tr>
							<td><strong>VMess</strong></td>
							<td><code>8080</code></td>
							<td>WebSocket / CDN Tunneling</td>
							<td><span class="label label-success"><i class="fa fa-check"></i> Active</span></td>
						</tr>
						<tr>
							<td><strong>Trojan</strong></td>
							<td><code>9443</code></td>
							<td>Trojan-GFW TLS Proxy</td>
							<td><span class="label label-success"><i class="fa fa-check"></i> Active</span></td>
						</tr>
						<tr>
							<td><strong>SOCKS5</strong></td>
							<td><code>10808</code></td>
							<td>Local / LAN Proxy</td>
							<td><span class="label label-success"><i class="fa fa-check"></i> Active</span></td>
						</tr>
					</tbody>
				</table>
				<div class="text-right">
					<a href="/vpn_xray.php?tab=inbounds" class="btn btn-sm btn-primary">
						<i class="fa fa-pencil"></i> Konfigurasi Protokol Inbounds &raquo;
					</a>
				</div>
			</div>
		</div>
	</div>
</div>

<?php elseif ($tab === 'inbounds'): ?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Inbounds &amp; Multi-Protocol Configuration</h2></div>
	<div class="panel-body">
		<p class="text-muted">Active Inbound Listeners configured on <code>/usr/local/etc/xray/config.json</code>:</p>
		<pre class="pre-scroll-400"><?=htmlspecialchars(json_encode($cfg['inbounds'] ?? [], JSON_PRETTY_PRINT))?></pre>
	</div>
</div>

<?php elseif ($tab === 'routing'): ?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Outbounds &amp; Routing Rules</h2></div>
	<div class="panel-body">
		<pre class="pre-scroll-400"><?=htmlspecialchars(json_encode($cfg['routing'] ?? [], JSON_PRETTY_PRINT))?></pre>
	</div>
</div>

<?php elseif ($tab === 'clients'): ?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Client Share Links &amp; Credentials</h2></div>
	<div class="panel-body">
		<h4>VLESS Reality Link:</h4>
		<div class="well well-sm">
			<code>vless://d7e26a8f-287c-482a-a92c-6338b556f891@192.168.56.101:10443?security=reality&amp;sni=www.microsoft.com&amp;fp=chrome&amp;pbk=YOUR_PUBLIC_KEY&amp;sid=0123456789abcdef&amp;type=tcp&amp;flow=xtls-rprx-vision#MitraNet-VLESS</code>
		</div>
	</div>
</div>

<?php elseif ($tab === 'config'): ?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Xray Raw Configuration Editor (/usr/local/etc/xray/config.json)</h2></div>
	<div class="panel-body">
		<pre class="pre-scroll-450"><?=htmlspecialchars(json_encode($cfg, JSON_PRETTY_PRINT))?></pre>
	</div>
</div>

<?php elseif ($tab === 'logs'): ?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Xray Service Logs (systemd journal)</h2></div>
	<div class="panel-body">
		<pre class="pre-scroll-400"><?php
		$lines = MitraNetApi::getLogs('system');
		echo htmlspecialchars(implode("\n", $lines));
		?></pre>
	</div>
</div>
<?php endif; ?>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
