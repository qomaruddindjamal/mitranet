<?php
/*
 * system_advanced_admin.php - MitraNet System: Advanced
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Advanced");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/system_advanced_admin.php" >Admin Access</a></li><li role="presentation"><a href="/system_advanced_firewall.php" >Firewall &amp; NAT</a></li><li role="presentation"><a href="/system_advanced_network.php" >Networking</a></li><li role="presentation"><a href="/system_advanced_misc.php" >Miscellaneous</a></li><li role="presentation"><a href="/system_advanced_sysctl.php" >System Tunables</a></li><li role="presentation"><a href="/system_advanced_notifications.php" >Notifications</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">webConfigurator</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Protocol</span>
		</label>
			<div class="checkbox col-sm-5">
		
		<label class="chkboxlbl"><input name="webguiproto" id="webguiproto_http:a3bd" type="radio" value="http"> HTTP</label>
		

		
	</div>	<div class="checkbox col-sm-5">
		
		<label class="chkboxlbl"><input name="webguiproto" id="webguiproto_https:a3d6" type="radio" value="https" checked="checked"> HTTPS (SSL/TLS)</label>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Secure Shell</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Secure Shell Server</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enablesshd" id="enablesshd" type="checkbox" value="yes" checked="checked"> Enable Secure Shell</label>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Login Protection</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Threshold</span>
		</label>
			<div class="col-sm-10">
		
		<input class="form-control" name="sshguard_threshold" id="sshguard_threshold" type="number" value="" min="10" step="10" placeholder="30">
		

		<span class="help-block">Block attackers when their cumulative attack score exceeds threshold.  Most attacks have a score of 10.</span>
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Serial Communications</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Serial Terminal</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enableserial" id="enableserial" type="checkbox" value="yes"> Enables the first serial port with 115200/8/N/1 by default, or another speed selectable below.</label>
		

		<span class="help-block">Note:	This will redirect the console output and messages to the serial port. The console menu can still be accessed from the internal video card/keyboard. A <b>null modem</b> serial cable or adapter is required to use the serial console.</span>
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Console Options</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Console menu</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="disableconsolemenu" id="disableconsolemenu" type="checkbox" value="yes"> Password protect the console menu</label>
		

		
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
