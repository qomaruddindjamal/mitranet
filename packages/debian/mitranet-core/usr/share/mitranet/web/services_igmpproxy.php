<?php
/*
 * services_igmpproxy.php - MitraNet Services: IGMP Proxy
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "IGMP Proxy");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">General IGMP Options</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Enable</span>
		</label>
			<div class="checkbox col-sm-10">
		
		<label class="chkboxlbl"><input name="enable" id="enable" type="checkbox" value="yes"> Enable IGMP</label>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title">IGMP Proxy</h2></div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-condensed table-rowdblclickedit">
					<thead>
						<tr>
							<th>Name</th>
							<th>Type</th>
							<th>Values</th>
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
	<a href="/services_igmpproxy_edit.php" class="btn btn-success btn-sm">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>

<div class="infoblock">
<div class="alert alert-info clearfix" role="alert"><div class="pull-left">Please add the interface for upstream, the allowed subnets, and the downstream interfaces for the proxy to allow. Only one "upstream" interface can be configured.</div></div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
