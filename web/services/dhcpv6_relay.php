<?php
/*
 * services_dhcpv6_relay.php - MitraNet Services: DHCPv6 Relay
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "DHCPv6 Relay");
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">DHCPv6 Relay Configuration</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enable" id="enable" type="checkbox" value="yes" disabled> Enable DHCPv6 Relay</label>
		

		
	</div>
		
	</div>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
