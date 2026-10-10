<?php
/*
 * vpn_wg_tunnels_edit.php - MitraNet WireGuard Edit / Add Tunnel
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = "";
$err_msg = "";
$tun_param = $_GET['tun'] ?? '';

// Handle AJAX Keygen
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'keygen') {
    header('Content-Type: application/json');
    $keys = MitraNetApi::generateWireGuardKeys();
    if (!empty($keys['data']['success'])) {
        echo json_encode(['success' => true, 'private_key' => $keys['data']['private_key'], 'public_key' => $keys['data']['public_key']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to generate keys']);
    }
    exit;
}

// Read current tunnels to determine default name
$wg = MitraNetApi::getWireGuard();
$existing_tuns = [];
foreach ($wg['tunnels'] ?? [] as $t) {
    $existing_tuns[$t['name']] = $t;
}

$is_edit = !empty($tun_param) && isset($existing_tuns[$tun_param]);
$current_tun = $is_edit ? $existing_tuns[$tun_param] : null;

// Determine suggest name for new tunnel
if (!$is_edit) {
    if (!isset($existing_tuns['wg0'])) {
        $suggested_name = 'wg0';
        $suggested_port = '51820';
        $suggested_addr = '10.10.99.1/24';
    } else {
        $i = 1;
        while (isset($existing_tuns["wg{$i}"])) $i++;
        $suggested_name = "wg{$i}";
        $suggested_port = (string)(51820 + $i);
        $suggested_addr = "10.10." . (99 + $i) . ".1/24";
    }
} else {
    $suggested_name = $current_tun['name'];
    $suggested_port = $current_tun['listen_port'] ?? '51820';
    $suggested_addr = $current_tun['address'] ?: '10.10.99.1/24';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save') {
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['name'] ?? 'wg0'));
    $listenport = trim($_POST['listenport'] ?? '51820');
    $privatekey = trim($_POST['privatekey'] ?? '');
    $descr = trim($_POST['descr'] ?? 'WireGuard Tunnel');
    $address = trim($_POST['address'] ?? '10.10.99.1/24');
    $mode = trim($_POST['mode'] ?? 'server');
    $route_interface = trim($_POST['route_interface'] ?? '');
    $enable_nat = isset($_POST['enable_nat']) && $_POST['enable_nat'] === 'yes';
    $dns = trim($_POST['dns'] ?? '');
    $mtu = trim($_POST['mtu'] ?? '1420');
    $dscp_class = trim($_POST['dscp_class'] ?? '');
    $clamp_mss = isset($_POST['clamp_mss']) && $_POST['clamp_mss'] === 'yes';

    if (empty($name)) {
        $err_msg = "Nama tunnel wajib diisi (contoh: wg0).";
    } elseif (empty($address)) {
        $err_msg = "Interface Address (CIDR) wajib diisi (contoh: 10.10.99.1/24).";
    } else {
        $res = MitraNetApi::saveWireGuardTunnel($name, $address, $listenport, $privatekey, $descr, $mode, $route_interface, $enable_nat, $dns, $mtu, $dscp_class, $clamp_mss);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            header('Location: /wg/vpn_wg_tunnels.php?savemsg=' . urlencode("Tunnel {$name} berhasil disimpan dan dijalankan."));
            exit;
        } else {
            $err_msg = $res['data']['error'] ?? 'Gagal menyimpan konfigurasi tunnel.';
        }
    }
}

$pgtitle = array("VPN", "WireGuard", "Tunnels", $is_edit ? "Edit Tunnel" : "Add Tunnel");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$tab_array = array(
    array("Tunnels", true, "/wg/vpn_wg_tunnels.php"),
    array("Peers", false, "/wg/vpn_wg_peers.php"),
    array("Settings", false, "/wg/vpn_wg_settings.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
display_top_tabs($tab_array, false, 'pills');

// Fetch available local interfaces for policy routing
$all_interfaces = MitraNetApi::getInterfaces();
$candidate_ifaces = [];
foreach ($all_interfaces as $k => $info) {
    $dev = $info['device'] ?? $info['name'] ?? $k;
    if ($dev && strpos($dev, 'wg') !== 0 && $dev !== 'lo') {
        $candidate_ifaces[$dev] = $info['descr'] ?? $info['description'] ?? strtoupper($dev);
    }
}
if (!isset($candidate_ifaces['veth0'])) {
    $candidate_ifaces['veth0'] = 'Virtual Ethernet (veth0)';
}
?>

<?php if ($err_msg): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($err_msg)?></div>
<?php endif; ?>

<form class="form-horizontal" method="post" action="vpn_wg_tunnels_edit.php<?=($is_edit ? '?tun=' . urlencode($suggested_name) : '')?>">
    <input type="hidden" name="act" value="save" />

    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title"><?=($is_edit ? "Edit WireGuard Tunnel ({$suggested_name})" : "Add New WireGuard Tunnel")?></h2>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label">Enable</label>
                <div class="checkbox col-sm-10">
                    <label><input name="enabled" id="enabled" type="checkbox" value="yes" checked> Enable Tunnel Service</label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="name"><span class="element-required">*</span> Tunnel Interface Name</label>
                <div class="col-sm-4">
                    <input class="form-control" name="name" id="name" type="text" value="<?=htmlspecialchars($suggested_name)?>" <?=$is_edit?'readonly':''?> required placeholder="e.g. wg0 or wg1" />
                    <span class="help-block">Nama antarmuka kernel Linux (disarankan format standar: <code>wg0</code>, <code>wg1</code>).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="mode">Operational Mode</label>
                <div class="col-sm-5">
                    <select class="form-control" name="mode" id="mode">
                        <option value="server" <?=(!$is_edit || empty($current_tun['mode']) || $current_tun['mode']==='server')?'selected':''?>>
                            VPN Server (Menerima koneksi dari Smartphone, Laptop, atau Remote Devices)
                        </option>
                        <option value="client" <?=($is_edit && ($current_tun['mode'] ?? '')==='client')?'selected':''?>>
                            Client / Uplink (MitraNet tersambung ke Server/VPS Eksternal)
                        </option>
                    </select>
                    <span class="help-block">Mode Server mengizinkan generate QR code dan konfigurasi client untuk HP/Laptop.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="descr">Description</label>
                <div class="col-sm-10">
                    <input class="form-control" name="descr" id="descr" type="text" value="<?=htmlspecialchars($current_tun['description'] ?? 'WireGuard Server Tunnel')?>" placeholder="Description" />
                    <span class="help-block">Deskripsi tujuan tunnel untuk dokumentasi administratif.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="address"><span class="element-required">*</span> Interface Address (CIDR)</label>
                <div class="col-sm-4">
                    <input class="form-control" name="address" id="address" type="text" value="<?=htmlspecialchars($suggested_addr)?>" required placeholder="e.g. 10.10.99.1/24" />
                    <span class="help-block">IP address dan prefix subnet internal interface WireGuard ini.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="listenport"><span class="element-required">*</span> Listen Port</label>
                <div class="col-sm-4">
                    <input class="form-control" name="listenport" id="listenport" type="text" value="<?=htmlspecialchars($suggested_port)?>" required placeholder="51820" />
                    <span class="help-block">Port UDP untuk komunikasi tunnel (default: 51820).</span>
                </div>
                <div class="col-sm-3">
                    <div class="dropdown">
                        <button class="btn btn-default btn-sm dropdown-toggle" type="button" id="dropdownCamouflageTun" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <i class="fa-solid fa-mask text-primary"></i> Camouflage <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownCamouflageTun">
                            <li class="dropdown-header">Penyamaran Port Server (Bypass ISP)</li>
                            <li><a href="javascript:void(0)" onclick="$('#listenport').val('53'); MitraNet.toast('Listen Port diatur ke 53 (DNS Protocol Camouflage)', 'info');"><i class="fa-solid fa-network-wired text-info"></i> Port 53 (DNS Server Bypass)</a></li>
                            <li><a href="javascript:void(0)" onclick="$('#listenport').val('443'); MitraNet.toast('Listen Port diatur ke 443 (QUIC / HTTPS Camouflage)', 'info');"><i class="fa-solid fa-shield-halved text-success"></i> Port 443 (QUIC/HTTPS Bypass)</a></li>
                            <li><a href="javascript:void(0)" onclick="$('#listenport').val('123'); MitraNet.toast('Listen Port diatur ke 123 (NTP Protocol Camouflage)', 'info');"><i class="fa-solid fa-clock text-warning"></i> Port 123 (NTP Time Bypass)</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a href="javascript:void(0)" onclick="$('#listenport').val('51820'); MitraNet.toast('Listen Port dikembalikan ke standar (51820)', 'info');"><i class="fa-solid fa-rotate-left"></i> Default (51820)</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="privatekey"><span class="element-required">*</span> Interface Keys</label>
                <div class="col-sm-4">
                    <input class="form-control" name="privatekey" id="privatekey" type="password" value="" placeholder="<?=$is_edit?'Leave blank to keep existing key':'Click Generate Keys or paste key'?>" autocomplete="new-password" <?=$is_edit?'':'required'?> />
                    <span class="help-block"><?=($is_edit ? 'Biarkan kosong jika tidak ingin mengubah private key saat ini.' : 'Private key untuk tunnel ini.')?></span>
                </div>
                <div class="col-sm-4">
                    <input class="form-control" name="publickey" id="publickey" type="text" value="<?=htmlspecialchars($current_tun['public_key'] ?? '')?>" readonly placeholder="Public Key" />
                    <span class="help-block">Public key tunnel (bagikan ini ke remote peer / client).</span>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-default btn-sm" type="button" id="btn-genkeys"><i class="fa-solid fa-key"></i> Generate Keys</button>
                </div>
            </div>

            <!-- Policy Routing & Outbound NAT Section -->
            <div class="well well-sm well-config">
                <strong class="text-dark-primary"><i class="fa-solid fa-route"></i> Advanced Policy Routing &amp; Outbound NAT (Masquerade)</strong>
                <p class="fs-085 text-muted mt-5">
                    Arahkan seluruh lalu lintas jaringan dari antarmuka lokal tertentu (misal: <code>veth0</code>, LAN, VM) agar otomatis di-routing keluar melalui tunnel WireGuard ini dengan NAT Masquerade.
                </p>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="route_interface">Route Local Interface</label>
                <div class="col-sm-4">
                    <select class="form-control" name="route_interface" id="route_interface">
                        <option value="">-- Do Not Route Local Interface (Default Point-to-Point) --</option>
                        <?php foreach ($candidate_ifaces as $if_name => $if_desc): ?>
                            <option value="<?=htmlspecialchars($if_name)?>" <?=(($current_tun['route_interface'] ?? '') === $if_name)?'selected':''?>>
                                <?=htmlspecialchars($if_name)?> (<?=htmlspecialchars($if_desc)?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help-block">Pilih interface lokal yang seluruh lalu lintasnya akan dialihkan melewati WireGuard ini (menggunakan Policy-Based Routing).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Outbound NAT</label>
                <div class="checkbox col-sm-10">
                    <label>
                        <input name="enable_nat" id="enable_nat" type="checkbox" value="yes" <?=(!empty($current_tun['enable_nat']) || !empty($current_tun['route_interface']))?'checked':''?>>
                        <strong>Enable Outbound NAT (Masquerade)</strong>
                    </label>
                    <span class="help-block">Aktifkan translasi alamat IP (SNAT/Masquerade) pada antarmuka WireGuard sehingga perangkat client mendapatkan akses internet penuh dari gateway remote.</span>
                </div>
            </div>

            <!-- Shaper Bypass & QoS DSCP Section -->
            <div class="well well-sm well-config">
                <strong class="text-dark-primary"><i class="fa-solid fa-gauge-high"></i> WireGuard Shaper &amp; Throttling Bypass</strong>
                <p class="fs-085 text-muted mt-5">
                    Teknik penembus batas bandwidth ISP dan DPI limiter: Injeksi QoS DSCP Priority Tagging agar paket WireGuard diperlakukan sebagai trafik prioritas tinggi (VoIP/Network Control) di router uplink, serta Anti-Fragmentation MSS Clamping untuk mencegah bottleneck fragmentasi paket UDP.
                </p>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="dscp_class">DSCP Priority Mark</label>
                <div class="col-sm-4">
                    <?php $cur_dscp = $current_tun['dscp_class'] ?? ''; ?>
                    <select class="form-control" name="dscp_class" id="dscp_class">
                        <option value="" <?=(empty($cur_dscp))?'selected':''?>>-- Disabled (Standard Best-Effort BE) --</option>
                        <option value="EF" <?=($cur_dscp==='EF')?'selected':''?>>EF (Expedited Forwarding / 46 - VoIP &amp; Lowest Latency)</option>
                        <option value="CS6" <?=($cur_dscp==='CS6')?'selected':''?>>CS6 (Internetwork Control / 48 - Router Routing Control)</option>
                        <option value="CS7" <?=($cur_dscp==='CS7')?'selected':''?>>CS7 (Network Control / 56 - Highest VIP Priority)</option>
                        <option value="AF41" <?=($cur_dscp==='AF41')?'selected':''?>>AF41 (Assured Forwarding High Throughput)</option>
                    </select>
                    <span class="help-block">Menandai paket WireGuard keluar dengan kode DSCP untuk melewati antrian shaping / drop ISP.</span>
                </div>
                <div class="col-sm-6">
                    <div class="checkbox">
                        <label>
                            <input name="clamp_mss" id="clamp_mss" type="checkbox" value="yes" <?=(!isset($current_tun['clamp_mss']) || !empty($current_tun['clamp_mss']))?'checked':''?>>
                            <strong>Anti-Fragmentation MSS Clamping (TCPMSS --clamp-mss-to-pmtu)</strong>
                        </label>
                        <span class="help-block">Mencegah paket TCP melebihi MTU WireGuard sehingga tidak dipecah/tercekik fragmentasi di jalur ISP.</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="dns">DNS Servers</label>
                <div class="col-sm-4">
                    <input class="form-control" name="dns" id="dns" type="text" value="<?=htmlspecialchars($current_tun['dns'] ?? '')?>" placeholder="e.g. 1.1.1.1, 8.8.8.8" />
                    <span class="help-block">DNS Resolver opsional untuk antarmuka WireGuard ini.</span>
                </div>
                <label class="col-sm-2 control-label" for="mtu">MTU</label>
                <div class="col-sm-2">
                    <input class="form-control" name="mtu" id="mtu" type="text" value="<?=htmlspecialchars($current_tun['mtu'] ?? '1420')?>" placeholder="1420" />
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2 save-row-20">
        <button class="btn btn-primary" type="submit" value="Save"><i class="fa-solid fa-save icon-embed-btn"></i> <?=($is_edit ? "Update Tunnel" : "Save Tunnel")?></button>
        <a href="vpn_wg_tunnels.php" class="btn btn-default"><i class="fa-solid fa-times"></i> Cancel</a>
    </div>
</form>

<script type="text/javascript">
$(document).ready(function() {
    $('#btn-genkeys').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Generating...');
        $.getJSON('vpn_wg_tunnels_edit.php?ajax=keygen', function(res) {
            btn.prop('disabled', false).html('<i class="fa-solid fa-key"></i> Generate Keys');
            if (res && res.success) {
                $('#privatekey').val(res.private_key);
                $('#publickey').val(res.public_key);
            } else {
                alert('Gagal menghasilkan kunci WireGuard.');
            }
        });
    });
});
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
