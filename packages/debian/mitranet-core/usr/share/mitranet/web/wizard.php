<?php
/*
 * wizard.php - MitraNet System: Setup Wizard
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$step = intval($_POST['stepid'] ?? $_GET['step'] ?? 0);
$savemsg = "";
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next'])) {
        $step++;
    } elseif (isset($_POST['prev'])) {
        $step = max(0, $step - 1);
    } elseif (isset($_POST['finish'])) {
        $new_host = trim($_POST['hostname'] ?? '');
        $new_domain = trim($_POST['domain'] ?? '');
        $new_dns = trim($_POST['dns1'] ?? '');
        $dns_list = $new_dns ? [$new_dns] : [];
        if ($new_host) {
            MitraNetApi::updateSystemSettings([
                'hostname' => $new_host,
                'domain' => $new_domain ?: 'home.arpa',
                'dns_servers' => $dns_list
            ]);
        }
        $step = 3; // Finished step
        $savemsg = "Setup completed successfully! Configuration reloaded.";
    }
}

$pgtitle = array("System", "Setup Wizard");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$sys = MitraNetApi::getSystem();
$hostname = $sys['hostname'] ?? 'mitranet';
$domain = $sys['domain'] ?? 'home.arpa';
$dns_servers = $sys['dns_servers'] ?? [];
?>

<?php if (!empty($savemsg)): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($savemsg) ?>
    </div>
<?php endif; ?>

<form action="wizard.php?xml=setup_wizard.xml" class="form-horizontal" method="post">
    <input type="hidden" name="stepid" value="<?= $step ?>">

    <?php if ($step === 0): ?>
        <div class="panel panel-default">
            <div class="panel-heading"><h2 class="panel-title">MitraNet Setup</h2></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-sm-2 control-label"></label>
                    <div class="col-sm-10">
                        <h4>Welcome to MitraNet OS appliance!</h4>
                        <p>This wizard will provide step-by-step guidance through the initial network and system configuration of your appliance.</p>
                        <p>The wizard may be cancelled at any time by navigating away via the main menu.</p>
                        <br/>
                        <p><strong>Appliance:</strong> <?= htmlspecialchars($sys['pretty_name'] ?? 'MitraNet') ?> (Kernel <?= htmlspecialchars($sys['kernel'] ?? 'Linux') ?>)</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-10 col-sm-offset-2 save-row">
            <button class="btn btn-primary" id="next" name="next" type="submit" value="Next">
                Next <i class="fa-solid fa-angle-double-right icon-embed-btn"></i>
            </button>
        </div>

    <?php elseif ($step === 1): ?>
        <div class="panel panel-default">
            <div class="panel-heading"><h2 class="panel-title">Step 1: General Information</h2></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-sm-2 control-label" for="hostname"><span class="element-required">Hostname</span></label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="hostname" id="hostname" value="<?= htmlspecialchars($hostname) ?>" required>
                        <span class="help-block">Name of the appliance without domain.</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label" for="domain"><span class="element-required">Domain</span></label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="domain" id="domain" value="<?= htmlspecialchars($domain) ?>" required>
                        <span class="help-block">System domain e.g. home.arpa</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label" for="dns1">Primary DNS</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="dns1" id="dns1" value="<?= htmlspecialchars($dns_servers[0] ?? '1.1.1.1') ?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-10 col-sm-offset-2 save-row">
            <button class="btn btn-default" name="prev" type="submit"><i class="fa-solid fa-angle-double-left icon-embed-btn"></i> Previous</button>
            <button class="btn btn-primary" name="next" type="submit">Next <i class="fa-solid fa-angle-double-right icon-embed-btn"></i></button>
        </div>

    <?php elseif ($step === 2): ?>
        <div class="panel panel-default">
            <div class="panel-heading"><h2 class="panel-title">Step 2: Review and Apply</h2></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-sm-2 control-label"></label>
                    <div class="col-sm-10">
                        <h4>Ready to Apply Configuration</h4>
                        <p>Click <strong>Finish &amp; Reload</strong> to commit your setup parameters to the live Debian Linux 13 system.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-10 col-sm-offset-2 save-row">
            <button class="btn btn-default" name="prev" type="submit"><i class="fa-solid fa-angle-double-left icon-embed-btn"></i> Previous</button>
            <button class="btn btn-success" name="finish" type="submit"><i class="fa-solid fa-check icon-embed-btn"></i> Finish &amp; Reload</button>
        </div>

    <?php else: ?>
        <div class="panel panel-default">
            <div class="panel-heading"><h2 class="panel-title">Setup Wizard Completed</h2></div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-sm-2 control-label"></label>
                    <div class="col-sm-10">
                        <div class="alert alert-success">
                            <h4><i class="fa-solid fa-check-circle"></i> Congratulations!</h4>
                            <p>MitraNet is now configured and operational. You can manage your firewall rules, interfaces, and packages from the dashboard.</p>
                        </div>
                        <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-home icon-embed-btn"></i> Return to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</form>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
