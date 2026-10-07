<?php
/*
 * status_graph.php - MitraNet Status: Traffic Graph
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "Traffic Graph");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Graph Settings</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Traffic Graph</span>
		</label>
			<div class="col-sm-2">
		
			<select class="form-control" name="if" id="if">
		<option value="wan" selected>WAN</option><option value="lan">LAN</option><option value="opt1">WGVPN</option>
	</select>
		

		<span class="help-block">Interface</span>
	</div>	<div class="col-sm-2">
		
			<select class="form-control" name="sort" id="sort">
		<option value="in">Bandwidth In</option><option value="out">Bandwidth Out</option>
	</select>
		

		<span class="help-block">Sort by</span>
	</div>	<div class="col-sm-2">
		
			<select class="form-control" name="filter" id="filter">
		<option value="local">Local</option><option value="remote">Remote</option><option value="all">All</option>
	</select>
		

		<span class="help-block">Filter</span>
	</div>	<div class="col-sm-2">
		
			<select class="form-control" name="hostipformat" id="hostipformat">
		<option value="" selected>IP Address</option><option value="hostname">Host Name</option><option value="descr">Description</option><option value="fqdn">FQDN</option>
	</select>
		

		<span class="help-block">Display</span>
	</div>	<div class="col-sm-2">
		
			<select class="form-control" name="mode" id="mode">
		<option value="rate">rate (standard)</option><option value="iftop">iftop (experimental)</option>
	</select>
		

		<span class="help-block">Mode</span>
	</div>
		
	</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title">Traffic Graph</h2>
	</div>
	<div class="panel-body">
		<div class="col-sm-6">
			<div id="traffic-chart-wan" class="d3-chart traffic-widget-chart">
				<svg></svg>
			</div>
		</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
