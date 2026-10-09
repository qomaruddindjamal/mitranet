<?php
/*
 * firewall_schedule.php - MitraNet Firewall: Schedules
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Firewall", "Schedules");
$selected_menu = "firewall";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Schedules</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
			<thead>
				<tr>
					<th><!--"Active" indicator--></th>
					<th>Name</th>
					<th>Range: Date / Times / Name</th>
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
	<a href="/firewall_schedule_edit.php" class="btn btn-sm btn-success">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert"><div class="pull-left">Schedules act as placeholders for time ranges to be used in firewall rules.</div></div>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
