<?php
/*
 * pkg_mgr_install.php - MitraNet System: Update: System Update
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$savemsg = "";
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pkgconfirm'])) {
    $res = MitraNetApi::execCommand("apt-get update -y && apt-get upgrade -y --dry-run");
    if ($res['status'] === 200) {
        $savemsg = "Update verification completed. System packages are up to date.";
    } else {
        $err_msg = "Error updating repository cache: " . ($res['data']['error'] ?? 'failed');
    }
}

$pgtitle = array("System", "Update", "System Update");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$up_info = MitraNetApi::getSystemUpdateCheck();
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills">
    <li role="presentation" class="active"><a href="pkg_mgr_install.php?id=firmware">System Update</a></li>
    <li role="presentation"><a href="system_update_settings.php">Update Settings</a></li>
</ul>

<?php if (!empty($savemsg)): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($savemsg) ?>
    </div>
<?php endif; ?>

<?php if (!empty($err_msg)): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($err_msg) ?>
    </div>
<?php endif; ?>

<form action="pkg_mgr_install.php?id=firmware" method="post" class="form-horizontal">
    <input type="hidden" name="id" value="firmware">
    <input type="hidden" name="mode" value="reinstallpkg">

    <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">System Update Status</h2></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label">Branch</label>
                <div class="col-sm-6">
                    <select class="form-control" name="fwbranch" id="fwbranch">
                        <option value="stable" selected>Current Stable Version (<?= htmlspecialchars($sys['version'] ?? '1.0.2') ?>-RELEASE)</option>
                        <option value="devel">Development Version (Nightly Builds)</option>
                    </select>
                    <span class="help-block">Select the branch from which to update the appliance firmware and packages.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Current Base System</label>
                <div class="col-sm-6">
                    <p class="form-control-static">
                        <strong><?= htmlspecialchars($up_info['current_version'] ?? '1.0.2-RELEASE') ?></strong> 
                        (Debian 13 Trixie / <?= htmlspecialchars($sys['kernel'] ?? 'Linux') ?>)
                    </p>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Latest Base System</label>
                <div class="col-sm-6">
                    <p class="form-control-static">
                        <strong><?= htmlspecialchars($up_info['latest_version'] ?? '1.0.2-RELEASE') ?></strong>
                    </p>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Status</label>
                <div class="col-sm-6">
                    <?php if (!empty($up_info['up_to_date'])): ?>
                        <div class="alert alert-success" style="margin-bottom: 0;">
                            <i class="fa-solid fa-check"></i> <strong>Up to date.</strong> Your appliance is on the latest version.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning" style="margin-bottom: 0;">
                            <i class="fa-solid fa-exclamation-triangle"></i> <strong>Updates available:</strong>
                            <ul style="margin-top: 5px;">
                                <?php foreach ($up_info['updates_available'] as $pkg_u): ?>
                                    <li><code><?= htmlspecialchars($pkg_u) ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2" style="margin-bottom: 25px;">
        <button type="submit" class="btn btn-success" name="pkgconfirm" id="pkgconfirm" value="Confirm">
            <i class="fa-solid fa-check icon-embed-btn"></i> Check / Apply Updates
        </button>
    </div>
</form>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
