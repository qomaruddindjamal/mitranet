<?php
/*
 * diag_pf_info.php - MitraNet Diagnostics: pfInfo
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "pfInfo");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Auto Update Page</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Refresh</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="refresh" id="refresh" type="checkbox" value="yes" checked="checked"> Automatically refresh the output below</label>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Output</h2></div>
	<div class="panel panel-body">
		<pre id="xhrOutput">Gathering PF information, please wait...</pre>
	</div>
</div>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
