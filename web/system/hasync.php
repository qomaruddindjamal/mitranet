<?php
/*
 * system_hasync.php - MitraNet System: High Availability
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = "";
$err_msg = "";

$hasync_file = "/etc/mitranet/secrets/hasync.json";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pfsync = isset($_POST['pfsyncenabled']) ? true : false;
    $sync_ip = trim($_POST['synchronizetoip'] ?? '');
    $peer_ip = trim($_POST['pfsyncpeerip'] ?? '');
    $remote_user = trim($_POST['username'] ?? 'admin');
    $iface = trim($_POST['pfsyncinterface'] ?? 'none');

    $ha_cfg = [
        'pfsync_enabled' => $pfsync,
        'pfsync_interface' => $iface,
        'pfsync_peer_ip' => $peer_ip,
        'sync_to_ip' => $sync_ip,
        'remote_user' => $remote_user,
        'sync_rules' => isset($_POST['synchronizerules']),
        'sync_users' => isset($_POST['synchronizeusers']),
        'updated_at' => time()
    ];

    MitraNetApi::execCommand("python3 -c \"import json, os; os.makedirs('/etc/mitranet/secrets', exist_ok=True); json.dump(" . var_export(json_encode($ha_cfg), true) . ", open('$hasync_file', 'w'))\"");
    $savemsg = "High availability settings saved successfully.";
}

$pgtitle = array("System", "High Availability");
$selected_menu = "system";
require_once(__DIR__ . '/../includes/head.inc');

$ifaces = MitraNetApi::getInterfaces();

// Read existing config
$ha_data = [];
$res_cmd = MitraNetApi::execCommand("cat $hasync_file 2>/dev/null || true");
if (!empty($res_cmd['data']['output'])) {
    $ha_data = json_decode($res_cmd['data']['output'], true) ?: [];
}
?>

<?php if (!empty($savemsg)): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($savemsg) ?>
    </div>
<?php endif; ?>

<form action="system_hasync.php" method="post" class="form-horizontal">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">State Synchronization Settings (conntrackd / pfsync)</h2>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label"><span>Synchronize states</span></label>
                <div class="col-sm-10">
                    <div class="checkbox">
                        <label>
                            <input name="pfsyncenabled" id="pfsyncenabled" type="checkbox" value="on" <?= !empty($ha_data['pfsync_enabled']) ? 'checked' : '' ?>>
                            Synchronize connection tracking state table between cluster members
                        </label>
                    </div>
                    <span class="help-block">Transfers state insertion, update, and deletion messages between redundant MitraNet appliances.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="pfsyncinterface">Synchronize Interface</label>
                <div class="col-sm-6">
                    <select class="form-control" name="pfsyncinterface" id="pfsyncinterface">
                        <option value="none">none</option>
                        <?php foreach ($ifaces as $if): ?>
                            <option value="<?= htmlspecialchars($if['name']) ?>" <?= (($ha_data['pfsync_interface'] ?? '') === $if['name']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($if['name']) ?> (<?= htmlspecialchars($if['ip'] ?? 'no IP') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="pfsyncpeerip">pfsync Synchronize Peer IP</label>
                <div class="col-sm-6">
                    <input class="form-control" name="pfsyncpeerip" id="pfsyncpeerip" type="text" value="<?= htmlspecialchars($ha_data['pfsync_peer_ip'] ?? '') ?>" placeholder="IP Address (optional for multicast)">
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">Configuration Synchronization Settings (XMLRPC / REST Sync)</h2>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label" for="synchronizetoip">Synchronize Config to IP</label>
                <div class="col-sm-6">
                    <input class="form-control" name="synchronizetoip" id="synchronizetoip" type="text" value="<?= htmlspecialchars($ha_data['sync_to_ip'] ?? '') ?>" placeholder="e.g. 192.168.1.2">
                    <span class="help-block">Enter the IP address of the peer firewall to which the configuration sections should be synchronized.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="username">Remote System Username</label>
                <div class="col-sm-6">
                    <input class="form-control" name="username" id="username" type="text" value="<?= htmlspecialchars($ha_data['remote_user'] ?? 'admin') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Select options to sync</label>
                <div class="col-sm-10">
                    <div class="checkbox">
                        <label><input name="synchronizerules" type="checkbox" value="on" <?= !empty($ha_data['sync_rules']) ? 'checked' : '' ?>> Firewall Rules &amp; NAT</label>
                    </div>
                    <div class="checkbox">
                        <label><input name="synchronizeusers" type="checkbox" value="on" <?= !empty($ha_data['sync_users']) ? 'checked' : '' ?>> User Manager Credentials</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2 mb-25">
        <button type="submit" class="btn btn-primary" name="save" id="save" value="Save">
            <i class="fa-solid fa-save icon-embed-btn"></i> Save
        </button>
    </div>
</form>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
