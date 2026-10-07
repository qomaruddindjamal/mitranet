<?php
/*
 * system.php - MitraNet System General Setup
 * Adapted from pfSense system.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "General Setup");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_hostname = trim($_POST['hostname'] ?? '');
    if (!empty($new_hostname)) {
        $res = MitraNetApi::updateHostname($new_hostname);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            $msg = "System hostname updated successfully to '" . htmlspecialchars($new_hostname) . "'.";
        } else {
            $err = $res['data']['error'] ?? 'Failed to update system hostname.';
        }
    } else {
        $err = "Hostname cannot be empty.";
    }
}

$sys = MitraNetApi::getSystem();
$hostname = $sys['hostname'] ?? gethostname();
?>

<?php if (!empty($msg)): ?>
	<div class="alert alert-success alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<i class="fa-solid fa-check"></i> <?=htmlspecialchars($msg)?>
	</div>
<?php endif; ?>
<?php if (!empty($err)): ?>
	<div class="alert alert-danger alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<i class="fa-solid fa-circle-exclamation"></i> <?=htmlspecialchars($err)?>
	</div>
<?php endif; ?>

<form method="post" action="system.php" class="form-horizontal">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><i class="fa-solid fa-server"></i> System Identification</h2></div>
		<div class="panel-body">
			<div class="form-group">
				<label class="col-sm-2 control-label" for="hostname"><span class="element-required">*</span>Hostname</label>
				<div class="col-sm-6">
					<input type="text" class="form-control" id="hostname" name="hostname" value="<?=htmlspecialchars($hostname)?>" required>
					<span class="help-block">Name of the firewall host, without domain part.</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="domain"><span class="element-required">*</span>Domain</label>
				<div class="col-sm-6">
					<input type="text" class="form-control" id="domain" name="domain" value="localdomain" readonly>
					<span class="help-block">Domain name for the firewall appliance.</span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">Operating System</label>
				<div class="col-sm-6">
					<p class="form-control-static">
						<strong><?=htmlspecialchars($sys['pretty_name'] ?? 'MitraNet Rinjani')?></strong> (<?=htmlspecialchars($sys['kernel'] ?? 'Linux')?>)
					</p>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">Version</label>
				<div class="col-sm-6">
					<p class="form-control-static">
						<span class="label label-info"><?=htmlspecialchars($sys['version'] ?? '1.0.2')?>-RELEASE</span> [<?=htmlspecialchars($sys['codename'] ?? 'Rinjani')?>]
					</p>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label">Timezone</label>
				<div class="col-sm-6">
					<input type="text" class="form-control" value="UTC (GMT+00:00)" readonly>
					<span class="help-block">System appliance standard timezone.</span>
				</div>
			</div>
		</div>
		<div class="panel-footer">
			<button type="submit" class="btn btn-primary"><i class="fa-solid fa-save icon-embed-btn"></i>Save</button>
		</div>
	</div>
</form>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
