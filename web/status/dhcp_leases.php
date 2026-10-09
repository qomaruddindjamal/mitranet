<?php
/*
 * status_dhcp_leases.php - MitraNet Status: DHCP Leases
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "DHCP Leases");
$selected_menu = "status";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default" id="search-panel">
	<div class="panel-heading">
		<h2 class="panel-title">
			Search			<span class="widget-heading-icon pull-right">
				<a data-toggle="collapse" href="#search-panel_panel-body">
					<i class="fa-solid fa-plus-circle"></i>
				</a>
			</span>
		</h2>
	</div>
	<div id="search-panel_panel-body" class="panel-body collapse in">
		<div class="form-group">
			<label class="col-sm-2 control-label">
				Search Term			</label>
			<div class="col-sm-5"><input class="form-control" name="searchstr" id="searchstr" type="text"/></div>
			<div class="col-sm-2">
				<select id="where" class="form-control">
					<option value="1" selected>All</option>
					<option value="2">Lease Type</option>
					<option value="3">Client Status</option>
					<option value="4">IP Address</option>
					<option value="5">MAC Address</option>
					<option value="6">Client ID</option>
					<option value="7">Hostname</option>
					<option value="8">Description</option>
					<option value="9">Start</option>
					<option value="10">End</option>

				</select>
			</div>
			<div class="col-sm-3">
				<a id="btnsearch" title="Search" class="btn btn-primary btn-sm"><i class="fa-solid fa-search icon-embed-btn"></i>Search</a>
				<a id="btnclear" title="Clear" class="btn btn-info btn-sm"><i class="fa-solid fa-undo icon-embed-btn"></i>Clear</a>
			</div>
			<div class="col-sm-10 col-sm-offset-2">
				<span class="help-block">Enter a search string or *nix regular expression to filter entries.</span>
			</div>
		</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Leases</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th data-sortable="false"><!-- icon --></th>
					<th>IP Address</th>
					<th>MAC Address</th>
					<th>Hostname</th>
					<th>Description</th>
					<th>Start</th>
					<th>End</th>
					<th data-sortable="false">Actions</th>
				</tr>
			</thead>
			<tbody id="leaselist">

				<tr>
					<td></td>
					<td colspan="10">No leases to display</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Lease Utilization</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th>Interface</th>
					<th>Pool Start</th>
					<th>Pool End</th>
					<th>Used</th>
					<th>Capacity</th>
					<th data-sortable="false" width="25%">Utilization</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="6">No leases are in use</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<nav class="action-buttons">
	<a class="btn btn-info" href="/status_dhcp_leases.php?all=1"><i class="fa-solid fa-plus-circle icon-embed-btn"></i>Show All Configured Leases</a>
	<a class="btn btn-danger no-confirm" id="cleardhcp"><i class="fa-solid fa-trash-can icon-embed-btn"></i>Clear All DHCP Leases</a>
</nav>



<?php include(__DIR__ . '/../includes/foot.inc'); ?>
