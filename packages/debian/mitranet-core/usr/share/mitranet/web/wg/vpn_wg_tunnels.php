<?php
/*
 * vpn_wg_tunnels.php - MitraNet WireGuard Tunnels
 * Faithful port from pfSense /wg/vpn_wg_tunnels.php
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = $_GET['savemsg'] ?? '';
$err_msg = "";

// Handle Delete Tunnel
if (isset($_REQUEST['act']) && $_REQUEST['act'] === 'delete') {
    $tun_to_del = trim($_REQUEST['tun'] ?? '');
    if ($tun_to_del) {
        $res = MitraNetApi::deleteWireGuardTunnel($tun_to_del);
        if (!empty($res['data']['success'])) {
            header("Location: /wg/vpn_wg_tunnels.php?savemsg=" . urlencode("Tunnel {$tun_to_del} berhasil dihapus."));
            exit;
        } else {
            $err_msg = $res['data']['error'] ?? "Gagal menghapus tunnel {$tun_to_del}.";
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

<?php if ($err_msg): ?>
<div class="alert alert-danger clearfix" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<div class="pull-left"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($err_msg)?></div>
</div>
<?php endif; ?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">
		<!-- TOOLBAR -->
		<div class="mitranet-toolbar">
			<div class="mitranet-toolbar-left">
				<a href="/wg/vpn_wg_tunnels_edit.php" class="mitranet-btn" title="Add New Tunnel">
					<i class="fa-solid fa-plus text-primary"></i> <strong>New Tunnel</strong>
				</a>
			</div>
			<div class="mitranet-toolbar-right">
				<div class="mitranet-search-wrapper">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="text" id="grid-search" placeholder="Find tunnel..." onkeyup="filterWgGrid(this.value)">
				</div>
				<button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh">
					<i class="fa-solid fa-arrows-rotate"></i>
				</button>
			</div>
		</div>

		<!-- GRID -->
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="wg-grid-table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Status</th>
						<th>Mode</th>
						<th>Description</th>
						<th>Interface Address</th>
						<th>Listen Port</th>
						<th>Public Key</th>
						<th>Peers</th>
						<th class="text-center col-menu">Actions</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($tunnels)): ?>
					<tr><td colspan="9" class="text-center text-muted p-20">Belum ada tunnel WireGuard yang dikonfigurasi. Klik tombol "New Tunnel" di toolbar untuk membuat.</td></tr>
				<?php else: ?>
					<?php foreach ($tunnels as $tun): ?>
					<?php 
						$tname = $tun['name'] ?? 'wg0'; 
						$is_srv = empty($tun['mode']) || $tun['mode'] === 'server';
						$tun_up = !empty($tun['enabled']);
					?>
					<tr>
						<td><strong><code><?=htmlspecialchars($tname)?></code></strong></td>
						<td>
							<?php if ($tun_up): ?>
								<span class="label label-success"><i class="fa-solid fa-circle-check"></i> UP</span>
							<?php else: ?>
								<span class="label label-default"><i class="fa-solid fa-circle-stop"></i> DOWN</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($is_srv): ?>
								<span class="label label-primary"><i class="fa-solid fa-server"></i> Server</span>
							<?php else: ?>
								<span class="label label-info"><i class="fa-solid fa-network-wired"></i> Client Uplink</span>
							<?php endif; ?>
						</td>
						<td><?=htmlspecialchars($tun['description'] ?? 'WireGuard Tunnel')?></td>
						<td><code><?=htmlspecialchars($tun['address'] ?: '—')?></code></td>
						<td><?=htmlspecialchars($tun['listen_port'] ?? '51820')?></td>
						<td class="td-pubkey"
						    title="Klik untuk salin: <?=htmlspecialchars($tun['public_key'])?>"
						    onclick="navigator.clipboard.writeText('<?=htmlspecialchars($tun['public_key'])?>');this.style.color='green';">
							<?=htmlspecialchars(substr($tun['public_key'], 0, 16))?>...
						</td>
						<td><span class="badge"><?=count($tun['peers'] ?? [])?></span></td>
						<td class="text-center">
							<a class="btn btn-xs btn-success" href="/wg/vpn_wg_peers_edit.php?tun=<?=urlencode($tname)?>" title="Add Peer to <?=htmlspecialchars($tname)?>"><i class="fa-solid fa-user-plus"></i></a>
							<a class="btn btn-xs btn-primary" href="/wg/vpn_wg_tunnels_edit.php?tun=<?=urlencode($tname)?>" title="Edit Tunnel"><i class="fa-solid fa-pencil"></i></a>
							<a class="btn btn-xs btn-danger" href="?act=delete&amp;tun=<?=urlencode($tname)?>"
							   onclick="return confirm('Hapus tunnel <?=htmlspecialchars($tname)?> beserta seluruh konfigurasinya?');"
							   title="Delete Tunnel"><i class="fa-solid fa-trash-can"></i></a>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($tunnels)?> tunnels</span>
			</div>
			<div>
				<span class="text-muted">WireGuard Subsystem</span>
			</div>
		</div>

	</div>
</div>

<div class="infoblock">
	<i class="fa-solid fa-circle-info text-info"></i> Layanan service daemon WireGuard (Start / Stop / Restart) dikelola secara terpusat di menu <a href="/status_services.php"><strong>Status: Services</strong></a>.
</div>

<script>
function filterWgGrid(val) {
    val = (val || '').toLowerCase();
    $('#wg-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(val) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
