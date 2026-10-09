<?php
/*
 * vpn_l2tp.php - MitraNet VPN: L2TP
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("VPN", "L2TP");
$selected_menu = "vpn";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/vpn_l2tp.php" >Configuration</a></li><li role="presentation"><a href="/vpn_l2tp_users.php" >Users</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Enable L2TP</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="mode" id="mode" type="checkbox" value="server"> Enable L2TP server</label>
		

		
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
