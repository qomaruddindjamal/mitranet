<?php
/*
 * system_advanced.php - MitraNet Advanced System Settings
 * Adapted from pfSense system_advanced_admin.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Advanced", "Admin Access");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$tab_array = array(
    array("Admin Access", true, "system_advanced.php"),
    array("Firewall & NAT", false, "firewall_rules.php"),
    array("Networking", false, "system_routes.php"),
    array("Transactions & Backups", false, "diag_backup.php")
);
display_top_tabs($tab_array);

$cfg = MitraNetApi::getConfigStatus();
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa-solid fa-lock"></i> WebConfigurator Settings</h2></div>
	<div class="panel-body form-horizontal">
		<div class="form-group">
			<label class="col-sm-2 control-label">Protocol</label>
			<div class="col-sm-10">
				<label class="radio-inline"><input type="radio" checked disabled> HTTP</label>
				<label class="radio-inline"><input type="radio" disabled> HTTPS (SSL/TLS)</label>
				<span class="help-block">MitraNet Management REST API & WebUI protocol.</span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">TCP Port</label>
			<div class="col-sm-4">
				<input type="text" class="form-control" value="8443" readonly>
				<span class="help-block">Enter a custom port number for the WebConfigurator.</span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">Session Security</label>
			<div class="col-sm-10">
				<p class="form-control-static text-success">
					<i class="fa-solid fa-shield-halved"></i> Strict CSRF Protection Enabled, HttpOnly & SameSite Session Cookies.
				</p>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">Configuration Engine</label>
			<div class="col-sm-10">
				<p class="form-control-static">
					Version <strong>v<?=htmlspecialchars($cfg['running_version'] ?? 1)?></strong> (Native Linux Atomic Transactions Engine).
				</p>
			</div>
		</div>
	</div>
	<div class="panel-footer">
		<a href="diag_backup.php" class="btn btn-default"><i class="fa-solid fa-clock-rotate-left icon-embed-btn"></i>View Transaction History</a>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
