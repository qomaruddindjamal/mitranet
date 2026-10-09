<?php
/*
 * system_gateways.php - MitraNet System: Routing: Gateways
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$pgtitle = array("System", "Routing", "Gateways");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$gateways = MitraNetApi::getGateways();

$tab_array = array();
$tab_array[] = array(gettext("Gateways"), true, "system_gateways.php");
$tab_array[] = array(gettext("Static Routes"), false, "system_routes.php");
display_top_tabs($tab_array);
?>

<div class="panel panel-default panel-mitranet">
    <div class="panel-heading"><h2 class="panel-title">Gateways</h2></div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th class="col-w-30"></th>
                        <th>Name</th>
                        <th>Default</th>
                        <th>Interface</th>
                        <th>Gateway</th>
                        <th>Monitor IP</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gateways)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No default gateways currently configured or detected on appliance.</td>
                        </tr>
                    <?php else: foreach ($gateways as $gw): ?>
                        <tr>
                            <td title="Gateway online and active">
                                <i class="fa-solid fa-circle-check text-success"></i>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($gw['name'] ?? 'GW') ?></strong>
                            </td>
                            <td>
                                <?php if (!empty($gw['default'])): ?>
                                    <i class="fa-solid fa-globe text-primary" title="Default Gateway"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?= htmlspecialchars($gw['interface'] ?? 'eth0') ?></code>
                            </td>
                            <td>
                                <?= htmlspecialchars($gw['gateway'] ?? 'dynamic') ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($gw['monitor_ip'] ?? ($gw['gateway'] ?? '')) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($gw['description'] ?? 'System Gateway') ?>
                            </td>
                            <td>
                                <a href="system_gateways_edit.php?gw=<?= urlencode($gw['name']) ?>" class="btn btn-xs btn-primary" title="Edit Gateway">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form action="system_gateways.php" method="post" class="form-horizontal">
    <div class="panel panel-default panel-mitranet">
        <div class="panel-heading"><h2 class="panel-title">Default gateway</h2></div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label" for="defaultgw4">Default gateway IPv4</label>
                <div class="col-sm-6">
                    <select class="form-control" name="defaultgw4" id="defaultgw4">
                        <option value="auto" selected>Automatic</option>
                        <?php foreach ($gateways as $gw): ?>
                            <option value="<?= htmlspecialchars($gw['name']) ?>">
                                <?= htmlspecialchars($gw['name']) ?> - <?= htmlspecialchars($gw['gateway']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="defaultgw6">Default gateway IPv6</label>
                <div class="col-sm-6">
                    <select class="form-control" name="defaultgw6" id="defaultgw6">
                        <option value="auto" selected>Automatic</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" class="btn btn-primary" name="save" id="save" value="Save">
                <i class="fa-solid fa-save icon-embed-btn"></i> Save
            </button>
        </div>
    </div>
</form>

<nav class="action-buttons">
    <a href="system_gateways_edit.php" class="btn btn-success btn-sm">
        <i class="fa-solid fa-plus icon-embed-btn"></i> Add
    </a>
</nav>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
