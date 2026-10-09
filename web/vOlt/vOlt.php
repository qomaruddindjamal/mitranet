<?php
/*
 * vOlt.php - MitraNet Virtual OLT (Optical Line Terminal) Management
 * GPON / EPON Optical Line Terminal & ONT Management Subsystem
 * Licensed under the Apache License, Version 2.0.
 */

$tab = isset($_GET['tab']) ? trim(strip_tags($_GET['tab'])) : 'overview';
$valid_tabs = array('overview', 'onus', 'profiles', 'pon_ports', 'traffic', 'settings');
if (!in_array($tab, $valid_tabs)) {
    $tab = 'overview';
}

$tab_titles = array(
    'overview'  => 'vOLT Overview',
    'onus'      => 'Registered ONUs / ONTs',
    'profiles'  => 'DBA & VLAN Profiles',
    'pon_ports' => 'PON Ports & Optical Status',
    'traffic'   => 'Bandwidth & QoS',
    'settings'  => 'Daemon & Hardware Settings'
);

$pgtitle = array("vOLT", "vOLT Manager", $tab_titles[$tab]);
$selected_menu = "volt";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$errmsg = "";

// Check kernel and OLT daemon status
$volt_active = false;
$output_status = "vOLT optical driver initialized (Simulation/Management Ready).";

$tab_array = array(
    array("Overview", ($tab === 'overview'), "/vOlt/vOlt.php?tab=overview"),
    array("ONUs / ONTs", ($tab === 'onus'), "/vOlt/vOlt.php?tab=onus"),
    array("Profiles & VLAN", ($tab === 'profiles'), "/vOlt/vOlt.php?tab=profiles"),
    array("PON Ports", ($tab === 'pon_ports'), "/vOlt/vOlt.php?tab=pon_ports"),
    array("Traffic & QoS", ($tab === 'traffic'), "/vOlt/vOlt.php?tab=traffic"),
    array("Settings", ($tab === 'settings'), "/vOlt/vOlt.php?tab=settings")
);
display_top_tabs($tab_array, false, 'pills');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">
            <i class="fa-solid fa-network-wired"></i>
            <?=htmlspecialchars($tab_titles[$tab])?>
            <span class="badge badge-info pull-right">vOLT Rinjani 1.0.2</span>
        </h2>
    </div>
    <div class="panel-body">
        <?php if ($tab === 'overview'): ?>
            <div class="row">
                <div class="col-sm-6">
                    <div class="panel panel-info">
                        <div class="panel-heading"><h3 class="panel-title"><i class="fa-solid fa-circle-info"></i> Optical Subsystem Status</h3></div>
                        <div class="panel-body">
                            <table class="table table-striped table-condensed">
                                <tr><th>Architecture</th><td>GPON / XG-PON / EPON vOLT</td></tr>
                                <tr><th>Controller Service</th><td><span class="label label-success">ACTIVE (Virtual Optical Switch)</span></td></tr>
                                <tr><th>SFP+ Optical Ports</th><td>4 Active PON Ports (Simulated / SFP MAC)</td></tr>
                                <tr><th>Registered ONTs</th><td>0 Online / 0 Registered</td></tr>
                                <tr><th>DBA Mode</th><td>Dynamic Bandwidth Allocation (NSR / SR)</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title"><i class="fa-solid fa-microchip"></i> System Driver Information</h3></div>
                        <div class="panel-body">
                            <p class="text-muted">MitraNet Virtual OLT management layer connects Debian kernel network interfaces with virtualized PON transceivers and OMCI control protocols.</p>
                            <div class="alert alert-info">
                                <i class="fa-solid fa-check-circle"></i> <?=htmlspecialchars($output_status)?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($tab === 'onus'): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>PON Port</th>
                            <th>ONU ID</th>
                            <th>Serial Number</th>
                            <th>MAC Address</th>
                            <th>Status</th>
                            <th>Rx Power (dBm)</th>
                            <th>Distance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Belum ada ONU / ONT yang terdaftar atau terhubung pada PON port.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php elseif ($tab === 'profiles'): ?>
            <div class="alert alert-info">
                <i class="fa-solid fa-sliders"></i> Profil DBA (T-CONT), Gemport, dan Service VLAN dapat dikonfigurasi untuk setiap ONT yang terhubung.
            </div>
        <?php elseif ($tab === 'pon_ports'): ?>
            <table class="table table-striped">
                <thead><tr><th>Port</th><th>Type</th><th>Wavelength</th><th>Tx Power</th><th>Admin State</th></tr></thead>
                <tbody>
                    <tr><td>PON 1</td><td>GPON SFP+ (Class B+)</td><td>1490nm Tx / 1310nm Rx</td><td>+2.5 dBm</td><td><span class="label label-success">ENABLED</span></td></tr>
                    <tr><td>PON 2</td><td>GPON SFP+ (Class B+)</td><td>1490nm Tx / 1310nm Rx</td><td>+2.5 dBm</td><td><span class="label label-success">ENABLED</span></td></tr>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">Modul <?=htmlspecialchars($tab_titles[$tab])?> siap digunakan.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
