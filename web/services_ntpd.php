<?php
/*
 * services_ntpd.php - MitraNet Services: NTP
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "NTP");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/services_ntpd.php" >Settings</a></li><li role="presentation"><a href="/services_ntpd_acls.php" >ACLs</a></li><li role="presentation"><a href="/services_ntpd_gps.php" >Serial GPS</a></li><li role="presentation"><a href="/services_ntpd_pps.php" >PPS</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">NTP Server Configuration</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enable" id="enable" type="checkbox" value="yes" checked="checked"> Enable NTP Server</label>
		

		<span class="help-block">You may need to disable NTP if pfSense is running in a virtual machine and the host is responsible for the clock.</span>
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
