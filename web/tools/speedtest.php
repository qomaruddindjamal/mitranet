<?php
/*
 * tools/speedtest.php - MitraNet Tools: Internet Speedtest & Bandwidth Benchmarking
 * Faithful WinBox & Modern Dashboard UI for Debian 13 (Rinjani)
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

    if ($action === 'benchmark_history') {
        $history = MitraNetApi::getBenchmarkHistory();
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }

    if ($action === 'clear_benchmark_history') {
        $res = MitraNetApi::clearBenchmarkHistory();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'run_benchmark') {
        $mode = $_POST['mode'] ?? 'cdn';
        $interface = $_POST['interface'] ?? '';
        $sizeMb = intval($_POST['size_mb'] ?? 25);
        $targetHost = trim($_POST['target_host'] ?? '103.93.162.168');
        $targetPort = intval($_POST['target_port'] ?? 5201);

        $res = MitraNetApi::runBenchmark($mode, $interface, $sizeMb, $targetHost, $targetPort);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            echo json_encode(['success' => true, 'data' => $res['data']['data']]);
        } else {
            $err = $res['data']['error'] ?? 'Pengujian Benchmark gagal dieksekusi.';
            echo json_encode(['success' => false, 'error' => $err]);
        }
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
/* Modern WinBox Styled Speedtest Dashboard */
.speedtest-page {
    margin-top: 5px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.speedtest-panel {
    background: #ffffff;
    border: 1px solid #dcdfe6;
    border-radius: 6px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
}
body.theme-dark .speedtest-panel {
    background: #1e222d;
    border-color: #2e3446;
}

/* WinBox Header Badge */
.speedtest-header {
    background: #edf2f7;
    border-bottom: 1px solid #cbd5e1;
    padding: 10px 16px;
    border-radius: 6px 6px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
body.theme-dark .speedtest-header {
    background: #1a202c;
    border-color: #2d3748;
}
.speedtest-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}
body.theme-dark .speedtest-title {
    color: #f1f5f9;
}

/* Config Bar */
.speedtest-config-bar {
    padding: 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
body.theme-dark .speedtest-config-bar {
    background: #181d28;
    border-color: #283042;
}

.speedtest-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 6px;
}
body.theme-dark .speedtest-label {
    color: #94a3b8;
}

/* Action Button */
.btn-run-speedtest {
    height: 38px;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 14px;
    border: none;
    border-radius: 5px;
    box-shadow: 0 3px 8px rgba(2, 132, 199, 0.35);
    transition: all 0.2s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-run-speedtest:hover:not(:disabled) {
    background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.45);
    color: #ffffff;
    transform: translateY(-1px);
}
.btn-run-speedtest:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Progress & Status Indicator */
.speedtest-status-bar {
    padding: 14px 16px;
    text-align: center;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
}
body.theme-dark .speedtest-status-bar {
    background: #1e222d;
    border-color: #2e3446;
}

.speedtest-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
}
body.theme-dark .speedtest-badge {
    background: #283042;
    color: #cbd5e1;
}
.speedtest-badge.active {
    background: #e0f2fe;
    color: #0284c7;
    animation: stPulse 1.5s infinite;
}
body.theme-dark .speedtest-badge.active {
    background: #0c4a6e;
    color: #38bdf8;
}
@keyframes stPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.02); opacity: 0.85; }
}

.st-progress-track {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 12px;
    display: none;
}
body.theme-dark .st-progress-track {
    background: #334155;
}
.st-progress-bar {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #0284c7, #10b981);
    transition: width 0.3s ease;
}

/* KPI Cards Layout */
.st-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 20px 16px;
}
@media (max-width: 991px) {
    .st-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 575px) {
    .st-kpi-grid {
        grid-template-columns: 1fr;
    }
}

.st-kpi-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 18px 14px;
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: all 0.2s ease;
}
body.theme-dark .st-kpi-card {
    background: #181d28;
    border-color: #283042;
}
.st-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.st-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}
.st-kpi-card.dl::before { background: #0284c7; }
.st-kpi-card.ul::before { background: #10b981; }
.st-kpi-card.ping::before { background: #f59e0b; }
.st-kpi-card.jitter::before { background: #8b5cf6; }

.st-kpi-icon {
    font-size: 20px;
    margin-bottom: 6px;
}
.st-kpi-card.dl .st-kpi-icon { color: #0284c7; }
.st-kpi-card.ul .st-kpi-icon { color: #10b981; }
.st-kpi-card.ping .st-kpi-icon { color: #f59e0b; }
.st-kpi-card.jitter .st-kpi-icon { color: #8b5cf6; }

.st-kpi-title {
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
body.theme-dark .st-kpi-title {
    color: #94a3b8;
}

.st-kpi-val {
    font-size: 34px;
    font-weight: 800;
    line-height: 1;
    color: #1e293b;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
body.theme-dark .st-kpi-val {
    color: #f8fafc;
}

.st-kpi-unit {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    margin-left: 4px;
}

/* Detail Info Grid */
.st-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 0 16px 20px 16px;
}
@media (max-width: 767px) {
    .st-details-grid {
        grid-template-columns: 1fr;
    }
}

.st-detail-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 14px 16px;
}
body.theme-dark .st-detail-box {
    background: #181d28;
    border-color: #283042;
}
.st-detail-header {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 8px;
}
body.theme-dark .st-detail-header {
    color: #cbd5e1;
    border-color: #283042;
}

.st-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 13px;
    border-bottom: 1px solid #f8fafc;
}
body.theme-dark .st-detail-row {
    border-color: #202738;
}
.st-detail-row:last-child {
    border-bottom: none;
}
.st-detail-key {
    color: #64748b;
    font-weight: 500;
}
body.theme-dark .st-detail-key {
    color: #94a3b8;
}
.st-detail-val {
    font-weight: 600;
    color: #1e293b;
}
body.theme-dark .st-detail-val {
    color: #f1f5f9;
}

/* History Table WinBox Style */
.st-history-panel {
    border-top: 1px solid #e2e8f0;
    background: #ffffff;
    border-radius: 0 0 6px 6px;
}
body.theme-dark .st-history-panel {
    background: #1e222d;
    border-color: #2e3446;
}
.st-history-header {
    padding: 12px 16px;
    background: #edf2f7;
    border-bottom: 1px solid #cbd5e1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
body.theme-dark .st-history-header {
    background: #1a202c;
    border-color: #2d3748;
}
.st-history-title {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
body.theme-dark .st-history-title {
    color: #f1f5f9;
}

.st-table {
    width: 100%;
    margin-bottom: 0;
    font-size: 12px;
}
.st-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 8px 12px;
}
body.theme-dark .st-table th {
    background: #181d28;
    color: #94a3b8;
    border-color: #283042 !important;
}
.st-table td {
    padding: 8px 12px;
    vertical-align: middle;
    border-top: 1px solid #f1f5f9;
}
body.theme-dark .st-table td {
    border-color: #202738;
}
.st-table tbody tr:hover {
    background-color: #f1f5f9;
}
body.theme-dark .st-table tbody tr:hover {
    background-color: #252d3d;
}
</style>

<div class="speedtest-page">
    <div class="speedtest-panel">
        <!-- WinBox Header -->
        <div class="speedtest-header">
            <h3 class="speedtest-title">
                <i class="fa-solid fa-gauge-high text-primary"></i>
                <span>Speedtest & Internet Bandwidth Benchmark</span>
            </h3>
            <div>
                <span class="label label-info"><i class="fa-solid fa-bolt"></i> Multi-Stream High Speed</span>
                <span class="label label-success"><i class="fa-brands fa-debian"></i> Rinjani 1.0.2</span>
            </div>
        </div>

        <!-- Configuration Bar -->
        <div class="speedtest-config-bar">
            <div class="row">
                <div class="col-md-3 col-sm-6" style="margin-bottom: 10px;">
                    <div class="speedtest-label">Engine Pengujian:</div>
                    <select id="engine-select" class="form-control input-sm">
                        <option value="ookla" selected>Speedtest Native (Auto Edge)</option>
                        <option value="sivel">Speedtest.net (Global Server)</option>
                    </select>
                </div>
                <div class="col-md-4 col-sm-6" style="margin-bottom: 10px;">
                    <div class="speedtest-label">Interface Routing:</div>
                    <select id="interface-select" class="form-control input-sm">
                        <option value="" selected>Default Gateway (Auto Route)</option>
                        <?php if (!empty($ifaces)): ?>
                            <?php foreach ($ifaces as $if): ?>
                                <option value="<?=htmlspecialchars($if['name'])?>">
                                    <?=htmlspecialchars($if['name'])?> (<?=htmlspecialchars($if['ip'] ?? 'No IP')?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6" style="margin-bottom: 10px;">
                    <div class="speedtest-label">Target Server:</div>
                    <select id="server-select" class="form-control input-sm">
                        <option value="auto">Auto (Best Latency Edge)</option>
                        <option value="32168">Biznet Networks (Jakarta)</option>
                        <option value="50552">Telkom Indonesia (Jakarta)</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6" style="margin-bottom: 10px;">
                    <div class="speedtest-label">&nbsp;</div>
                    <button type="button" id="btn-start" class="btn btn-run-speedtest btn-block" onclick="if(typeof window.startSpeedtest==='function'){window.startSpeedtest();}">
                        <i class="fa-solid fa-play"></i> <span>Start Test</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Status & Progress -->
        <div class="speedtest-status-bar">
            <div id="test-status" class="speedtest-badge">
                <i class="fa-solid fa-circle-play text-primary"></i>
                <span>Siap melakukan pengujian bandwidth (Klik 'Start Test')</span>
            </div>
            <div id="speed-progress" class="st-progress-track">
                <div id="speed-progress-inner" class="st-progress-bar"></div>
            </div>
        </div>

        <!-- KPI Metrics -->
        <div class="st-kpi-grid">
            <div class="st-kpi-card dl">
                <div class="st-kpi-icon"><i class="fa-solid fa-circle-arrow-down"></i></div>
                <div class="st-kpi-title">Download</div>
                <div class="st-kpi-val"><span id="val-dl">0.00</span><span class="st-kpi-unit">Mbps</span></div>
            </div>
            <div class="st-kpi-card ul">
                <div class="st-kpi-icon"><i class="fa-solid fa-circle-arrow-up"></i></div>
                <div class="st-kpi-title">Upload</div>
                <div class="st-kpi-val"><span id="val-ul">0.00</span><span class="st-kpi-unit">Mbps</span></div>
            </div>
            <div class="st-kpi-card ping">
                <div class="st-kpi-icon"><i class="fa-solid fa-stopwatch"></i></div>
                <div class="st-kpi-title">Latency / Ping</div>
                <div class="st-kpi-val"><span id="val-ping">0.0</span><span class="st-kpi-unit">ms</span></div>
            </div>
            <div class="st-kpi-card jitter">
                <div class="st-kpi-icon"><i class="fa-solid fa-wave-square"></i></div>
                <div class="st-kpi-title">Jitter</div>
                <div class="st-kpi-val"><span id="val-jitter">0.0</span><span class="st-kpi-unit">ms</span></div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="st-details-grid">
            <div class="st-detail-box">
                <div class="st-detail-header">
                    <i class="fa-solid fa-network-wired text-info"></i>
                    <span>Informasi Jaringan Klien</span>
                </div>
                <div class="st-detail-row">
                    <span class="st-detail-key">ISP / Provider:</span>
                    <span class="st-detail-val" id="det-isp">-</span>
                </div>
                <div class="st-detail-row">
                    <span class="st-detail-key">Public IP Address:</span>
                    <span class="st-detail-val" id="det-ip">-</span>
                </div>
                <div class="st-detail-row">
                    <span class="st-detail-key">Packet Loss:</span>
                    <span class="st-detail-val" id="det-loss">-</span>
                </div>
            </div>

            <div class="st-detail-box">
                <div class="st-detail-header">
                    <i class="fa-solid fa-server text-success"></i>
                    <span>Target & Endpoint Pengujian</span>
                </div>
                <div class="st-detail-row">
                    <span class="st-detail-key">Server Node:</span>
                    <span class="st-detail-val" id="det-server">-</span>
                </div>
                <div class="st-detail-row">
                    <span class="st-detail-key">Interface Diuji:</span>
                    <span class="st-detail-val" id="det-if">-</span>
                </div>
                <div class="st-detail-row">
                    <span class="st-detail-key">Tautan Hasil:</span>
                    <span class="st-detail-val" id="det-url">-</span>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="st-history-panel">
            <div class="st-history-header">
                <h4 class="st-history-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat Pengujian Terakhir (Last 25 Tests)</span>
                </h4>
                <button type="button" id="btn-clear-history" class="btn btn-xs btn-default">
                    <i class="fa-solid fa-trash text-danger"></i> Hapus Riwayat
                </button>
            </div>
            <div class="table-responsive">
                <table class="table st-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Engine</th>
                            <th>Interface</th>
                            <th>Server Endpoint</th>
                            <th>Ping</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Tautan</th>
                        </tr>
                    </thead>
                    <tbody id="history-tbody">
                        <tr><td colspan="8" class="text-center text-muted" style="padding: 20px;">Belum ada riwayat pengujian. Silakan klik 'Start Test'.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Bandwidth & Hardware Benchmark Panel (Directly Below Speedtest) -->
    <div class="speedtest-panel" style="margin-top: 25px;">
        <div class="speedtest-header" style="background: #eef2ff;">
            <h3 class="speedtest-title">
                <i class="fa-solid fa-microchip text-primary"></i>
                <span>Bandwidth &amp; Hardware Benchmark (Throughput Stress &amp; PPS Testing)</span>
            </h3>
            <div>
                <span class="label label-primary"><i class="fa-solid fa-gauge-high"></i> High-Throughput Engine</span>
                <span class="label label-info"><i class="fa-solid fa-shield-halved"></i> Shaper Validation</span>
            </div>
        </div>

        <div style="padding: 20px;">
            <!-- Mode Selector Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 20px;">
                <div class="bm-card active" data-mode="cdn" onclick="selectBenchmarkMode('cdn')" style="border: 1px solid #2563eb; background: #eff6ff; border-radius: 8px; padding: 14px; cursor: pointer;">
                    <div style="font-size: 22px; color: #2563eb; margin-bottom: 8px;"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Multi-Stream Pipe Stress (CDN)</div>
                    <div style="font-size: 12px; color: #64748b; line-height: 1.4;">Uji kapasitas pipa maksimal dan bypass shaper ISP dengan mengalirkan data biner multi-stream paralel ke Edge CDN global.</div>
                </div>

                <div class="bm-card" data-mode="iperf3" onclick="selectBenchmarkMode('iperf3')" style="border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 8px; padding: 14px; cursor: pointer;">
                    <div style="font-size: 22px; color: #16a34a; margin-bottom: 8px;"><i class="fa-solid fa-network-wired"></i></div>
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">iPerf3 Point-to-Point (VPS / CHR)</div>
                    <div style="font-size: 12px; color: #64748b; line-height: 1.4;">Ukur bandwidth point-to-point murni antara router MitraNet langsung ke server MikroTik CHR atau VPS target.</div>
                </div>

                <div class="bm-card" data-mode="pps" onclick="selectBenchmarkMode('pps')" style="border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 8px; padding: 14px; cursor: pointer;">
                    <div style="font-size: 22px; color: #d97706; margin-bottom: 8px;"><i class="fa-solid fa-bolt"></i></div>
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Packet-Per-Second (PPS Stress)</div>
                    <div style="font-size: 12px; color: #64748b; line-height: 1.4;">Ukur batas penerusan paket (Forwarding Rate) kernel Netfilter dan ketahanan CPU Mini PC di bawah beban paket tinggi.</div>
                </div>
            </div>

            <!-- Configuration Form -->
            <div class="well well-sm">
                <div class="row">
                    <div class="col-md-4 col-sm-6" style="margin-bottom: 10px;">
                        <label class="control-label fs-085 text-muted">Antarmuka Pengujian (Interface):</label>
                        <select id="bm-iface" class="form-control input-sm">
                            <option value="">-- Default Gateway (Auto Routing) --</option>
                            <?php foreach ($ifaces as $if): ?>
                                <option value="<?=htmlspecialchars($if['name'])?>">
                                    <?=htmlspecialchars($if['name'])?> (<?=htmlspecialchars($if['ip'] ?? 'No IP')?> - <?=htmlspecialchars($if['descr'] ?? strtoupper($if['name']))?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Options for CDN mode -->
                    <div class="col-md-4 col-sm-6 bm-opt-cdn" style="margin-bottom: 10px;">
                        <label class="control-label fs-085 text-muted">Ukuran Beban Data (Throughput Payload):</label>
                        <select id="bm-size" class="form-control input-sm">
                            <option value="10">10 MB (Quick Burst Test)</option>
                            <option value="25" selected>25 MB (Standard ISP Shaper Benchmark)</option>
                            <option value="50">50 MB (Heavy Pipe Stress Test)</option>
                            <option value="100">100 MB (Ultra High Bandwidth Saturation)</option>
                        </select>
                    </div>

                    <!-- Options for iPerf3 mode -->
                    <div class="col-md-3 col-sm-6 bm-opt-iperf hidden" style="margin-bottom: 10px;">
                        <label class="control-label fs-085 text-muted">Target Host (VPS / CHR IP):</label>
                        <input type="text" id="bm-host" class="form-control input-sm" value="103.93.162.168" placeholder="e.g. 103.93.162.168">
                    </div>
                    <div class="col-md-1 col-sm-6 bm-opt-iperf hidden" style="margin-bottom: 10px;">
                        <label class="control-label fs-085 text-muted">Port:</label>
                        <input type="number" id="bm-port" class="form-control input-sm" value="5201" placeholder="5201">
                    </div>

                    <div class="col-md-4 col-sm-12" style="margin-top: 18px;">
                        <button id="btn-run-benchmark" class="btn btn-primary btn-sm btn-block" onclick="startBenchmark()">
                            <i class="fa-solid fa-play"></i> Mulai Benchmark Sekarang
                        </button>
                    </div>
                </div>
            </div>

            <!-- Real-Time Result HUD Display -->
            <div style="background: #0f172a; border-radius: 8px; color: #f8fafc; padding: 20px; margin-bottom: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; align-items: center;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 4px;"><i class="fa-solid fa-gauge-high"></i> Max Throughput Rate</div>
                        <div style="font-size: 28px; font-weight: 800; color: #38bdf8;" id="hud-throughput">-- Mbps</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;" id="hud-mode-sub">Menunggu pengujian...</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 4px;"><i class="fa-solid fa-wave-square"></i> Latensi Jaringan</div>
                        <div style="font-size: 28px; font-weight: 800; color: #34d399;" id="hud-latency">-- ms</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;">RTT Average Ping</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 4px;"><i class="fa-solid fa-database"></i> Total Data Terkirim</div>
                        <div style="font-size: 28px; font-weight: 800; color: #fbbf24;" id="hud-transferred">-- MB</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;" id="hud-duration">Durasi: -- s</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 4px;"><i class="fa-solid fa-circle-check"></i> Status Eksekusi</div>
                        <div style="font-size: 28px; font-weight: 800; color: #a78bfa;" id="hud-status">READY</div>
                        <div style="font-size: 11px; color: #64748b; margin-top: 2px;" id="hud-target">Target: Cloudflare Edge</div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h4 style="margin: 0; font-size: 13px; font-weight: 700; color: #334155;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pengujian Benchmark
                </h4>
                <div>
                    <button class="btn btn-default btn-xs" onclick="loadBenchmarkHistory()"><i class="fa-solid fa-rotate"></i> Refresh</button>
                    <button class="btn btn-danger btn-xs" onclick="clearBenchmarkHistory()"><i class="fa-solid fa-trash"></i> Hapus Riwayat</button>
                </div>
            </div>

            <div class="table-responsive" style="border: 1px solid #e2e8f0; border-radius: 6px;">
                <table class="table st-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Mode Benchmark</th>
                            <th>Interface</th>
                            <th>Max Throughput</th>
                            <th>Latensi</th>
                            <th>Volume Data</th>
                            <th>Durasi</th>
                            <th>Target</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="bm-history-tbody">
                        <tr>
                            <td colspan="9" class="text-center text-muted" style="padding: 15px;">
                                Belum ada riwayat benchmark.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>

<script type="text/javascript">
function initSpeedtest() {
    var currentUrl = '/tools/speedtest.php';

    // 1. Fetch Speedtest Servers
    $.ajax({
        url: currentUrl,
        type: 'GET',
        data: { ajax: '1', action: 'servers' },
        dataType: 'json',
        success: function(res) {
            if (res && res.success && res.servers && res.servers.length > 0) {
                var sel = $('#server-select');
                sel.empty().append($('<option>', {
                    value: 'auto',
                    text: 'Auto (Best Latency Edge)'
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

    // 2. Fetch History
    window.refreshHistory = function() {
        $.ajax({
            url: currentUrl,
            type: 'GET',
            data: { ajax: '1', action: 'history' },
            dataType: 'json',
            success: function(res) {
                if (res && res.success && res.history) {
                    var tbody = $('#history-tbody');
                    tbody.empty();
                    if (res.history.length === 0) {
                        tbody.html('<tr><td colspan="8" class="text-center text-muted" style="padding: 20px;">Belum ada riwayat pengujian. Silakan klik "Start Test".</td></tr>');
                    } else {
                        res.history.forEach(function(h) {
                            var linkHtml = (h.url && h.url.indexOf('http') === 0) ? '<a href="' + h.url + '" target="_blank" class="btn btn-xs btn-info"><i class="fa-solid fa-arrow-up-right-from-square"></i> Result</a>' : '-';
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

    window.refreshHistory();

    // 3. Start Speedtest Runner
    window.startSpeedtest = function() {
        var btn = $('#btn-start');
        if (btn.prop('disabled')) return false;

        btn.prop('disabled', true);
        btn.find('span').text('Testing...');
        btn.find('i').removeClass('fa-play').addClass('fa-spinner fa-spin');

        $('#test-status').removeClass('speedtest-badge').addClass('speedtest-badge active').html('<i class="fa-solid fa-spinner fa-spin"></i> <span>Sedang menguji latency dan bandwidth multi-stream (10-25 detik)...</span>');
        $('#speed-progress').show();
        $('#speed-progress-inner').css('width', '15%');

        var engine = $('#engine-select').val() || 'ookla';
        var iface = $('#interface-select').val() || '';
        var srv = $('#server-select').val() || 'auto';

        var progressVal = 15;
        var progressTimer = setInterval(function() {
            progressVal += 12;
            if (progressVal > 88) progressVal = 88;
            $('#speed-progress-inner').css('width', progressVal + '%');
        }, 1500);

        $.ajax({
            url: currentUrl,
            type: 'POST',
            data: {
                ajax: '1',
                action: 'run',
                engine: engine,
                interface: iface,
                server_id: srv
            },
            dataType: 'json',
            timeout: 120000,
            success: function(res) {
                clearInterval(progressTimer);
                $('#speed-progress-inner').css('width', '100%');
                setTimeout(function() { $('#speed-progress').fadeOut(); }, 1000);

                btn.prop('disabled', false);
                btn.find('span').text('Start Test');
                btn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-play');

                if (res && res.success && res.data) {
                    var d = res.data;
                    $('#test-status').removeClass('speedtest-badge active').addClass('speedtest-badge').html('<i class="fa-solid fa-circle-check text-success"></i> <span>Pengujian Selesai! Throughput berhasil diukur.</span>');

                    $('#val-dl').text(d.download);
                    $('#val-ul').text(d.upload);
                    $('#val-ping').text(d.ping);
                    $('#val-jitter').text(d.jitter);

                    $('#det-isp').text(d.isp);
                    $('#det-ip').text(d.client_ip);
                    $('#det-loss').text(d.loss ? d.loss + '%' : '0.0%');
                    $('#det-server').text(d.server);
                    $('#det-if').text(d.interface);

                    if (d.url && d.url.indexOf('http') === 0) {
                        $('#det-url').html('<a href="' + d.url + '" target="_blank" class="btn btn-xs btn-info"><i class="fa-solid fa-link"></i> Result Link</a>');
                    } else {
                        $('#det-url').text('-');
                    }

                    if (typeof MitraNet !== 'undefined' && MitraNet.toast) {
                        MitraNet.toast('Speedtest berhasil: ' + d.download + ' Mbps Down / ' + d.upload + ' Mbps Up', 'success');
                    }

                    window.refreshHistory();
                } else {
                    var errMsg = (res && res.error) ? res.error : 'Pengujian gagal';
                    $('#test-status').removeClass('speedtest-badge active').addClass('speedtest-badge').html('<i class="fa-solid fa-triangle-exclamation text-danger"></i> <span>Gagal: ' + errMsg + '</span>');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Speedtest Error',
                            text: errMsg,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#d33'
                        });
                    } else {
                        alert(errMsg);
                    }
                }
            },
            error: function(xhr, status, err) {
                clearInterval(progressTimer);
                $('#speed-progress').fadeOut();
                btn.prop('disabled', false);
                btn.find('span').text('Start Test');
                btn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-play');

                var errMsg = 'Koneksi ke backend pengujian gagal (' + (err || status) + '). Status: ' + xhr.status;
                $('#test-status').removeClass('speedtest-badge active').addClass('speedtest-badge').html('<i class="fa-solid fa-triangle-exclamation text-danger"></i> <span>' + errMsg + '</span>');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Koneksi Gagal',
                        text: errMsg,
                        icon: 'error',
                        confirmButtonText: 'Tutup',
                        confirmButtonColor: '#d33'
                    });
                } else {
                    alert(errMsg);
                }
            }
        });
        return false;
    };

    $('#btn-start').off('click').on('click', function(e) {
        e.preventDefault();
        window.startSpeedtest();
    });

    // 4. Clear History Handler
    $('#btn-clear-history').off('click').on('click', function(e) {
        e.preventDefault();
        var doClear = function() {
            $.ajax({
                url: currentUrl,
                type: 'POST',
                data: { ajax: '1', action: 'clear_history' },
                dataType: 'json',
                success: function() {
                    $('#history-tbody').html('<tr><td colspan="8" class="text-center text-muted" style="padding: 20px;">Belum ada riwayat pengujian. Silakan klik "Start Test".</td></tr>');
                    $('#val-dl').text('0.00');
                    $('#val-ul').text('0.00');
                    $('#val-ping').text('0.0');
                    $('#val-jitter').text('0.0');
                    $('#det-isp').text('-');
                    $('#det-ip').text('-');
                    $('#det-loss').text('-');
                    $('#det-server').text('-');
                    $('#det-if').text('-');
                    $('#det-url').text('-');
                    if (typeof MitraNet !== 'undefined' && MitraNet.toast) {
                        MitraNet.toast('Riwayat pengujian berhasil dibersihkan', 'info');
                    }
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Riwayat?',
                text: 'Seluruh histori pengujian kecepatan akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Bersihkan!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) {
                    doClear();
                }
            });
        } else {
            if (confirm('Hapus seluruh riwayat pengujian Speedtest?')) {
                doClear();
            }
        }
    });
// Benchmark JavaScript Engine
var currentBenchmarkMode = 'cdn';

function selectBenchmarkMode(mode) {
    currentBenchmarkMode = mode;
    $('.bm-card').css({'border': '1px solid #e2e8f0', 'background': '#f8fafc'});
    $('.bm-card[data-mode="' + mode + '"]').css({'border': '1px solid #2563eb', 'background': '#eff6ff'});

    if (mode === 'cdn') {
        $('.bm-opt-cdn').removeClass('hidden');
        $('.bm-opt-iperf').addClass('hidden');
    } else if (mode === 'iperf3') {
        $('.bm-opt-cdn').addClass('hidden');
        $('.bm-opt-iperf').removeClass('hidden');
    } else if (mode === 'pps') {
        $('.bm-opt-cdn').addClass('hidden');
        $('.bm-opt-iperf').addClass('hidden');
    }
}

function startBenchmark() {
    var btn = $('#btn-run-benchmark');
    btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Sedang Menjalankan Benchmark...');

    $('#hud-throughput').html('<i class="fa-solid fa-circle-notch fa-spin"></i>');
    $('#hud-latency').text('Menguji...');
    $('#hud-status').text('RUNNING').css('color', '#fbbf24');

    var payload = {
        ajax: '1',
        action: 'run_benchmark',
        mode: currentBenchmarkMode,
        interface: $('#bm-iface').val(),
        size_mb: $('#bm-size').val(),
        target_host: $('#bm-host').val(),
        target_port: $('#bm-port').val()
    };

    $.post('/tools/speedtest.php', payload, function(res) {
        btn.prop('disabled', false).html('<i class="fa-solid fa-play"></i> Mulai Benchmark Sekarang');
        if (res && res.success && res.data) {
            var d = res.data;
            $('#hud-throughput').text(d.throughput);
            $('#hud-latency').text(d.latency);
            $('#hud-transferred').text(d.transferred);
            $('#hud-duration').text('Durasi: ' + d.duration);
            $('#hud-status').text(d.status).css('color', d.status === 'PASS' ? '#34d399' : '#f87171');
            $('#hud-target').text('Target: ' + d.target);
            $('#hud-mode-sub').text(d.mode + ' (' + d.interface + ')');

            if (typeof MitraNet !== 'undefined' && MitraNet.toast) {
                MitraNet.toast('Benchmark selesai: ' + d.throughput, 'success');
            }
            loadBenchmarkHistory();
        } else {
            $('#hud-status').text('ERROR').css('color', '#f87171');
            if (typeof MitraNet !== 'undefined' && MitraNet.toast) {
                MitraNet.toast(res.error || 'Benchmark gagal dieksekusi', 'error');
            }
        }
    }).fail(function(xhr) {
        btn.prop('disabled', false).html('<i class="fa-solid fa-play"></i> Mulai Benchmark Sekarang');
        $('#hud-status').text('TIMEOUT').css('color', '#f87171');
        if (typeof MitraNet !== 'undefined' && MitraNet.toast) {
            MitraNet.toast('Koneksi timeout atau gagal menghubungi server.', 'error');
        }
    });
}

function loadBenchmarkHistory() {
    $.getJSON('/tools/speedtest.php?ajax=1&action=benchmark_history', function(res) {
        var tbody = $('#bm-history-tbody');
        tbody.empty();
        if (res && res.success && res.history && res.history.length > 0) {
            $.each(res.history, function(idx, item) {
                var badgeClass = item.status === 'PASS' ? 'label-success' : (item.status === 'RUNNING' ? 'label-warning' : 'label-danger');
                var tr = '<tr>' +
                    '<td><code style="font-size:11px;">' + (item.timestamp || '-') + '</code></td>' +
                    '<td><strong>' + (item.mode || '-') + '</strong></td>' +
                    '<td><span class="label label-default">' + (item.interface || 'Default') + '</span></td>' +
                    '<td><strong class="text-primary" style="font-size:13px;">' + (item.throughput || '-') + '</strong></td>' +
                    '<td>' + (item.latency || '-') + '</td>' +
                    '<td>' + (item.transferred || '-') + '</td>' +
                    '<td>' + (item.duration || '-') + '</td>' +
                    '<td><small class="text-muted">' + (item.target || '-') + '</small></td>' +
                    '<td><span class="label ' + badgeClass + '">' + (item.status || '-') + '</span></td>' +
                    '</tr>';
                tbody.append(tr);
            });
        } else {
            tbody.html('<tr><td colspan="9" class="text-center text-muted" style="padding: 15px;">Belum ada riwayat benchmark. Silakan jalankan pengujian di atas.</td></tr>');
        }
    });
}

function clearBenchmarkHistory() {
    if (typeof MitraNet !== 'undefined' && MitraNet.confirmDelete) {
        MitraNet.confirmDelete('Apakah Anda yakin ingin menghapus seluruh riwayat benchmark?', function() {
            $.post('/tools/speedtest.php', { ajax: '1', action: 'clear_benchmark_history' }, function(res) {
                MitraNet.toast('Riwayat benchmark berhasil dibersihkan', 'info');
                loadBenchmarkHistory();
            });
        });
    } else {
        if (confirm('Hapus seluruh riwayat benchmark?')) {
            $.post('/tools/speedtest.php', { ajax: '1', action: 'clear_benchmark_history' }, function(res) {
                loadBenchmarkHistory();
            });
        }
    }
}

$(document).ready(function() {
    initSpeedtest();
    loadBenchmarkHistory();
});
</script>
