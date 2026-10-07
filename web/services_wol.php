<?php
/*
 * services_wol.php - MitraNet Services: Wake-on-LAN
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "Wake-on-LAN");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Wake-on-LAN</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span class="element-required">Interface</span>
		</label>
			<div class="col-sm-10">
		
			<select class="form-control" name="if" id="if">
		<option value="wan">WAN</option><option value="lan" selected>LAN</option><option value="opt1">WGVPN</option>
	</select>
		

		<span class="help-block">Choose which interface the host to be woken up is connected to.</span>
	</div>
		
	</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title">Wake-on-LAN Devices</h2>
	</div>


	<div class="panel-body">
		<p class="text-danger" style="margin-left: 8px;margin-bottom:0px;">Click the MAC address to wake up an individual device.</p>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-rowdblclickedit">
				<thead>
					<tr>
						<th>Interface</th>
						<th>MAC address</th>
						<th>Description</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
									</tbody>
			</table>
		</div>
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
