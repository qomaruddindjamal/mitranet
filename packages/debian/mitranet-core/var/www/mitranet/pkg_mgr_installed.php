<?php
/*
 * pkg_mgr_installed.php - MitraNet Package Manager: Installed Packages
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

// Support AJAX request like pfSense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $packages = MitraNetApi::getPackages();
    if (empty($packages)) {
        echo "nopkg";
        exit;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packages as $pkg): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($pkg['name']) ?></strong></td>
                    <td><span class="label label-info"><?= htmlspecialchars($pkg['category'] ?? 'system') ?></span></td>
                    <td><code><?= htmlspecialchars($pkg['version']) ?></code></td>
                    <td><span class="label label-success"><i class="fa-solid fa-check"></i> <?= htmlspecialchars(strtoupper($pkg['status'])) ?></span></td>
                    <td><?= htmlspecialchars($pkg['descr'] ?? $pkg['description'] ?? '') ?></td>
                    <td>
                        <a href="pkg_mgr_install.php?mode=reinstallpkg&pkg=<?= urlencode($pkg['name']) ?>" class="btn btn-xs btn-info" title="Reinstall Package"><i class="fa-solid fa-retweet"></i></a>
                        <a href="pkg_mgr_install.php?mode=delete&pkg=<?= urlencode($pkg['name']) ?>" class="btn btn-xs btn-danger" title="Remove Package"><i class="fa-solid fa-trash-can"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    exit;
}

$pgtitle = array("System", "Package Manager", "Installed Packages");
$pglinks = array("", "/pkg_mgr_installed.php", "@self");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$packages = MitraNetApi::getPackages();
?>

<ul class="nav nav-pills">
    <li role="presentation" class="active"><a href="pkg_mgr_installed.php">Installed Packages</a></li>
    <li role="presentation"><a href="pkg_mgr.php">Available Packages</a></li>
</ul>

<div class="panel panel-default">
    <div class="panel-heading"><h2 class="panel-title">Installed Packages</h2></div>
    <div id="pkgtbl" class="panel-body">
        <?php if (empty($packages)): ?>
            <div id="nopkg">
                <div class="alert alert-warning clearfix" role="alert">
                    <div class="pull-left">There are no packages currently installed.</div>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $pkg): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pkg['name']) ?></strong></td>
                            <td><span class="label label-info"><?= htmlspecialchars($pkg['category'] ?? 'system') ?></span></td>
                            <td><code><?= htmlspecialchars($pkg['version']) ?></code></td>
                            <td><span class="label label-success"><i class="fa-solid fa-check"></i> <?= htmlspecialchars(strtoupper($pkg['status'])) ?></span></td>
                            <td><?= htmlspecialchars($pkg['descr'] ?? $pkg['description'] ?? '') ?></td>
                            <td>
                                <a href="pkg_mgr_install.php?mode=reinstallpkg&pkg=<?= urlencode($pkg['name']) ?>" class="btn btn-xs btn-info" title="Reinstall Package"><i class="fa-solid fa-retweet"></i></a>
                                <a href="pkg_mgr_install.php?mode=delete&pkg=<?= urlencode($pkg['name']) ?>" class="btn btn-xs btn-danger" title="Remove Package"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div id="legend" class="alert-info text-center legend-bar">
        <p><i class="fa-solid fa-arrows-rotate"></i> = Update &nbsp; <i class="fa-solid fa-check"></i> = Current &nbsp; <i class="fa-solid fa-retweet"></i> = Reinstall &nbsp; <i class="fa-solid fa-trash-can"></i> = Remove</p>
        <span><i class="fa-solid fa-check text-success"></i> = Installed &bull; Native Debian GNU/Linux 13 (Trixie) amd64 Repository Pool</span>
    </div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
