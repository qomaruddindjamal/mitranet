<?php
/*
 * services_dyndns.php - MitraNet Services: Dynamic DNS
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "Dynamic DNS");
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/services_dyndns.php" >Dynamic DNS Clients</a></li><li role="presentation"><a href="/services_rfc2136.php" >RFC 2136 Clients</a></li><li role="presentation"><a href="/services_checkip.php" >Check IP Services</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title">Dynamic DNS Clients</h2></div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
					<thead>
						<tr>
							<th>Status</th>
							<th>Interface</th>
							<th>Service</th>
							<th>Hostname</th>
							<th>Cached IP</th>
							<th>Description</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
			  </table>
			</div>
		</div>

<nav class="action-buttons">
	<a href="/services_dyndns_edit.php" class="btn btn-sm btn-success btn-sm">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>



<?php include(__DIR__ . '/../includes/foot.inc'); ?>
