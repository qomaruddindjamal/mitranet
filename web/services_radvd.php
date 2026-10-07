<?php
/*
 * services_radvd.php - MitraNet Services: Router Advertisement
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "Router Advertisement");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/services_radvd.php?if=lan" >LAN</a></li><li role="presentation"><a href="/services_radvd.php?if=opt1" >WGVPN</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Router Advertisement</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span class="element-required">Router Mode</span>
		</label>
			<div class="col-sm-10">
		
			<select class="form-control" name="ramode" id="ramode">
		<option value="disabled">Disabled</option><option value="router">Router Only - RA Flags [none], Prefix Flags [router]</option><option value="unmanaged">Unmanaged - RA Flags [none], Prefix Flags [onlink, auto, router]</option><option value="managed">Managed - RA Flags [managed, other stateful], Prefix Flags [onlink, router]</option><option value="assist" selected>Assisted - RA Flags [managed, other stateful], Prefix Flags [onlink, auto, router]</option><option value="stateless_dhcp">Stateless DHCP - RA Flags [other stateful], Prefix Flags [onlink, auto, router]</option>
	</select>
		

		<span class="help-block">Select the Operating Mode for the Router Advertisement (RA) Daemon.<div class="infoblock"><dl class="dl-horizontal responsive"><dt>Disabled</dt><dd>RADVD will not be enabled on this interface.</dd><dt>Router Only</dt><dd>Will advertise this router.</dd><dt>Unmanaged</dt><dd>Will advertise this router with Stateless Address Auto-Configuration (SLAAC).</dd><dt>Managed</dt><dd>Will advertise this router with all configuration through a DHCPv6 server.</dd><dt>Assisted</dt><dd>Will advertise this router with configuration through a DHCPv6 server and/or SLAAC.</dd><dt>Stateless DHCP</dt><dd>Will advertise this router with SLAAC and other configuration information available via DHCPv6.</dd></dl>It is not required to activate DHCPv6 server on pfSense when set to "Managed", "Assisted" or "Stateless DHCP", it can be another host on the network.</div></span>
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">DNS Configuration</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable DNS</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="radvd-dns" id="radvd-dns" type="checkbox" value="yes" checked="checked"> Provide DNS Configuration via the RA Daemon</label>
		

		<span class="help-block">Unchecking this box disables the RA Daemon RDNSS/DNSSL options. Use with caution, as the resulting behavior may violate some RFCs.</span>
	</div>
		
	</div>



<div class="infoblock"><dl class="dl-horizontal responsive"><dt>Disabled</dt><dd>RADVD will not be enabled on this interface.</dd><dt>Router Only</dt><dd>Will advertise this router.</dd><dt>Unmanaged</dt><dd>Will advertise this router with Stateless Address Auto-Configuration (SLAAC).</dd><dt>Managed</dt><dd>Will advertise this router with all configuration through a DHCPv6 server.</dd><dt>Assisted</dt><dd>Will advertise this router with configuration through a DHCPv6 server and/or SLAAC.</dd><dt>Stateless DHCP</dt><dd>Will advertise this router with SLAAC and other configuration information available via DHCPv6.</dd></dl>It is not required to activate DHCPv6 server on pfSense when set to "Managed", "Assisted" or "Stateless DHCP", it can be another host on the network.</div></span>
	</div>
		
	</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
