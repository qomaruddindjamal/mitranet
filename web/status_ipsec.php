<?php
/*
 * status_ipsec.php - MitraNet Status: IPsec
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "IPsec");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/status_ipsec.php" >Overview</a></li><li role="presentation"><a href="/status_ipsec_leases.php" >Leases</a></li><li role="presentation"><a href="/status_ipsec_sad.php" >SADs</a></li><li role="presentation"><a href="/status_ipsec_spd.php" >SPDs</a></li></ul>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">IPsec Status</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-condensed table-hover sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th>ID</th>
					<th>Description</th>
					<th>Local</th>
					<th>Remote</th>
					<th>Role</th>
					<th>Timers</th>
					<th>Algo</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody id="ipsec-body">
				<tr>
					<td colspan="10">
						<div class="alert alert-warning clearfix" role="alert"><div class="pull-left"><i class="fa-solid fa-gear fa-spin"></i>&nbsp;&nbsp;Collecting IPsec status information.</div></div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
