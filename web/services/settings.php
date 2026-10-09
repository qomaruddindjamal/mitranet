<?php
/*
 * services_acb_settings.php - MitraNet Services: Auto Config Backup
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "Auto Config Backup");
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/services_acb_settings.php" >Settings</a></li><li role="presentation"><a href="/services_acb.php" >Restore</a></li><li role="presentation"><a href="/services_acb_backup.php" >Backup Now</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Auto Config Backup</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable ACB</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enable" id="enable" type="checkbox" value="yes"> Enable automatic configuration backups</label>
		

		<span class="help-block">Auto Configuration Backup automatically encrypts configuration backup content using the Encryption Password below and then securely uploads the encrypted backup over HTTPS to Netgate servers.</span>
	</div>
		
	</div>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
