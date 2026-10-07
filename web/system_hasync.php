<?php
/*
 * system_hasync.php - MitraNet System: High Availability
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "High Availability");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">State Synchronization Settings (pfsync)</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Synchronize states</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="pfsyncenabled" id="pfsyncenabled" type="checkbox" value="on"> pfsync transfers state insertion, update, and deletion messages between firewalls.</label>
		

		<span class="help-block">Each firewall sends these messages out via multicast on a specified interface, using the PFSYNC protocol (IP Protocol 240). It also listens on that interface for similar messages from other firewalls, and imports them into the local state table.<br />This setting should be enabled on all members of a failover group.<br />Clicking "Save" will force a configuration sync if it is enabled! (see Configuration Synchronization Settings below)</span>
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Configuration Synchronization Settings (XMLRPC Sync)</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Synchronize Config to IP</span>
		</label>
			<div class="col-sm-10">
		
		<input class="form-control" name="synchronizetoip" id="synchronizetoip" type="text" placeholder="IP Address">
		

		<span class="help-block">Enter the IP address of the firewall to which the selected configuration sections should be synchronized.<br /><br />XMLRPC sync is currently only supported over connections using the same protocol and port as this system - make sure the remote system's port and protocol are set accordingly!<br />Do not use the Synchronize Config to IP and password option on backup cluster members!</span>
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
