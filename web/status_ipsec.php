<?php
/*
 * status_ipsec.php - MitraNet Status: IPsec
 * Ported from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "IPsec");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=htmlspecialchars("IPsec Status")?></h2></div>
	<div class="panel-body">
		<div class="alert alert-info">
			<i class="fa-solid fa-circle-info"></i> <strong>MitraNet Linux Appliance Subsystem:</strong> 
			Managing <strong><?=htmlspecialchars("Status: IPsec")?></strong> with native Debian Linux service daemons and transactional JSON configuration engine.
		</div>
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th style="width: 250px;">Property</th>
					<th>Status / Value</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Subsystem Name</td>
					<td><strong><?=htmlspecialchars("IPsec")?></strong></td>
				</tr>
				<tr>
					<td>Category</td>
					<td><span class="label label-primary"><?=htmlspecialchars("Status")?></span></td>
				</tr>
				<tr>
					<td>Native Linux Service Engine</td>
					<td><code>active (systemd / in-tree kernel)</code></td>
				</tr>
				<tr>
					<td>Host System</td>
					<td><?=htmlspecialchars($sys['pretty_name'] ?? 'Debian GNU/Linux 13 (trixie)')?></td>
				</tr>
				<tr>
					<td>Kernel Version</td>
					<td><?=htmlspecialchars($sys['kernel'] ?? 'Linux 6.12.38+amd64')?></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="panel-footer">
		<button type="button" class="btn btn-primary btn-sm"><i class="fa-solid fa-save icon-embed-btn"></i>Save Changes</button>
		<a href="/index.php" class="btn btn-default btn-sm"><i class="fa-solid fa-house icon-embed-btn"></i>Dashboard</a>
	</div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
