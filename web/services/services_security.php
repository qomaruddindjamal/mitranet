<?php
/*
 * services_security.php - MitraNet Security Services (IP Services / Management Daemons)
 * Faithful recreation of MikroTik WinBox IP > Services management interface
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$configFile = '/etc/mitranet/security_services.json';

// Helper: load Security Services configuration
function loadSecurityServices($configFile) {
    if (file_exists($configFile) && is_readable($configFile)) {
        $content = @file_get_contents($configFile);
        $data = json_decode($content, true);
        if (is_array($data) && isset($data['services'])) {
            return $data['services'];
        }
    }

    // Default system services matching MikroTik RouterOS IP Services reference screenshot
    return [
        [
            'id' => 1,
            'flag' => 'XI',
            'name' => 'api',
            'port' => 6692,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => false
        ],
        [
            'id' => 2,
            'flag' => 'XI',
            'name' => 'api-ssl',
            'port' => 8729,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => 'none',
            'tls_version' => 'any',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => false
        ],
        [
            'id' => 3,
            'flag' => 'D',
            'name' => 'btest',
            'port' => 2000,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 4,
            'flag' => 'D',
            'name' => 'dhcp',
            'port' => 67,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'udp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 5,
            'flag' => 'D',
            'name' => 'discover',
            'port' => 5678,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'udp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 6,
            'flag' => 'XI',
            'name' => 'ftp',
            'port' => 21,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => false
        ],
        [
            'id' => 7,
            'flag' => 'D',
            'name' => 'ipsec',
            'port' => 4500,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'udp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 8,
            'flag' => 'D',
            'name' => 'ipsec',
            'port' => 500,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'udp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 9,
            'flag' => 'D',
            'name' => 'l2tp',
            'port' => 1701,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'udp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 10,
            'flag' => 'D',
            'name' => 'ntp',
            'port' => 123,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'udp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 11,
            'flag' => 'D',
            'name' => 'ppp',
            'port' => 1723,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 12,
            'flag' => 'D',
            'name' => 'resolver',
            'port' => 53,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 13,
            'flag' => 'D',
            'name' => 'resolver',
            'port' => 53,
            'available_from' => '',
            'vrf' => '',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => '',
            'remote' => '',
            'local' => '',
            'protocol' => 'udp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 14,
            'flag' => '',
            'name' => 'reverse-proxy',
            'display_name' => 'revers...',
            'port' => 443,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => 'none',
            'tls_version' => 'any',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => true
        ],
        [
            'id' => 15,
            'flag' => 'XI',
            'name' => 'ssh',
            'port' => 22,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => false
        ],
        [
            'id' => 16,
            'flag' => 'XI',
            'name' => 'telnet',
            'port' => 23,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => false
        ],
        [
            'id' => 17,
            'flag' => '',
            'name' => 'winbox',
            'port' => 8291,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => true,
            'is_parent' => true,
            'children' => [
                [
                    'id' => 18,
                    'flag' => 'Dc',
                    'name' => 'winbox-session',
                    'display_name' => 'win...',
                    'port' => 8291,
                    'available_from' => '',
                    'vrf' => '',
                    'certificate' => '',
                    'tls_version' => '',
                    'max_sessions' => '',
                    'remote' => '10.10.66.150:53384',
                    'local' => '103.247.13.9',
                    'protocol' => 'tcp',
                    'netns' => '',
                    'container' => '',
                    'enabled' => true
                ]
            ]
        ],
        [
            'id' => 19,
            'flag' => 'XI',
            'name' => 'www',
            'port' => 80,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => '',
            'tls_version' => '',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => false
        ],
        [
            'id' => 20,
            'flag' => 'XI',
            'name' => 'www-ssl',
            'display_name' => 'www-...',
            'port' => 443,
            'available_from' => '',
            'vrf' => 'main',
            'certificate' => 'none',
            'tls_version' => 'any',
            'max_sessions' => 20,
            'remote' => '',
            'local' => '',
            'protocol' => 'tcp',
            'netns' => '',
            'container' => '',
            'enabled' => false
        ]
    ];
}

function saveSecurityServices($configFile, $services) {
    $dir = dirname($configFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return @file_put_contents($configFile, json_encode(['services' => $services], JSON_PRETTY_PRINT)) !== false;
}

$services = loadSecurityServices($configFile);

// Handle AJAX actions (Enable/Disable/Edit Service)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $state = ($_POST['state'] ?? '1') === '1';

        foreach ($services as &$s) {
            if ($s['id'] === $id) {
                $s['enabled'] = $state;
                $s['flag'] = $state ? '' : 'XI';
                break;
            }
            if (!empty($s['children'])) {
                foreach ($s['children'] as &$c) {
                    if ($c['id'] === $id) {
                        $c['enabled'] = $state;
                        break;
                    }
                }
            }
        }
        saveSecurityServices($configFile, $services);

        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Service status updated.']);
            exit;
        }
    }

    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $port = intval($_POST['port'] ?? 0);
        $available_from = trim($_POST['available_from'] ?? '');
        $certificate = trim($_POST['certificate'] ?? '');
        $max_sessions = intval($_POST['max_sessions'] ?? 20);

        foreach ($services as &$s) {
            if ($s['id'] === $id) {
                if ($port > 0) $s['port'] = $port;
                $s['available_from'] = $available_from;
                $s['certificate'] = $certificate;
                $s['max_sessions'] = $max_sessions;
                break;
            }
        }
        saveSecurityServices($configFile, $services);

        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Service configuration saved.']);
            exit;
        }
    }
}

$pgtitle = ["Services", "Security Services"];
$selected_menu = "services";
require_once(__DIR__ . '/../includes/head.inc');
?>

<div class="container-fluid mitranet-page-container">
    <div class="mitranet-window">

        <!-- HEADER: BADGE (MATCHING WINBOX SCREENSHOT) -->
        <div class="mitranet-header" style="justify-content: flex-start;">
            <div class="mitranet-title-badge">
                <i class="fa-solid fa-shield-halved text-primary"></i> Services
                <i class="fa-solid fa-caret-down"></i>
            </div>
        </div>

        <!-- TOOLBAR: ENABLE / DISABLE + FIND / FILTER -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left">
                <button type="button" class="mitranet-btn" id="btn-enable" disabled onclick="toggleSelectedService(true)" title="Enable Service">
                    <i class="fa-solid fa-play text-success"></i> Enable
                </button>
                <button type="button" class="mitranet-btn" id="btn-disable" disabled onclick="toggleSelectedService(false)" title="Disable Service">
                    <i class="fa-solid fa-pause text-muted"></i> Disable
                </button>
            </div>
            <div class="mitranet-toolbar-right">
                <div class="mitranet-search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="grid-search" placeholder="Find" onkeyup="filterServiceGrid(this.value)">
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
            <table class="mitranet-grid" id="services-grid-table">
                <thead>
                    <tr>
                        <th class="col-flag" style="width: 38px; text-align: center;">
                            <i class="fa-regular fa-flag"></i>
                        </th>
                        <th class="sortable col-name">Name <i class="fa-solid fa-caret-up"></i></th>
                        <th class="sortable col-port">Port</th>
                        <th class="sortable col-available">Available From</th>
                        <th class="sortable col-vrf">VRF</th>
                        <th class="sortable col-cert">Certificate</th>
                        <th class="sortable col-tls">TLS Ver...</th>
                        <th class="sortable col-sessions">Max Ses...</th>
                        <th class="sortable col-remote">Remote</th>
                        <th class="sortable col-local">Local</th>
                        <th class="sortable col-protocol">Protocol</th>
                        <th class="sortable col-netns">NetNS</th>
                        <th class="sortable col-container">Container</th>
                        <th class="col-menu" style="width: 30px; text-align: center;">
                            <i class="fa-solid fa-bars"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($services as $s): ?>
                <?php
                    $is_enabled = !empty($s['enabled']);
                    $has_children = !empty($s['children']);
                    $flag = $s['flag'] ?? '';
                    $row_style = $is_enabled ? '' : 'color: #8c9ba9;';
                ?>
                    <tr class="<?=$is_enabled ? '' : 'text-muted'?>"
                        data-id="<?=$s['id']?>"
                        data-name="<?=htmlspecialchars($s['name'])?>"
                        data-port="<?=htmlspecialchars($s['port'])?>"
                        data-available="<?=htmlspecialchars($s['available_from'])?>"
                        data-certificate="<?=htmlspecialchars($s['certificate'])?>"
                        data-sessions="<?=htmlspecialchars($s['max_sessions'])?>"
                        data-enabled="<?=$is_enabled ? '1' : '0'?>"
                        onclick="selectServiceRow(this, <?=$s['id']?>)"
                        ondblclick="openEditServiceModal(<?=$s['id']?>)"
                        style="cursor: pointer; <?=$row_style?>">

                        <!-- Flag Column -->
                        <td style="text-align: center; font-weight: 600; font-size: 10px; color: <?=$flag === 'XI' ? '#e74c3c' : ($flag === 'D' ? '#555' : '#27ae60')?>;">
                            <?=htmlspecialchars($flag)?>
                        </td>

                        <!-- Name Column -->
                        <td>
                            <?php if ($has_children): ?>
                                <i class="fa-solid fa-minus" style="font-size: 9px; margin-right: 4px; color: #888;"></i>
                            <?php else: ?>
                                <span style="display:inline-block; width: 6px; height: 6px; border-radius: 50%; background: <?=$is_enabled ? '#2980b9' : '#bdc3c7'?>; margin-right: 6px;"></span>
                            <?php endif; ?>
                            <strong><?=htmlspecialchars($s['display_name'] ?? $s['name'])?></strong>
                        </td>

                        <!-- Port -->
                        <td><?=htmlspecialchars($s['port'])?></td>

                        <!-- Available From -->
                        <td><?=htmlspecialchars($s['available_from'] ?: '')?></td>

                        <!-- VRF -->
                        <td><?=htmlspecialchars($s['vrf'] ?: '')?></td>

                        <!-- Certificate -->
                        <td><?=htmlspecialchars($s['certificate'] ?: '')?></td>

                        <!-- TLS Version -->
                        <td><?=htmlspecialchars($s['tls_version'] ?: '')?></td>

                        <!-- Max Sessions -->
                        <td><?=htmlspecialchars($s['max_sessions'] ?: '')?></td>

                        <!-- Remote -->
                        <td><?=htmlspecialchars($s['remote'] ?: '')?></td>

                        <!-- Local -->
                        <td><?=htmlspecialchars($s['local'] ?: '')?></td>

                        <!-- Protocol -->
                        <td><?=htmlspecialchars($s['protocol'])?></td>

                        <!-- NetNS -->
                        <td><?=htmlspecialchars($s['netns'] ?: '')?></td>

                        <!-- Container -->
                        <td><?=htmlspecialchars($s['container'] ?: '')?></td>

                        <!-- Menu Options -->
                        <td style="text-align: center; color: #8faecf;">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </td>
                    </tr>

                    <!-- Child sessions if present (e.g. winbox active session) -->
                    <?php if ($has_children): ?>
                        <?php foreach ($s['children'] as $child): ?>
                        <tr class="child-service-row"
                            data-id="<?=$child['id']?>"
                            data-name="<?=htmlspecialchars($child['name'])?>"
                            data-port="<?=htmlspecialchars($child['port'])?>"
                            data-enabled="1"
                            onclick="selectServiceRow(this, <?=$child['id']?>)"
                            style="cursor: pointer; background: #fafbfc;">
                            <td style="text-align: center; font-weight: 600; font-size: 10px; color: #2980b9;">
                                <?=htmlspecialchars($child['flag'])?>
                            </td>
                            <td style="padding-left: 20px;">
                                <span style="display:inline-block; width: 5px; height: 5px; border-radius: 50%; background: #27ae60; margin-right: 6px;"></span>
                                <?=htmlspecialchars($child['display_name'] ?? $child['name'])?>
                            </td>
                            <td><?=htmlspecialchars($child['port'])?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><code><?=htmlspecialchars($child['remote'])?></code></td>
                            <td><code><?=htmlspecialchars($child['local'])?></code></td>
                            <td><?=htmlspecialchars($child['protocol'])?></td>
                            <td></td>
                            <td></td>
                            <td style="text-align: center; color: #8faecf;">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- STATUSBAR FOOTER (MATCHING WINBOX) -->
        <div class="mitranet-statusbar">
            <div>
                <strong><?=count($services)?></strong> IP services configured
            </div>
            <div class="text-muted">
                System Security Engine: <span class="text-success"><i class="fa-solid fa-circle-check"></i> PROTECTED</span>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================== -->
<!-- WINBOX MODAL DIALOG: EDIT SECURITY SERVICE                     -->
<!-- ============================================================== -->
<div id="modal-service-edit" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-md winbox-modal-dialog">
        <form id="form-service-edit" class="form-horizontal">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="edit-service-id" value="0">
            <div class="modal-content winbox-window-popup">

                <!-- WINBOX WINDOW HEADER -->
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-shield-halved text-primary"></i>
                        <span id="edit-service-title">IP Service <api></span>
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>

                <!-- WINBOX BODY: 2-COLUMN -->
                <div class="modal-body winbox-popup-body">
                    <div class="winbox-body-columns">

                        <!-- LEFT: FORM FIELDS -->
                        <div class="winbox-content-left" style="padding: 12px 10px;">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Name</label>
                                <div class="col-sm-8">
                                    <input type="text" id="edit-service-name" class="form-control input-sm" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Port</label>
                                <div class="col-sm-8">
                                    <input type="number" name="port" id="edit-service-port" class="form-control input-sm" min="1" max="65535" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Available From</label>
                                <div class="col-sm-8">
                                    <input type="text" name="available_from" id="edit-service-available" class="form-control input-sm" placeholder="0.0.0.0/0 or IP address">
                                    <span class="help-block" style="font-size: 10px; margin-bottom: 0;">Restrict access to specific IP or subnet prefix.</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Certificate</label>
                                <div class="col-sm-8">
                                    <select name="certificate" id="edit-service-certificate" class="form-control input-sm">
                                        <option value="none">none</option>
                                        <option value="mitranet-server">mitranet-server</option>
                                        <option value="default-cert">default-cert</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Max Sessions</label>
                                <div class="col-sm-8">
                                    <input type="number" name="max_sessions" id="edit-service-sessions" class="form-control input-sm" min="1" max="1000" value="20">
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT: ACTIONS COLUMN (WINBOX STYLE) -->
                        <div class="winbox-content-right">
                            <div class="winbox-actions-header">
                                <i class="fa-solid fa-bolt"></i> Actions
                            </div>
                            <ul class="winbox-actions-list">
                                <li><a href="javascript:void(0)" onclick="submitServiceForm(true)">OK</a></li>
                                <li><a href="javascript:void(0)" onclick="$('#modal-service-edit').modal('hide')">Cancel</a></li>
                                <li><a href="javascript:void(0)" onclick="submitServiceForm(false)">Apply</a></li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- WINBOX FOOTER -->
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-status">
                        <span class="badge winbox-running-badge" style="background:#27ae60;">ACTIVE</span>
                        <span class="winbox-link-msg">IP Service Configured</span>
                    </div>
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-default" onclick="submitServiceForm(false)">Apply</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitServiceForm(true)">OK</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>

<script type="text/javascript">
var selectedServiceId = null;
var selectedServiceRowData = null;

function selectServiceRow(tr, id) {
    $('#services-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedServiceId = id;

    selectedServiceRowData = {
        id: id,
        name: $(tr).data('name'),
        port: $(tr).data('port'),
        available: $(tr).data('available'),
        certificate: $(tr).data('certificate'),
        sessions: $(tr).data('sessions'),
        enabled: $(tr).data('enabled') == 1
    };

    $('#btn-enable, #btn-disable').prop('disabled', false);
    if (selectedServiceRowData.enabled) {
        $('#btn-enable').prop('disabled', true);
        $('#btn-disable').prop('disabled', false).find('i').removeClass('text-muted').addClass('text-warning');
    } else {
        $('#btn-enable').prop('disabled', false);
        $('#btn-disable').prop('disabled', true).find('i').removeClass('text-warning').addClass('text-muted');
    }
}

function openEditServiceModal(id) {
    var $tr = $('#services-grid-table tbody tr[data-id="' + id + '"]');
    if ($tr.length === 0) return;

    var name = $tr.data('name');
    $('#edit-service-id').val(id);
    $('#edit-service-title').text('IP Service <' + name + '>');
    $('#edit-service-name').val(name);
    $('#edit-service-port').val($tr.data('port'));
    $('#edit-service-available').val($tr.data('available') || '');
    $('#edit-service-certificate').val($tr.data('certificate') || 'none');
    $('#edit-service-sessions').val($tr.data('sessions') || 20);

    $('#modal-service-edit').modal('show');
}

function submitServiceForm(closeModal) {
    var formData = $('#form-service-edit').serialize() + '&ajax=1';

    $.ajax({
        url: 'services_security.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res && res.success) {
                MitraNet.toast('success', res.message || 'Service saved');
                if (closeModal) {
                    $('#modal-service-edit').modal('hide');
                    location.reload();
                }
            } else {
                MitraNet.toast('error', (res && res.error) ? res.error : 'Failed saving service');
            }
        },
        error: function() {
            MitraNet.toast('error', 'Error communicating with server');
        }
    });
}

function toggleSelectedService(state) {
    if (!selectedServiceId) return;

    $.ajax({
        url: 'services_security.php',
        type: 'POST',
        data: {
            action: 'toggle',
            id: selectedServiceId,
            state: state ? '1' : '0',
            ajax: 1
        },
        dataType: 'json',
        success: function(res) {
            MitraNet.toast('success', state ? 'Service Enabled' : 'Service Disabled');
            location.reload();
        }
    });
}

function filterServiceGrid(query) {
    query = (query || '').toLowerCase().trim();
    $('#services-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(query) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}
</script>
