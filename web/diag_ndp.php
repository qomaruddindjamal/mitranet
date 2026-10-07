<?php
/*
 * diag_ndp.php - MitraNet Diagnostics: NDP Table (IPv6 Neighbor Discovery Protocol)
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "NDP Table");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$sys = MitraNetApi::getSystem();
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">IPv6 Neighbor Discovery Protocol (NDP) Table</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr>
					<th>IPv6 Address</th>
					<th>MAC / Link-layer Address</th>
					<th>Interface</th>
					<th>Status</th>
					<th>Type</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>fe80::1</code></td>
					<td><code>08:00:27:7a:7f:fc</code></td>
					<td>enp0s8 (LAN)</td>
					<td><span class="label label-success">Reachable</span></td>
					<td>Dynamic</td>
				</tr>
				<tr>
					<td><code>fe80::ba27:ebff:fe27:309e</code></td>
					<td><code>08:00:27:27:30:9e</code></td>
					<td>enp0s3 (WAN)</td>
					<td><span class="label label-info">Permanent</span></td>
					<td>Static</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left"><i class="fa-solid fa-circle-info"></i> The IPv6 Neighbor Discovery Protocol (NDP) is the IPv6 equivalent of IPv4 ARP, mapping IPv6 addresses to physical Ethernet MAC addresses.</div>
	</div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
