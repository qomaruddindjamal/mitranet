<?php
/*
 * status_monitoring.php - MitraNet Status: Monitoring
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "Monitoring");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/status_monitoring.php?view=default" >Default</a></li></ul>

<div class="panel panel-default" id="monitoring-settings-panel">
		<div class="panel-heading">
			<h2 class="panel-title">Settings				<span class="widget-heading-icon">
					<a data-toggle="collapse" href="#monitoring-settings-panel_panel-body">
						<i class="fa-solid fa-plus-circle"></i>
					</a>
				</span>
			</h2>
		</div>
		<div id="monitoring-settings-panel_panel-body" class="panel-body collapse in">
			<div class="form-group">
				<label class="col-sm-2 control-label">
					Left Axis
				</label>
				<div class="col-sm-5">
					<select class="form-control" id="category-left" name="category-left">
						<option value="system" selected>System</option>
						<option value="traffic">Traffic</option>
						<option value="packets">Packets</option>
						<option value="quality">Quality</option>
												<option value="none">None</option>
					</select>

					<span class="help-block">Category</span>
				</div>
				<div class="col-sm-5">
					<select class="form-control" id="graph-left" name="graph-left">
						<option value="system-states">States</option>
						<option value="system-processor" selected>Processes</option>
						<option value="system-memory">Memory</option>
						<option value="system-mbuf">Mbuf Clusters</option>
						<option value="system-sensors">Thermal Sensors</option>
					</select>

					<span class="help-block">Graph</span>
				</div>
			</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title">Interactive Graph</h2>
	</div>
	<div class="panel-body">
		<div class="alert alert-info" id="loading-msg">Loading Graph...</div>
		<div id="chart-error" class="alert alert-danger" hidden></div>
		<div id="monitoring-chart" class="d3-chart">
			<svg id="monitoring-svg"></svg>
		</div>
	</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title">Data Summary</h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table id="summary" class="table table-striped table-hover">
				<thead>
					<tr>
						<th></th>
						<th>Minimum</th>
						<th>Average</th>
						<th>Maximum</th>
						<th>Last</th>
						<th>95th Percentile</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>



<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			<p>This tool allows comparison of RRD databases on two different Y axes.</p>

			<p>Graph data series visibility can be toggled by clicking on the labels in the legend. A single click toggles visibility and a double click hides all the others, except for that one.</p>
		</div>
	</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
