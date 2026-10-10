<?php
/*
 * vpn_booster.php - MitraNet Cloud Speed Booster (Dual-Role: Client Uplink & Aggregation Hub Server)
 * Multi-link WireGuard & Layer 2 Tunneling with ECMP/PCC, DSCP Bypass, TCP MSS Clamping & Aggregation Hub.
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = ["VPN", "Cloud Speed Booster"];
$selected_menu = "vpn";
require_once(__DIR__ . '/../includes/api.inc');
require_once(__DIR__ . '/../includes/head.inc');

// Fetch live booster telemetry
$booster = MitraNetApi::getBoosterStatus();

// Client role variables
$enabled = !empty($booster['enabled']);
$vps_host = $booster['vps_host'] ?? '';
$client_stream_count = intval($booster['client_stream_count'] ?? $booster['stream_count'] ?? 2);
$stream_count = $client_stream_count;
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

// Server role variables
$server_enabled = !empty($booster['server_enabled']);
$server_listen_port_start = intval($booster['server_listen_port_start'] ?? 51831);
$server_stream_count = intval($booster['server_stream_count'] ?? $booster['stream_count'] ?? 2);
$server_subnet = $booster['server_subnet'] ?? '10.250.0.0/16';
$server_public_key = $booster['server_public_key'] ?? '';
$server_peers_telemetry = $booster['server_peers_telemetry'] ?? [];
$server_peers = $booster['server_peers'] ?? [];
$server_total_rx = $booster['server_total_rx'] ?? '0 B';
$server_total_tx = $booster['server_total_tx'] ?? '0 B';
$server_active = !empty($booster['server_active']);
$server_scripts = $booster['server_scripts'] ?? [];
$srv_ros_script = $server_scripts['routeros'] ?? '';
$srv_linux_script = $server_scripts['linux'] ?? '';

// Active mode tab: Default mutlak adalah 'client' (Uplink Booster). Server mode hanya aktif jika diminta via ?mode=server
$active_mode = (isset($_GET['mode']) && strtolower($_GET['mode']) === 'server') ? 'server' : 'client';
?>

<div class="container-fluid mitranet-page-container">
    <div class="mitranet-window">

        <!-- HEADER: BADGE + MAIN TABS -->
        <div class="mitranet-header">
            <div class="mitranet-title-badge">
                <i class="fa-solid fa-bolt text-warning"></i> Cloud Speed Booster
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="mitranet-tabs">
                <li class="active">
                    <a href="vpn_booster.php"><i class="fa-solid fa-gauge-high"></i> Cloud Speed Booster</a>
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

        <!-- SUB-TOOLBAR: DUAL ROLE SELECTOR & ACTIONS -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left" style="display:flex; align-items:center; gap:8px;">
                <!-- DUAL ROLE SWITCH PILLS -->
                <div class="btn-group btn-group-sm" role="group" style="margin-right:10px;">
                    <button type="button" class="btn <?=($active_mode==='client'?'btn-primary active':'btn-default')?>" onclick="switchBoosterRole('client')" style="font-weight:600;">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Client Mode (Uplink Booster)
                    </button>
                    <button type="button" class="btn <?=($active_mode==='server'?'btn-primary active':'btn-default')?>" onclick="switchBoosterRole('server')" style="font-weight:600;">
                        <i class="fa-solid fa-server"></i> Server Mode (Aggregation Hub)
                    </button>
                </div>

                <!-- ROLE SPECIFIC ACTION BUTTONS -->
                <div id="toolbar-actions-client" style="<?=$active_mode==='client'?'display:inline-flex; gap:6px;':'display:none;'?>">
                    <button type="button" class="mitranet-btn text-success" onclick="applyBoosterConfig()" title="Terapkan Konfigurasi Client Multi-Stream">
                        <i class="fa-solid fa-circle-check"></i> <strong>Terapkan Client Booster</strong>
                    </button>
                    <button type="button" class="mitranet-btn text-danger" onclick="stopBoosterConfig('client')" title="Hentikan Client Booster">
                        <i class="fa-solid fa-circle-stop"></i> Hentikan Client
                    </button>
                    <button type="button" class="mitranet-btn text-primary" onclick="showVpsScripts()" title="Salin Skrip VPS Server Gateway">
                        <i class="fa-solid fa-terminal"></i> Skrip VPS Gateway
                    </button>
                </div>

                <div id="toolbar-actions-server" style="<?=$active_mode==='server'?'display:inline-flex; gap:6px;':'display:none;'?>">
                    <button type="button" class="mitranet-btn text-success" onclick="applyServerBoosterConfig()" title="Aktifkan Aggregation Hub Server">
                        <i class="fa-solid fa-circle-check"></i> <strong>Aktifkan Server Hub</strong>
                    </button>
                    <button type="button" class="mitranet-btn text-danger" onclick="stopBoosterConfig('server')" title="Hentikan Aggregation Hub Server">
                        <i class="fa-solid fa-circle-stop"></i> Hentikan Server Hub
                    </button>
                    <button type="button" class="mitranet-btn text-info" onclick="showServerClientScripts()" title="Salin Skrip untuk Router Client MikroTik/Linux">
                        <i class="fa-solid fa-file-code"></i> Skrip Client MikroTik
                    </button>
                </div>

                <a href="/tools/speedtest.php?tab=benchmark" class="mitranet-btn text-warning" title="Uji Kecepatan Agregasi">
                    <i class="fa-solid fa-bolt"></i> Uji Kecepatan
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

        <!-- ================================================================================== -->
        <!-- CLIENT MODE WORKSPACE -->
        <!-- ================================================================================== -->
        <div id="panel-client-mode" style="<?=$active_mode==='client'?'display:block;':'display:none;'?>">
            <!-- CLIENT TELEMETRY HUD CARDS -->
            <div style="padding: 12px 14px 6px; background: #16202c; border-bottom: 1px solid #283747;">
                <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3">
                        <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                            <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Status Client Agregasi</div>
                            <div id="hud-client-status" style="font-size:18px; font-weight:700; margin-top:4px;" class="<?=$enabled ? 'text-success' : 'text-danger'?>">
                                <i class="fa-solid <?=$enabled ? 'fa-circle-check' : 'fa-circle-xmark'?>"></i> <?=$enabled ? 'AKTIF (MULTI-PATH)' : 'NON-AKTIF'?>
                            </div>
                            <div style="font-size:11px; color:#aaa; margin-top:2px;">
                                Alokasi: <strong id="hud-client-streams"><?=$stream_count?> Parallel Streams</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3">
                        <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                            <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Total RX Client (Masuk)</div>
                            <div id="hud-client-rx" style="font-size:18px; font-weight:700; margin-top:4px; color:#00d2be;">
                                <i class="fa-solid fa-arrow-down"></i> <?=htmlspecialchars($total_rx)?>
                            </div>
                            <div style="font-size:11px; color:#aaa; margin-top:2px;">Akumulasi seluruh stream</div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3">
                        <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                            <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Total TX Client (Keluar)</div>
                            <div id="hud-client-tx" style="font-size:18px; font-weight:700; margin-top:4px; color:#58a6ff;">
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

            <!-- TWO-COLUMN WORKSPACE: CLIENT CONFIG & STATUS -->
            <div style="padding: 14px;">
                <div class="row">
                    <!-- CLIENT PARAMETER FORM -->
                    <div class="col-md-5">
                        <div class="panel panel-default" style="background:#1a232f; border:1px solid #2d3b4b; border-radius:4px;">
                            <div class="panel-heading" style="background:#202c3b; color:#fff; font-weight:600; border-bottom:1px solid #2d3b4b;">
                                <i class="fa-solid fa-sliders"></i> Parameter Client Uplink Booster
                            </div>
                            <div class="panel-body" style="padding: 15px;">
                                <form id="form-booster">
                                    <div class="form-group">
                                        <label style="color:#d1d5db; font-size:12px;">Alamat VPS / Cloud Gateway IP atau Hostname <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control input-sm" id="booster-vps-host" value="<?=htmlspecialchars($vps_host)?>" placeholder="contoh: 103.93.162.168 atau vps.contoh.com" required>
                                        <small class="text-muted" style="font-size:11px;">IP Publik VPS gateway WireGuard tujuan agregasi.</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label style="color:#d1d5db; font-size:12px;">Jumlah Parallel Streams</label>
                                                <select class="form-control input-sm" id="booster-streams">
                                                    <option value="1" <?=($stream_count===1?'selected':'')?>>1 Stream (Baseline)</option>
                                                    <option value="2" <?=($stream_count===2?'selected':'')?>>2 Streams (2x Multi-Link)</option>
                                                    <option value="3" <?=($stream_count===3?'selected':'')?>>3 Streams (3x Multi-Link)</option>
                                                    <option value="4" <?=($stream_count===4?'selected':'')?>>4 Streams (4x Multi-Link)</option>
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
                                            <i class="fa-solid fa-play"></i> Terapkan & Aktifkan Client
                                        </button>
                                        <button type="button" class="btn btn-default btn-sm" onclick="stopBoosterConfig('client')" style="flex:1;">
                                            <i class="fa-solid fa-stop text-danger"></i> Hentikan Client
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- CLIENT LIVE STREAM HUD STATUS TABLE -->
                    <div class="col-md-7">
                        <div class="panel panel-default" style="background:#1a232f; border:1px solid #2d3b4b; border-radius:4px;">
                            <div class="panel-heading" style="background:#202c3b; color:#fff; font-weight:600; border-bottom:1px solid #2d3b4b; display:flex; justify-content:space-between; align-items:center;">
                                <span><i class="fa-solid fa-satellite-dish"></i> Status Real-Time Client Multi-Stream</span>
                                <span class="badge" style="background:#0984e3;"><?=count($streams)?> Streams Client</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed" style="margin-bottom:0; font-size:12px;">
                                    <thead>
                                        <tr style="background:#131b24; color:#95afc0;">
                                            <th>Stream</th>
                                            <th>Interface</th>
                                            <th>Alamat IP</th>
                                            <th>Port</th>
                                            <th>Status Link</th>
                                            <th>Latency RTT</th>
                                            <th>RX Terukur</th>
                                            <th>TX Terukur</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-client-streams">
                                        <?php if (empty($streams)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted" style="padding:20px;">
                                                    <i class="fa-solid fa-circle-info"></i> Belum ada stream client yang aktif. Masukkan alamat VPS dan klik <strong>Terapkan Client Booster</strong>.
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

                        <!-- PANDUAN KERJA TEKNIS CLIENT -->
                        <div class="panel panel-default" style="background:#141d27; border:1px solid #233140; border-radius:4px;">
                            <div class="panel-body" style="font-size:12px; color:#95afc0; line-height:1.6;">
                                <h5 style="color:#f1c40f; margin-top:0;"><i class="fa-solid fa-triangle-exclamation"></i> Prinsip Kerja Client Multi-Stream:</h5>
                                <p>
                                    1. <strong>Bypass Policer Bandwidth ISP:</strong> Banyak ISP menerapkan batasan speed per koneksi port UDP tunggal. Dengan membagi trafik ke beberapa tunnel port berbeda (51831–51834) menuju server gateway, throughput total terakumulasi berlipat ganda.
                                </p>
                                <p>
                                    2. <strong>ECMP / PCC Multipath:</strong> Kernel Linux mengalirkan koneksi secara simultan ke beberapa gateway WireGuard secara seimbang.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================================================================================== -->
        <!-- SERVER MODE WORKSPACE (AGGREGATION HUB CONCENTRATOR) -->
        <!-- ================================================================================== -->
        <div id="panel-server-mode" style="<?=$active_mode==='server'?'display:block;':'display:none;'?>">
            <!-- SERVER TELEMETRY HUD CARDS -->
            <div style="padding: 12px 14px 6px; background: #16202c; border-bottom: 1px solid #283747;">
                <div class="row">
                    <div class="col-xs-12 col-sm-3 col-md-3">
                        <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                            <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Status Aggregation Hub</div>
                            <div id="hud-server-status" style="font-size:18px; font-weight:700; margin-top:4px;" class="<?=$server_enabled ? 'text-success' : 'text-danger'?>">
                                <i class="fa-solid <?=$server_enabled ? 'fa-circle-check' : 'fa-circle-xmark'?>"></i> <?=$server_enabled ? 'SERVER LISTENING' : 'NON-AKTIF'?>
                            </div>
                            <div style="font-size:11px; color:#aaa; margin-top:2px;">
                                Listener Range: <strong id="hud-server-range"><?=$server_listen_port_start?>–<?=($server_listen_port_start + max(1, $server_stream_count) - 1)?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3">
                        <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                            <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Total RX Hub (Diterima dari Client)</div>
                            <div id="hud-server-rx" style="font-size:18px; font-weight:700; margin-top:4px; color:#00d2be;">
                                <i class="fa-solid fa-arrow-down"></i> <?=htmlspecialchars($server_total_rx)?>
                            </div>
                            <div style="font-size:11px; color:#aaa; margin-top:2px;">Akumulasi seluruh port listener</div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3">
                        <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                            <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Total TX Hub (Dikirim ke Client/Internet)</div>
                            <div id="hud-server-tx" style="font-size:18px; font-weight:700; margin-top:4px; color:#58a6ff;">
                                <i class="fa-solid fa-arrow-up"></i> <?=htmlspecialchars($server_total_tx)?>
                            </div>
                            <div style="font-size:11px; color:#aaa; margin-top:2px;">Akumulasi seluruh port listener</div>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-3 col-md-3">
                        <div style="background:#1c2938; border:1px solid #2f435a; border-radius:4px; padding:10px; margin-bottom:8px;">
                            <div style="font-size:11px; text-transform:uppercase; color:#8899a6; font-weight:600;">Subnet Pool Booster</div>
                            <div id="hud-server-subnet" style="font-size:14px; font-weight:700; margin-top:4px; color:#2ecc71;">
                                <i class="fa-solid fa-network-wired"></i> <?=htmlspecialchars($server_subnet)?>
                            </div>
                            <div style="font-size:11px; color:#aaa; margin-top:2px;">NAT Forwarding: <strong>MASQUERADE Enabled</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TWO-COLUMN WORKSPACE: SERVER CONFIG & CLIENT PEERS TABLE -->
            <div style="padding: 14px;">
                <div class="row">
                    <!-- SERVER CONFIG FORM & SERVER PUBLIC KEY -->
                    <div class="col-md-5">
                        <!-- SERVER PUBLIC KEY CARD -->
                        <div class="panel panel-default" style="background:#1a232f; border:1px solid #2d3b4b; border-radius:4px; margin-bottom:14px;">
                            <div class="panel-heading" style="background:#202c3b; color:#fff; font-weight:600; border-bottom:1px solid #2d3b4b; display:flex; justify-content:space-between; align-items:center;">
                                <span><i class="fa-solid fa-key text-warning"></i> Public Key Server MitraNet Ini</span>
                                <span class="label label-primary">Server WireGuard Key</span>
                            </div>
                            <div class="panel-body" style="padding:15px;">
                                <div class="form-group" style="margin-bottom:8px;">
                                    <label style="color:#d1d5db; font-size:12px;">Salin Public Key ini ke Router Client / MikroTik:</label>
                                    <div class="input-group">
                                        <input type="text" id="srv-public-key" class="form-control input-sm font-monospace" value="<?=htmlspecialchars($server_public_key)?>" readonly style="background:#0f1620; color:#00d2be; font-weight:bold;">
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="copyToClipboard('srv-public-key', 'Public Key Server berhasil disalin!')" title="Salin ke clipboard">
                                                <i class="fa-solid fa-copy"></i> Salin
                                            </button>
                                        </span>
                                    </div>
                                    <small class="text-muted" style="font-size:11px;">Kunci publik ini digenerate secara kriptografis oleh modul kernel WireGuard.</small>
                                </div>
                            </div>
                        </div>

                        <!-- SERVER PARAMETERS FORM -->
                        <div class="panel panel-default" style="background:#1a232f; border:1px solid #2d3b4b; border-radius:4px;">
                            <div class="panel-heading" style="background:#202c3b; color:#fff; font-weight:600; border-bottom:1px solid #2d3b4b;">
                                <i class="fa-solid fa-sliders"></i> Pengaturan Aggregation Hub Server
                            </div>
                            <div class="panel-body" style="padding: 15px;">
                                <form id="form-server-booster">
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label style="color:#d1d5db; font-size:12px;">Jumlah Listener Ports</label>
                                                <select class="form-control input-sm" id="srv-stream-count">
                                                    <option value="1" <?=($server_stream_count===1?'selected':'')?>>1 Port (51831)</option>
                                                    <option value="2" <?=($server_stream_count===2?'selected':'')?>>2 Ports (51831–51832)</option>
                                                    <option value="3" <?=($server_stream_count===3?'selected':'')?>>3 Ports (51831–51833)</option>
                                                    <option value="4" <?=($server_stream_count===4?'selected':'')?>>4 Ports (51831–51834)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                <label style="color:#d1d5db; font-size:12px;">Port Awal (Listen Port Start)</label>
                                                <input type="number" class="form-control input-sm" id="srv-port-start" value="<?=$server_listen_port_start?>" min="1024" max="65500" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label style="color:#d1d5db; font-size:12px;">Alokasi Subnet Hub Pool</label>
                                        <input type="text" class="form-control input-sm" id="srv-subnet" value="<?=htmlspecialchars($server_subnet)?>" placeholder="10.250.0.0/16" required>
                                        <small class="text-muted" style="font-size:11px;">Tiap stream port akan menggunakan alokasi <code>10.250.{x}.1/30</code> untuk server dan <code>10.250.{x}.2/30</code> untuk client.</small>
                                    </div>

                                    <div class="form-group">
                                        <label style="color:#d1d5db; font-size:12px;">Akselerasi BBR Kernel Server</label>
                                        <div class="checkbox" style="margin-top:4px;">
                                            <label style="color:#d1d5db; font-size:12px;">
                                                <input type="checkbox" id="srv-enable-bbr" <?=$enable_bbr ? 'checked' : ''?>>
                                                Aktifkan Algoritma TCP BBR + FQ Queuing di Server
                                            </label>
                                        </div>
                                    </div>

                                    <!-- PEER CLIENT PUBLIC KEYS INPUTS -->
                                    <div style="border-top:1px solid #2d3b4b; padding-top:10px; margin-top:10px;">
                                        <label style="color:#f1c40f; font-size:12px;"><i class="fa-solid fa-users"></i> Daftarkan Public Key Client Tiap Stream:</label>
                                        <?php for ($i = 1; $i <= 4; $i++): 
                                            $curr_peer_pub = '';
                                            foreach ($server_peers as $sp) {
                                                if (intval($sp['stream_id'] ?? 0) === $i) {
                                                    $curr_peer_pub = $sp['client_pubkey'] ?? '';
                                                    break;
                                                }
                                            }
                                        ?>
                                            <div class="form-group srv-peer-input-row" id="row-srv-peer-<?=$i?>" style="margin-bottom:8px; <?=$i > $server_stream_count ? 'display:none;' : ''?>">
                                                <div style="display:flex; justify-content:space-between; font-size:11px; color:#95afc0;">
                                                    <span>Stream #<?=$i?> (Port <?=$server_listen_port_start + $i - 1?>, Client IP: 10.250.<?=$i?>.2):</span>
                                                </div>
                                                <input type="text" class="form-control input-sm font-monospace srv-client-key" data-stream="<?=$i?>" id="srv-client-pubkey-<?=$i?>" value="<?=htmlspecialchars($curr_peer_pub)?>" placeholder="Public Key dari Client Stream #<?=$i?>">
                                            </div>
                                        <?php endfor; ?>
                                    </div>

                                    <div style="margin-top: 15px; border-top: 1px solid #2d3b4b; padding-top: 12px; display:flex; gap:10px;">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="applyServerBoosterConfig()" style="flex:1;">
                                            <i class="fa-solid fa-play"></i> Terapkan & Jalankan Server Hub
                                        </button>
                                        <button type="button" class="btn btn-default btn-sm" onclick="stopBoosterConfig('server')" style="flex:1;">
                                            <i class="fa-solid fa-stop text-danger"></i> Hentikan Server
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- SERVER REAL-TIME STREAMS & PEERS TABLE -->
                    <div class="col-md-7">
                        <div class="panel panel-default" style="background:#1a232f; border:1px solid #2d3b4b; border-radius:4px;">
                            <div class="panel-heading" style="background:#202c3b; color:#fff; font-weight:600; border-bottom:1px solid #2d3b4b; display:flex; justify-content:space-between; align-items:center;">
                                <span><i class="fa-solid fa-tower-broadcast"></i> Status Port Listener & Handshake Client</span>
                                <span class="badge" style="background:#27ae60;"><?=count($server_peers_telemetry)?> Listener Streams</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-condensed" style="margin-bottom:0; font-size:12px;">
                                    <thead>
                                        <tr style="background:#131b24; color:#95afc0;">
                                            <th>Stream</th>
                                            <th>Dev</th>
                                            <th>Port</th>
                                            <th>IP Server / Client</th>
                                            <th>Status</th>
                                            <th>Handshake</th>
                                            <th>RX Hub</th>
                                            <th>TX Hub</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-server-streams">
                                        <?php if (empty($server_peers_telemetry)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted" style="padding:20px;">
                                                    <i class="fa-solid fa-circle-info"></i> Server Hub belum aktif. Klik <strong>Aktifkan Server Hub</strong> di atas.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($server_peers_telemetry as $sp): 
                                                $status = $sp['status'] ?? 'DOWN';
                                                $lbl_cls = ($status === 'UP') ? 'label-success' : (($status === 'READY') ? 'label-info' : 'label-danger');
                                                $hs = intval($sp['latest_handshake'] ?? 0);
                                                $hs_str = ($hs > 0) ? (time() - $hs) . ' detik lalu' : 'Belum pernah';
                                            ?>
                                                <tr>
                                                    <td><strong>#<?=$sp['stream_id']?></strong></td>
                                                    <td><span class="label label-default font-monospace"><?=$sp['interface']?></span></td>
                                                    <td><span class="badge" style="background:#2d3b4b;"><?=$sp['listen_port']?></span></td>
                                                    <td>
                                                        <div style="font-size:11px;">S: <code><?=$sp['server_ip']?></code></div>
                                                        <div style="font-size:11px; color:#95afc0;">C: <code><?=$sp['client_ip']?></code></div>
                                                    </td>
                                                    <td>
                                                        <span class="label <?=$lbl_cls?>">
                                                            <i class="fa-solid <?=$status==='UP'?'fa-check':($status==='READY'?'fa-satellite':'fa-times')?>"></i> <?=$status?>
                                                        </span>
                                                    </td>
                                                    <td class="text-muted" style="font-size:11px;"><?=$hs_str?></td>
                                                    <td class="text-success font-monospace"><?=$sp['rx_formatted']?></td>
                                                    <td class="text-primary font-monospace"><?=$sp['tx_formatted']?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- PANDUAN KERJA TEKNIS SERVER -->
                        <div class="panel panel-default" style="background:#141d27; border:1px solid #233140; border-radius:4px;">
                            <div class="panel-body" style="font-size:12px; color:#95afc0; line-height:1.6;">
                                <h5 style="color:#2ecc71; margin-top:0;"><i class="fa-solid fa-circle-check"></i> Arsitektur Aggregation Hub Server MitraNet:</h5>
                                <p>
                                    1. <strong>MitraNet sebagai Server Gateway:</strong> Mesin MitraNet ini bertindak sebagai VPS/Konsentrator terpusat. Router MikroTik di cabang atau pelanggan di luar jaringan dapat menghubungkan beberapa interface WireGuard secara paralel menuju MitraNet ini.
                                </p>
                                <p>
                                    2. <strong>Multi-Port UDP Listeners:</strong> Setiap stream port (51831–51834) berjalan secara terisolasi dengan tunnel subnet <code>/30</code> dan terintegrasi ke tabel <code>iptables MASQUERADE</code> sehingga client mendapatkan akses internet teragregasi.
                                </p>
                                <p>
                                    3. <strong>BBR Server Side:</strong> Mengoptimalkan transmisi paket balik (downlink) ke router client dengan latensi minimal dan zero packet drop.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ================================================================================== -->
<!-- MODAL: CLIENT SCRIPTS UNTUK VPS GATEWAY -->
<!-- ================================================================================== -->
<div class="modal fade" id="modalVpsScripts" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="background:#1a232f; color:#fff; border:1px solid #334455;">
            <div class="modal-header" style="border-bottom:1px solid #2d3b4b;">
                <button type="button" class="close text-muted" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa-solid fa-terminal text-primary"></i> Skrip Konfigurasi VPS Gateway (Jika MitraNet Berperan Sebagai Client)</h4>
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

<!-- ================================================================================== -->
<!-- MODAL: SERVER SCRIPTS UNTUK CLIENT MIKROTIK/LINUX -->
<!-- ================================================================================== -->
<div class="modal fade" id="modalServerClientScripts" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="background:#1a232f; color:#fff; border:1px solid #334455;">
            <div class="modal-header" style="border-bottom:1px solid #2d3b4b;">
                <button type="button" class="close text-muted" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa-solid fa-file-code text-info"></i> Skrip Setup untuk Router Client (MikroTik / Linux)</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:12px;">
                    Gunakan skrip di bawah ini pada Router MikroTik atau Client Linux Anda agar dapat terhubung ke <strong>Server Hub MitraNet</strong> ini:
                </p>
                <ul class="nav nav-tabs" style="border-bottom:1px solid #2d3b4b; margin-bottom:12px;">
                    <li class="active"><a href="#tab-srv-ros" data-toggle="tab"><i class="fa-solid fa-network-wired"></i> MikroTik Client Script</a></li>
                    <li><a href="#tab-srv-linux" data-toggle="tab"><i class="fa-brands fa-linux"></i> Linux Client Config</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-srv-ros">
                        <textarea id="txt-srv-ros-script" class="form-control font-monospace" rows="12" readonly style="background:#0f1620; color:#00d2be; font-size:12px; border:1px solid #283747;"><?=htmlspecialchars($srv_ros_script)?></textarea>
                        <div style="margin-top:8px;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="copyToClipboard('txt-srv-ros-script', 'Skrip MikroTik Client berhasil disalin!')">
                                <i class="fa-solid fa-copy"></i> Salin Skrip MikroTik
                            </button>
                        </div>
                    </div>
                    <div class="tab-pane" id="tab-srv-linux">
                        <textarea id="txt-srv-linux-script" class="form-control font-monospace" rows="12" readonly style="background:#0f1620; color:#58a6ff; font-size:12px; border:1px solid #283747;"><?=htmlspecialchars($srv_linux_script)?></textarea>
                        <div style="margin-top:8px;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="copyToClipboard('txt-srv-linux-script', 'Skrip Linux Client berhasil disalin!')">
                                <i class="fa-solid fa-copy"></i> Salin Skrip Linux
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
// Switch antara Client Mode dan Server Mode
function switchBoosterRole(role) {
    if (role === 'server') {
        $('#panel-client-mode').hide();
        $('#panel-server-mode').show();
        $('#toolbar-actions-client').hide();
        $('#toolbar-actions-server').css('display', 'inline-flex');
    } else {
        $('#panel-server-mode').hide();
        $('#panel-client-mode').show();
        $('#toolbar-actions-server').hide();
        $('#toolbar-actions-client').css('display', 'inline-flex');
    }
}

// Update dinamis baris input peer client saat jumlah port server berubah
$('#srv-stream-count').on('change', function() {
    var count = parseInt($(this).val()) || 1;
    for (var i = 1; i <= 4; i++) {
        if (i <= count) {
            $('#row-srv-peer-' + i).show();
        } else {
            $('#row-srv-peer-' + i).hide();
        }
    }
});

// Handler Client Booster Apply
function applyBoosterConfig() {
    var vpsHost = $('#booster-vps-host').val().trim();
    if (!vpsHost) {
        MitraNet.toast('error', 'Alamat VPS / Cloud Gateway wajib diisi.');
        $('#booster-vps-host').focus();
        return;
    }

    var payload = {
        role: 'client',
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
        title: 'Terapkan Client Booster?',
        text: 'Sistem akan menyiapkan ' + payload.stream_count + ' parallel stream WireGuard dan konfigurasi ECMP multipath routing.',
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
                        MitraNet.toast('success', resp.message || 'Client Booster berhasil dikonfigurasi.');
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

// Handler Server Booster Apply
function applyServerBoosterConfig() {
    var portStart = parseInt($('#srv-port-start').val()) || 51831;
    var streamCount = parseInt($('#srv-stream-count').val()) || 2;
    var subnet = $('#srv-subnet').val().trim() || '10.250.0.0/16';
    var enableBbr = $('#srv-enable-bbr').is(':checked');

    var serverPeers = [];
    for (var i = 1; i <= streamCount; i++) {
        var key = $('#srv-client-pubkey-' + i).val().trim();
        serverPeers.push({
            stream_id: i,
            name: 'Client-Stream-' + i,
            client_pubkey: key
        });
    }

    var payload = {
        role: 'server',
        server_listen_port_start: portStart,
        stream_count: streamCount,
        server_subnet: subnet,
        enable_bbr: enableBbr,
        server_peers: serverPeers
    };

    Swal.fire({
        title: 'Aktifkan Aggregation Hub Server?',
        text: 'MitraNet akan membuka ' + streamCount + ' port UDP listener (' + portStart + '–' + (portStart + streamCount - 1) + ') dan mengaktifkan IP Forwarding serta NAT.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#27ae60',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Jalankan Server',
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
                        MitraNet.toast('success', resp.message || 'Server Hub Booster berhasil diaktifkan.');
                        if (resp.data && resp.data.server_scripts) {
                            $('#txt-srv-ros-script').val(resp.data.server_scripts.routeros || '');
                            $('#txt-srv-linux-script').val(resp.data.server_scripts.linux || '');
                        }
                        setTimeout(function() {
                            location.href = 'vpn_booster.php?mode=server';
                        }, 1200);
                    } else {
                        MitraNet.toast('error', resp.error || 'Gagal mengaktifkan Server Hub.');
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

// Handler Stop Booster (Client atau Server)
function stopBoosterConfig(targetRole) {
    var roleName = (targetRole === 'server') ? 'Server Hub' : 'Client Booster';
    Swal.fire({
        title: 'Hentikan ' + roleName + '?',
        text: 'Seluruh antarmuka tunnel ' + roleName + ' akan ditutup dan routing dipulihkan.',
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
                data: JSON.stringify({ role: targetRole || 'all' }),
                contentType: 'application/json',
                success: function(resp) {
                    Swal.close();
                    if (resp.success) {
                        MitraNet.toast('success', resp.message || (roleName + ' berhasil dihentikan.'));
                        setTimeout(function() {
                            location.href = 'vpn_booster.php?mode=' + (targetRole || 'client');
                        }, 1000);
                    } else {
                        MitraNet.toast('error', resp.error || 'Gagal menghentikan layanan.');
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

function showServerClientScripts() {
    $('#modalServerClientScripts').modal('show');
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

// -------------------------------------------------------------
// Live Auto-Polling Telemetry (Setiap 3 Detik)
// -------------------------------------------------------------
var isPollingActive = true;
function pollBoosterTelemetry() {
    if (!isPollingActive || document.hidden) return;

    $.ajax({
        url: '/api/v1/vpn/booster/status',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp && resp.success && resp.data) {
                var d = resp.data;

                // Update Client Telemetry
                if (d.enabled) {
                    $('#hud-client-status').html('<i class="fa-solid fa-circle-check"></i> AKTIF (MULTI-PATH)').removeClass('text-danger').addClass('text-success');
                } else {
                    $('#hud-client-status').html('<i class="fa-solid fa-circle-xmark"></i> NON-AKTIF').removeClass('text-success').addClass('text-danger');
                }
                $('#hud-client-streams').text((d.client_stream_count || d.stream_count || 2) + ' Parallel Streams');
                $('#hud-client-rx').html('<i class="fa-solid fa-arrow-down"></i> ' + (d.total_rx_formatted || '0 B'));
                $('#hud-client-tx').html('<i class="fa-solid fa-arrow-up"></i> ' + (d.total_tx_formatted || '0 B'));

                // Update Client Stream Table
                if (d.streams && d.streams.length > 0) {
                    var cHtml = '';
                    d.streams.forEach(function(s) {
                        var isUp = (s.status === 'UP');
                        var badgeCls = isUp ? 'label-success' : 'label-danger';
                        var icon = isUp ? 'fa-check' : 'fa-times';
                        cHtml += '<tr>' +
                            '<td><strong>Stream #' + s.id + '</strong></td>' +
                            '<td><span class="label label-default" style="font-family:monospace;">' + s.interface + '</span></td>' +
                            '<td><code>' + s.ip + '/30</code></td>' +
                            '<td><span class="badge" style="background:#2d3b4b; font-weight:normal;">' + s.port + '</span></td>' +
                            '<td><span class="label ' + badgeCls + '"><i class="fa-solid ' + icon + '"></i> ' + s.status + '</span></td>' +
                            '<td class="text-info font-monospace">' + s.latency + '</td>' +
                            '<td class="text-success font-monospace">' + s.rx_formatted + '</td>' +
                            '<td class="text-primary font-monospace">' + s.tx_formatted + '</td>' +
                            '</tr>';
                    });
                    $('#tbody-client-streams').html(cHtml);
                }

                // Update Server Telemetry
                if (d.server_enabled) {
                    $('#hud-server-status').html('<i class="fa-solid fa-circle-check"></i> SERVER LISTENING').removeClass('text-danger').addClass('text-success');
                } else {
                    $('#hud-server-status').html('<i class="fa-solid fa-circle-xmark"></i> NON-AKTIF').removeClass('text-success').addClass('text-danger');
                }
                var pStart = d.server_listen_port_start || 51831;
                var sCount = d.server_stream_count || 2;
                $('#hud-server-range').text(pStart + '–' + (pStart + sCount - 1));
                $('#hud-server-rx').html('<i class="fa-solid fa-arrow-down"></i> ' + (d.server_total_rx || '0 B'));
                $('#hud-server-tx').html('<i class="fa-solid fa-arrow-up"></i> ' + (d.server_total_tx || '0 B'));

                // Update Server Streams Table
                if (d.server_peers_telemetry && d.server_peers_telemetry.length > 0) {
                    var sHtml = '';
                    var now = Math.floor(Date.now() / 1000);
                    d.server_peers_telemetry.forEach(function(sp) {
                        var st = sp.status || 'DOWN';
                        var lblCls = (st === 'UP') ? 'label-success' : ((st === 'READY') ? 'label-info' : 'label-danger');
                        var icon = (st === 'UP') ? 'fa-check' : ((st === 'READY') ? 'fa-satellite' : 'fa-times');
                        var hs = parseInt(sp.latest_handshake || 0);
                        var hsStr = (hs > 0) ? (now - hs) + ' detik lalu' : 'Belum pernah';
                        sHtml += '<tr>' +
                            '<td><strong>#' + sp.stream_id + '</strong></td>' +
                            '<td><span class="label label-default font-monospace">' + sp.interface + '</span></td>' +
                            '<td><span class="badge" style="background:#2d3b4b;">' + sp.listen_port + '</span></td>' +
                            '<td><div style="font-size:11px;">S: <code>' + sp.server_ip + '</code></div><div style="font-size:11px; color:#95afc0;">C: <code>' + sp.client_ip + '</code></div></td>' +
                            '<td><span class="label ' + lblCls + '"><i class="fa-solid ' + icon + '"></i> ' + st + '</span></td>' +
                            '<td class="text-muted" style="font-size:11px;">' + hsStr + '</td>' +
                            '<td class="text-success font-monospace">' + sp.rx_formatted + '</td>' +
                            '<td class="text-primary font-monospace">' + sp.tx_formatted + '</td>' +
                            '</tr>';
                    });
                    $('#tbody-server-streams').html(sHtml);
                }
            }
        }
    });
}

// Start polling
setInterval(pollBoosterTelemetry, 3000);
</script>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
