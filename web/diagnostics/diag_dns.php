<?php
/*
 * diag_dns.php - MitraNet Diagnostics: DNS Lookup
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "DNS Lookup");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">DNS Lookup</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span class="element-required">Hostname</span>
		</label>
			<div class="col-sm-10">
		
		<input class="form-control" name="host" id="host" type="text" value="" placeholder="Hostname to look up.">
		

		
	</div>
		
	</div>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
