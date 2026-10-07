<?php
/*
 * firewall_virtual_ip.php - MitraNet Firewall: Virtual IPs
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Firewall", "Virtual IPs");
$selected_menu = "firewall";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Virtual IP Address</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed table-rowdblclickedit sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th>Virtual IP address</th>
					<th>Interface</th>
					<th>Type</th>
					<th>Description</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
10.20.30.254/24					</td>
					<td>
						WAN&nbsp;
					</td>
					<td>
						IP Alias					</td>
					<td>
						IP Virtual					</td>
					<td>
						<a class="fa-solid fa-pencil" title="Edit virtual ip" href="firewall_virtual_ip_edit.php?id=0"></a>
						<a class="fa-solid fa-trash-can"	title="Delete virtual ip" href="firewall_virtual_ip.php?act=del&amp;id=0" usepost></a>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<nav class="action-buttons">
	<a href="/firewall_virtual_ip_edit.php" class="btn btn-sm btn-success">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert"><div class="pull-left">The virtual IP addresses defined on this page may be used in <a href="firewall_nat.php">NAT</a> mappings.<br />Check the status of CARP Virtual IPs and interfaces <a href="status_carp.php">here</a>.</div></div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
