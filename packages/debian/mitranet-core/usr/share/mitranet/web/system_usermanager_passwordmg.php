<?php
/*
 * system_usermanager_passwordmg.php - MitraNet System: User Password Manager
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$savemsg = "";
$err_msg = "";
$current_user = $_SESSION['username'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = $_POST['passwordfld1'] ?? '';
    $p2 = $_POST['passwordfld2'] ?? '';

    if (empty($p1) || empty($p2)) {
        $err_msg = "Password fields cannot be empty.";
    } elseif ($p1 !== $p2) {
        $err_msg = "The passwords do not match.";
    } elseif ($p1 === $current_user) {
        $err_msg = "The password cannot be identical to the username.";
    } else {
        $res = MitraNetApi::changeUserPassword($current_user, $p1);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            $savemsg = "The password for '{$current_user}' has been successfully changed.";
        } else {
            $err_msg = $res['data']['error'] ?? "Failed to update password.";
        }
    }
}

$pgtitle = array("System", "User Password Manager");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');
?>

<ul class="nav nav-pills">
    <li role="presentation"><a href="system_usermanager.php">Users</a></li>
    <li role="presentation"><a href="system_groupmanager.php">Groups</a></li>
    <li role="presentation"><a href="system_usermanager_settings.php">Settings</a></li>
    <li role="presentation" class="active"><a href="system_usermanager_passwordmg.php">Change Password</a></li>
    <li role="presentation"><a href="system_authservers.php">Authentication Servers</a></li>
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

<form action="system_usermanager_passwordmg.php" method="post" class="form-horizontal">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">Change Password</h2>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label"></label>
                <div class="col-sm-10">
                    This page changes the password for the current user in the local configuration. This affects all services which utilize the Local Authentication database (User Manager).<br/><br/>
                    This page cannot change passwords for users from other authentication sources such as LDAP or RADIUS.
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label"><span>Database</span></label>
                <div class="col-sm-10">
                    <p class="form-control-static">Local Authentication</p>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label"><span>Username</span></label>
                <div class="col-sm-10">
                    <p class="form-control-static"><strong><?= htmlspecialchars($current_user) ?></strong></p>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label"><span class="element-required">Password</span></label>
                <div class="col-sm-10">
                    <input type="password" class="form-control" name="passwordfld1" id="passwordfld1" autocomplete="new-password" required />
                    <span class="help-block">
                        Enter a new password.<br/><br/>
                        Hints:<br/>
                        Current NIST guidelines prioritize password length over complexity.<br/>
                        The password cannot be identical to the username.
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label"><span class="element-required">Confirmation</span></label>
                <div class="col-sm-10">
                    <input type="password" class="form-control" name="passwordfld2" id="passwordfld2" autocomplete="new-password" required />
                    <span class="help-block">Type the new password again for confirmation.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2 save-row-20">
        <button type="submit" class="btn btn-primary" name="save" id="save" value="Save">
            <i class="fa-solid fa-save icon-embed-btn"></i> Save
        </button>
    </div>
</form>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
