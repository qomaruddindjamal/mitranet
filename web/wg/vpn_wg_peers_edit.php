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

// Handle AJAX PSK Generation
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'genpsk') {
    header('Content-Type: application/json');
    $keys = MitraNetApi::generateWireGuardKeys();
    if (!empty($keys['data']['success'])) {
        echo json_encode(['success' => true, 'preshared_key' => $keys['data']['private_key']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to generate PSK']);
    }
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save') {
    $tunnel = trim($_POST['tun'] ?? 'wg0');
    $descr = trim($_POST['descr'] ?? 'WireGuard Peer');
    $pubkey = trim($_POST['publickey'] ?? '');
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
        $err_msg = "Public Key and Allowed IPs are required for a WireGuard peer.";
    } else {
        $res = MitraNetApi::saveWireGuardPeer($tunnel, $pubkey, $allowed_ips, $full_endpoint, $keepalive, $preshared, $descr);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            header('Location: /wg/vpn_wg_peers.php?savemsg=' . urlencode("Peer saved successfully."));
            exit;
        } else {
            $err_msg = $res['data']['error'] ?? 'Failed to save peer.';
        }
    }
}

// Read current peer info if editing
$wg = MitraNetApi::getWireGuard();
$current_peer = null;
foreach ($wg['tunnels'] ?? [] as $t) {
    foreach ($t['peers'] ?? [] as $p) {
        if ($p['public_key'] === $peer_pubkey) {
            $current_peer = $p;
            $tun_name = $t['name'];
            break 2;
        }
    }
}

$pgtitle = array("VPN", "WireGuard", "Peers", "Edit");
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

    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">Peer Configuration</h2>
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
                                <?=htmlspecialchars($t['name'])?> (Port: <?=htmlspecialchars($t['listen_port'])?>)
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
                    <input class="form-control" name="descr" id="descr" type="text" value="<?=htmlspecialchars($current_peer ? 'Remote Peer' : 'Client / VPS Peer')?>" placeholder="Description" />
                    <span class="help-block">Deskripsi peer untuk referensi administratif (misal: Smartphone, MikroTik CHR, Laptop).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Dynamic Endpoint</label>
                <div class="checkbox col-sm-10">
                    <label><input name="dynamic" id="dynamic" type="checkbox" value="yes" <?=(!$current_peer || empty($current_peer['endpoint']) || $current_peer['endpoint']==='(none)' || $current_peer['endpoint']==='Dynamic')?'checked':''?>> Dynamic (Centang jika peer bertindak sebagai client yang tersambung ke router ini)</label>
                </div>
            </div>

            <div class="form-group endpoint-group">
                <label class="col-sm-2 control-label" for="endpoint">Endpoint Host &amp; Port</label>
                <div class="col-sm-6">
                    <input class="form-control" name="endpoint" id="endpoint" type="text" value="" placeholder="e.g. 103.93.162.168 or vpn.example.com" />
                    <span class="help-block">Isi IP/Host publik jika router MitraNet bertindak sebagai <strong>Client</strong> yang menyambung ke server eksternal.</span>
                </div>
                <div class="col-sm-2">
                    <input class="form-control" name="port" id="port" type="text" value="51820" placeholder="Port (51820)" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="persistentkeepalive">Keep Alive</label>
                <div class="col-sm-3">
                    <input class="form-control" name="persistentkeepalive" id="persistentkeepalive" type="text" value="25" placeholder="25" />
                    <span class="help-block">Interval Keep Alive dalam detik (direkomendasikan 25s untuk NAT traversal).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="publickey"><span class="element-required">*</span> Public Key</label>
                <div class="col-sm-10">
                    <input class="form-control" name="publickey" id="publickey" type="text" value="<?=htmlspecialchars($current_peer['public_key'] ?? '')?>" required placeholder="WireGuard Public Key of Peer" />
                    <span class="help-block">Kunci publik dari peer remote (Smartphone, Laptop, atau VPS eksternal).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="allowed_ips"><span class="element-required">*</span> Allowed IPs</label>
                <div class="col-sm-6">
                    <input class="form-control" name="allowed_ips" id="allowed_ips" type="text" value="<?=htmlspecialchars($current_peer['allowed_ips'] ?? '10.10.99.10/32')?>" required placeholder="e.g. 10.10.99.10/32 or 0.0.0.0/0" />
                    <span class="help-block">IP internal yang diizinkan untuk peer ini (misal: <code>10.10.99.10/32</code> untuk single client, atau <code>0.0.0.0/0</code> jika router adalah client yang merutekan seluruh traffic internet ke VPS).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="presharedkey">Pre-shared Key (PSK)</label>
                <div class="col-sm-8">
                    <input class="form-control" name="presharedkey" id="presharedkey" type="password" placeholder="Optional Pre-shared Key" autocomplete="new-password" />
                    <span class="help-block">Kunci rahasia bersama opsional untuk keamanan post-quantum tambahan.</span>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-default btn-sm" type="button" id="btn-genpsk"><i class="fa-solid fa-key"></i> Generate PSK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2" style="margin-bottom: 20px;">
        <button class="btn btn-primary" type="submit" value="Save"><i class="fa-solid fa-save icon-embed-btn"></i> Save Peer</button>
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
});
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
