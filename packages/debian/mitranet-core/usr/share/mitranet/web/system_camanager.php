<?php
/*
 * system_camanager.php - MitraNet System: Certificates: Authorities
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$pgtitle = array("System", "Certificates", "Authorities");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$cert_data = MitraNetApi::getCertificates();
$cas = $cert_data['authorities'] ?? [];
?>

<ul class="nav nav-pills">
    <li role="presentation" class="active"><a href="system_camanager.php">Authorities</a></li>
    <li role="presentation"><a href="system_certmanager.php">Certificates</a></li>
    <li role="presentation"><a href="system_crlmanager.php">Revocation</a></li>
</ul>

<div class="panel panel-default" id="search-panel">
    <div class="panel-heading">
        <h2 class="panel-title">
            Search
            <span class="widget-heading-icon pull-right">
                <a data-toggle="collapse" href="#search-panel_panel-body">
                    <i class="fa-solid fa-plus-circle"></i>
                </a>
            </span>
        </h2>
    </div>
    <div id="search-panel_panel-body" class="panel-body collapse in">
        <div class="form-group">
            <label class="col-sm-2 control-label">Search term</label>
            <div class="col-sm-5">
                <input class="form-control" name="searchstr" id="searchstr" type="text" placeholder="Filter CAs by name or DN">
            </div>
            <div class="col-sm-2">
                <select id="where" class="form-control">
                    <option value="0">Name</option>
                    <option value="1">Distinguished Name</option>
                    <option value="2" selected>Both</option>
                </select>
            </div>
            <div class="col-sm-3">
                <button type="button" id="btnsearch" class="btn btn-primary btn-sm"><i class="fa-solid fa-search icon-embed-btn"></i> Search</button>
                <button type="button" id="btnclear" class="btn btn-info btn-sm"><i class="fa-solid fa-undo icon-embed-btn"></i> Clear</button>
            </div>
            <div class="col-sm-10 col-sm-offset-2">
                <span class="help-block">Enter a search string to search certificate names and distinguished names.</span>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading"><h2 class="panel-title">Certificate Authorities</h2></div>
    <div class="panel-body">
        <div class="table-responsive">
            <table id="catable" class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Internal</th>
                        <th>Issuer</th>
                        <th>Certificates</th>
                        <th>Identity</th>
                        <th>In Use</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cas)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No Certificate Authorities found in appliance trust store.</td></tr>
                    <?php else: foreach ($cas as $ca): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ca['name']) ?></strong></td>
                            <td><?= !empty($ca['internal']) ? '<i class="fa-solid fa-check text-success"></i>' : '<i class="fa-solid fa-times text-muted"></i>' ?></td>
                            <td><?= htmlspecialchars($ca['issuer']) ?></td>
                            <td><span class="badge"><?= intval($ca['count']) ?></span></td>
                            <td><code><?= htmlspecialchars($ca['distinguished_name']) ?></code></td>
                            <td><i class="fa-solid fa-check text-success"></i></td>
                            <td>
                                <a href="system_camanager.php?act=export&name=<?= urlencode($ca['name']) ?>" class="btn btn-xs btn-default" title="Export CA"><i class="fa-solid fa-download"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<nav class="action-buttons">
    <a href="system_camanager.php?act=new" class="btn btn-success btn-sm">
        <i class="fa-solid fa-plus icon-embed-btn"></i> Add
    </a>
</nav>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
