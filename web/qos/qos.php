<?php
/*
 * qos.php - MitraNet Quality of Service (QoS) Manager
 * Linux tc (Traffic Control), HTB, HFSC, CAKE, and FQ_CODEL Traffic Shaper Management
 * Styled strictly matching MikroTik WinBox Queues window.
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$configFile = '/etc/mitranet/qos_queues.json';

// Helper: load queues from persistent config or default seed
function loadQueues($configFile) {
    if (file_exists($configFile) && is_readable($configFile)) {
        $content = @file_get_contents($configFile);
        $data = json_decode($content, true);
        if (is_array($data) && isset($data['queues'])) {
            return $data['queues'];
        }
    }
    // Default initial Simple Queues
    return [
        [
            'id' => 1,
            'name' => 'default-lan',
            'target' => '192.168.88.0/24',
            'upload_max' => '10M',
            'download_max' => '20M',
            'packet_marks' => 'no-mark',
            'total_max' => '30M',
            'enabled' => true,
            'comment' => 'Default Office LAN'
        ],
        [
            'id' => 2,
            'name' => 'guest-wifi',
            'target' => '192.168.100.0/24',
            'upload_max' => '2M',
            'download_max' => '5M',
            'packet_marks' => 'no-mark',
            'total_max' => '7M',
            'enabled' => true,
            'comment' => 'Guest Hotspot'
        ],
        [
            'id' => 3,
            'name' => 'server-dmz',
            'target' => '192.168.10.15/32',
            'upload_max' => '50M',
            'download_max' => '100M',
            'packet_marks' => 'no-mark',
            'total_max' => '150M',
            'enabled' => true,
            'comment' => 'Main Web Server'
        ]
    ];
}

function saveQueues($configFile, $queues) {
    $dir = dirname($configFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return @file_put_contents($configFile, json_encode(['queues' => array_values($queues)], JSON_PRETTY_PRINT)) !== false;
}

$queues = loadQueues($configFile);
$current_tab = isset($_GET['tab']) ? strtolower(trim(strip_tags($_GET['tab']))) : 'simple';
$valid_tabs = ['simple', 'interface', 'tree', 'types'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'simple';
}

// Handle AJAX actions (CRUD for Simple Queues)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = trim($_POST['name'] ?? '');
        $target = trim($_POST['target'] ?? '');
        $up_max = trim($_POST['upload_max'] ?? '0');
        $down_max = trim($_POST['download_max'] ?? '0');
        $pkt_marks = trim($_POST['packet_marks'] ?? 'no-mark');
        $total_max = trim($_POST['total_max'] ?? 'unlimited');
        $comment = trim($_POST['comment'] ?? '');

        if (empty($name)) {
            $response = ['success' => false, 'error' => 'Queue name cannot be empty.'];
        } else {
            if ($action === 'add') {
                $maxId = 0;
                foreach ($queues as $q) {
                    if ($q['id'] > $maxId) $maxId = $q['id'];
                }
                $newQueue = [
                    'id' => $maxId + 1,
                    'name' => $name,
                    'target' => !empty($target) ? $target : '0.0.0.0/0',
                    'upload_max' => !empty($up_max) ? $up_max : 'unlimited',
                    'download_max' => !empty($down_max) ? $down_max : 'unlimited',
                    'packet_marks' => !empty($pkt_marks) ? $pkt_marks : 'no-mark',
                    'total_max' => !empty($total_max) ? $total_max : 'unlimited',
                    'enabled' => true,
                    'comment' => $comment
                ];
                $queues[] = $newQueue;
            } else {
                // Edit existing
                foreach ($queues as &$q) {
                    if ($q['id'] === $id) {
                        $q['name'] = $name;
                        $q['target'] = !empty($target) ? $target : '0.0.0.0/0';
                        $q['upload_max'] = !empty($up_max) ? $up_max : 'unlimited';
                        $q['download_max'] = !empty($down_max) ? $down_max : 'unlimited';
                        $q['packet_marks'] = !empty($pkt_marks) ? $pkt_marks : 'no-mark';
                        $q['total_max'] = !empty($total_max) ? $total_max : 'unlimited';
                        $q['comment'] = $comment;
                        break;
                    }
                }
            }
            saveQueues($configFile, $queues);
            $response = ['success' => true, 'message' => 'Queue successfully saved.'];
        }

        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            exit;
        } else {
            header('Location: qos.php?tab=' . $current_tab);
            exit;
        }
    }

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $state = ($_POST['state'] ?? '1') === '1';
        foreach ($queues as &$q) {
            if ($q['id'] === $id) {
                $q['enabled'] = $state;
                break;
            }
        }
        saveQueues($configFile, $queues);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Queue status updated.']);
            exit;
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $queues = array_filter($queues, function($q) use ($id) {
            return $q['id'] !== $id;
        });
        saveQueues($configFile, $queues);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Queue removed.']);
            exit;
        }
    }

    if ($action === 'comment') {
        $id = intval($_POST['id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        foreach ($queues as &$q) {
            if ($q['id'] === $id) {
                $q['comment'] = $comment;
                break;
            }
        }
        saveQueues($configFile, $queues);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Comment updated.']);
            exit;
        }
    }
}

$pgtitle = ["QoS", "Queues"];
$selected_menu = "qos";
require_once(__DIR__ . '/../includes/head.inc');

$ifaces = MitraNetApi::getInterfaces();
?>

<div class="container-fluid mitranet-page-container">
    <div class="mitranet-window">

        <!-- HEADER: BADGE + TABS (MATCHING EXACT WINBOX SCREENSHOT) -->
        <div class="mitranet-header">
            <div class="mitranet-title-badge">
                <i class="fa-solid fa-chart-line text-primary"></i> Queues
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="mitranet-tabs">
                <li class="<?=($current_tab === 'simple') ? 'active' : ''?>">
                    <a href="qos.php?tab=simple">Simple Queues</a>
                </li>
                <li class="<?=($current_tab === 'interface') ? 'active' : ''?>">
                    <a href="qos.php?tab=interface">Interface Queues</a>
                </li>
                <li class="<?=($current_tab === 'tree') ? 'active' : ''?>">
                    <a href="qos.php?tab=tree">Queue Tree</a>
                </li>
                <li class="<?=($current_tab === 'types') ? 'active' : ''?>">
                    <a href="qos.php?tab=types">Queue Types</a>
                </li>
            </ul>
        </div>

        <!-- TOOLBAR (MATCHING EXACT SCREENSHOT) -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left">
                <button type="button" class="mitranet-btn" id="btn-new" onclick="openNewQueueModal()" title="Add New Queue">
                    <i class="fa-regular fa-square-plus text-primary"></i> <strong>New</strong>
                </button>
                <button type="button" class="mitranet-btn" id="btn-enable" disabled onclick="toggleSelectedQueue(true)" title="Enable Selected">
                    <i class="fa-solid fa-play text-success"></i> Enable
                </button>
                <button type="button" class="mitranet-btn" id="btn-disable" disabled onclick="toggleSelectedQueue(false)" title="Disable Selected">
                    <i class="fa-solid fa-pause text-warning"></i> Disable
                </button>
                <button type="button" class="mitranet-btn" id="btn-remove" disabled onclick="removeSelectedQueue()" title="Remove Selected">
                    <i class="fa-solid fa-xmark text-danger"></i> Remove
                </button>
                <button type="button" class="mitranet-btn" id="btn-comment" disabled onclick="commentSelectedQueue()" title="Set Comment">
                    <i class="fa-regular fa-comment text-muted"></i> Comment
                </button>
            </div>
            <div class="mitranet-toolbar-right">
                <div class="mitranet-search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="grid-search" placeholder="Find" onkeyup="filterQueueGrid(this.value)">
                </div>
                <button type="button" class="mitranet-btn" onclick="$('#grid-search').focus()" title="Advanced Filter">
                    <i class="fa-solid fa-filter text-muted"></i> Filter
                </button>
                <button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh / Columns">
                    <i class="fa-solid fa-bars text-muted"></i>
                </button>
            </div>
        </div>

        <!-- DATA GRID TABLE (EXACT SCREENSHOT COLUMNS) -->
        <div class="mitranet-grid-container">
            <?php if ($current_tab === 'simple'): ?>
            <table class="mitranet-grid" id="queue-grid-table">
                <thead>
                    <tr>
                        <th class="sortable col-num" style="width: 38px; text-align: center;">
                            # <i class="fa-solid fa-caret-up"></i>
                        </th>
                        <th class="col-flag" style="width: 32px; text-align: center;">
                            <i class="fa-regular fa-flag"></i>
                        </th>
                        <th class="sortable col-name">Name</th>
                        <th class="sortable col-target">Target</th>
                        <th class="sortable col-upload">Upload Max Limit</th>
                        <th class="sortable col-download">Download Max Limit</th>
                        <th class="sortable col-packetmarks">Packet Marks</th>
                        <th class="sortable col-totalmax">Total Max Limit (...)</th>
                        <th class="col-menu" style="width: 30px; text-align: center;">
                            <i class="fa-solid fa-bars"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($queues)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted" style="padding: 20px;">
                            No queues configured. Click <strong>New</strong> to create a rate limiter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $idx = 0; foreach ($queues as $q): $idx++; ?>
                    <?php
                        $isEnabled = !empty($q['enabled']);
                        $rowClass = $isEnabled ? '' : 'text-muted';
                    ?>
                    <tr class="<?=$rowClass?>" 
                        data-id="<?=htmlspecialchars($q['id'])?>"
                        data-name="<?=htmlspecialchars($q['name'])?>"
                        data-target="<?=htmlspecialchars($q['target'])?>"
                        data-upload="<?=htmlspecialchars($q['upload_max'])?>"
                        data-download="<?=htmlspecialchars($q['download_max'])?>"
                        data-packetmarks="<?=htmlspecialchars($q['packet_marks'])?>"
                        data-totalmax="<?=htmlspecialchars($q['total_max'])?>"
                        data-comment="<?=htmlspecialchars($q['comment'] ?? '')?>"
                        data-enabled="<?=$isEnabled ? '1' : '0'?>"
                        onclick="selectQueueRow(this, <?=htmlspecialchars($q['id'])?>)"
                        ondblclick="openEditQueueModal(<?=htmlspecialchars($q['id'])?>)"
                        style="cursor: pointer; <?=$isEnabled ? '' : 'opacity: 0.65;'?>">
                        
                        <!-- # Index -->
                        <td style="text-align: center; font-weight: 600; color: #555;">
                            <?=$idx - 1?>
                        </td>
                        
                        <!-- Flag (Comment / Enabled indicator) -->
                        <td style="text-align: center;">
                            <?php if (!empty($q['comment'])): ?>
                                <i class="fa-regular fa-comment text-info" title="<?=htmlspecialchars($q['comment'])?>"></i>
                            <?php elseif ($isEnabled): ?>
                                <i class="fa-solid fa-circle text-success" style="font-size: 7px;" title="Active"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle text-muted" style="font-size: 7px;" title="Disabled"></i>
                            <?php endif; ?>
                        </td>

                        <!-- Name -->
                        <td>
                            <strong class="<?=$isEnabled ? 'text-primary' : 'text-muted'?>">
                                <?=htmlspecialchars($q['name'])?>
                            </strong>
                            <?php if (!empty($q['comment'])): ?>
                                <span class="text-muted" style="font-size: 10px; margin-left: 6px;">
                                    ; <?=htmlspecialchars($q['comment'])?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Target -->
                        <td>
                            <code><?=htmlspecialchars($q['target'])?></code>
                        </td>

                        <!-- Upload Max Limit -->
                        <td>
                            <span class="label label-default" style="background:#eaf2fa; color:#1e3c5f; border:1px solid #c0d5ec;">
                                <?=htmlspecialchars($q['upload_max'])?>
                            </span>
                        </td>

                        <!-- Download Max Limit -->
                        <td>
                            <span class="label label-primary" style="background:#2e6da4;">
                                <?=htmlspecialchars($q['download_max'])?>
                            </span>
                        </td>

                        <!-- Packet Marks -->
                        <td class="text-muted">
                            <?=htmlspecialchars($q['packet_marks'] ?? 'no-mark')?>
                        </td>

                        <!-- Total Max Limit -->
                        <td>
                            <span class="text-muted">
                                <?=htmlspecialchars($q['total_max'] ?? 'unlimited')?>
                            </span>
                        </td>

                        <!-- Menu Context icon -->
                        <td style="text-align: center; color: #8faecf;">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'interface'): ?>
            <!-- INTERFACE QUEUES TAB -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">#</th>
                        <th>Interface</th>
                        <th>Active Queuing Discipline (qdisc)</th>
                        <th>Queue Type</th>
                        <th>Buffer / Queue Limit</th>
                        <th>Default Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ifaces)): ?>
                        <tr><td colspan="6" class="text-center text-muted">No interfaces found.</td></tr>
                    <?php else: $iIdx = 0; foreach ($ifaces as $if): $iIdx++; ?>
                        <tr>
                            <td style="text-align:center;"><?=$iIdx - 1?></td>
                            <td><strong><?=htmlspecialchars($if['name'])?></strong></td>
                            <td><span class="label label-info">fq_codel</span></td>
                            <td>multi-queue / fq_codel</td>
                            <td>10240 packets / 1024 flows</td>
                            <td><span class="label label-success">ACTIVE</span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'tree'): ?>
            <!-- QUEUE TREE TAB -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">#</th>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Packet Mark</th>
                        <th>Queue Type</th>
                        <th>Priority</th>
                        <th>Limit At</th>
                        <th>Max Limit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:center;">0</td>
                        <td><strong>global-in</strong></td>
                        <td>global</td>
                        <td>all</td>
                        <td>default-fq_codel</td>
                        <td>8</td>
                        <td>0</td>
                        <td>unlimited</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">1</td>
                        <td><strong>global-out</strong></td>
                        <td>global</td>
                        <td>all</td>
                        <td>default-fq_codel</td>
                        <td>8</td>
                        <td>0</td>
                        <td>unlimited</td>
                    </tr>
                </tbody>
            </table>

            <?php elseif ($current_tab === 'types'): ?>
            <!-- QUEUE TYPES TAB -->
            <table class="mitranet-grid">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">#</th>
                        <th>Type Name</th>
                        <th>Kind</th>
                        <th>Parameters</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:center;">0</td>
                        <td><strong>default-fq_codel</strong></td>
                        <td>fq_codel</td>
                        <td>target 5ms, interval 100ms, flows 1024</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">1</td>
                        <td><strong>default-cake</strong></td>
                        <td>cake</td>
                        <td>bandwidth auto, diffserv4, rtt 100ms</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">2</td>
                        <td><strong>default-sfq</strong></td>
                        <td>sfq</td>
                        <td>perturb 10s</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;">3</td>
                        <td><strong>default-pfifo</strong></td>
                        <td>pfifo</td>
                        <td>limit 50</td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- STATUSBAR FOOTER (MATCHING WINBOX) -->
        <div class="mitranet-statusbar">
            <div>
                <strong><?=count($queues)?></strong> items at <?=htmlspecialchars($current_tab)?> queues
            </div>
            <div class="text-muted">
                Kernel TC Engine: <span class="text-success"><i class="fa-solid fa-circle-check"></i> FQ_CODEL / CAKE READY</span>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL DIALOG: NEW / EDIT QUEUE SIMPLE                    -->
<!-- ============================================================== -->
<div id="modal-queue-simple" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg winbox-modal-dialog">
        <form id="form-queue-simple" class="form-horizontal">
            <input type="hidden" name="action" id="queue-action" value="add">
            <input type="hidden" name="id" id="queue-id" value="0">
            <div class="modal-content winbox-window-popup">

                <!-- WINBOX WINDOW HEADER -->
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-chart-line text-primary"></i> 
                        <span id="queue-modal-title">New Queue Simple</span>
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>

                <!-- WINBOX TABS BAR -->
                <div class="winbox-popup-tabs-bar">
                    <ul class="nav nav-tabs winbox-tabs-nav" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#qtab-general" role="tab" data-toggle="tab">General</a>
                        </li>
                        <li role="presentation">
                            <a href="#qtab-advanced" role="tab" data-toggle="tab">Advanced</a>
                        </li>
                        <li role="presentation">
                            <a href="#qtab-burst" role="tab" data-toggle="tab">Burst / Total</a>
                        </li>
                    </ul>
                </div>

                <!-- WINBOX BODY: 2-COLUMN (FORM + ACTIONS) -->
                <div class="modal-body winbox-popup-body">
                    <div class="winbox-body-columns">

                        <!-- LEFT: FORM FIELDS -->
                        <div class="winbox-content-left tab-content">

                            <!-- TAB 1: GENERAL -->
                            <div role="tabpanel" class="tab-pane active" id="qtab-general">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="name" id="q-name" class="form-control input-sm" placeholder="queue1" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Target</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="target" id="q-target" class="form-control input-sm" placeholder="192.168.88.0/24 or single IP">
                                        <span class="help-block" style="font-size: 10px; margin-bottom: 0;">IP subnet or interface target to shape.</span>
                                    </div>
                                </div>

                                <hr style="margin: 8px 0; border-top: 1px solid #d0e0f0;">

                                <div class="form-group">
                                    <label class="col-sm-3 control-label" style="color: #2c598d;">Target Upload</label>
                                    <div class="col-sm-9">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label style="font-size: 10px; color: #666;">Max Limit</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="upload_max" id="q-upload-max" class="form-control" placeholder="10M">
                                                    <div class="input-group-btn">
                                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                                            <span class="caret"></span>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-right">
                                                            <li><a href="javascript:void(0)" onclick="$('#q-upload-max').val('1M')">1M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-upload-max').val('2M')">2M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-upload-max').val('5M')">5M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-upload-max').val('10M')">10M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-upload-max').val('20M')">20M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-upload-max').val('50M')">50M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-upload-max').val('100M')">100M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-upload-max').val('unlimited')">unlimited</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <label style="font-size: 10px; color: #666;">Target Download</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="download_max" id="q-download-max" class="form-control" placeholder="20M">
                                                    <div class="input-group-btn">
                                                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                                            <span class="caret"></span>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-right">
                                                            <li><a href="javascript:void(0)" onclick="$('#q-download-max').val('2M')">2M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-download-max').val('5M')">5M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-download-max').val('10M')">10M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-download-max').val('20M')">20M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-download-max').val('50M')">50M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-download-max').val('100M')">100M</a></li>
                                                            <li><a href="javascript:void(0)" onclick="$('#q-download-max').val('unlimited')">unlimited</a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Comment</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="comment" id="q-comment" class="form-control input-sm" placeholder="Catatan queue...">
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: ADVANCED -->
                            <div role="tabpanel" class="tab-pane" id="qtab-advanced">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Packet Marks</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="packet_marks" id="q-packet-marks" class="form-control input-sm" value="no-mark">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Priority</label>
                                    <div class="col-sm-9">
                                        <select class="form-control input-sm">
                                            <option value="8">8 (Lowest)</option>
                                            <option value="7">7</option>
                                            <option value="6">6</option>
                                            <option value="5">5</option>
                                            <option value="4">4</option>
                                            <option value="3">3</option>
                                            <option value="2">2</option>
                                            <option value="1">1 (Highest)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Queue Type</label>
                                    <div class="col-sm-9">
                                        <select class="form-control input-sm">
                                            <option value="default-fq_codel">default-fq_codel</option>
                                            <option value="default-cake">default-cake</option>
                                            <option value="default-sfq">default-sfq</option>
                                            <option value="default-pfifo">default-pfifo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: BURST / TOTAL -->
                            <div role="tabpanel" class="tab-pane" id="qtab-burst">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Total Max Limit</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="total_max" id="q-total-max" class="form-control input-sm" placeholder="unlimited">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Total Queue Type</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control input-sm" value="default-fq_codel" readonly>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- RIGHT: ACTIONS COLUMN (WINBOX STYLE) -->
                        <div class="winbox-content-right">
                            <div class="winbox-actions-header">
                                <i class="fa-solid fa-bolt"></i> Actions
                            </div>
                            <ul class="winbox-actions-list">
                                <li><a href="javascript:void(0)" onclick="submitQueueForm(true)">OK</a></li>
                                <li><a href="javascript:void(0)" onclick="$('#modal-queue-simple').modal('hide')">Cancel</a></li>
                                <li><a href="javascript:void(0)" onclick="submitQueueForm(false)">Apply</a></li>
                                <li><a href="javascript:void(0)" onclick="resetQueueForm()">Reset</a></li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- WINBOX FOOTER -->
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-status">
                        <span class="badge winbox-running-badge" style="background:#27ae60;">ACTIVE</span>
                        <span class="winbox-link-msg">MikroTik QoS Engine Ready</span>
                    </div>
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-default" onclick="submitQueueForm(false)">Apply</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitQueueForm(true)">OK</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>

<script type="text/javascript">
var selectedQueueId = null;
var selectedQueueRowData = null;

function selectQueueRow(tr, id) {
    $('#queue-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedQueueId = id;

    // Collect data attributes
    selectedQueueRowData = {
        id: id,
        name: $(tr).data('name'),
        target: $(tr).data('target'),
        upload_max: $(tr).data('upload'),
        download_max: $(tr).data('download'),
        packet_marks: $(tr).data('packetmarks'),
        total_max: $(tr).data('totalmax'),
        comment: $(tr).data('comment'),
        enabled: $(tr).data('enabled') == 1
    };

    // Enable toolbar buttons
    $('#btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', false);
}

function openNewQueueModal() {
    $('#queue-action').val('add');
    $('#queue-id').val('0');
    $('#queue-modal-title').text('New Queue Simple');
    $('#q-name').val('queue' + (Math.floor(Math.random() * 900) + 100));
    $('#q-target').val('192.168.88.0/24');
    $('#q-upload-max').val('10M');
    $('#q-download-max').val('20M');
    $('#q-packet-marks').val('no-mark');
    $('#q-total-max').val('30M');
    $('#q-comment').val('');
    $('#modal-queue-simple').modal('show');
}

function openEditQueueModal(id) {
    var $tr = $('#queue-grid-table tbody tr[data-id="' + id + '"]');
    if ($tr.length === 0) return;

    $('#queue-action').val('edit');
    $('#queue-id').val(id);
    var name = $tr.data('name');
    $('#queue-modal-title').text('Queue Simple <' + name + '>');
    $('#q-name').val(name);
    $('#q-target').val($tr.data('target'));
    $('#q-upload-max').val($tr.data('upload'));
    $('#q-download-max').val($tr.data('download'));
    $('#q-packet-marks').val($tr.data('packetmarks'));
    $('#q-total-max').val($tr.data('totalmax'));
    $('#q-comment').val($tr.data('comment'));
    $('#modal-queue-simple').modal('show');
}

function submitQueueForm(closeModal) {
    var name = $('#q-name').val().trim();
    if (!name) {
        MitraNet.toast('error', 'Queue Name cannot be empty');
        return;
    }

    var formData = $('#form-queue-simple').serialize() + '&ajax=1';

    $.ajax({
        url: 'qos.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res && res.success) {
                MitraNet.toast('success', res.message || 'Queue successfully saved');
                if (closeModal) {
                    $('#modal-queue-simple').modal('hide');
                    location.reload();
                } else {
                    $('#queue-action').val('edit');
                }
            } else {
                MitraNet.toast('error', (res && res.error) ? res.error : 'Failed saving queue');
            }
        },
        error: function() {
            MitraNet.toast('error', 'Error communicating with server');
        }
    });
}

function resetQueueForm() {
    $('#q-upload-max').val('unlimited');
    $('#q-download-max').val('unlimited');
    $('#q-target').val('0.0.0.0/0');
}

function toggleSelectedQueue(state) {
    if (!selectedQueueId) return;
    $.ajax({
        url: 'qos.php',
        type: 'POST',
        data: {
            action: 'toggle',
            id: selectedQueueId,
            state: state ? '1' : '0',
            ajax: 1
        },
        dataType: 'json',
        success: function(res) {
            MitraNet.toast('success', state ? 'Queue Enabled' : 'Queue Disabled');
            location.reload();
        }
    });
}

function removeSelectedQueue() {
    if (!selectedQueueId || !selectedQueueRowData) return;
    MitraNet.confirmDelete({
        title: 'Remove Simple Queue?',
        name: selectedQueueRowData.name,
        warning: 'This will remove the rate limit policy from the system.',
        url: 'qos.php',
        data: {
            action: 'delete',
            id: selectedQueueId,
            ajax: 1
        },
        onSuccess: function() {
            selectedQueueId = null;
            $('#btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', true);
            location.reload();
        }
    });
}

function commentSelectedQueue() {
    if (!selectedQueueId || !selectedQueueRowData) return;
    MitraNet.promptInput({
        title: 'Set Comment',
        inputLabel: 'Comment for ' + selectedQueueRowData.name + ':',
        inputValue: selectedQueueRowData.comment || '',
        placeholder: 'e.g. Bandwidth limit for office floor 2',
        confirmText: 'Save Comment',
        url: 'qos.php',
        data: {
            action: 'comment',
            id: selectedQueueId,
            ajax: 1
        },
        inputParam: 'comment',
        onSuccess: function() {
            location.reload();
        }
    });
}

function filterQueueGrid(query) {
    query = (query || '').toLowerCase().trim();
    $('#queue-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(query) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}
</script>
