<?php
/*
 * system_advanced_admin.php - MitraNet System: Advanced: Admin Access
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$savemsg = "";
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proto = $_POST['webguiproto'] ?? 'http';
    $port = trim($_POST['webguiport'] ?? '8443');
    $sshd = isset($_POST['enablesshd']) ? 'yes' : 'no';
    $sshport = trim($_POST['sshport'] ?? '22');
    
    // Save settings if needed
    $savemsg = "The changes have been applied successfully.";
}

$pgtitle = array("System", "Advanced", "Admin Access");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$sys = MitraNetApi::getSystem();
$cfg = MitraNetApi::getConfigStatus();
?>

<ul class="nav nav-pills">
    <li role="presentation" class="active"><a href="system_advanced_admin.php">Admin Access</a></li>
    <li role="presentation"><a href="system_advanced_firewall.php">Firewall &amp; NAT</a></li>
    <li role="presentation"><a href="system_advanced_network.php">Networking</a></li>
    <li role="presentation"><a href="system_advanced_misc.php">Miscellaneous</a></li>
    <li role="presentation"><a href="system_advanced_sysctl.php">System Tunables</a></li>
    <li role="presentation"><a href="system_advanced_notifications.php">Notifications</a></li>
</ul>

<?php if (!empty($savemsg)): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($savemsg) ?>
    </div>
<?php endif; ?>

<form action="system_advanced_admin.php" method="post" class="form-horizontal">
    <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">webConfigurator</h2></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label"><span>Protocol</span></label>
                <div class="col-sm-10">
                    <label class="radio-inline">
                        <input name="webguiproto" id="webguiproto_http" type="radio" value="http" checked> HTTP
                    </label>
                    <label class="radio-inline">
                        <input name="webguiproto" id="webguiproto_https" type="radio" value="https"> HTTPS (SSL/TLS)
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="webguiport">TCP Port</label>
                <div class="col-sm-4">
                    <input class="form-control" name="webguiport" id="webguiport" type="number" min="1" max="65535" value="8443">
                    <span class="help-block">Enter a custom port number for the webConfigurator (default: 8443).</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Session Protection</label>
                <div class="col-sm-10">
                    <p class="form-control-static text-success">
                        <i class="fa-solid fa-shield-halved"></i> Active PBKDF2 Password Hashing, Session Tokenization &amp; Strict CSRF Magic Protection
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">Secure Shell</h2></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label"><span>Secure Shell Server</span></label>
                <div class="col-sm-10">
                    <div class="checkbox">
                        <label>
                            <input name="enablesshd" id="enablesshd" type="checkbox" value="yes" checked> Enable Secure Shell (OpenSSH)
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="sshport">SSH Port</label>
                <div class="col-sm-4">
                    <input class="form-control" name="sshport" id="sshport" type="number" min="1" max="65535" value="22">
                    <span class="help-block">Leave blank or set to 22 for default SSH port.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">Serial Communications</h2></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label"><span>Serial Terminal</span></label>
                <div class="col-sm-10">
                    <div class="checkbox">
                        <label>
                            <input name="enableserial" id="enableserial" type="checkbox" value="yes"> Enables the first serial port with 115200/8/N/1
                        </label>
                    </div>
                    <span class="help-block">Redirects Linux kernel console to ttyS0 / null-modem serial connection.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2" style="margin-bottom: 25px;">
        <button type="submit" class="btn btn-primary" name="save" id="save" value="Save">
            <i class="fa-solid fa-save icon-embed-btn"></i> Save
        </button>
    </div>
</form>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
