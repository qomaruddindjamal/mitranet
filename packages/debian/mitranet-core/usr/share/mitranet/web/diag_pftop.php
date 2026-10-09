<?php
/*
 * diag_pftop.php - MitraNet Diagnostics: pfTop
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "pfTop");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">pfTop Configuration</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>View</span>
		</label>
			<div class="col-sm-10">
		
			<select class="form-control" name="viewtype" id="viewtype">
		<option value="default" selected>default</option><option value="label">label</option><option value="long">long</option><option value="queue">queue</option><option value="rules">rules</option><option value="size">size</option><option value="speed">speed</option><option value="state">state</option><option value="time">time</option>
	</select>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Output</h2></div>
	<div class="panel panel-body">
		<pre id="xhrOutput">Gathering pfTOP activity, please wait...</pre>
	</div>
</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
