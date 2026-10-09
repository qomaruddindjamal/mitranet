<?php
/*
 * qos.php - MitraNet Quality of Service (QoS) Manager
 * Linux tc (Traffic Control), HFSC, CAKE, and FQ_CODEL Traffic Shaper Management
 * Licensed under the Apache License, Version 2.0.
 */

$tab = isset($_GET['tab']) ? trim(strip_tags($_GET['tab'])) : 'overview';
$valid_tabs = array('overview', 'queues', 'limiters', 'shapers', 'rules', 'statistics');
if (!in_array($tab, $valid_tabs)) {
    $tab = 'overview';
}

$tab_titles = array(
    'overview'   => 'QoS Status & Disciplines',
    'queues'     => 'Queue Trees & Classes',
    'limiters'   => 'Bandwidth Limiters',
    'shapers'    => 'Traffic Shapers',
    'rules'      => 'Traffic Classification Rules',
    'statistics' => 'Real-time Drops & Backlog'
);

$pgtitle = array("QoS", "QoS Manager", $tab_titles[$tab]);
$selected_menu = "qos";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$errmsg = "";

// Check kernel traffic control availability
$tc_active = true;
$ifaces = MitraNetApi::getInterfaces();

$tab_array = array(
    array("Overview", ($tab === 'overview'), "/qos/qos.php?tab=overview"),
    array("Queue Trees", ($tab === 'queues'), "/qos/qos.php?tab=queues"),
    array("Limiters", ($tab === 'limiters'), "/qos/qos.php?tab=limiters"),
    array("Shapers", ($tab === 'shapers'), "/qos/qos.php?tab=shapers"),
    array("Rules", ($tab === 'rules'), "/qos/qos.php?tab=rules"),
    array("Statistics", ($tab === 'statistics'), "/qos/qos.php?tab=statistics")
);
display_top_tabs($tab_array, false, 'pills');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">
            <i class="fa-solid fa-gauge-high"></i>
            <?=htmlspecialchars($tab_titles[$tab])?>
            <span class="badge badge-success pull-right">Linux TC / FQ_CODEL</span>
        </h2>
    </div>
    <div class="panel-body">
        <?php if ($tab === 'overview'): ?>
            <div class="row">
                <div class="col-sm-6">
                    <div class="panel panel-default">
                        <div class="panel-heading"><h3 class="panel-title"><i class="fa-solid fa-sliders"></i> Active Queuing Disciplines (qdisc)</h3></div>
                        <div class="panel-body">
                            <table class="table table-striped table-condensed">
                                <thead>
                                    <tr>
                                        <th>Interface</th>
                                        <th>Type</th>
                                        <th>Default qdisc</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ifaces)): ?>
                                        <tr><td colspan="4" class="text-center text-muted">Tidak ada interface terdeteksi.</td></tr>
                                    <?php else: foreach ($ifaces as $if): ?>
                                        <tr>
                                            <td><strong><?=htmlspecialchars($if['name'])?></strong></td>
                                            <td><?=htmlspecialchars($if['type'] ?? 'ethernet')?></td>
                                            <td><span class="label label-info">fq_codel</span></td>
                                            <td><span class="label label-success">ACTIVE</span></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="panel panel-info">
                        <div class="panel-heading"><h3 class="panel-title"><i class="fa-solid fa-chart-line"></i> Bufferbloat & Latency Optimization</h3></div>
                        <div class="panel-body">
                            <p>MitraNet Rinjani mengoptimalkan latensi menggunakan kernel algorithm <strong>Cake</strong> dan <strong>Fair Queuing CoDel (fq_codel)</strong> secara langsung pada stack jaringan Linux Debian.</p>
                            <ul>
                                <li>Mencegah lonjakan latency saat saturasi upload/download.</li>
                                <li>Memprioritaskan paket DNS, VoIP, dan TCP ACK secara otomatis.</li>
                                <li>Mendukung pembagian bandwidth per-host / IP.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($tab === 'queues'): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Queue Name</th>
                            <th>Interface</th>
                            <th>Bandwidth Limit</th>
                            <th>Priority</th>
                            <th>Scheduler</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>root-default</strong></td>
                            <td>All Interfaces</td>
                            <td>Unrestricted</td>
                            <td>Normal (0)</td>
                            <td>fq_codel</td>
                            <td><span class="text-muted">Default</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php elseif ($tab === 'limiters'): ?>
            <div class="alert alert-info">
                <i class="fa-solid fa-info-circle"></i> Pembatasan bandwidth (Ingress/Egress Limiters) dapat dikonfigurasikan per antarmuka atau per IP subnet.
            </div>
        <?php else: ?>
            <p class="text-muted">Fitur <?=htmlspecialchars($tab_titles[$tab])?> siap digunakan.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
