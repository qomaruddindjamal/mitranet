<?php
/*
 * vpn_booster.php - MitraNet Cloud Speed Booster (Multi-Stream Tunnel Aggregator)
 * Multi-link WireGuard & Layer 2 Tunneling with ECMP/PCC, DSCP Bypass & TCP MSS Clamping.
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = ["VPN", "Cloud Speed Booster"];
$selected_menu = "vpn";
require_once(__DIR__ . '/../includes/api.inc');
require_once(__DIR__ . '/../includes/head.inc');

// Fetch live booster telemetry
$booster = MitraNetApi::getBoosterStatus();
$enabled = !empty($booster['enabled']);
$vps_host = $booster['vps_host'] ?? '';
$stream_count = intval($booster['stream_count'] ?? 2);
$tunnel_type = $booster['tunnel_type'] ?? 'wireguard';
$balancer_mode = $booster['balancer_mode'] ?? 'ecmp';
$dscp_mode = $booster['dscp_mode'] ?? 'AF41';
$clamp_mss = intval($booster['clamp_mss'] ?? 1360);
$enable_bbr = isset($booster['enable_bbr']) ? (bool)$booster['enable_bbr'] : true;
$peer_pubkey = $booster['peer_public_key'] ?? '';
$streams = $booster['streams'] ?? [];
$scripts = $booster['scripts'] ?? [];
$ros_script = $scripts['routeros'] ?? '';
$linux_script = $scripts['linux'] ?? '';
$total_rx = $booster['total_rx_formatted'] ?? '0 B';
$total_tx = $booster['total_tx_formatted'] ?? '0 B';
$current_cc = $booster['current_congestion_control'] ?? 'cubic';
?>

<div class="container-fluid mitranet-page-container">
    <div class="mitranet-window">

        <!-- HEADER: BADGE + TABS -->
        <div class="mitranet-header">
            <div class="mitranet-title-badge">
                <i class="fa-solid fa-bolt text-warning"></i> Cloud Speed Booster
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="mitranet-tabs">
                <li class="active">
                    <a href="vpn_booster.php"><i class="fa-solid fa-gauge-high"></i> Multi-Stream Booster</a>
                </li>
                <li>
                    <a href="vpn.php?tab=interface"><i class="fa-solid fa-network-wired"></i> VPN Interfaces</a>
                </li>
                <li>
                    <a href="vpn.php?tab=secrets"><i class="fa-solid fa-user-lock"></i> Secrets</a>
                </li>
                <li>
                    <a href="/tools/speedtest.php?tab=benchmark" class="text-success"><i class="fa-solid fa-chart-line"></i> Benchmark & Speedtest</a>
                </li>
            </ul>
        </div>

        <!-- TOOLBAR -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left">
                <button type="button" class="mitranet-btn text-success" onclick="applyBoosterConfig()" title="Terapkan Konfigurasi Multi-Stream">
                    <i class="fa-solid fa-circle-check"></i> <strong>Terapkan Booster</strong>
                </button>
                <button type="button" class="mitranet-btn text-danger" onclick="stopBoosterConfig()" title="Hentikan dan Pulihkan Routing Default">
                    <i class="fa-solid fa-circle-stop"></i> Hentikan Booster
                </button>
                <button type="button" class="mitranet-btn text-primary" onclick="showVpsScripts()" title="Salin Skrip VPS Server">
                    <i class="fa-solid fa-terminal"></i> Skrip VPS Gateway
                </button>
                <a href="/tools/speedtest.php?tab=benchmark" class="mitranet-btn text-warning" title="Uji Kecepatan Agregasi">
                    <i class="fa-solid fa-bolt"></i> Uji Kecepatan Agregasi
                </a>
            </div>
            <div class="mitranet-toolbar-right">
                <span class="badge" style="background:#202b38; border:1px solid #334455; padding:5px 10px; font-size:11px;">
                    TCP Congestion: <strong class="<?=($current_cc==='bbr'?'text-success':'text-info')?>"><?=htmlspecialchars(strtoupper($current_cc))?></strong>
                </span>
                <button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh Telemetri">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>

        <!-- TELEMETRY HUD CARDS -->
        <div style="padding: 12px 14px 6px; background: #16202c; border-bottom: 1px solid #283747;">
            <div class="row">
                <div class="col-xs-12 col-sm-3 col-md-3">
                    <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Status Agregasi</div>
                        <div style="font-size:18px; font-weight:700; margin-top:4px;" class="<?=$enabled ? 'text-success' : 'text-danger'?>">
                            <i class="fa-solid <?=$enabled ? 'fa-circle-check' : 'fa-circle-xmark'?>"></i> <?=$enabled ? 'AKTIF (MULTI-PATH)' : 'NON-AKTIF'?>
                        </div>
                        <div style="font-size:11px; color:#aaa; margin-top:2px;">
                            Alokasi: <strong><?=$stream_count?> Parallel Streams</strong>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-3 col-md-3">
                    <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Total Throughput RX (Masuk)</div>
                        <div style="font-size:18px; font-weight:700; margin-top:4px; color:#00d2be;">
                            <i class="fa-solid fa-arrow-down"></i> <?=htmlspecialchars($total_rx)?>
                        </div>
                        <div style="font-size:11px; color:#aaa; margin-top:2px;">Akumulasi seluruh stream</div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-3 col-md-3">
                    <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Total Throughput TX (Keluar)</div>
                        <div style="font-size:18px; font-weight:700; margin-top:4px; color:#58a6ff;">
                            <i class="fa-solid fa-arrow-up"></i> <?=htmlspecialchars($total_tx)?>
                        </div>
                        <div style="font-size:11px; color:#aaa; margin-top:2px;">Akumulasi seluruh stream</div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-3 col-md-3">
                    <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                        <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Metode Bypass & Clamping</div>
                        <div style="font-size:14px; font-weight:700; margin-top:4px; color:#f1c40f;">
                            DSCP: <?=htmlspecialchars($dscp_mode)?> | MSS: <?=htmlspecialchars($clamp_mss)?>
                        </div>
                        <div style="font-size:11px; color:#aaa; margin-top:2px;">Algoritma: <?=strtoupper($balancer_mode)?> Balancing</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN TWO-COLUMN WINBOX WORKSPACE -->
        <div style="padding: 14px;">
            <div class="row">
                <!-- FORM PARAMETER -->
                <div class="col-md-5">
                    <div class="panel panel-default" style="background:#1a232f; border:1px solid #2d3b4b; border-radius:4px;">
                        <div class="panel-heading" style="background:#202c3b; color:#fff; font-weight:600; border-bottom:1px solid #2d3b4b;">
                            <i class="fa-solid fa-sliders"></i> Parameter Konfigurasi Cloud Booster
                        </div>
                        <div class="panel-body" style="padding: 15px;">
                            <form id="form-booster">
                                <div class="form-group">
                                    <label style="color:#d1d5db; font-size:12px;">Alamat VPS / Cloud Gateway IP atau Hostname <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control input-sm" id="booster-vps-host" value="<?=htmlspecialchars($vps_host)?>" placeholder="contoh: 103.93.162.168 atau vps.contoh.com" required>
                                    <small class="text-muted" style="font-size:11px;">IP Publik VPS yang telah menjalankan gateway WireGuard / L2 tunnel.</small>
                                </div>

                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label style="color:#d1d5db; font-size:12px;">Jumlah Parallel Streams</label>
                                            <select class="form-control input-sm" id="booster-streams">
                                                <option value="2" <?=($stream_count===2?'selected':'')?>>2 Streams (2x Port Agregasi)</option>
                                                <option value="3" <?=($stream_count===3?'selected':'')?>>3 Streams (3x Port Agregasi)</option>
                                                <option value="4" <?=($stream_count===4?'selected':'')?>>4 Streams (4x Port Agregasi)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label style="color:#d1d5db; font-size:12px;">Tipe Tunnel</label>
                                            <select class="form-control input-sm" id="booster-type">
                                                <option value="wireguard" <?=($tunnel_type==='wireguard'?'selected':'')?>>WireGuard Multi-Link</option>
                                                <option value="l2_gre" <?=($tunnel_type==='l2_gre'?'selected':'')?>>Layer 2 GRETAP Tunnel</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label style="color:#d1d5db; font-size:12px;">Balancing Mode</label>
                                            <select class="form-control input-sm" id="booster-balancer">
                                                <option value="ecmp" <?=($balancer_mode==='ecmp'?'selected':'')?>>ECMP (Equal Cost Multi-Path)</option>
                                                <option value="pcc" <?=($balancer_mode==='pcc'?'selected':'')?>>PCC (Per-Connection Classifier)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label style="color:#d1d5db; font-size:12px;">DSCP Marking (Shaper Bypass)</label>
                                            <select class="form-control input-sm" id="booster-dscp">
                                                <option value="AF41" <?=($dscp_mode==='AF41'?'selected':'')?>>AF41 (0x28 - Multimedia Stream)</option>
                                                <option value="CS6" <?=($dscp_mode==='CS6'?'selected':'')?>>CS6 (0x30 - Internetwork Control)</option>
                                                <option value="EF" <?=($dscp_mode==='EF'?'selected':'')?>>EF (0x2e - Expedited Forwarding)</option>
                                                <option value="NONE" <?=($dscp_mode==='NONE'?'selected':'')?>>Tanpa DSCP Marking</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label style="color:#d1d5db; font-size:12px;">TCP MSS Clamping</label>
                                            <input type="number" class="form-control input-sm" id="booster-mss" value="<?=$clamp_mss?>" min="1200" max="1500">
                                            <small class="text-muted" style="font-size:11px;">Nilai 1360 mencegah fragmentasi ISP.</small>
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="form-group">
                                            <label style="color:#d1d5db; font-size:12px;">Akselerasi Kernel</label>
                                            <div class="checkbox" style="margin-top:6px;">
                                                <label style="color:#d1d5db; font-size:12px;">
                                                    <input type="checkbox" id="booster-bbr" <?=$enable_bbr ? 'checked' : ''?>>
                                                    Aktifkan TCP BBR / FQ
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label style="color:#d1d5db; font-size:12px;">Public Key Server VPS (Peer Public Key)</label>
                                    <input type="text" class="form-control input-sm font-monospace" id="booster-peer-key" value="<?=htmlspecialchars($peer_pubkey)?>" placeholder="Masukkan Public Key dari VPS (kosongkan jika sedang generate)">
                                    <small class="text-muted" style="font-size:11px;">Public Key server VPS tujuan untuk handshake WireGuard.</small>
                                </div>

                                <div style="margin-top: 15px; border-top: 1px solid #2d3b4b; padding-top: 12px; display:flex; gap:10px;">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="applyBoosterConfig()" style="flex:1;">
                                        <i class="fa-solid fa-play"></i> Terapkan & Aktifkan Booster
                                    </button>
                                    <button type="button" class="btn btn-default btn-sm" onclick="stopBoosterConfig()" style="flex:1;">
                                        <i class="fa-solid fa-stop text-danger"></i> Hentikan Booster
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- LIVE STREAM HUD STATUS TABLE -->
                <div class="col-md-7">
                    <div class="panel panel-default" style="background:#1a232f; border:1px solid #2d3b4b; border-radius:4px;">
                        <div class="panel-heading" style="background:#202c3b; color:#fff; font-weight:600; border-bottom:1px solid #2d3b4b; display:flex; justify-content:space-between; align-items:center;">
                            <span><i class="fa-solid fa-satellite-dish"></i> Status Real-Time Multi-Stream Interface</span>
                            <span class="badge" style="background:#0984e3;"><?=count($streams)?> Streams Terdaftar</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-condensed" style="margin-bottom:0; font-size:12px;">
                                <thead>
                                    <tr style="background:#131b24; color:#95afc0;">
                                        <th>Stream</th>
                                        <th>Interface</th>
                                        <th>Alamat IP</th>
                                        <th>Port Port</th>
                                        <th>Status Link</th>
                                        <th>Latency RTT</th>
                                        <th>RX Terukur</th>
                                        <th>TX Terukur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($streams)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted" style="padding:20px;">
                                                <i class="fa-solid fa-circle-info"></i> Belum ada stream yang aktif. Masukkan alamat VPS dan klik <strong>Terapkan Booster</strong>.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($streams as $s): 
                                            $is_up = ($s['status'] === 'UP');
                                        ?>
                                            <tr>
                                                <td><strong>Stream #<?=$s['id']?></strong></td>
                                                <td><span class="label label-default" style="font-family:monospace;"><?=$s['interface']?></span></td>
                                                <td><code><?=$s['ip']?>/30</code></td>
                                                <td><span class="badge" style="background:#2d3b4b; font-weight:normal;"><?=$s['port']?></span></td>
                                                <td>
                                                    <span class="label <?=$is_up ? 'label-success' : 'label-danger'?>">
                                                        <i class="fa-solid <?=$is_up ? 'fa-check' : 'fa-times'?>"></i> <?=$s['status']?>
                                                    </span>
                                                </td>
                                                <td class="text-info font-monospace"><?=$s['latency']?></td>
                                                <td class="text-success font-monospace"><?=$s['rx_formatted']?></td>
                                                <td class="text-primary font-monospace"><?=$s['tx_formatted']?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- PANDUAN KERJA TEKNIS -->
                    <div class="panel panel-default" style="background:#141d27; border:1px solid #233140; border-radius:4px;">
                        <div class="panel-body" style="font-size:12px; color:#95afc0; line-height:1.6;">
                            <h5 style="color:#f1c40f; margin-top:0;"><i class="fa-solid fa-triangle-exclamation"></i> Prinsip Kerja Agregasi Multi-Stream:</h5>
                            <p>
                                1. <strong>Bypass Pembatasan Per-Koneksi:</strong> Beberapa ISP menerapkan pembatasan bandwidth (misal 5 Mbps) per koneksi stream/port UDP. Dengan membagi trafik ke 2 hingga 4 tunnel berbeda port (51831–51834) menuju VPS, agregasi total dapat menembus batas hingga 10–20 Mbps.
                            </p>
                            <p>
                                2. <strong>ECMP vs PCC:</strong> Mode ECMP mendistribusikan aliran koneksi baru secara seimbang ke seluruh nexthop WireGuard. Mode PCC menjamin stabilitas sesi per source/destination IP.
                            </p>
                            <p>
                                3. <strong>MSS Clamping (1360):</strong> Mengeliminasi packet drop dan retransmisi akibat overhead enkripsi WireGuard pada jaringan ISP yang menggunakan PPPoE atau MTU terbatas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL SCRIPT VPS GATEWAY -->
<div class="modal fade" id="modalVpsScripts" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="background:#1a232f; color:#fff; border:1px solid #334455;">
            <div class="modal-header" style="border-bottom:1px solid #2d3b4b;">
                <button type="button" class="close text-muted" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa-solid fa-terminal text-primary"></i> Skrip Konfigurasi VPS Gateway</h4>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" style="border-bottom:1px solid #2d3b4b; margin-bottom:12px;">
                    <li class="active"><a href="#tab-ros" data-toggle="tab"><i class="fa-solid fa-server"></i> RouterOS-Compatible Script</a></li>
                    <li><a href="#tab-linux" data-toggle="tab"><i class="fa-brands fa-linux"></i> Linux VPS (Ubuntu/Debian)</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-ros">
                        <p class="text-muted" style="font-size:12px;">Salin perintah di bawah ini dan paste langsung ke Terminal / CLI Gateway RouterOS VPS Anda:</p>
                        <textarea id="txt-ros-script" class="form-control font-monospace" rows="12" readonly style="background:#0f1620; color:#00d2be; font-size:12px; border:1px solid #283747;"><?=htmlspecialchars($ros_script)?></textarea>
                        <div style="margin-top:8px;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="copyToClipboard('txt-ros-script', 'Skrip RouterOS berhasil disalin!')">
                                <i class="fa-solid fa-copy"></i> Salin Skrip RouterOS
                            </button>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-linux">
                        <p class="text-muted" style="font-size:12px;">Jalankan perintah ini di VPS Linux Anda (pastikan wireguard sudah terinstall):</p>
                        <textarea id="txt-linux-script" class="form-control font-monospace" rows="12" readonly style="background:#0f1620; color:#58a6ff; font-size:12px; border:1px solid #283747;"><?=htmlspecialchars($linux_script)?></textarea>
                        <div style="margin-top:8px;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="copyToClipboard('txt-linux-script', 'Skrip Linux VPS berhasil disalin!')">
                                <i class="fa-solid fa-copy"></i> Salin Skrip Linux VPS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #2d3b4b;">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function applyBoosterConfig() {
    var vpsHost = $('#booster-vps-host').val().trim();
    if (!vpsHost) {
        MitraNet.toast('error', 'Alamat VPS / Cloud Gateway wajib diisi.');
        $('#booster-vps-host').focus();
        return;
    }

    var payload = {
        vps_host: vpsHost,
        stream_count: parseInt($('#booster-streams').val()),
        tunnel_type: $('#booster-type').val(),
        balancer_mode: $('#booster-balancer').val(),
        dscp_mode: $('#booster-dscp').val(),
        clamp_mss: parseInt($('#booster-mss').val()),
        enable_bbr: $('#booster-bbr').is(':checked'),
        peer_public_key: $('#booster-peer-key').val().trim()
    };

    Swal.fire({
        title: 'Terapkan Cloud Booster?',
        text: 'Sistem akan menyiapkan ' + payload.stream_count + ' parallel stream WireGuard dan konfigurasi ECMP routing.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Terapkan Sekarang',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.showLoading();
            $.ajax({
                url: '/api/v1/vpn/booster/apply',
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                success: function(resp) {
                    Swal.close();
                    if (resp.success) {
                        MitraNet.toast('success', resp.message || 'Booster berhasil dikonfigurasi.');
                        if (resp.data && resp.data.scripts) {
                            $('#txt-ros-script').val(resp.data.scripts.routeros || '');
                            $('#txt-linux-script').val(resp.data.scripts.linux || '');
                        }
                        setTimeout(function() {
                            location.reload();
                        }, 1200);
                    } else {
                        MitraNet.toast('error', resp.error || 'Gagal menerapkan booster.');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    var errMsg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Terjadi kesalahan sistem.';
                    MitraNet.toast('error', errMsg);
                }
            });
        }
    });
}

function stopBoosterConfig() {
    Swal.fire({
        title: 'Hentikan Cloud Booster?',
        text: 'Seluruh stream WireGuard akan ditutup dan routing default dikembalikan seperti semula.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hentikan',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.showLoading();
            $.ajax({
                url: '/api/v1/vpn/booster/stop',
                type: 'POST',
                data: JSON.stringify({}),
                contentType: 'application/json',
                success: function(resp) {
                    Swal.close();
                    if (resp.success) {
                        MitraNet.toast('success', resp.message || 'Booster berhasil dihentikan.');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        MitraNet.toast('error', resp.error || 'Gagal menghentikan booster.');
                    }
                },
                error: function(xhr) {
                    Swal.close();
                    MitraNet.toast('error', 'Gagal memanggil endpoint stop.');
                }
            });
        }
    });
}

function showVpsScripts() {
    $('#modalVpsScripts').modal('show');
}

function copyToClipboard(elementId, successMsg) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function() {
        MitraNet.toast('success', successMsg || 'Teks berhasil disalin ke clipboard.');
    }).catch(function() {
        document.execCommand("copy");
        MitraNet.toast('success', successMsg || 'Teks berhasil disalin ke clipboard.');
    });
}
</script>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
