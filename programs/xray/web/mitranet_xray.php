<?php
/*
 * mitranet_xray.php
 * MitraNet OS - Complete Native pfSense Xray / V2Ray Management WebGUI
 * Supports: Client Mode (VLESS/VMESS/Trojan), VPS Server Mode (Inbound Reality),
 * Routing (Global/Bypass), DNS Anti-Leak, and Live Logs.
 */

require_once('guiconfig.inc');
require_once('pfsense-utils.inc');
require_once('service-utils.inc');

$pgtitle = array(gettext("VPN"), gettext("MitraNet Xray (V2Ray / VLESS)"));
$shortcut_section = "xray";

$NODES_DIR = "/usr/local/etc/mitranet/nodes";
$ACTIVE_CONFIG = "/usr/local/etc/mitranet/config.json";
$SETTINGS_FILE = "/usr/local/etc/mitranet/settings.json";
$LOG_FILE = "/var/log/xray.log";

if (!is_dir($NODES_DIR)) {
    @mkdir($NODES_DIR, 0755, true);
}

// Load Persistent Settings
$settings = array(
    'role' => 'client', // 'client' or 'server'
    'routing_mode' => 'bypass_private', // 'global' or 'bypass_private'
    'socks_port' => 10808,
    'http_port' => 10809,
    'tproxy_port' => 12345,
    'dns_servers' => '1.1.1.1, 8.8.8.8',
    'server_proto' => 'vless',
    'server_port' => 443,
    'server_uuid' => '',
    'server_sni' => 'gateway.icloud.com',
    'server_privkey' => '',
    'server_pubkey' => '',
    'server_shortid' => '1688'
);

if (file_exists($SETTINGS_FILE)) {
    $loaded = json_decode(file_get_contents($SETTINGS_FILE), true);
    if (is_array($loaded)) {
        $settings = array_merge($settings, $loaded);
    }
}

// Auto-generate Server Keys if empty
if (empty($settings['server_uuid'])) {
    $settings['server_uuid'] = trim(shell_exec("xray uuid 2>/dev/null") ?: "11111111-2222-3333-4444-555555555555");
}
if (empty($settings['server_privkey'])) {
    $kout = shell_exec("xray x25519 2>/dev/null");
    if (preg_match('/Private key:\s*(\S+)/', $kout, $m1)) {
        $settings['server_privkey'] = $m1[1];
    }
    if (preg_match('/Public key:\s*(\S+)/', $kout, $m2)) {
        $settings['server_pubkey'] = $m2[1];
    }
}

$tab = $_GET['tab'] ?? 'client';
$savemsg = "";
$savemsg_type = "info";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    
    if ($act === 'start') {
        exec("/usr/local/bin/mitranet-cli start 2>&1", $out, $ret);
        $savemsg = "Xray service started.";
        $savemsg_type = ($ret === 0) ? "success" : "danger";
    } elseif ($act === 'stop') {
        exec("/usr/local/bin/mitranet-cli stop 2>&1", $out, $ret);
        $savemsg = "Xray service stopped.";
        $savemsg_type = "warning";
    } elseif ($act === 'restart') {
        exec("/usr/local/bin/mitranet-cli restart 2>&1", $out, $ret);
        $savemsg = "Xray service restarted.";
        $savemsg_type = ($ret === 0) ? "success" : "danger";
    } elseif ($act === 'import_link') {
        $link = trim($_POST['link'] ?? '');
        if (!empty($link)) {
            $cmd = "/usr/local/bin/mitranet-cli import-link " . escapeshellarg($link) . " 2>&1";
            exec($cmd, $out, $ret);
            if ($ret === 0) {
                $savemsg = "Node berhasil diimpor dan siap digunakan!";
                $savemsg_type = "success";
            } else {
                $savemsg = "Gagal mengimpor link: " . htmlspecialchars(implode(" ", $out));
                $savemsg_type = "danger";
            }
        }
    } elseif ($act === 'use_node') {
        $node = trim($_POST['node'] ?? '');
        if (!empty($node)) {
            $cmd = "/usr/local/bin/mitranet-cli use-node " . escapeshellarg($node) . " 2>&1";
            exec($cmd, $out, $ret);
            $savemsg = "Node aktif dialihkan ke: " . htmlspecialchars($node);
            $savemsg_type = "success";
        }
    } elseif ($act === 'delete_node') {
        $node = trim($_POST['node'] ?? '');
        $filepath = $NODES_DIR . "/" . basename($node) . ".json";
        if (file_exists($filepath)) {
            unlink($filepath);
            $savemsg = "Node '" . htmlspecialchars($node) . "' berhasil dihapus.";
            $savemsg_type = "info";
        }
    } elseif ($act === 'ping') {
        exec("/usr/local/bin/mitranet-cli ping-node 2>&1", $out);
        $savemsg = htmlspecialchars(implode("<br>", $out));
        $savemsg_type = "info";
    } elseif ($act === 'tproxy_enable') {
        exec("/usr/local/bin/mitranet-cli tproxy enable 2>&1");
        $savemsg = "Transparent Proxy (Firewall TProxy) diaktifkan.";
        $savemsg_type = "success";
    } elseif ($act === 'tproxy_disable') {
        exec("/usr/local/bin/mitranet-cli tproxy disable 2>&1");
        $savemsg = "Transparent Proxy dinonaktifkan.";
        $savemsg_type = "warning";
    } elseif ($act === 'save_routing') {
        $settings['routing_mode'] = $_POST['routing_mode'] ?? 'bypass_private';
        $settings['socks_port'] = intval($_POST['socks_port'] ?? 10808);
        $settings['http_port'] = intval($_POST['http_port'] ?? 10809);
        $settings['tproxy_port'] = intval($_POST['tproxy_port'] ?? 12345);
        $settings['dns_servers'] = trim($_POST['dns_servers'] ?? '1.1.1.1, 8.8.8.8');
        file_put_contents($SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
        $savemsg = "Pengaturan Routing & Ports berhasil disimpan.";
        $savemsg_type = "success";
    } elseif ($act === 'save_server') {
        $settings['server_proto'] = $_POST['server_proto'] ?? 'vless';
        $settings['server_port'] = intval($_POST['server_port'] ?? 443);
        $settings['server_uuid'] = trim($_POST['server_uuid'] ?? '');
        $settings['server_sni'] = trim($_POST['server_sni'] ?? 'gateway.icloud.com');
        $settings['server_privkey'] = trim($_POST['server_privkey'] ?? '');
        $settings['server_pubkey'] = trim($_POST['server_pubkey'] ?? '');
        $settings['server_shortid'] = trim($_POST['server_shortid'] ?? '1688');
        file_put_contents($SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
        
        // Build Server Inbound Config
        $server_cfg = array(
            "log" => array("loglevel" => "warning", "access" => "/var/log/xray/access.log", "error" => "/var/log/xray/error.log"),
            "dns" => array("servers" => array("1.1.1.1", "8.8.8.8")),
            "inbounds" => array(
                array(
                    "tag" => "vless-reality-in",
                    "port" => $settings['server_port'],
                    "listen" => "0.0.0.0",
                    "protocol" => "vless",
                    "settings" => array(
                        "clients" => array(
                            array("id" => $settings['server_uuid'], "flow" => "xtls-rprx-vision")
                        ),
                        "decryption" => "none"
                    ),
                    "streamSettings" => array(
                        "network" => "tcp",
                        "security" => "reality",
                        "realitySettings" => array(
                            "show" => false,
                            "dest" => $settings['server_sni'] . ":443",
                            "xver" => 0,
                            "serverNames" => array($settings['server_sni']),
                            "privateKey" => $settings['server_privkey'],
                            "shortIds" => array($settings['server_shortid'])
                        )
                    )
                )
            ),
            "outbounds" => array(
                array("tag" => "direct", "protocol" => "freedom"),
                array("tag" => "block", "protocol" => "blackhole")
            )
        );
        file_put_contents($ACTIVE_CONFIG, json_encode($server_cfg, JSON_PRETTY_PRINT));
        exec("/usr/local/bin/mitranet-cli restart 2>&1");
        $savemsg = "Konfigurasi Xray SERVER VPS berhasil diterapkan dan Xray direstart!";
        $savemsg_type = "success";
    }
}

// Fetch Status
exec("pgrep -f 'xray run' || pgrep -x xray", $pids);
$is_running = !empty($pids);

$active_node_info = array('proto' => '-', 'server' => '-', 'port' => '-', 'name' => '-');
if (file_exists($ACTIVE_CONFIG)) {
    $cfg = json_decode(file_get_contents($ACTIVE_CONFIG), true);
    if (!empty($cfg['outbounds'][0])) {
        $ob = $cfg['outbounds'][0];
        $active_node_info['proto'] = strtoupper($ob['protocol'] ?? 'UNKNOWN');
        $vnext = $ob['settings']['vnext'][0] ?? null;
        if ($vnext) {
            $active_node_info['server'] = $vnext['address'] ?? '-';
            $active_node_info['port'] = $vnext['port'] ?? '-';
        }
    }
}

// Fetch All Nodes
$node_files = glob($NODES_DIR . "/*.json");
$nodes = array();
if (!empty($node_files)) {
    foreach ($node_files as $nf) {
        $nname = basename($nf, ".json");
        $d = json_decode(file_get_contents($nf), true);
        $proto = $d['outbounds'][0]['protocol'] ?? 'unknown';
        $vnext = $d['outbounds'][0]['settings']['vnext'][0] ?? null;
        $addr = $vnext['address'] ?? '-';
        $port = $vnext['port'] ?? '-';
        $is_active = ($addr === $active_node_info['server'] && (string)$port === (string)$active_node_info['port']);
        $nodes[] = array(
            'name' => $nname,
            'proto' => strtoupper($proto),
            'server' => $addr,
            'port' => $port,
            'is_active' => $is_active
        );
    }
}

// Transparent Proxy Status
exec("pfctl -s nat 2>/dev/null | grep 12345", $tproxy_check);
$tproxy_active = !empty($tproxy_check);

// WAN IP for Server Link
$wan_ip = get_interface_ip("wan") ?: "172.23.110.213";
$generated_client_link = "vless://{$settings['server_uuid']}@{$wan_ip}:{$settings['server_port']}?security=reality&encryption=none&pbk={$settings['server_pubkey']}&headerType=none&fp=chrome&type=tcp&flow=xtls-rprx-vision&sni={$settings['server_sni']}&sid={$settings['server_shortid']}#MitraNet_VPS_Server";

// Logs
$recent_logs = "";
if (file_exists($LOG_FILE)) {
    $recent_logs = shell_exec("tail -n 40 " . escapeshellarg($LOG_FILE));
}

$tab_array = array();
$tab_array[] = array(gettext("Dashboard & Klien"), ($tab === 'client'), "/mitranet_xray.php?tab=client");
$tab_array[] = array(gettext("VPS Server Inbound"), ($tab === 'server'), "/mitranet_xray.php?tab=server");
$tab_array[] = array(gettext("Pengaturan Routing & Ports"), ($tab === 'routing'), "/mitranet_xray.php?tab=routing");
$tab_array[] = array(gettext("Live Logs"), ($tab === 'logs'), "/mitranet_xray.php?tab=logs");

include('head.inc');
display_top_tabs($tab_array);

if (!empty($savemsg)) {
    print_info_box($savemsg, $savemsg_type);
}
?>

<?php if ($tab === 'client'): ?>
<!-- CLIENT DASHBOARD & NODES TAB -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><?=gettext("Status Layanan Xray Core (Client Mode)")?></h2>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-3 text-center" style="border-right: 1px solid #e5e5e5; padding: 15px;">
                <h4>Status Service</h4>
                <?php if ($is_running): ?>
                    <span class="label label-success" style="font-size: 15px; padding: 6px 12px; display: inline-block; margin-bottom: 10px;">
                        <i class="fa fa-play-circle"></i> RUNNING (PID: <?=implode(", ", $pids)?>)
                    </span>
                <?php else: ?>
                    <span class="label label-danger" style="font-size: 15px; padding: 6px 12px; display: inline-block; margin-bottom: 10px;">
                        <i class="fa fa-stop-circle"></i> STOPPED
                    </span>
                <?php endif; ?>
                <div>
                    <form method="post" style="display:inline-block; margin-top: 5px;">
                        <?php if ($is_running): ?>
                            <input type="hidden" name="action" value="stop" />
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-stop"></i> Stop</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="start" />
                            <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-play"></i> Start</button>
                        <?php endif; ?>
                    </form>
                    <form method="post" style="display:inline-block; margin-top: 5px;">
                        <input type="hidden" name="action" value="restart" />
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fa fa-refresh"></i> Restart</button>
                    </form>
                    <form method="post" style="display:inline-block; margin-top: 5px;">
                        <input type="hidden" name="action" value="ping" />
                        <button type="submit" class="btn btn-info btn-sm"><i class="fa fa-bolt"></i> Test Ping</button>
                    </form>
                </div>
            </div>

            <div class="col-sm-5" style="border-right: 1px solid #e5e5e5; padding: 15px;">
                <h4>Node Aktif Saat Ini</h4>
                <p><strong>Protokol:</strong> <span class="badge badge-primary"><?=$active_node_info['proto']?></span></p>
                <p><strong>Server Endpoint:</strong> <?=$active_node_info['server']?>:<?=$active_node_info['port']?></p>
                <p><strong>Port Lokal SOCKS5:</strong> <code>127.0.0.1:<?=$settings['socks_port']?></code> | <strong>HTTP:</strong> <code>127.0.0.1:<?=$settings['http_port']?></code></p>
            </div>

            <div class="col-sm-4" style="padding: 15px;">
                <h4>Transparent Proxy (Firewall TProxy)</h4>
                <p>Membelokkan trafik klien LAN otomatis ke tunnel proxy tanpa perlu setting manual di HP/laptop.</p>
                <p>
                    Status: 
                    <?php if ($tproxy_active): ?>
                        <span class="label label-success">AKTIF (Port <?=$settings['tproxy_port']?>)</span>
                    <?php else: ?>
                        <span class="label label-default">NON-AKTIF</span>
                    <?php endif; ?>
                </p>
                <form method="post">
                    <?php if ($tproxy_active): ?>
                        <input type="hidden" name="action" value="tproxy_disable" />
                        <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-ban"></i> Nonaktifkan TProxy</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="tproxy_enable" />
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-shield"></i> Aktifkan TProxy</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-info">
    <div class="panel-heading">
        <h2 class="panel-title"><i class="fa fa-plus-circle"></i> Tambah / Import Node Baru (Sangat Mudah Seperti di HP / MikroTik)</h2>
    </div>
    <div class="panel-body">
        <form method="post">
            <input type="hidden" name="action" value="import_link" />
            <div class="form-group">
                <label for="link">Paste Link Konfigurasi Akun VLESS, VMESS, atau Trojan di Sini:</label>
                <input type="text" name="link" id="link" class="form-control" placeholder="vless://xxxx-xxxx@domain.com:443?security=reality&sni=...#Nama_Node" required />
                <span class="help-block">Contoh: Salin (Copy) link akun VLESS / VMESS dari aplikasi v2rayNG / v2rayN atau provider VPN Anda, lalu Paste dan klik Import.</span>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-download"></i> 📥 Import & Simpan Node</button>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><i class="fa fa-server"></i> Daftar Node Proxy yang Tersedia</h2>
    </div>
    <div class="panel-body table-responsive">
        <?php if (empty($nodes)): ?>
            <p class="text-muted text-center" style="padding: 20px;">Belum ada node yang diimpor. Masukkan link vless:// atau vmess:// pada form di atas.</p>
        <?php else: ?>
            <table class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Node</th>
                        <th style="width: 100px;">Protokol</th>
                        <th>Server Host : Port</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nodes as $idx => $nd): ?>
                        <tr class="<?=$nd['is_active'] ? 'success' : ''?>">
                            <td><?=($idx + 1)?></td>
                            <td><strong><?=htmlspecialchars($nd['name'])?></strong></td>
                            <td><span class="label label-primary"><?=$nd['proto']?></span></td>
                            <td><code><?=htmlspecialchars($nd['server'])?>:<?=htmlspecialchars($nd['port'])?></code></td>
                            <td>
                                <?php if ($nd['is_active']): ?>
                                    <span class="label label-success"><i class="fa fa-check"></i> AKTIF</span>
                                <?php else: ?>
                                    <span class="label label-default">Standby</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$nd['is_active']): ?>
                                    <form method="post" style="display:inline-block;">
                                        <input type="hidden" name="action" value="use_node" />
                                        <input type="hidden" name="node" value="<?=htmlspecialchars($nd['name'])?>" />
                                        <button type="submit" class="btn btn-success btn-xs"><i class="fa fa-plug"></i> Sambungkan</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" style="display:inline-block; margin-left: 5px;" onsubmit="return confirm('Hapus node ini?');">
                                    <input type="hidden" name="action" value="delete_node" />
                                    <input type="hidden" name="node" value="<?=htmlspecialchars($nd['name'])?>" />
                                    <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'server'): ?>
<!-- SERVER INBOUND VPS TAB -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h2 class="panel-title"><i class="fa fa-cloud"></i> Konfigurasi pfSense Sebagai Server Xray VPS (Inbound Reality)</h2>
    </div>
    <div class="panel-body">
        <p class="text-muted">Gunakan mode ini jika pfSense Anda diinstal pada <strong>VPS dengan IP Publik</strong>. Anda dapat membuat Server VLESS Reality berkecepatan tinggi untuk disambungkan oleh router rumah / klien.</p>
        
        <form method="post" class="form-horizontal">
            <input type="hidden" name="action" value="save_server" />
            
            <div class="form-group">
                <label class="col-sm-3 control-label">Protokol Server</label>
                <div class="col-sm-6">
                    <select name="server_proto" class="form-control">
                        <option value="vless" selected>VLESS Reality (XTLS-Vision) - Rekomendasi Tertinggi Anti-DPI</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Port Listen Publik</label>
                <div class="col-sm-3">
                    <input type="number" name="server_port" class="form-control" value="<?=$settings['server_port']?>" />
                    <span class="help-block">Disarankan port <code>443</code> atau <code>8443</code> agar tersamar seperti HTTPS biasa.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">UUID Klien</label>
                <div class="col-sm-6">
                    <input type="text" name="server_uuid" class="form-control" value="<?=$settings['server_uuid']?>" required />
                    <span class="help-block">Kunci identitas akun (User ID).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Target SNI Samaran (Dest / ServerNames)</label>
                <div class="col-sm-6">
                    <input type="text" name="server_sni" class="form-control" value="<?=$settings['server_sni']?>" required />
                    <span class="help-block">Contoh domain samaran: <code>gateway.icloud.com</code>, <code>www.microsoft.com</code>, atau domain Anda.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Private Key (Server)</label>
                <div class="col-sm-6">
                    <input type="text" name="server_privkey" class="form-control" value="<?=$settings['server_privkey']?>" required />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Public Key (Untuk Klien)</label>
                <div class="col-sm-6">
                    <input type="text" name="server_pubkey" class="form-control" value="<?=$settings['server_pubkey']?>" required />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Short ID</label>
                <div class="col-sm-3">
                    <input type="text" name="server_shortid" class="form-control" value="<?=$settings['server_shortid']?>" required />
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Terapkan & Jalankan Server di VPS</button>
                </div>
            </div>
        </form>

        <hr/>
        <h4><i class="fa fa-share-alt"></i> Link Client Siap Pakai (Salin untuk Router Rumah / Klien):</h4>
        <div class="well well-sm">
            <textarea class="form-control" rows="3" readonly style="font-family: monospace; font-size: 12px;"><?=$generated_client_link?></textarea>
            <span class="help-block">Salin link ini lalu tempel (Paste) di pfSense rumah pada tab <strong>Dashboard & Klien</strong>.</span>
        </div>
    </div>
</div>

<?php elseif ($tab === 'routing'): ?>
<!-- ROUTING & PORTS TAB -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><i class="fa fa-random"></i> Pengaturan Routing & Port Jaringan</h2>
    </div>
    <div class="panel-body">
        <form method="post" class="form-horizontal">
            <input type="hidden" name="action" value="save_routing" />
            
            <div class="form-group">
                <label class="col-sm-3 control-label">Mode Kebijakan Routing</label>
                <div class="col-sm-6">
                    <select name="routing_mode" class="form-control">
                        <option value="bypass_private" <?=$settings['routing_mode'] === 'bypass_private' ? 'selected' : ''?>>Bypass Jaringan Lokal (IP Lokal Direct, Internet Lewat Xray)</option>
                        <option value="global" <?=$settings['routing_mode'] === 'global' ? 'selected' : ''?>>Global Proxy (Semua Trafik Dipaksa Lewat Xray)</option>
                    </select>
                    <span class="help-block">Pilih mode routing yang sesuai dengan kebutuhan jaringan kantor / warnet / rumah.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Port Inbound SOCKS5</label>
                <div class="col-sm-3">
                    <input type="number" name="socks_port" class="form-control" value="<?=$settings['socks_port']?>" />
                    <span class="help-block">Default: <code>10808</code></span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Port Inbound HTTP Proxy</label>
                <div class="col-sm-3">
                    <input type="number" name="http_port" class="form-control" value="<?=$settings['http_port']?>" />
                    <span class="help-block">Default: <code>10809</code></span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Port Transparent Proxy (TProxy)</label>
                <div class="col-sm-3">
                    <input type="number" name="tproxy_port" class="form-control" value="<?=$settings['tproxy_port']?>" />
                    <span class="help-block">Default: <code>12345</code> (digunakan oleh pf firewall).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Upstream DNS Server (Anti-Leak)</label>
                <div class="col-sm-6">
                    <input type="text" name="dns_servers" class="form-control" value="<?=$settings['dns_servers']?>" />
                    <span class="help-block">Pisahkan dengan koma. Contoh: <code>1.1.1.1, 8.8.8.8</code></span>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Pengaturan Routing</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'logs'): ?>
<!-- LIVE LOGS TAB -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title"><i class="fa fa-file-text-o"></i> Live Log Xray Core Engine</h2>
    </div>
    <div class="panel-body">
        <pre style="background: #111; color: #00ff66; max-height: 450px; overflow-y: scroll; font-family: monospace; font-size: 12px; padding: 15px; border-radius: 4px;"><?=htmlspecialchars($recent_logs ?: "(Belum ada log trafik tercatat di /var/log/xray.log)")?></pre>
        <p class="text-muted"><i class="fa fa-info-circle"></i> Log otomatis mencatat lalu lintas tunnel VLESS, handshake Reality, dan error koneksi.</p>
    </div>
</div>

<?php endif; ?>

<?php
include('foot.inc');
?>
