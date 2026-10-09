<?php
/*
 * services_unbound.php - MitraNet Services: DNS Resolver
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "DNS Resolver");
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/services_unbound.php" >General Settings</a></li><li role="presentation"><a href="/services_unbound_advanced.php" >Advanced Settings</a></li><li role="presentation"><a href="/services_unbound_acls.php" >Access Lists</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">General DNS Resolver Options</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enable" id="enable" type="checkbox" value="yes" checked="checked"> Enable DNS resolver</label>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Host Overrides</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
			<thead>
				<tr>
					<th>Host</th>
					<th>Parent domain of host</th>
					<th>IP to return for host</th>
					<th>Description</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Domain Overrides</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
			<thead>
				<tr>
					<th>Domain</th>
					<th>Lookup Server IP Address</th>
					<th>Description</th>
					<th>Actions</th>
				</tr>
			</thead>

			<tbody>
			</tbody>
		</table>
	</div>
</div>

<nav class="action-buttons">
	<a href="/services_unbound_host_edit.php" class="btn btn-success">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert"><div class="pull-left">If the DNS Resolver is enabled, the DHCP service (if enabled) will automatically serve the LAN IP address as a DNS server to DHCP clients so they will use the DNS Resolver. If Forwarding is enabled, the DNS Resolver will use the DNS servers entered in <a href="system.php">System &gt; General Setup</a> or those obtained via DHCP or PPP on WAN if &quot;Allow DNS server list to be overridden by DHCP/PPP on WAN&quot; is checked.</div></div>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
