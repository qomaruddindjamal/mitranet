<?php
/*
 * services_dhcpv6_settings.php - MitraNet Services: DHCPv6 Server
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "DHCPv6 Server");
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/services_dhcpv6.php?if=lan" >LAN</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">General Settings</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>DHCP Backend</span>
		</label>
			<div class="col-sm-10">
		
		ISC DHCP
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Primary Address Pool</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Prefix</span>
		</label>
			<div class="col-sm-10">
		
		Delegated Prefix: WAN/0/64
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Prefix Delegation Pool</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Prefix Delegation Range</span>
		</label>
			<div class="col-sm-5">
		
		<input class="form-control trim" name="prefixrange_from" id="prefixrange_from" type="text">
		

		<span class="help-block">From</span>
	</div>	<div class="col-sm-5">
		
		<input class="form-control trim" name="prefixrange_to" id="prefixrange_to" type="text">
		

		<span class="help-block">To</span>
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Server Options</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable DNS</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="dhcp6c-dns" id="dhcp6c-dns" type="checkbox" value="yes" checked="checked"> Provide DNS servers to DHCPv6 clients</label>
		

		<span class="help-block">Unchecking this box disables the dhcp6.name-servers option. Use with caution, as the resulting behavior may violate RFCs and lead to unintended client behavior.</span>
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Other DHCPv6 Options</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Domain Name</span>
		</label>
			<div class="col-sm-10">
		
		<input class="form-control autotrim" name="domain" id="domain" type="text" placeholder="home.arpa">
		

		<span class="help-block">The default is to use the domain name of this firewall as the default domain name provided by DHCP. An alternate domain name may be specified here.</span>
	</div>
		
	</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">DHCPv6 Static Mappings</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr>
					<th><!-- status icons --></th>
					<th>IPv6 Address</th>
					<th>Hostname</th>
					<th>DUID</th>
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
	<a href="/services_dhcpv6_edit.php?if=lan" class="btn btn-success"/>
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add Static Mapping	</a>
</nav>



<?php include(__DIR__ . '/../includes/foot.inc'); ?>
