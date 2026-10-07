<?php
/*
 * services_snmp.php - MitraNet Services: SNMP
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "SNMP");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">SNMP Daemon</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enable" id="enable" type="checkbox" value="yes"> Enable the SNMP Daemon and its controls</label>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">SNMP Daemon Settings</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Polling Port</span>
		</label>
			<div class="col-sm-10">
		
		<input class="form-control" name="pollport" id="pollport" type="text" value="161">
		

		<span class="help-block">Enter the port to accept polling events on (default 161).</span>
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">SNMP Traps Enable</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="trapenable" id="trapenable" type="checkbox" value="yes" data-target=".toggle-traps" data-toggle="collapse"> Enable the SNMP Trap and its controls</label>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">SNMP Modules</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			SNMP modules
		</label>

		<div class="checkbox multi col-sm-10">
			<label class="chkboxlbl"><input name="mibii" id="mibii" type="checkbox" value="yes" checked="checked"> MibII</label><label class="chkboxlbl"><input name="netgraph" id="netgraph" type="checkbox" value="yes" checked="checked"> Netgraph</label><label class="chkboxlbl"><input name="pf" id="pf" type="checkbox" value="yes" checked="checked"> PF</label><label class="chkboxlbl"><input name="hostres" id="hostres" type="checkbox" value="yes" checked="checked"> Host Resources</label><label class="chkboxlbl"><input name="ucd" id="ucd" type="checkbox" value="yes" checked="checked"> UCD</label><label class="chkboxlbl"><input name="regex" id="regex" type="checkbox" value="yes" checked="checked"> Regex</label>
		</div>

		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Interface Binding</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Internet Protocol</span>
		</label>
			<div class="col-sm-10">
		
			<select class="form-control" name="ipprotocol" id="ipprotocol">
		<option value="inet4">IPv4</option><option value="inet6">IPv6</option><option value="inet46">IPv4+IPv6</option>
	</select>
		

		
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
