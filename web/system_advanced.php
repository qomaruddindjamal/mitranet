<?php
/*
 * system_advanced.php - MitraNet Advanced System Settings
 * Adapted from pfSense system_advanced_admin.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Advanced", "Admin Access");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$cfg = MitraNetApi::getConfigStatus();
?>

<h2>System: Advanced: Admin Access</h2>

<ul class="nav nav-tabs">
	<li class="active"><a href="system_advanced.php">Admin Access</a></li>
	<li><a href="diag_backup.php">Transactions & Recovery</a></li>
</ul>

<div class="panel panel-default" style="margin-top: 15px;">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa fa-lock"></i> WebUI & Management API Settings</h2></div>
	<div class="panel-body form-horizontal">
		<div class="form-group">
			<label class="col-sm-3 control-label">Protocol</label>
			<div class="col-sm-6">
				<p class="form-control-static"><span class="label label-primary">HTTP / REST JSON</span> (Port 8443)</p>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">TCP Port</label>
			<div class="col-sm-6">
				<p class="form-control-static"><code>8443</code> (External) &rarr; <code>8000</code> (Internal PHP worker)</p>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">Session Security</label>
			<div class="col-sm-6">
				<p class="form-control-static">
					<i class="fa fa-shield-alt text-success"></i> Strict CSRF Protection Enabled, HttpOnly Session Cookies
				</p>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 control-label">Configuration Engine</label>
			<div class="col-sm-6">
				<p class="form-control-static">
					Version: <strong>v<?=htmlspecialchars($cfg['running_version'] ?? 1)?></strong> (Atomic JSON Candidate/Running Engine)
				</p>
			</div>
		</div>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
