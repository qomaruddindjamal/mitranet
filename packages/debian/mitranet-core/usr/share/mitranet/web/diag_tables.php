<?php
/*
 * diag_tables.php - MitraNet Diagnostics: Tables
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Tables");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Table to Display</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Table</span>
		</label>
			<div class="col-sm-10">
		
			<select class="form-control" name="type" id="type">
		<option value="LAN__NETWORK">LAN__NETWORK</option><option value="OPT1__NETWORK">OPT1__NETWORK</option><option value="WAN__NETWORK">WAN__NETWORK</option><option value="WIREGUARD__NETWORK">WIREGUARD__NETWORK</option><option value="_nat64reserved_">_nat64reserved_</option><option value="bogons">bogons</option><option value="bogonsv6">bogonsv6</option><option value="snort2c">snort2c</option><option value="sshguard" selected>sshguard</option><option value="virusprot">virusprot</option>
	</select>
		

		<span class="help-block">Select a user-defined alias name or system table name to view its contents. <br/><br/>Aliases become Tables when loaded into the active firewall ruleset. The contents displayed on this page reflect the current addresses inside tables used by the firewall.</span>
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
