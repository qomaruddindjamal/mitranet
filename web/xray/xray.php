<?php
/*
 * xray.php - MitraNet XRay-Core Management (Server, Client & Service)
 * WinBox Esthetic & SweetAlert2 UX Standards
 */

$tab = isset($_GET['tab']) ? trim(strip_tags($_GET['tab'])) : 'status';
$valid_tabs = array('status', 'server', 'client', 'routing', 'clients', 'config');
if (!in_array($tab, $valid_tabs)) {
    $tab = 'status';
}

$tab_titles = array(
    'status'   => 'Status & Service',
    'server'   => 'XRay Server',
    'client'   => 'XRay Client',
    'routing'  => 'Routing & Rules',
    'clients'  => 'Client Links & Share',
    'config'   => 'Config Editor'
);

$pgtitle = array("VPN", "XRay-Core", $tab_titles[$tab]);
$pglinks = array("", "/xray/xray.php", "@self");
$selected_menu = "vpn";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$errmsg = "";

// Ambil status dan config awal
$xray = MitraNetApi::getXray();
$is_running = !empty($xray['running']);
$xray_ver = $xray['version'] ?? 'Xray 26.3.27 (go1.26.1 linux/amd64)';
$cfg = $xray['config'] ?? [];

// Helper Default Config Builder jika kosong
function getDefaultXrayConfig() {
    return [
        "log" => [
            "loglevel" => "warning",
            "access" => "/var/log/xray/access.log",
            "error" => "/var/log/xray/error.log"
        ],
        "dns" => [
            "servers" => [
                "https+local://1.1.1.1/dns-query",
                "8.8.8.8",
                "localhost"
            ]
        ],
        "inbounds" => [
            [
                "tag" => "vless-reality-in",
                "port" => 10443,
                "protocol" => "vless",
                "settings" => [
                    "clients" => [
                        [
                            "id" => "d7e26a8f-287c-482a-a92c-6338b556f891",
                            "flow" => "xtls-rprx-vision",
                            "email" => "admin@mitranet.lan"
                        ]
                    ],
                    "decryption" => "none"
                ],
                "streamSettings" => [
                    "network" => "tcp",
                    "security" => "reality",
                    "realitySettings" => [
                        "show" => false,
                        "dest" => "www.microsoft.com:443",
                        "xver" => 0,
                        "serverNames" => ["www.microsoft.com", "microsoft.com"],
                        "privateKey" => "mOw6JXLRTHKnG5zAU4cWdHRitrZCrE-CkG_a8WlpVlk",
                        "shortIds" => ["0123456789abcdef", ""]
                    ]
                ]
            ],
            [
                "tag" => "vmess-ws-in",
                "port" => 8080,
                "protocol" => "vmess",
                "settings" => [
                    "clients" => [
                        [
                            "id" => "ca2dfe28-ff28-4cf9-85c8-f40c0aa3ebb7",
                            "alterId" => 0,
                            "email" => "client-vmess@mitranet.lan"
                        ]
                    ]
                ],
                "streamSettings" => [
                    "network" => "ws",
                    "wsSettings" => [
                        "path" => "/vmess"
                    ]
                ]
            ],
            [
                "tag" => "socks-lan",
                "port" => 10808,
                "listen" => "0.0.0.0",
                "protocol" => "socks",
                "settings" => [
                    "auth" => "noauth",
                    "udp" => true
                ]
            ],
            [
                "tag" => "http-lan",
                "port" => 10809,
                "listen" => "0.0.0.0",
                "protocol" => "http",
                "settings" => [
                    "allowTransparent" => true
                ]
            ]
        ],
        "outbounds" => [
            [
                "tag" => "direct",
                "protocol" => "freedom",
                "settings" => [
                    "domainStrategy" => "UseIP"
                ]
            ],
            [
                "tag" => "blocked",
                "protocol" => "blackhole",
                "settings" => []
            ],
            [
                "tag" => "proxy-vps",
                "protocol" => "vless",
                "settings" => [
                    "vnext" => [
                        [
                            "address" => "vps.example.com",
                            "port" => 443,
                            "users" => [
                                [
                                    "id" => "00000000-0000-0000-0000-000000000000",
                                    "encryption" => "none",
                                    "flow" => "xtls-rprx-vision"
                                ]
                            ]
                        ]
                    ]
                ],
                "streamSettings" => [
                    "network" => "tcp",
                    "security" => "reality",
                    "realitySettings" => [
                        "show" => false,
                        "fingerprint" => "chrome",
                        "serverName" => "www.microsoft.com",
                        "publicKey" => "T6Pkrut45w6i02GJAWPuK6nokn5aD2kA1SrGG7GwmBw",
                        "shortId" => "0123456789abcdef",
                        "spiderX" => ""
                    ]
                ]
            ]
        ],
        "routing" => [
            "domainStrategy" => "IPIfNonMatch",
            "rules" => [
                [
                    "type" => "field",
                    "outboundTag" => "blocked",
                    "domain" => ["geosite:category-ads-all"]
                ],
                [
                    "type" => "field",
                    "outboundTag" => "direct",
                    "ip" => ["geoip:private", "geoip:id"]
                ]
            ]
        ]
    ];
}

// Jika config dari backend kosong, isi dengan baseline
if (empty($cfg) || empty($cfg['inbounds'])) {
    $cfg = getDefaultXrayConfig();
}

// POST Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    // Kontrol Layanan (start, stop, restart, test)
    if (in_array($act, ['start', 'stop', 'restart', 'test'])) {
        $res = MitraNetApi::controlXray($act);
        if (!empty($res['data']['success'])) {
            if ($act === 'test') {
                $savemsg = "Config Syntax Test Result: OK\n" . htmlspecialchars($res['data']['output'] ?? '');
            } else {
                $savemsg = "XRay-Core service {$act} executed successfully.";
            }
        } else {
            $errmsg = "XRay-Core action failed: " . htmlspecialchars($res['data']['error'] ?? $res['data']['output'] ?? 'Error');
        }
    }

    // Simpan Raw Config JSON
    if ($act === 'save_raw') {
        $raw_json = $_POST['raw_config'] ?? '';
        $decoded = json_decode($raw_json, true);
        if ($decoded === null && !empty($raw_json)) {
            $errmsg = "Invalid JSON syntax. Periksa kembali format kurung dan tanda petik.";
        } else {
            $res = MitraNetApi::saveXrayConfig($decoded);
            if (!empty($res['data']['success'])) {
                $savemsg = "XRay-Core configuration saved successfully. Menjalankan reload service...";
                MitraNetApi::controlXray('restart');
                $cfg = $decoded;
            } else {
                $errmsg = "Gagal menyimpan konfigurasi: " . htmlspecialchars($res['data']['error'] ?? 'Unknown error');
            }
        }
    }

    // Simpan Konfigurasi XRay Server (VLESS Reality & VMess)
    if ($act === 'save_server') {
        $server_port = intval($_POST['server_port'] ?? 10443);
        $server_uuid = trim($_POST['server_uuid'] ?? '');
        $server_dest = trim($_POST['server_dest'] ?? 'www.microsoft.com:443');
        $server_sni = trim($_POST['server_sni'] ?? 'www.microsoft.com');
        $server_privkey = trim($_POST['server_privkey'] ?? '');
        $server_shortid = trim($_POST['server_shortid'] ?? '');

        // Update Inbound VLESS Reality
        $updated_inbounds = [];
        $found_vless = false;
        foreach (($cfg['inbounds'] ?? []) as $ib) {
            if (($ib['protocol'] ?? '') === 'vless' && ($ib['tag'] ?? '') === 'vless-reality-in') {
                $ib['port'] = $server_port;
                $ib['settings']['clients'][0]['id'] = $server_uuid;
                $ib['streamSettings']['realitySettings']['dest'] = $server_dest;
                $ib['streamSettings']['realitySettings']['serverNames'] = array_values(array_filter([$server_sni]));
                $ib['streamSettings']['realitySettings']['privateKey'] = $server_privkey;
                $ib['streamSettings']['realitySettings']['shortIds'] = [$server_shortid, ""];
                $found_vless = true;
            }
            $updated_inbounds[] = $ib;
        }

        if (!$found_vless) {
            $updated_inbounds[] = [
                "tag" => "vless-reality-in",
                "port" => $server_port,
                "protocol" => "vless",
                "settings" => [
                    "clients" => [["id" => $server_uuid, "flow" => "xtls-rprx-vision", "email" => "admin@mitranet.lan"]],
                    "decryption" => "none"
                ],
                "streamSettings" => [
                    "network" => "tcp",
                    "security" => "reality",
                    "realitySettings" => [
                        "show" => false,
                        "dest" => $server_dest,
                        "serverNames" => [$server_sni],
                        "privateKey" => $server_privkey,
                        "shortIds" => [$server_shortid, ""]
                    ]
                ]
            ];
        }

        $cfg['inbounds'] = $updated_inbounds;
        $res = MitraNetApi::saveXrayConfig($cfg);
        if (!empty($res['data']['success'])) {
            $savemsg = "XRay Server (Inbound Reality) berhasil diperbarui dan diterapkan.";
            MitraNetApi::controlXray('restart');
        } else {
            $errmsg = "Gagal memperbarui XRay Server: " . htmlspecialchars($res['data']['error'] ?? '');
        }
    }

    // Simpan Konfigurasi XRay Client (Outbounds / Proxy Client)
    if ($act === 'save_client') {
        $client_enabled = !empty($_POST['client_enabled']);
        $vps_host = trim($_POST['vps_host'] ?? '');
        $vps_port = intval($_POST['vps_port'] ?? 443);
        $vps_uuid = trim($_POST['vps_uuid'] ?? '');
        $vps_sni = trim($_POST['vps_sni'] ?? 'www.microsoft.com');
        $vps_pubkey = trim($_POST['vps_pubkey'] ?? '');
        $vps_shortid = trim($_POST['vps_shortid'] ?? '');
        $client_socks_port = intval($_POST['client_socks_port'] ?? 10808);

        // Update Outbound proxy-vps
        $updated_outbounds = [];
        $found_proxy = false;
        foreach (($cfg['outbounds'] ?? []) as $ob) {
            if (($ob['tag'] ?? '') === 'proxy-vps') {
                $ob['settings']['vnext'][0]['address'] = $vps_host;
                $ob['settings']['vnext'][0]['port'] = $vps_port;
                $ob['settings']['vnext'][0]['users'][0]['id'] = $vps_uuid;
                $ob['streamSettings']['realitySettings']['serverName'] = $vps_sni;
                $ob['streamSettings']['realitySettings']['publicKey'] = $vps_pubkey;
                $ob['streamSettings']['realitySettings']['shortId'] = $vps_shortid;
                $found_proxy = true;
            }
            $updated_outbounds[] = $ob;
        }

        if (!$found_proxy) {
            $updated_outbounds[] = [
                "tag" => "proxy-vps",
                "protocol" => "vless",
                "settings" => [
                    "vnext" => [[
                        "address" => $vps_host,
                        "port" => $vps_port,
                        "users" => [["id" => $vps_uuid, "encryption" => "none", "flow" => "xtls-rprx-vision"]]
                    ]]
                ],
                "streamSettings" => [
                    "network" => "tcp",
                    "security" => "reality",
                    "realitySettings" => [
                        "show" => false,
                        "fingerprint" => "chrome",
                        "serverName" => $vps_sni,
                        "publicKey" => $vps_pubkey,
                        "shortId" => $vps_shortid,
                        "spiderX" => ""
                    ]
                ]
            ];
        }
        $cfg['outbounds'] = $updated_outbounds;

        // Ensure SOCKS LAN port is updated
        foreach ($cfg['inbounds'] as &$ib) {
            if (($ib['tag'] ?? '') === 'socks-lan') {
                $ib['port'] = $client_socks_port;
            }
        }

        $res = MitraNetApi::saveXrayConfig($cfg);
        if (!empty($res['data']['success'])) {
            $savemsg = "XRay Client (Outbound Proxy) berhasil disimpan dan diterapkan.";
            MitraNetApi::controlXray('restart');
        } else {
            $errmsg = "Gagal memperbarui XRay Client: " . htmlspecialchars($res['data']['error'] ?? '');
        }
    }

    // Refresh state
    $xray = MitraNetApi::getXray();
    $is_running = !empty($xray['running']);
    $xray_ver = $xray['version'] ?? $xray_ver;
}

// Ekstraksi Nilai Server Form
$vless_inbound = null;
$vmess_inbound = null;
foreach (($cfg['inbounds'] ?? []) as $ib) {
    if (($ib['protocol'] ?? '') === 'vless') {
        $vless_inbound = $ib;
    } elseif (($ib['protocol'] ?? '') === 'vmess') {
        $vmess_inbound = $ib;
    }
}

$srv_port = $vless_inbound['port'] ?? 10443;
$srv_uuid = $vless_inbound['settings']['clients'][0]['id'] ?? 'd7e26a8f-287c-482a-a92c-6338b556f891';
$srv_dest = $vless_inbound['streamSettings']['realitySettings']['dest'] ?? 'www.microsoft.com:443';
$srv_sni  = $vless_inbound['streamSettings']['realitySettings']['serverNames'][0] ?? 'www.microsoft.com';
$srv_privkey = $vless_inbound['streamSettings']['realitySettings']['privateKey'] ?? 'mOw6JXLRTHKnG5zAU4cWdHRitrZCrE-CkG_a8WlpVlk';
$srv_shortid = $vless_inbound['streamSettings']['realitySettings']['shortIds'][0] ?? '0123456789abcdef';

// Ekstraksi Nilai Client Outbound Form
$vps_outbound = null;
foreach (($cfg['outbounds'] ?? []) as $ob) {
    if (($ob['tag'] ?? '') === 'proxy-vps') {
        $vps_outbound = $ob;
        break;
    }
}

$cl_host = $vps_outbound['settings']['vnext'][0]['address'] ?? 'vps.example.com';
$cl_port = $vps_outbound['settings']['vnext'][0]['port'] ?? 443;
$cl_uuid = $vps_outbound['settings']['vnext'][0]['users'][0]['id'] ?? '';
$cl_sni  = $vps_outbound['streamSettings']['realitySettings']['serverName'] ?? 'www.microsoft.com';
$cl_pubkey = $vps_outbound['streamSettings']['realitySettings']['publicKey'] ?? '';
$cl_shortid = $vps_outbound['streamSettings']['realitySettings']['shortId'] ?? '';

// IP MiniPC untuk Share Link
$minipc_ip = $_SERVER['SERVER_ADDR'] ?? '10.10.66.228';
if ($minipc_ip === '127.0.0.1' || empty($minipc_ip)) {
    $minipc_ip = '10.10.66.228';
}
$share_vless = "vless://{$srv_uuid}@{$minipc_ip}:{$srv_port}?security=reality&sni={$srv_sni}&fp=chrome&pbk=T6Pkrut45w6i02GJAWPuK6nokn5aD2kA1SrGG7GwmBw&sid={$srv_shortid}&type=tcp&flow=xtls-rprx-vision#MitraNet-Rinjani-VLESS";

?>

<div class="container-fluid mitranet-page-container">
    <div class="mitranet-window">

        <!-- HEADER: BADGE "XRay-Core" + TABS (WINBOX STANDARD) -->
        <div class="mitranet-header">
            <div class="mitranet-title-badge">
                <i class="fa-solid fa-bolt text-warning"></i> XRay-Core
                <i class="fa-solid fa-caret-down"></i>
            </div>
            <ul class="mitranet-tabs">
                <li class="<?=($tab === 'status') ? 'active' : ''?>">
                    <a href="xray.php?tab=status"><i class="fa-solid fa-heart-pulse"></i> Status &amp; Service</a>
                </li>
                <li class="<?=($tab === 'server') ? 'active' : ''?>">
                    <a href="xray.php?tab=server"><i class="fa-solid fa-server"></i> XRay Server</a>
                </li>
                <li class="<?=($tab === 'client') ? 'active' : ''?>">
                    <a href="xray.php?tab=client"><i class="fa-solid fa-shield-halved"></i> XRay Client</a>
                </li>
                <li class="<?=($tab === 'routing') ? 'active' : ''?>">
                    <a href="xray.php?tab=routing"><i class="fa-solid fa-route"></i> Routing &amp; Rules</a>
                </li>
                <li class="<?=($tab === 'clients') ? 'active' : ''?>">
                    <a href="xray.php?tab=clients"><i class="fa-solid fa-qrcode"></i> Client Links &amp; Share</a>
                </li>
                <li class="<?=($tab === 'config') ? 'active' : ''?>">
                    <a href="xray.php?tab=config"><i class="fa-solid fa-code"></i> Config Editor</a>
                </li>
            </ul>
        </div>

        <!-- TOOLBAR WINBOX -->
        <div class="mitranet-toolbar">
            <div class="mitranet-toolbar-left">
                <button type="button" class="mitranet-btn" onclick="submitXrayAction('restart')" title="Restart XRay-Core Service">
                    <i class="fa-solid fa-arrows-rotate text-warning"></i> <strong>Restart</strong>
                </button>
                <?php if ($is_running): ?>
                <button type="button" class="mitranet-btn" onclick="submitXrayAction('stop')" title="Stop XRay-Core">
                    <i class="fa-solid fa-stop text-danger"></i> Stop
                </button>
                <?php else: ?>
                <button type="button" class="mitranet-btn" onclick="submitXrayAction('start')" title="Start XRay-Core">
                    <i class="fa-solid fa-play text-success"></i> Start
                </button>
                <?php endif; ?>
                <button type="button" class="mitranet-btn" onclick="submitXrayAction('test')" title="Test Configuration Syntax">
                    <i class="fa-solid fa-check text-info"></i> Test Syntax
                </button>
                <button type="button" class="mitranet-btn" onclick="generateNewKeys()" title="Generate X25519 &amp; UUID">
                    <i class="fa-solid fa-key text-primary"></i> KeyGen
                </button>
            </div>
            <div class="mitranet-toolbar-right">
                <span class="label <?= $is_running ? 'label-success' : 'label-danger' ?>" style="font-size:11px; padding:4px 8px;">
                    <i class="fa-solid <?= $is_running ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                    <?= $is_running ? 'SERVICE RUNNING' : 'SERVICE STOPPED' ?>
                </span>
                <span class="text-muted" style="font-size:11px; margin-left:8px;">
                    <code><?= htmlspecialchars($xray_ver) ?></code>
                </span>
            </div>
        </div>

        <div class="mitranet-window-body">

        <?php if ($savemsg): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <strong><i class="fa-solid fa-circle-check"></i> Sukses:</strong> <?= nl2br($savemsg) ?>
        </div>
        <?php endif; ?>

        <?php if ($errmsg): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <strong><i class="fa-solid fa-triangle-exclamation"></i> Perhatian:</strong> <?= nl2br($errmsg) ?>
        </div>
        <?php endif; ?>

        <!-- ================= TAB 1: STATUS & SERVICE ================= -->
        <?php if ($tab === 'status'): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa-solid fa-server"></i> Status Runtime XRay-Core</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-striped">
                            <tr>
                                <th style="width: 35%;">Systemd Service</th>
                                <td>
                                    <?php if ($is_running): ?>
                                        <span class="label label-success"><i class="fa-solid fa-check"></i> Active (Running)</span>
                                    <?php else: ?>
                                        <span class="label label-danger"><i class="fa-solid fa-xmark"></i> Inactive (Stopped)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Binary Version</th>
                                <td><code><?= htmlspecialchars($xray_ver) ?></code></td>
                            </tr>
                            <tr>
                                <th>Binary Executable</th>
                                <td><code>/usr/local/bin/xray</code></td>
                            </tr>
                            <tr>
                                <th>Active Config File</th>
                                <td><code>/usr/local/etc/xray/config.json</code></td>
                            </tr>
                            <tr>
                                <th>GeoIP / GeoSite Assets</th>
                                <td>
                                    <span class="label label-success"><i class="fa-solid fa-database"></i> geoip.dat</span>
                                    <span class="label label-success"><i class="fa-solid fa-database"></i> geosite.dat</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Logging Destination</th>
                                <td><code>/var/log/xray/access.log</code> &amp; <code>error.log</code></td>
                            </tr>
                        </table>

                        <div class="well well-sm">
                            <strong>Aksi Cepat:</strong>
                            <div style="margin-top: 8px;">
                                <a href="xray.php?tab=server" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-sliders"></i> Konfigurasi Server
                                </a>
                                <a href="xray.php?tab=client" class="btn btn-sm btn-info">
                                    <i class="fa-solid fa-shield-halved"></i> Konfigurasi Client
                                </a>
                                <a href="xray.php?tab=config" class="btn btn-sm btn-default">
                                    <i class="fa-solid fa-code"></i> Edit JSON Raw
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa-solid fa-network-wired"></i> Port &amp; Listener Aktif</h3>
                    </div>
                    <div class="panel-body">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr style="background:#f5f5f5;">
                                    <th>Tag</th>
                                    <th>Protokol</th>
                                    <th>Port</th>
                                    <th>Keamanan / Stream</th>
                                    <th>Fungsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($cfg['inbounds'] ?? []) as $ib): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ib['tag'] ?? 'inbound') ?></strong></td>
                                    <td><span class="badge" style="background:#337ab7;"><?= strtoupper(htmlspecialchars($ib['protocol'] ?? 'unknown')) ?></span></td>
                                    <td><code><?= htmlspecialchars($ib['port'] ?? '-') ?></code></td>
                                    <td>
                                        <?php
                                        $sec = $ib['streamSettings']['security'] ?? 'none';
                                        $net = $ib['streamSettings']['network'] ?? 'tcp';
                                        echo htmlspecialchars(ucfirst($sec) . " ({$net})");
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (in_array($ib['protocol'] ?? '', ['vless', 'vmess', 'trojan'])): ?>
                                            <span class="text-success"><i class="fa-solid fa-arrow-down-left"></i> Server Inbound</span>
                                        <?php else: ?>
                                            <span class="text-info"><i class="fa-solid fa-laptop"></i> Local Gateway Proxy</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= TAB 2: XRAY SERVER ================= -->
        <?php elseif ($tab === 'server'): ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa-solid fa-server"></i> Konfigurasi XRay Server (Inbound VLESS Reality / VMess)</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted">
                    Modul ini mengonfigurasi MitraNet Appliance agar berfungsi sebagai <strong>XRay Server</strong> (Stealth Anti-Censorship Node).
                    Protokol <strong>VLESS + Reality + XTLS-Vision</strong> meniru trafik TLS ke domain terpercaya (SNI Camouflage) tanpa memerlukan sertifikat SSL terbitan publik.
                </p>

                <form method="post" action="xray.php?tab=server" id="form-xray-server">
                    <input type="hidden" name="act" value="save_server">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="server_port"><i class="fa-solid fa-plug"></i> Listen Port Inbound:</label>
                                <input type="number" class="form-control" id="server_port" name="server_port" value="<?= htmlspecialchars($srv_port) ?>" required>
                                <span class="help-block">Port default untuk VLESS Reality adalah <code>10443</code> atau <code>443</code>.</span>
                            </div>

                            <div class="form-group">
                                <label for="server_uuid"><i class="fa-solid fa-id-card"></i> Client User UUID:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="server_uuid" name="server_uuid" value="<?= htmlspecialchars($srv_uuid) ?>" required>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" onclick="generateNewUUID()" title="Generate Random UUID">
                                            <i class="fa-solid fa-dice"></i> New
                                        </button>
                                    </span>
                                </div>
                                <span class="help-block">UUID kredensial otentikasi client yang diizinkan connect ke server.</span>
                            </div>

                            <div class="form-group">
                                <label for="server_dest"><i class="fa-solid fa-globe"></i> Reality Dest Camouflage (Target Target TLS):</label>
                                <input type="text" class="form-control" id="server_dest" name="server_dest" value="<?= htmlspecialchars($srv_dest) ?>" required>
                                <span class="help-block">Contoh: <code>www.microsoft.com:443</code> atau <code>www.apple.com:443</code>.</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="server_sni"><i class="fa-solid fa-shield"></i> Server Name (SNI Camouflage):</label>
                                <input type="text" class="form-control" id="server_sni" name="server_sni" value="<?= htmlspecialchars($srv_sni) ?>" required>
                                <span class="help-block">Domain yang disamarkan dalam TLS Client Hello.</span>
                            </div>

                            <div class="form-group">
                                <label for="server_privkey"><i class="fa-solid fa-key"></i> X25519 Private Key:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="server_privkey" name="server_privkey" value="<?= htmlspecialchars($srv_privkey) ?>" required>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" onclick="generateNewKeys()" title="Generate X25519 Key Pair">
                                            <i class="fa-solid fa-arrows-rotate"></i> KeyGen
                                        </button>
                                    </span>
                                </div>
                                <span class="help-block">Private Key X25519 disimpan di server untuk proses handshake Reality.</span>
                            </div>

                            <div class="form-group">
                                <label for="server_shortid"><i class="fa-solid fa-fingerprint"></i> Short ID (Hex):</label>
                                <input type="text" class="form-control" id="server_shortid" name="server_shortid" value="<?= htmlspecialchars($srv_shortid) ?>" required>
                                <span class="help-block">Short ID 16-karakter hex untuk verifikasi keaslian client.</span>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="text-right">
                        <button type="button" class="btn btn-primary" onclick="confirmSaveServer()">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan &amp; Terapkan Konfigurasi Server
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= TAB 3: XRAY CLIENT ================= -->
        <?php elseif ($tab === 'client'): ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa-solid fa-shield-halved"></i> Konfigurasi XRay Client (Outbound Proxy Uplink)</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted">
                    Modul ini mengonfigurasi MitraNet Appliance sebagai <strong>XRay Client</strong>.
                    Traffic jaringan LAN atau koneksi internal dialihkan keluar menuju remote XRay Server / VPS (Transparent Proxy Gateway).
                </p>

                <form method="post" action="xray.php?tab=client" id="form-xray-client">
                    <input type="hidden" name="act" value="save_client">

                    <div class="row">
                        <div class="col-md-6">
                            <h4><i class="fa-solid fa-cloud"></i> Upstream Remote Server (VPS)</h4>
                            <div class="form-group">
                                <label for="vps_host"><i class="fa-solid fa-server"></i> Server Address / Hostname VPS:</label>
                                <input type="text" class="form-control" id="vps_host" name="vps_host" value="<?= htmlspecialchars($cl_host) ?>" placeholder="Contoh: vps.domain.com atau 103.x.x.x" required>
                            </div>

                            <div class="form-group">
                                <label for="vps_port"><i class="fa-solid fa-plug"></i> Server Port:</label>
                                <input type="number" class="form-control" id="vps_port" name="vps_port" value="<?= htmlspecialchars($cl_port) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="vps_uuid"><i class="fa-solid fa-id-card"></i> User UUID Akun VPS:</label>
                                <input type="text" class="form-control" id="vps_uuid" name="vps_uuid" value="<?= htmlspecialchars($cl_uuid) ?>" placeholder="UUID dari server VPS penyedia" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h4><i class="fa-solid fa-lock"></i> Parameter Keamanan (Reality / TLS)</h4>
                            <div class="form-group">
                                <label for="vps_sni"><i class="fa-solid fa-shield"></i> SNI Domain:</label>
                                <input type="text" class="form-control" id="vps_sni" name="vps_sni" value="<?= htmlspecialchars($cl_sni) ?>" placeholder="Contoh: www.microsoft.com" required>
                            </div>

                            <div class="form-group">
                                <label for="vps_pubkey"><i class="fa-solid fa-key"></i> Public Key Reality VPS:</label>
                                <input type="text" class="form-control" id="vps_pubkey" name="vps_pubkey" value="<?= htmlspecialchars($cl_pubkey) ?>" placeholder="Public Key dari server VPS" required>
                            </div>

                            <div class="form-group">
                                <label for="vps_shortid"><i class="fa-solid fa-fingerprint"></i> Short ID:</label>
                                <input type="text" class="form-control" id="vps_shortid" name="vps_shortid" value="<?= htmlspecialchars($cl_shortid) ?>" placeholder="Short ID dari server VPS">
                            </div>

                            <div class="form-group">
                                <label for="client_socks_port"><i class="fa-solid fa-network-wired"></i> Local LAN SOCKS5 Port:</label>
                                <input type="number" class="form-control" id="client_socks_port" name="client_socks_port" value="10808">
                                <span class="help-block">Port SOCKS5 lokal untuk client di LAN menghubungkan proxy browser/aplikasi.</span>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="text-right">
                        <button type="button" class="btn btn-primary" onclick="confirmSaveClient()">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan &amp; Hubungkan XRay Client
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ================= TAB 4: ROUTING & RULES ================= -->
        <?php elseif ($tab === 'routing'): ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa-solid fa-route"></i> Aturan Routing Cerdas (Smart Shunting Rules)</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted">
                    XRay-Core memisahkan trafik secara otomatis berdasarkan database <strong>GeoIP</strong> dan <strong>GeoSite</strong>.
                </p>

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th>Urutan</th>
                            <th>Target Domain / IP</th>
                            <th>Tindakan Outbound</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><code>geosite:category-ads-all</code></td>
                            <td><span class="label label-danger">BLOCKED (blackhole)</span></td>
                            <td>Memblokir iklan, tracking, dan malware secara terpusat</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><code>geoip:private</code>, <code>geoip:id</code></td>
                            <td><span class="label label-success">DIRECT (freedom)</span></td>
                            <td>Trafik lokal LAN (RFC1918) &amp; situs domestik Indonesia di-bypass langsung</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Semua trafik internasional lainnya</td>
                            <td><span class="label label-primary">PROXY (proxy-vps)</span></td>
                            <td>Diarahkan lewat jalur tunnel aman XRay</td>
                        </tr>
                    </tbody>
                </table>

                <h4>Raw Routing Object:</h4>
                <pre class="pre-scroll-400" style="background:#2d3748; color:#a0aec0;"><?= htmlspecialchars(json_encode($cfg['routing'] ?? [], JSON_PRETTY_PRINT)) ?></pre>
            </div>
        </div>

        <!-- ================= TAB 5: CLIENT LINKS & SHARE ================= -->
        <?php elseif ($tab === 'clients'): ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa-solid fa-qrcode"></i> Export Client URI &amp; QR Generator</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted">
                    Gunakan tautan URI di bawah ini untuk mengimpor profil koneksi ke client Xray (seperti <em>v2rayN</em> di Windows, <em>v2rayNG</em> di Android, atau <em>FoXray/Streisand</em> di iOS).
                </p>

                <h4><i class="fa-solid fa-link"></i> VLESS Reality Share Link:</h4>
                <div class="well well-sm" style="word-break: break-all; font-family: monospace; background:#f8f9fa;">
                    <span id="vless-link-text"><?= htmlspecialchars($share_vless) ?></span>
                </div>
                <button type="button" class="btn btn-sm btn-info" onclick="copyShareLink()">
                    <i class="fa-solid fa-copy"></i> Salin ke Clipboard
                </button>

                <hr>
                <h4><i class="fa-solid fa-qrcode"></i> QR Code Quick Scan:</h4>
                <div style="padding: 15px; background: white; display: inline-block; border: 1px solid #ddd; border-radius: 4px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($share_vless) ?>" alt="XRay VLESS QR Code" style="width:200px; height:200px;">
                </div>
                <p class="text-muted" style="margin-top: 8px; font-size: 12px;">
                    Scan QR ini menggunakan kamera v2rayNG / v2rayN untuk menghubungkan perangkat Anda secara instan.
                </p>
            </div>
        </div>

        <!-- ================= TAB 6: CONFIG EDITOR ================= -->
        <?php elseif ($tab === 'config'): ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa-solid fa-code"></i> Live Config Editor (<code>/usr/local/etc/xray/config.json</code>)</h3>
            </div>
            <div class="panel-body">
                <form method="post" action="xray.php?tab=config" id="form-raw-config">
                    <input type="hidden" name="act" value="save_raw">

                    <div class="form-group">
                        <textarea class="form-control" id="raw_config" name="raw_config" rows="22" style="font-family: monospace; font-size: 13px; background:#1e1e1e; color:#d4d4d4;"><?= htmlspecialchars(json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-info" onclick="formatConfigJSON()">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Rapikan / Format JSON
                            </button>
                            <button type="button" class="btn btn-default" onclick="submitXrayAction('test')">
                                <i class="fa-solid fa-check"></i> Uji Sintaks
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-primary" onclick="confirmSaveRaw()">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan &amp; Restart Service
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        </div><!-- /.mitranet-window-body -->
    </div><!-- /.mitranet-window -->
</div><!-- /.mitranet-page-container -->

<!-- Form POST Tersembunyi untuk Kontrol Layanan -->
<form id="action-post-form" method="post" action="xray.php?tab=<?= htmlspecialchars($tab) ?>" style="display:none;">
    <input type="hidden" name="act" id="action-post-act" value="">
</form>

<script>
// Aksi Kontrol Layanan via SweetAlert2
function submitXrayAction(action) {
    var titles = {
        'restart': 'Restart XRay-Core?',
        'stop': 'Hentikan Layanan XRay-Core?',
        'start': 'Jalankan Layanan XRay-Core?',
        'test': 'Uji Sintaks Konfigurasi?'
    };
    var title = titles[action] || 'Konfirmasi Aksi';

    MitraNet.confirmAction({
        title: title,
        text: 'Perintah ' + action.toUpperCase() + ' akan segera dieksekusi pada service systemd.',
        icon: (action === 'stop' ? 'warning' : 'info'),
        confirmButtonText: 'Ya, Jalankan'
    }).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Memproses perintah ' + action + '...', 'info');
            document.getElementById('action-post-act').value = action;
            document.getElementById('action-post-form').submit();
        }
    });
}

// Konfirmasi Simpan Server
function confirmSaveServer() {
    MitraNet.confirmAction({
        title: 'Simpan Konfigurasi XRay Server?',
        text: 'Konfigurasi Inbound VLESS Reality akan diperbarui dan service akan direstart.',
        icon: 'question',
        confirmButtonText: 'Simpan & Restart'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('form-xray-server').submit();
        }
    });
}

// Konfirmasi Simpan Client
function confirmSaveClient() {
    MitraNet.confirmAction({
        title: 'Simpan Konfigurasi XRay Client?',
        text: 'Outbound Proxy Uplink ke VPS akan diperbarui dan service akan direstart.',
        icon: 'question',
        confirmButtonText: 'Simpan & Restart'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('form-xray-client').submit();
        }
    });
}

// Konfirmasi Simpan Raw Config
function confirmSaveRaw() {
    try {
        var text = document.getElementById('raw_config').value;
        JSON.parse(text);
    } catch (e) {
        MitraNet.toast('Error JSON Syntax: ' + e.message, 'error');
        return;
    }

    MitraNet.confirmAction({
        title: 'Simpan Raw Config JSON?',
        text: 'Pastikan sintaks konfigurasi XRay valid sebelum menerapkan perubahan.',
        icon: 'warning',
        confirmButtonText: 'Terapkan'
    }).then(function(result) {
        if (result.isConfirmed) {
            document.getElementById('form-raw-config').submit();
        }
    });
}

// Format JSON
function formatConfigJSON() {
    var ta = document.getElementById('raw_config');
    try {
        var obj = JSON.parse(ta.value);
        ta.value = JSON.stringify(obj, null, 2);
        MitraNet.toast('JSON berhasil dirapikan.', 'success');
    } catch (e) {
        MitraNet.toast('Gagal merapikan: ' + e.message, 'error');
    }
}

// Salin Share Link
function copyShareLink() {
    var text = document.getElementById('vless-link-text').innerText;
    navigator.clipboard.writeText(text).then(function() {
        MitraNet.toast('Link VLESS berhasil disalin ke clipboard!', 'success');
    }).catch(function() {
        MitraNet.toast('Gagal menyalin link.', 'error');
    });
}

// Generate New UUID via Browser Random
function generateNewUUID() {
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (d + Math.random()*16)%16 | 0;
        d = Math.floor(d/16);
        return (c=='x' ? r : (r&0x3|0x8)).toString(16);
    });
    var input = document.getElementById('server_uuid');
    if (input) {
        input.value = uuid;
        MitraNet.toast('UUID baru ter-generate: ' + uuid, 'info');
    }
}

// Generate Keys via Backend Keygen
function generateNewKeys() {
    MitraNet.toast('Meminta X25519 Key Pair dari XRay Engine...', 'info');
    fetch('/api/v1/xray/keygen', { credentials: 'same-origin' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.private_key) {
            if (document.getElementById('server_privkey')) {
                document.getElementById('server_privkey').value = data.private_key;
            }
            if (document.getElementById('server_shortid')) {
                document.getElementById('server_shortid').value = data.short_id;
            }
            if (document.getElementById('server_uuid')) {
                document.getElementById('server_uuid').value = data.uuid;
            }
            MitraNet.toast('Keygen Berhasil! PrivateKey & ShortID diperbarui.', 'success');
        } else {
            generateNewUUID();
        }
    }).catch(function(err) {
        generateNewUUID();
    });
}
</script>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
