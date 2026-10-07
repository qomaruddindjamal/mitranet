<?php
/*
 * system_register.php - MitraNet System: Register
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$savemsg = "";
$err_msg = "";
$license_file = "/etc/mitranet/secrets/license.json";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['activation_token'] ?? '');
    if (empty($token)) {
        $err_msg = "Please paste a valid activation token.";
    } else {
        $lic = [
            'token' => $token,
            'status' => 'Activated',
            'registered_at' => time()
        ];
        MitraNetApi::execCommand("python3 -c \"import json, os; os.makedirs('/etc/mitranet/secrets', exist_ok=True); json.dump(" . var_export(json_encode($lic), true) . ", open('$license_file', 'w'))\"");
        $savemsg = "MitraNet system successfully registered and activated!";
    }
}

$pgtitle = array("System", "Register");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$is_registered = false;
$res_cmd = MitraNetApi::execCommand("cat $license_file 2>/dev/null || true");
if (!empty($res_cmd['data']['output'])) {
    $lic_data = json_decode($res_cmd['data']['output'], true) ?: [];
    if (!empty($lic_data['token'])) {
        $is_registered = true;
    }
}
?>

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

<?php if ($is_registered): ?>
    <div class="alert alert-info">
        <i class="fa-solid fa-certificate"></i> <strong>This appliance is registered.</strong> License status: <span class="label label-success">Activated</span>
    </div>
<?php endif; ?>

<form action="system_register.php" class="form-horizontal" method="post">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">Register MitraNet</h2>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label">
                    <span class="element-required">Activation token</span>
                </label>
                <div class="col-sm-10">
                    <textarea class="form-control" id="activation_token" name="activation_token" rows="8" placeholder="Paste MitraNet / pfSense Plus subscription activation token here..." required></textarea>
                    <span class="help-block">Enter the activation token provided with your subscription to register this appliance.</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-10 col-sm-offset-2" style="margin-bottom: 25px;">
        <button class="btn btn-primary" id="Submit" name="Submit" type="submit" value="Register">
            <i class="fa-regular fa-registered icon-embed-btn"></i> Register
        </button>
    </div>
</form>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
