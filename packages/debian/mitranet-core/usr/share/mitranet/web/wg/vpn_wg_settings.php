<?php
/*
 * vpn_wg_settings.php - MitraNet WireGuard Global Settings
 * Adapted from pfSense /wg/vpn_wg_settings.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("VPN", "WireGuard", "Settings");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$wg = MitraNetApi::getWireGuard();

$tab_array = array(
    array("Tunnels", false, "/wg/vpn_wg_tunnels.php"),
    array("Peers", false, "/wg/vpn_wg_peers.php"),
    array("Settings", true, "/wg/vpn_wg_settings.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
display_top_tabs($tab_array, false, 'pills');
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">WireGuard Subsystem Configuration</h2></div>
	<div class="panel-body">
		<form method="post" class="form-horizontal">
			<div class="form-group">
				<label class="col-sm-3 control-label">Linux Kernel Driver</label>
				<div class="col-sm-9">
					<p class="form-control-static text-success">
						<i class="fa-solid fa-check-circle"></i> In-Tree Linux 6.12 WireGuard Kernel Module (wireguard.ko)
					</p>
					<span class="help-block">MitraNet utilizes native Linux kernel WireGuard for line-rate throughput and hardware crypto acceleration.</span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label">Service Daemon Status</label>
				<div class="col-sm-9">
					<p class="form-control-static">
						<?php if (!empty($wg['running'])): ?>
							<span class="label label-success"><i class="fa-solid fa-check"></i> Active (systemd: wg-quick@wg0)</span>
						<?php else: ?>
							<span class="label label-danger"><i class="fa-solid fa-ban"></i> Inactive</span>
						<?php endif; ?>
					</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label">Configuration Path</label>
				<div class="col-sm-9">
					<p class="form-control-static"><code>/etc/wireguard/wg0.conf</code></p>
				</div>
			</div>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
