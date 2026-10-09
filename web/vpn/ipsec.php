<?php
/*
 * vpn_ipsec.php - MitraNet VPN: IPsec
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("VPN", "IPsec");
$selected_menu = "vpn";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/vpn_ipsec.php" >Tunnels</a></li><li role="presentation"><a href="/vpn_ipsec_mobile.php" >Mobile Clients</a></li><li role="presentation"><a href="/vpn_ipsec_keys.php" >Pre-Shared Keys</a></li><li role="presentation"><a href="/vpn_ipsec_settings.php" >Advanced Settings</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title">IPsec Tunnels</h2></div>
		<div class="panel-body table-responsive">
			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
						<th>ID</th>
						<th>IKE</th>
						<th>Remote Gateway</th>
						<th>Auth/Mode</th>
						<th>P1 Protocol</th>
						<th>P1 Transforms</th>
						<th>P1 DH-Group</th>
						<th>P1 Description</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody class="p1-entries">
				</tbody>
			</table>
		</div>
	</div>

<nav class="action-buttons">
		<a href="/vpn_ipsec_phase1.php" class="btn btn-success btn-sm"  usepost>
			<i class="fa-solid fa-plus icon-embed-btn"></i>
			Add P1		</a>
	</nav>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert"><div class="pull-left">The IPsec status can be checked at <a href="status_ipsec.php">Status:IPsec</a>.<br />IPsec debug mode can be enabled at <a href="vpn_ipsec_settings.php">VPN:IPsec:Advanced Settings</a>.<br />IPsec can be set to prefer older SAs at <a href="vpn_ipsec_settings.php">VPN:IPsec:Advanced Settings</a>.</div></div>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
