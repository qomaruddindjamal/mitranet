<?php
/*
 * vpn_wg_peers_edit.php - MitraNet WireGuard Edit / Add Peer
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = "";
$err_msg = "";
$tun_name = $_GET['tun'] ?? 'wg0';
$peer_pubkey = $_GET['peer'] ?? '';

// Handle AJAX PSK & Client Keypair Generation
if (isset($_REQUEST['ajax'])) {
    header('Content-Type: application/json');
    if ($_REQUEST['ajax'] === 'genpsk') {
        $keys = MitraNetApi::generateWireGuardKeys();
        if (!empty($keys['data']['success'])) {
            echo json_encode(['success' => true, 'preshared_key' => $keys['data']['private_key']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to generate PSK']);
        }
    } elseif ($_REQUEST['ajax'] === 'genclientkeys') {
        $keys = MitraNetApi::generateWireGuardKeys();
        if (!empty($keys['data']['success'])) {
            echo json_encode(['success' => true, 'private_key' => $keys['data']['private_key'], 'public_key' => $keys['data']['public_key']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to generate keys']);
        }
    }
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save') {
    $tunnel = trim($_POST['tun'] ?? 'wg0');
    $descr = trim($_POST['descr'] ?? 'WireGuard Peer');
    $pubkey = trim($_POST['publickey'] ?? '');
    $old_pubkey = trim($_POST['old_publickey'] ?? '');
    $client_privkey = trim($_POST['client_private_key'] ?? '');
    $client_dns = trim($_POST['client_dns'] ?? '1.1.1.1, 8.8.8.8');
    $endpoint = trim($_POST['endpoint'] ?? '');
    $port = trim($_POST['port'] ?? '');
    $allowed_ips = trim($_POST['allowed_ips'] ?? '10.10.99.2/32');
    $preshared = trim($_POST['presharedkey'] ?? '');
    $keepalive = trim($_POST['persistentkeepalive'] ?? '25');

    $full_endpoint = '';
    if (!empty($endpoint) && !isset($_POST['dynamic'])) {
        $endpoint_port = !empty($port) ? $port : '51820';
        $full_endpoint = "{$endpoint}:{$endpoint_port}";
    }

    if (empty($pubkey) || empty($allowed_ips)) {
        $err_msg = "Public Key dan Allowed IPs wajib diisi.";
    } else {
        $res = MitraNetApi::saveWireGuardPeer($tunnel, $pubkey, $allowed_ips, $full_endpoint, $keepalive, $preshared, $descr, $old_pubkey, $client_privkey, $client_dns);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            header('Location: /wg/vpn_wg_peers.php?savemsg=' . urlencode("Peer WireGuard berhasil disimpan."));
            exit;
        } else {
            $err_msg = $res['data']['error'] ?? 'Gagal menyimpan peer WireGuard.';
        }
    }
}

// Read current peer info if editing
$wg = MitraNetApi::getWireGuard();
$current_peer = null;
if (!empty($peer_pubkey)) {
    foreach ($wg['tunnels'] ?? [] as $t) {
        foreach ($t['peers'] ?? [] as $p) {
            if ($p['public_key'] === $peer_pubkey) {
                $current_peer = $p;
                $tun_name = $t['name'];
                break 2;
            }
        }
    }
}

$is_edit = !empty($current_peer);

// Parse endpoint host and port if present
$ep_host = '';
$ep_port = '51820';
if (!empty($current_peer['endpoint']) && $current_peer['endpoint'] !== '(none)' && $current_peer['endpoint'] !== 'Dynamic') {
    if (strpos($current_peer['endpoint'], ':') !== false) {
        $ep_parts = explode(':', $current_peer['endpoint']);
        $ep_host = $ep_parts[0];
        $ep_port = $ep_parts[1] ?? '51820';
    } else {
        $ep_host = $current_peer['endpoint'];
    }
}
$is_dynamic = empty($ep_host);

$pgtitle = array("VPN", "WireGuard", "Peers", $is_edit ? "Edit Peer" : "Add Peer");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "/wg/vpn_wg_peers.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$tab_array = array(
    array("Tunnels", false, "/wg/vpn_wg_tunnels.php"),
    array("Peers", true, "/wg/vpn_wg_peers.php"),
    array("Settings", false, "/wg/vpn_wg_settings.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
display_top_tabs($tab_array, false, 'pills');
?>

<?php if ($err_msg): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($err_msg)?></div>
<?php endif; ?>

<form class="form-horizontal" method="post" action="vpn_wg_peers_edit.php">
    <input type="hidden" name="act" value="save" />
    <input type="hidden" name="old_publickey" value="<?=htmlspecialchars($current_peer['public_key'] ?? '')?>" />

    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title"><?=($is_edit ? "Edit WireGuard Peer" : "Add WireGuard Peer")?></h2>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label">Enable</label>
                <div class="checkbox col-sm-10">
                    <label><input name="enabled" id="enabled" type="checkbox" value="yes" checked> Enable Peer</label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="tun"><span class="element-required">*</span> Tunnel</label>
                <div class="col-sm-4">
                    <select class="form-control" name="tun" id="tun">
                        <?php foreach ($wg['tunnels'] ?? [] as $t): ?>
                            <option value="<?=htmlspecialchars($t['name'])?>" <?=$t['name']===$tun_name?'selected':''?>>
                                <?=htmlspecialchars($t['name'])?> (Port: <?=htmlspecialchars($t['listen_port'] ?? 51820)?>)
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($wg['tunnels'])): ?>
                            <option value="wg0" selected>wg0 (Default)</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="descr">Description</label>
                <div class="col-sm-10">
                    <input class="form-control" name="descr" id="descr" type="text" value="<?=htmlspecialchars($current_peer['description'] ?? '')?>" placeholder="e.g. Smartphone Bapak, Laptop Admin, MikroTik CHR VPS" />
                    <span class="help-block">Deskripsi peer untuk referensi administratif (misal: Smartphone, MikroTik CHR, Laptop).</span>
                </div>
            </div>

            <!-- Client Generator Helper Banner -->
            <div class="well well-sm" style="margin-left: 15px; margin-right: 15px; background: #f8fafc; border: 1px solid #e2e8f0;">
                <div class="row">
                    <div class="col-sm-9">
                        <strong style="color: #1e293b;"><i class="fa-solid fa-mobile-screen"></i> Client Setup Helper (Smartphone / Laptop / Device):</strong>
                        <p style="margin: 3px 0 0; font-size: 0.85em; color: #64748b;">
                            Jika peer ini adalah perangkat client (HP/Laptop), klik tombol di samping untuk otomatis menghasilkan pasangan kunci client. Kunci pribadi client akan disimpan untuk membuat QR code siap scan.
                        </p>
                    </div>
                    <div class="col-sm-3 text-right" style="padding-top: 5px;">
                        <button class="btn btn-info btn-sm" type="button" id="btn-genclient"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Client Keys</button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="client_private_key">Client Private Key</label>
                <div class="col-sm-10">
                    <input class="form-control" name="client_private_key" id="client_private_key" type="password" value="<?=htmlspecialchars($current_peer['client_private_key'] ?? '')?>" placeholder="Optional - Used to generate scannable Client QR Code" autocomplete="new-password" />
                    <span class="help-block">Kunci pribadi client (hanya diperlukan jika ingin router menghasilkan QR Code lengkap untuk scan langsung di HP).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="client_dns">Client DNS</label>
                <div class="col-sm-4">
                    <input class="form-control" name="client_dns" id="client_dns" type="text" value="<?=htmlspecialchars($current_peer['client_dns'] ?? '1.1.1.1, 8.8.8.8')?>" placeholder="1.1.1.1, 8.8.8.8" />
                    <span class="help-block">DNS server yang disuntikkan ke konfigurasi client.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="publickey"><span class="element-required">*</span> Peer Public Key</label>
                <div class="col-sm-10">
                    <input class="form-control" name="publickey" id="publickey" type="text" value="<?=htmlspecialchars($current_peer['public_key'] ?? '')?>" required placeholder="WireGuard Public Key of Peer" />
                    <span class="help-block">Kunci publik dari peer remote (otomatis terisi jika menggunakan Generate Client Keys di atas).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="allowed_ips"><span class="element-required">*</span> Allowed IPs</label>
                <div class="col-sm-6">
                    <input class="form-control" name="allowed_ips" id="allowed_ips" type="text" value="<?=htmlspecialchars($current_peer['allowed_ips'] ?? '10.10.99.10/32')?>" required placeholder="e.g. 10.10.99.10/32 or 0.0.0.0/0" />
                    <span class="help-block">IP internal yang diizinkan untuk peer ini (misal: <code>10.10.99.10/32</code> untuk single client HP/Laptop, atau <code>0.0.0.0/0</code> jika router adalah client uplink).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Dynamic Endpoint</label>
                <div class="checkbox col-sm-10">
                    <label><input name="dynamic" id="dynamic" type="checkbox" value="yes" <?=$is_dynamic?'checked':''?>> Dynamic (Centang jika peer bertindak sebagai client yang menyambung secara dinamis ke router ini)</label>
                </div>
            </div>

            <div class="form-group endpoint-group">
                <label class="col-sm-2 control-label" for="endpoint">Endpoint Host &amp; Port</label>
                <div class="col-sm-6">
                    <input class="form-control" name="endpoint" id="endpoint" type="text" value="<?=htmlspecialchars($ep_host)?>" placeholder="e.g. 103.93.162.168 or vpn.example.com" />
                    <span class="help-block">Isi IP/Host publik jika router MitraNet bertindak sebagai <strong>Client</strong> yang menyambung ke server eksternal.</span>
                </div>
                <div class="col-sm-2">
                    <input class="form-control" name="port" id="port" type="text" value="<?=htmlspecialchars($ep_port)?>" placeholder="51820" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="persistentkeepalive">Keep Alive</label>
                <div class="col-sm-3">
                    <input class="form-control" name="persistentkeepalive" id="persistentkeepalive" type="text" value="<?=htmlspecialchars($current_peer['persistent_keepalive'] ?? '25')?>" placeholder="25" />
                    <span class="help-block">Interval Keep Alive dalam detik (direkomendasikan 25s untuk NAT traversal perangkat mobile).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="presharedkey">Pre-shared Key (PSK)</label>
                <div class="col-sm-8">
                    <input class="form-control" name="presharedkey" id="presharedkey" type="password" value="<?=htmlspecialchars($current_peer['preshared_key'] ?? '')?>" placeholder="Optional Pre-shared Key" autocomplete="new-password" />
                    <span class="help-block">Kunci rahasia bersama opsional untuk keamanan post-quantum tambahan.</span>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-default btn-sm" type="button" id="btn-genpsk"><i class="fa-solid fa-key"></i> Generate PSK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2" style="margin-bottom: 20px;">
        <button class="btn btn-primary" type="submit" value="Save"><i class="fa-solid fa-save icon-embed-btn"></i> <?=($is_edit ? "Update Peer" : "Save Peer")?></button>
        <a href="vpn_wg_peers.php" class="btn btn-default"><i class="fa-solid fa-times"></i> Cancel</a>
    </div>
</form>

<script type="text/javascript">
$(document).ready(function() {
    function toggleEndpoint() {
        if ($('#dynamic').is(':checked')) {
            $('.endpoint-group').hide();
        } else {
            $('.endpoint-group').show();
        }
    }
    $('#dynamic').change(toggleEndpoint);
    toggleEndpoint();

    $('#btn-genpsk').click(function(e) {
        e.preventDefault();
        $.getJSON('vpn_wg_peers_edit.php?ajax=genpsk', function(res) {
            if (res && res.success) {
                $('#presharedkey').val(res.preshared_key);
            }
        });
    });

    $('#btn-genclient').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Generating...');
        $.getJSON('vpn_wg_peers_edit.php?ajax=genclientkeys', function(res) {
            btn.prop('disabled', false).html('<i class="fa-solid fa-wand-magic-sparkles"></i> Generate Client Keys');
            if (res && res.success) {
                $('#client_private_key').val(res.private_key);
                $('#publickey').val(res.public_key);
                $('#dynamic').prop('checked', true);
                toggleEndpoint();
            } else {
                alert('Gagal menghasilkan kunci client.');
            }
        });
    });
});
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
