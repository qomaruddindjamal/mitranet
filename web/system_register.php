<?php
/*
 * system_register.php - MitraNet System: Register
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Register");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Register pfSense</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span class="element-required">Activation token</span>
		</label>
			<div class="col-sm-10">
		
			<textarea rows="10" class="row-fluid col-sm-8" name="activation_token" id="activation_token" ></textarea>
		

		
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
