<?php
/*
 * diag_authentication.php - MitraNet Diagnostics: Authentication
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Authentication");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Authentication Test</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span class="element-required">Authentication Server</span>
		</label>
			<div class="col-sm-10">
		
			<select class="form-control" name="authmode" id="authmode">
		<option value="Local Database" selected>Local Database</option>
	</select>
		

		<span class="help-block">Select the authentication server to test against.</span>
	</div>
		
	</div>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
