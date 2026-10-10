<?php
/*
 * tools/benchmark.php - MitraNet Tools: High-Performance Bandwidth & Hardware Benchmark
 * Dedicated Stress Testing, WireGuard DSCP/Tunnel Validation, and PPS Hardware Engine
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

// Handle AJAX actions
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == '1') {
    header('Content-Type: application/json');
    $action = $_REQUEST['action'] ?? '';

    if ($action === 'history') {
        $history = MitraNetApi::getBenchmarkHistory();
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }

    if ($action === 'clear_history') {
        $res = MitraNetApi::clearBenchmarkHistory();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'run') {
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

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

$pgtitle = array("Tools", "Bandwidth & Hardware Benchmark");
$selected_menu = "tools";
require_once(__DIR__ . '/../includes/head.inc');

$tab_array = array(
    array("Internet Speedtest", false, "/tools/speedtest.php"),
    array("Bandwidth & Hardware Benchmark", true, "/tools/benchmark.php")
);
display_top_tabs($tab_array, false, 'pills');

$ifaces = MitraNetApi::getInterfaces();
?>

<style>
.benchmark-page {
    margin-top: 10px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
.benchmark-panel {
    background: #ffffff;
    border: 1px solid #dcdfe6;
    border-radius: 6px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
    margin-bottom: 20px;
}
body.theme-dark .benchmark-panel {
    background: #1e222d;
    border-color: #2e3446;
}
.benchmark-header {
    background: #edf2f7;
    border-bottom: 1px solid #cbd5e1;
    padding: 12px 16px;
    border-radius: 6px 6px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
body.theme-dark .benchmark-header {
    background: #1a202c;
    border-color: #2d3748;
}
.benchmark-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}
body.theme-dark .benchmark-title {
    color: #e2e8f0;
}
.benchmark-body {
    padding: 20px;
}
.benchmark-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.bm-card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    background: #f8fafc;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
}
body.theme-dark .bm-card {
    background: #252d3d;
    border-color: #333e52;
}
.bm-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.12);
}
.bm-card.active {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}
body.theme-dark .bm-card.active {
    background: #1e293b;
    border-color: #3b82f6;
}
.bm-card-icon {
    font-size: 24px;
    margin-bottom: 10px;
}
.bm-card-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}
body.theme-dark .bm-card-title {
    color: #f1f5f9;
}
.bm-card-desc {
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
}
body.theme-dark .bm-card-desc {
    color: #94a3b8;
}

/* Gauge and Result display */
.bm-result-area {
    background: #0f172a;
    border-radius: 8px;
    color: #f8fafc;
    padding: 24px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.bm-result-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    align-items: center;
}
.bm-metric-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    margin-bottom: 6px;
}
.bm-metric-val {
    font-size: 32px;
    font-weight: 800;
    color: #38bdf8;
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
}
.bm-metric-sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

/* History table */
.bm-table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}
body.theme-dark .bm-table-wrap {
    border-color: #2e3446;
}
.bm-table {
    width: 100%;
    margin-bottom: 0;
    font-size: 13px;
}
.bm-table th {
    background: #f1f5f9;
    color: #475569;
    font-weight: 600;
    padding: 10px 12px;
    border-bottom: 1px solid #cbd5e1;
}
body.theme-dark .bm-table th {
    background: #1e2430;
    color: #cbd5e1;
    border-color: #2e3446;
}
.bm-table td {
    padding: 10px 12px;
    border-top: 1px solid #f1f5f9;
}
body.theme-dark .bm-table td {
    border-color: #242c3d;
}
.bm-table tr:hover {
    background-color: #f8fafc;
}
body.theme-dark .bm-table tr:hover {
    background-color: #202738;
}
</style>

<div class="benchmark-page">
    <div class="benchmark-panel">
        <div class="benchmark-header">
            <h3 class="benchmark-title">
                <i class="fa-solid fa-microchip text-primary"></i>
                <span>Bandwidth &amp; Hardware Kernel Benchmark</span>
            </h3>
            <div>
                <span class="label label-primary"><i class="fa-solid fa-gauge-high"></i> High-Throughput Engine</span>
                <span class="label label-info"><i class="fa-solid fa-shield-halved"></i> Shaper Validation</span>
            </div>
        </div>

        <div class="benchmark-body">
            <!-- Mode Selector Cards -->
            <div class="benchmark-cards">
                <div class="bm-card active" data-mode="cdn" onclick="selectBenchmarkMode('cdn')">
                    <div class="bm-card-icon text-primary"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                    <div class="bm-card-title">Multi-Stream Pipe Stress (CDN)</div>
                    <div class="bm-card-desc">Uji kapasitas pipa maksimal dan bypass shaper ISP dengan mengalirkan data biner multi-stream paralel ke Edge CDN global.</div>
                </div>

                <div class="bm-card" data-mode="iperf3" onclick="selectBenchmarkMode('iperf3')">
                    <div class="bm-card-icon text-success"><i class="fa-solid fa-network-wired"></i></div>
                    <div class="bm-card-title">iPerf3 Point-to-Point (VPS / CHR)</div>
                    <div class="bm-card-desc">Ukur bandwidth point-to-point murni antara router MitraNet langsung ke server MikroTik CHR atau VPS target.</div>
                </div>

                <div class="bm-card" data-mode="pps" onclick="selectBenchmarkMode('pps')">
                    <div class="bm-card-icon text-warning"><i class="fa-solid fa-bolt"></i></div>
                    <div class="bm-card-title">Packet-Per-Second (PPS Stress)</div>
                    <div class="bm-card-desc">Ukur batas penerusan paket (Forwarding Rate) kernel Netfilter dan ketahanan CPU Mini PC di bawah beban paket tinggi.</div>
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
            <div class="bm-result-area">
                <div class="bm-result-grid">
                    <div>
                        <div class="bm-metric-label"><i class="fa-solid fa-gauge-high"></i> Max Throughput Rate</div>
                        <div class="bm-metric-val" id="hud-throughput">-- Mbps</div>
                        <div class="bm-metric-sub" id="hud-mode-sub">Menunggu pengujian...</div>
                    </div>
                    <div>
                        <div class="bm-metric-label"><i class="fa-solid fa-wave-square"></i> Latensi Jaringan</div>
                        <div class="bm-metric-val" style="color: #34d399;" id="hud-latency">-- ms</div>
                        <div class="bm-metric-sub">RTT Average Ping</div>
                    </div>
                    <div>
                        <div class="bm-metric-label"><i class="fa-solid fa-database"></i> Total Data Terkirim</div>
                        <div class="bm-metric-val" style="color: #fbbf24;" id="hud-transferred">-- MB</div>
                        <div class="bm-metric-sub" id="hud-duration">Durasi: -- s</div>
                    </div>
                    <div>
                        <div class="bm-metric-label"><i class="fa-solid fa-circle-check"></i> Status Eksekusi</div>
                        <div class="bm-metric-val" style="color: #a78bfa;" id="hud-status">READY</div>
                        <div class="bm-metric-sub" id="hud-target">Target: Cloudflare Edge</div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: #334155;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pengujian Benchmark
                </h4>
                <div>
                    <button class="btn btn-default btn-xs" onclick="loadBenchmarkHistory()"><i class="fa-solid fa-rotate"></i> Refresh</button>
                    <button class="btn btn-danger btn-xs" onclick="clearBenchmarkHistory()"><i class="fa-solid fa-trash"></i> Hapus Riwayat</button>
                </div>
            </div>

            <div class="bm-table-wrap">
                <table class="table bm-table">
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
                            <td colspan="9" class="text-center text-muted" style="padding: 20px;">
                                <i class="fa-solid fa-spinner fa-spin"></i> Memuat riwayat benchmark...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var currentBenchmarkMode = 'cdn';

function selectBenchmarkMode(mode) {
    currentBenchmarkMode = mode;
    $('.bm-card').removeClass('active');
    $('.bm-card[data-mode="' + mode + '"]').addClass('active');

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
        action: 'run',
        mode: currentBenchmarkMode,
        interface: $('#bm-iface').val(),
        size_mb: $('#bm-size').val(),
        target_host: $('#bm-host').val(),
        target_port: $('#bm-port').val()
    };

    $.post('/tools/benchmark.php', payload, function(res) {
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

            MitraNet.toast('Benchmark selesai: ' + d.throughput, 'success');
            loadBenchmarkHistory();
        } else {
            $('#hud-status').text('ERROR').css('color', '#f87171');
            MitraNet.toast(res.error || 'Benchmark gagal dieksekusi', 'error');
        }
    }).fail(function(xhr) {
        btn.prop('disabled', false).html('<i class="fa-solid fa-play"></i> Mulai Benchmark Sekarang');
        $('#hud-status').text('TIMEOUT').css('color', '#f87171');
        MitraNet.toast('Koneksi timeout atau gagal menghubungi API server.', 'error');
    });
}

function loadBenchmarkHistory() {
    $.getJSON('/tools/benchmark.php?ajax=1&action=history', function(res) {
        var tbody = $('#bm-history-tbody');
        tbody.empty();
        if (res && res.success && res.history && res.history.length > 0) {
            $.each(res.history, function(idx, item) {
                var badgeClass = item.status === 'PASS' ? 'label-success' : (item.status === 'RUNNING' ? 'label-warning' : 'label-danger');
                var tr = '<tr>' +
                    '<td><code style="font-size:11px;">' + (item.timestamp || '-') + '</code></td>' +
                    '<td><strong>' + (item.mode || '-') + '</strong></td>' +
                    '<td><span class="label label-default">' + (item.interface || 'Default') + '</span></td>' +
                    '<td><strong class="text-primary" style="font-size:14px;">' + (item.throughput || '-') + '</strong></td>' +
                    '<td>' + (item.latency || '-') + '</td>' +
                    '<td>' + (item.transferred || '-') + '</td>' +
                    '<td>' + (item.duration || '-') + '</td>' +
                    '<td><small class="text-muted">' + (item.target || '-') + '</small></td>' +
                    '<td><span class="label ' + badgeClass + '">' + (item.status || '-') + '</span></td>' +
                    '</tr>';
                tbody.append(tr);
            });
        } else {
            tbody.html('<tr><td colspan="9" class="text-center text-muted" style="padding: 20px;">Belum ada data pengujian benchmark. Silakan jalankan pengujian di atas.</td></tr>');
        }
    });
}

function clearBenchmarkHistory() {
    MitraNet.confirmDelete('Apakah Anda yakin ingin menghapus seluruh riwayat benchmark?', function() {
        $.post('/tools/benchmark.php', { ajax: '1', action: 'clear_history' }, function(res) {
            MitraNet.toast('Riwayat benchmark berhasil dibersihkan', 'info');
            loadBenchmarkHistory();
        });
    });
}

$(document).ready(function() {
    loadBenchmarkHistory();
});
</script>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
