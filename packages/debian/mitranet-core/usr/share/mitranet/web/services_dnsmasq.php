<?php
/*
 * services_dnsmasq.php - MitraNet Services: DNS Forwarder
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "DNS Forwarder");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">General DNS Forwarder Options</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enable" id="enable" type="checkbox" value="yes" data-target=".toggle-dhcp" data-toggle="disable"> Enable DNS forwarder</label>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Host Overrides</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
			<thead>
				<tr>
					<th>Host</th>
					<th>Domain</th>
					<th>IP</th>
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
					<th>IP</th>
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
	<a href="/services_dnsmasq_edit.php" class="btn btn-sm btn-success btn-sm">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>

<div class="infoblock">
<div class="alert alert-info clearfix" role="alert"><div class="pull-left"><p>If the DNS forwarder is enabled, the DHCP service (if enabled) will automatically serve the LAN IP address as a DNS server to DHCP clients so they will use the forwarder.</p><p>The DNS forwarder will use the DNS servers entered in <a href="system.php">System > General Setup</a> or those obtained via DHCP or PPP on WAN if &quot;Allow DNS server list to be overridden by DHCP/PPP on WAN&quot; is checked. If that option is not used (or if a static IP address is used on WAN), at least one DNS server must be manually specified on the <a href="system.php">System > General Setup</a> page.</p></div></div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
