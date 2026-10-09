<?php
/*
 * vpn_openvpn_server.php - MitraNet VPN: OpenVPN
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("VPN", "OpenVPN");
$selected_menu = "vpn";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/vpn_openvpn_server.php" >Servers</a></li><li role="presentation"><a href="/vpn_openvpn_client.php" >Clients</a></li><li role="presentation"><a href="/vpn_openvpn_csc.php" >Client Specific Overrides</a></li><li role="presentation"><a href="/wizard.php?xml=openvpn_wizard.xml" >Wizards</a></li></ul>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">OpenVPN Servers</h2></div>
		<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
			<thead>
				<tr>
					<th>Interface</th>
					<th data-sortable-type="alpha">Protocol / Port</th>
					<th>Tunnel Network</th>
					<th>Mode / Crypto</th>
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
	<a href="/vpn_openvpn_server.php?act=new" class="btn btn-sm btn-success btn-sm">
	<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>



<?php include(__DIR__ . '/../includes/foot.inc'); ?>
