<?php
/*
 * services_captiveportal.php - MitraNet Services: Captive Portal
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "Captive Portal");
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Captive Portal Zones</h2></div>
		<div class="panel-body table-responsive">
			<table class="table table-striped table-hover table-rowdblclickedit sortable-theme-bootstrap" data-sortable>
				<thead>
					<tr>
						<th>Zone</th>
						<th>Interfaces</th>
						<th>Number of users</th>
						<th>Description</th>
						<th data-sortable="false">Actions</th>
					</tr>
				</thead>
				<tbody>

				</tbody>
			</table>
		</div>
	</div>

<nav class="action-buttons">
	<a href="/services_captiveportal_zones_edit.php" class="btn btn-success btn-sm">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>



<?php include(__DIR__ . '/../includes/foot.inc'); ?>
