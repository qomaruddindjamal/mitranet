<?php
/*
 * status_filter_reload.php - MitraNet Status: Filter Reload
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "Filter Reload");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Filter Reload</h2></div>
	<div class="panel-body">
		<div class="content">
			<form action="status_filter_reload.php" method="post" name="filter">
				<button type="submit" class="btn btn-success" value="Reload Filter" name="reloadfilter" id="reloadfilter"><i class="fa-solid fa-arrows-rotate icon-embed-btn"></i>Reload Filter</button>
			</form>
			<br />
			<div id="doneurl"></div>
			<br />
			<div class="panel panel-default">
				<div class="panel-heading"><h2 class="panel-title">Reload status</h2></div>
				<div class="panel-body" id="status">
				</div>
			</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
