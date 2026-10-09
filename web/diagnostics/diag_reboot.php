<?php
/*
 * diag_reboot.php - MitraNet Diagnostics: Reboot
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Reboot");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Reboot Method</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span class="element-required">Reboot Method</span>
		</label>
			<div class="col-sm-10">
		
			<select class="form-control" name="rebootmode" id="rebootmode">
		<option value="reboot">Normal Reboot</option><option value="reroot">Reroot</option>
	</select>
		

		<span class="help-block">Select "Normal reboot" to reboot the system immediately.<br />Select "Reroot" to stop processes, remount disks and re-run startup sequence.</span>
	</div>
		
	</div>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
