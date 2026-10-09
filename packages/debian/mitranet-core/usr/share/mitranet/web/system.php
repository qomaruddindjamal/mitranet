<?php
/*
 * system.php - MitraNet System: General Setup
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_hostname = trim($_POST['hostname'] ?? '');
    $new_domain = trim($_POST['domain'] ?? '');
    $new_tz = trim($_POST['timezone'] ?? '');
    
    $dns_servers = [];
    for ($i = 0; $i < 4; $i++) {
        $dns = trim($_POST["dns{$i}"] ?? '');
        if (!empty($dns)) {
            $dns_servers[] = $dns;
        }
    }

    if (empty($new_hostname)) {
        $err = "Hostname cannot be empty.";
    } else {
        $update_payload = [
            'hostname' => $new_hostname,
            'domain' => $new_domain,
            'timezone' => $new_tz,
            'dns_servers' => $dns_servers
        ];
        $res = MitraNetApi::updateSystemSettings($update_payload);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            $msg = "The changes have been applied successfully. " . ($res['data']['message'] ?? '');
        } else {
            $err = $res['data']['error'] ?? 'Failed to apply configuration.';
        }
    }
}

$pgtitle = array("System", "General Setup");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$sys = MitraNetApi::getSystem();
$hostname = $sys['hostname'] ?? gethostname();
$domain = $sys['domain'] ?? 'home.arpa';
$current_tz = $sys['timezone'] ?? 'Etc/UTC';
$dns_servers = $sys['dns_servers'] ?? [];
$timeservers = $sys['timeservers'] ?? 'pool.ntp.org';

$timezones = [
    'Etc/UTC' => 'Etc/UTC (Universal Coordinated Time)',
    'Asia/Jakarta' => 'Asia/Jakarta (WIB, UTC+7)',
    'Asia/Makassar' => 'Asia/Makassar (WITA, UTC+8)',
    'Asia/Jayapura' => 'Asia/Jayapura (WIT, UTC+9)',
    'Asia/Singapore' => 'Asia/Singapore (SGT, UTC+8)',
    'Asia/Tokyo' => 'Asia/Tokyo (JST, UTC+9)',
    'America/New_York' => 'America/New_York (EST/EDT)',
    'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
    'Europe/London' => 'Europe/London (GMT/BST)',
    'Europe/Paris' => 'Europe/Paris (CET/CEST)'
];
?>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if (!empty($err)): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <i class="fa-solid fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?>
    </div>
<?php endif; ?>

<form method="post" action="system.php" class="form-horizontal">
    <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">System</h2></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label" for="hostname"><span class="element-required">Hostname</span></label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="hostname" name="hostname" value="<?= htmlspecialchars($hostname) ?>" required>
                    <span class="help-block">Name of the firewall host, without the domain part e.g. <em>mitranet</em></span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="domain"><span class="element-required">Domain</span></label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="domain" name="domain" value="<?= htmlspecialchars($domain) ?>" required>
                    <span class="help-block">e.g. <em>home.arpa</em> or <em>corp.local</em></span>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">DNS Server Settings</h2></div>
        <div class="panel-body">
            <?php for ($i = 0; $i < 4; $i++): 
                $val = $dns_servers[$i] ?? '';
            ?>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="dns<?= $i ?>">DNS Server <?= $i + 1 ?></label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="dns<?= $i ?>" name="dns<?= $i ?>" value="<?= htmlspecialchars($val) ?>" placeholder="e.g. 1.1.1.1 or 8.8.8.8">
                    <span class="help-block">IPv4 or IPv6 address for DNS server <?= $i + 1 ?>.</span>
                </div>
            </div>
            <?php endfor; ?>
            <div class="form-group">
                <label class="col-sm-2 control-label">DNS Resolution Behavior</label>
                <div class="col-sm-6">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="dnslocalhost" value="yes" checked>
                            <strong>Use local DNS (127.0.0.1)</strong>, fall back to remote DNS Servers
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">Localization</h2></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label" for="timezone">Timezone</label>
                <div class="col-sm-6">
                    <select class="form-control" id="timezone" name="timezone">
                        <?php foreach ($timezones as $tz_id => $tz_label): ?>
                            <option value="<?= htmlspecialchars($tz_id) ?>" <?= ($tz_id === $current_tz) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tz_label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help-block">Select the operating system timezone.</span>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="timeservers">NTP Time Server</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" id="timeservers" name="timeservers" value="<?= htmlspecialchars($timeservers) ?>">
                    <span class="help-block">Network Time Protocol pool or server address.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">webConfigurator</h2></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label">Theme</label>
                <div class="col-sm-6">
                    <select class="form-control" name="webguicss">
                        <option value="pfSense.css" selected>pfSense (default)</option>
                        <option value="pfSense-dark.css">pfSense-dark</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">Operating System Platform</label>
                <div class="col-sm-6">
                    <p class="form-control-static">
                        <strong><?= htmlspecialchars($sys['pretty_name'] ?? 'MitraNet') ?></strong> (Kernel: <?= htmlspecialchars($sys['kernel'] ?? 'Linux') ?>)
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2 mb-25">
        <button type="submit" class="btn btn-primary" id="save" name="save">
            <i class="fa-solid fa-save icon-embed-btn"></i> Save
        </button>
    </div>
</form>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
