<?php
/*
 * tools_speedtest.php - MitraNet Tools: Speedtest
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

// Handle AJAX actions
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1') {
    header('Content-Type: application/json');
    $action = $_REQUEST['action'] ?? '';

    if ($action === 'servers') {
        $servers = MitraNetApi::getSpeedtestServers();
        echo json_encode(['success' => true, 'servers' => $servers]);
        exit;
    }

    if ($action === 'history') {
        $history = MitraNetApi::getSpeedtestHistory();
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }

    if ($action === 'clear_history') {
        $res = MitraNetApi::clearSpeedtestHistory();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'run') {
        $engine = $_POST['engine'] ?? 'ookla';
        $interface = $_POST['interface'] ?? '';
        $serverId = $_POST['server_id'] ?? '';
        $res = MitraNetApi::runSpeedtest($engine, $interface, $serverId);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            echo json_encode(['success' => true, 'data' => $res['data']['data']]);
        } else {
            $err = $res['data']['error'] ?? 'Pengujian Speedtest gagal dijalankan.';
            echo json_encode(['success' => false, 'error' => $err]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

$pgtitle = array("Tools", "Speedtest");
$selected_menu = "tools";
require_once(__DIR__ . '/../includes/head.inc');

$ifaces = MitraNetApi::getInterfaces();
?>

<style>
.speedtest-container {
    padding: 10px 0;
}
.kpi-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    margin-bottom: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
    text-align: center;
}
body.theme-dark .kpi-card,
body.theme-matrix .kpi-card {
    background: #1e293b;
    border-color: #334155;
    color: #f8fafc;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.kpi-label {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 8px;
}
body.theme-dark .kpi-label,
body.theme-matrix .kpi-label {
    color: #94a3b8;
}
.kpi-value {
    font-size: 38px;
    font-weight: 700;
    line-height: 1.1;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.kpi-unit {
    font-size: 16px;
    font-weight: 500;
    color: #64748b;
    margin-left: 4px;
}
.kpi-dl { color: #0284c7; }
.kpi-ul { color: #16a34a; }
.kpi-ping { color: #d97706; }
.kpi-jitter { color: #9333ea; }

.status-badge {
    display: inline-block;
    padding: 8px 18px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
    transition: all 0.3s;
}
body.theme-dark .status-badge {
    background: #334155;
    color: #cbd5e1;
}
.status-badge.running {
    background: #e0f2fe;
    color: #0369a1;
    box-shadow: 0 0 10px rgba(2,132,199,0.3);
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.85; transform: scale(1.02); }
}

.btn-speedtest-start {
    font-size: 16px;
    font-weight: 600;
    padding: 12px 28px;
    border-radius: 8px;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    border: none;
    color: #fff;
    box-shadow: 0 4px 10px rgba(2,132,199,0.3);
    transition: all 0.2s;
    cursor: pointer;
}
.btn-speedtest-start:hover:not(:disabled) {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
    box-shadow: 0 6px 14px rgba(2,132,199,0.4);
    transform: translateY(-1px);
    color: #fff;
}
.btn-speedtest-start:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.speed-progress-bar {
    height: 10px;
    border-radius: 5px;
    background: #e2e8f0;
    overflow: hidden;
    margin: 15px 0;
    display: none;
}
.speed-progress-inner {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #0284c7, #16a34a);
    transition: width 0.4s ease;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}
body.theme-dark .info-item {
    border-bottom-color: #334155;
}
.info-item:last-child { border-bottom: none; }
.info-title { color: #64748b; font-weight: 500; }
body.theme-dark .info-title { color: #94a3b8; }
.info-val { font-weight: 600; }
</style>

<div class="panel panel-default speedtest-container">
    <div class="panel-heading">
        <h2 class="panel-title">
            <i class="fa-solid fa-gauge-high"></i> &nbsp;Speedtest Internet Bandwidth & Latency Tool            <span class="pull-right">
                <span class="label label-info"><i class="fa-solid fa-bolt"></i> Speedtest Official Native</span>
                <span class="label label-success"><i class="fa-brands fa-debian"></i> Debian 13 CLI</span>
            </span>
        </h2>
    </div>
    <div class="panel-body">
        
        <!-- Controls & Options -->
        <div class="row speedtest-header-row">
            <div class="col-md-3">
                <label for="engine-select"><strong>Engine:</strong></label>
                <select id="engine-select" class="form-control">
                    <option value="ookla" selected>Speedtest Native CLI (Multi-stream, Fast)</option>
                    <option value="sivel">Speedtest-CLI (Python Engine)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="interface-select"><strong>Interface / Jalur Routing:</strong></label>
                <select id="interface-select" class="form-control">
                    <option value="" selected>Default Gateway (Automatic Routing)</option>
                    <?php if (!empty($ifaces)): ?>
                        <?php foreach ($ifaces as $if): ?>
                            <option value="<?=htmlspecialchars($if['name'])?>">
                                <?=htmlspecialchars($if['name'])?> (<?=htmlspecialchars($if['ip'] ?? 'No IP')?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small class="text-muted"><i class="fa-solid fa-info-circle"></i> Pilih interface untuk menguji throughput spesifik.</small>
            </div>
            <div class="col-md-3">
                <label for="server-select"><strong>Test Server:</strong></label>
                <select id="server-select" class="form-control">
                    <option value="">Auto (Best Latency Server)</option>
                </select>
            </div>
            <div class="col-md-2 speedtest-col-btn">
                <button type="button" id="btn-start" class="btn btn-speedtest-start btn-block" onclick="if(typeof window.startSpeedtest === 'function'){ window.startSpeedtest(); } return false;">
                    <i class="fa-solid fa-play"></i> &nbsp;Start Test                </button>
            </div>
        </div>

        <!-- Progress Bar & Status -->
        <div class="row">
            <div class="col-md-12 text-center speedtest-col-center">
                <span id="test-status" class="status-badge">
                    <i class="fa-solid fa-circle-play text-primary"></i> Siap pengujian (Klik 'Start Test')                </span>
            </div>
            <div class="col-md-12">
                <div id="speed-progress" class="speed-progress-bar">
                    <div id="speed-progress-inner" class="speed-progress-inner"></div>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="fa-solid fa-download"></i> Download</div>
                    <div class="kpi-value kpi-dl">
                        <span id="val-dl">0.00</span><span class="kpi-unit">Mbps</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="fa-solid fa-upload"></i> Upload</div>
                    <div class="kpi-value kpi-ul">
                        <span id="val-ul">0.00</span><span class="kpi-unit">Mbps</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="fa-solid fa-stopwatch"></i> Ping / Latency</div>
                    <div class="kpi-value kpi-ping">
                        <span id="val-ping">0</span><span class="kpi-unit">ms</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="kpi-card">
                    <div class="kpi-label"><i class="fa-solid fa-wave-square"></i> Jitter</div>
                    <div class="kpi-value kpi-jitter">
                        <span id="val-jitter">0</span><span class="kpi-unit">ms</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Connection Info -->
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3 class="panel-title"><i class="fa-solid fa-circle-info"></i> Connection Details</h3></div>
                    <div class="panel-body">
                        <div class="info-item">
                            <span class="info-title">ISP Provider:</span>
                            <span class="info-val" id="det-isp">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-title">Client IP:</span>
                            <span class="info-val" id="det-ip">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-title">Packet Loss:</span>
                            <span class="info-val" id="det-loss">-</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3 class="panel-title"><i class="fa-solid fa-server"></i> Target Server</h3></div>
                    <div class="panel-body">
                        <div class="info-item">
                            <span class="info-title">Server Sponsor:</span>
                            <span class="info-val" id="det-server">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-title">Tested Interface:</span>
                            <span class="info-val" id="det-if">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-title">Online Result:</span>
                            <span class="info-val" id="det-url">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="panel panel-default panel-mt-10">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa-solid fa-clock-rotate-left"></i> Speedtest History (Last 20 Tests)                    <button type="button" id="btn-clear-history" class="btn btn-xs btn-default pull-right">
                        <i class="fa-solid fa-trash"></i> Clear History                    </button>
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Engine</th>
                            <th>Interface</th>
                            <th>Server</th>
                            <th>Ping</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Link</th>
                        </tr>
                    </thead>
                    <tbody id="history-tbody">
                        <tr><td colspan="8" class="text-center text-muted td-empty-muted">Belum ada data pengujian. Silakan klik tombol 'Start Test' untuk melakukan pengujian kecepatan riil.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
function initSpeedtest() {
    // 1. Load Server list on init
    $.ajax({
        url: '/tools_speedtest.php',
        type: 'GET',
        data: { ajax: '1', action: 'servers' },
        dataType: 'json',
        success: function(res) {
            if (res && res.success && res.servers && res.servers.length > 0) {
                var sel = $('#server-select');
                sel.empty().append($('<option>', {
                    value: '',
                    text: 'Auto (Best Latency Server)'
                }));
                res.servers.forEach(function(s) {
                    sel.append($('<option>', {
                        value: s.id,
                        text: s.name + ' - ' + s.location + ' (' + s.country + ')'
                    }));
                });
            }
        }
    });

    // 2. Function to refresh history table
    window.refreshHistory = function() {
        $.ajax({
            url: '/tools_speedtest.php',
            type: 'GET',
            data: { ajax: '1', action: 'history' },
            dataType: 'json',
            success: function(res) {
                if (res && res.success && res.history) {
                    var tbody = $('#history-tbody');
                    tbody.empty();
                    if (res.history.length === 0) {
                        tbody.html('<tr><td colspan="8" class="text-center text-muted td-empty-muted">Belum ada data pengujian. Silakan klik tombol "Start Test" untuk melakukan pengujian kecepatan riil.</td></tr>');
                    } else {
                        res.history.forEach(function(h) {
                            var linkHtml = h.url ? '<a href="' + h.url + '" target="_blank" class="btn btn-xs btn-info"><i class="fa-solid fa-arrow-up-right-from-square"></i> Result</a>' : '-';
                            var row = $('<tr>');
                            row.append($('<td>').text(h.timestamp));
                            row.append($('<td>').html('<span class="label label-default">' + h.engine + '</span>'));
                            row.append($('<td>').text(h.interface));
                            row.append($('<td>').text(h.server));
                            row.append($('<td>').html('<strong>' + h.ping + '</strong> ms'));
                            row.append($('<td>').html('<strong class="text-primary">' + h.download + '</strong> Mbps'));
                            row.append($('<td>').html('<strong class="text-success">' + h.upload + '</strong> Mbps'));
                            row.append($('<td>').html(linkHtml));
                            tbody.append(row);
                        });
                    }
                }
            }
        });
    };

    // Load initial history
    window.refreshHistory();

    // 3. Start Speedtest execution handler
    window.startSpeedtest = function() {
        var btn = $('#btn-start');
        if (btn.prop('disabled')) {
            return false;
        }

        btn.prop('disabled', true);
        btn.html('<i class="fa-solid fa-spinner fa-spin"></i> &nbsp;Testing...');

        $('#test-status').removeClass().addClass('status-badge running').html('<i class="fa-solid fa-spinner fa-spin"></i> Sedang Menjalankan Pengujian (15-30 detik)...');
        $('#speed-progress').show();
        $('#speed-progress-inner').css('width', '20%');

        var engine = $('#engine-select').val() || 'ookla';
        var iface = $('#interface-select').val() || '';
        var srv = $('#server-select').val() || '';

        var progressVal = 20;
        var progressTimer = setInterval(function() {
            progressVal += 10;
            if (progressVal > 85) progressVal = 85;
            $('#speed-progress-inner').css('width', progressVal + '%');
        }, 2000);

        var postData = {
            ajax: '1',
            action: 'run',
            engine: engine,
            interface: iface,
            server_id: srv
        };

        $.ajax({
            url: '/tools_speedtest.php',
            type: 'POST',
            data: postData,
            dataType: 'json',
            timeout: 120000,
            success: function(res) {
                clearInterval(progressTimer);
                $('#speed-progress-inner').css('width', '100%');
                setTimeout(function() { $('#speed-progress').fadeOut(); }, 1200);

                btn.prop('disabled', false);
                btn.html('<i class="fa-solid fa-play"></i> &nbsp;Start Test');

                if (res && res.success && res.data) {
                    var d = res.data;
                    $('#test-status').removeClass().addClass('status-badge').html('<i class="fa-solid fa-circle-check text-success"></i> Pengujian Selesai!');

                    $('#val-dl').text(d.download);
                    $('#val-ul').text(d.upload);
                    $('#val-ping').text(d.ping);
                    $('#val-jitter').text(d.jitter);

                    $('#det-isp').text(d.isp);
                    $('#det-ip').text(d.client_ip);
                    $('#det-loss').text(d.loss ? d.loss + '%' : '-');
                    $('#det-server').text(d.server);
                    $('#det-if').text(d.interface);

                    if (d.url) {
                        $('#det-url').html('<a href="' + d.url + '" target="_blank" class="btn btn-xs btn-info"><i class="fa-solid fa-link"></i> View Certificate</a>');
                    } else {
                        $('#det-url').text('-');
                    }

                    if (typeof window.refreshHistory === 'function') {
                        window.refreshHistory();
                    }
                } else {
                    var errMsg = (res && res.error) ? res.error : 'Pengujian gagal mendapatkan hasil';
                    $('#test-status').removeClass().addClass('status-badge').html('<i class="fa-solid fa-triangle-exclamation text-danger"></i> Error: ' + errMsg);
                    alert(errMsg);
                }
            },
            error: function(xhr, status, err) {
                clearInterval(progressTimer);
                $('#speed-progress').fadeOut();
                btn.prop('disabled', false);
                btn.html('<i class="fa-solid fa-play"></i> &nbsp;Start Test');
                var errMsg = 'Koneksi pengujian gagal (' + (err || status) + '). Status HTTP: ' + xhr.status;
                $('#test-status').removeClass().addClass('status-badge').html('<i class="fa-solid fa-triangle-exclamation text-danger"></i> ' + errMsg);
                alert(errMsg);
            }
        });
        return false;
    };

    // 4. Attach click handler to Start Button
    $('#btn-start').off('click').on('click', function(e) {
        e.preventDefault();
        window.startSpeedtest();
        return false;
    });

    // 5. Clear History button handler
    $('#btn-clear-history').off('click').on('click', function(e) {
        e.preventDefault();
        if (confirm('Hapus seluruh riwayat pengujian Speedtest?')) {
            $.ajax({
                url: '/tools_speedtest.php',
                type: 'POST',
                data: { ajax: '1', action: 'clear_history' },
                dataType: 'json',
                success: function() {
                    $('#history-tbody').html('<tr><td colspan="8" class="text-center text-muted td-empty-muted">Belum ada data pengujian. Silakan klik tombol "Start Test" untuk melakukan pengujian kecepatan riil.</td></tr>');
                    $('#val-dl').text('0.00');
                    $('#val-ul').text('0.00');
                    $('#val-ping').text('0');
                    $('#val-jitter').text('0');
                    $('#det-isp').text('-');
                    $('#det-ip').text('-');
                    $('#det-loss').text('-');
                    $('#det-server').text('-');
                    $('#det-if').text('-');
                    $('#det-url').text('-');
                }
            });
        }
        return false;
    });
}

$(document).ready(initSpeedtest);
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
