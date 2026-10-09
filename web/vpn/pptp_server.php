<?php
/*
 * services_pppoe.php - MitraNet Services: PPPoE Server
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "PPPoE Server");
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">PPPoE Server</h2></div>
	<div class="panel-body">

	<div class="table-responsive">
	<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
		<thead>
			<tr>
				<th>Interface</th>
				<th>Local IP</th>
				<th>Number of users</th>
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
	<a href="/services_pppoe_edit.php" class="btn btn-success">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>



<?php include(__DIR__ . '/../includes/foot.inc'); ?>
