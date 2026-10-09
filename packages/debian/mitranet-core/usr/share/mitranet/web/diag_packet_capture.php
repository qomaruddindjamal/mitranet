<?php
/*
 * diag_packet_capture.php - MitraNet Diagnostics: Packet Capture
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Packet Capture");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Packet Capture Options</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Capture Options</span>
		</label>
			<div class="col-sm-4">
		
			<select class="form-control" name="interface" id="interface">
		<option value="vtnet0">WAN (vtnet0)</option><option value="vtnet1">LAN (vtnet1)</option><option value="enc0">IPsec (enc0)</option><option value="lo0">Localhost (lo0)</option><option value="tap0">unassigned (tap0)</option>
	</select>
		

		<span class="help-block">Interface to capture packets on.</span>
	</div>	<div class="col-sm-2">
		
			<select class="form-control match-selection" name="filter" id="filter">
		<option value="29" selected>Custom Filter</option><option value="20">Everything</option><option value="21">Only Untagged</option><option value="22">Only Tagged</option>
	</select>
		

		<span class="help-block">Filter preset.</span>
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
