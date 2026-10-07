<?php
/*
 * vpn_wg_tunnels_edit.php - MitraNet WireGuard Edit / Add Tunnel
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = "";
$err_msg = "";
$tun_name = $_GET['tun'] ?? 'wg0';

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'save') {
    $tun_name = trim($_POST['name'] ?? 'wg0');
    $listenport = trim($_POST['listenport'] ?? '51820');
    $privatekey = trim($_POST['privatekey'] ?? '');
    $descr = trim($_POST['descr'] ?? '');
    $address = trim($_POST['address'] ?? '10.10.99.1/24');

    if (empty($privatekey)) {
        $err_msg = "Private key is required to configure WireGuard.";
    } else {
        $res = MitraNetApi::saveWireGuardTunnel($tun_name, $address, $listenport, $privatekey, $descr);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            header('Location: /wg/vpn_wg_tunnels.php?savemsg=' . urlencode("Tunnel {$tun_name} saved successfully."));
            exit;
        } else {
            $err_msg = $res['data']['error'] ?? 'Failed to save tunnel configuration.';
        }
    }
}

// Read current tunnel info
$wg = MitraNetApi::getWireGuard();
$current_tun = null;
foreach ($wg['tunnels'] ?? [] as $t) {
    if ($t['name'] === $tun_name) {
        $current_tun = $t;
        break;
    }
}

$pgtitle = array("VPN", "WireGuard", "Tunnels", "Edit");
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
?>

<?php if ($err_msg): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($err_msg)?></div>
<?php endif; ?>

<form class="form-horizontal" method="post" action="vpn_wg_tunnels_edit.php?tun=<?=htmlspecialchars($tun_name)?>">
    <input type="hidden" name="act" value="save" />
    <input type="hidden" name="name" value="<?=htmlspecialchars($tun_name)?>" />

    <div class="panel panel-default">
        <div class="panel-heading">
            <h2 class="panel-title">Tunnel Configuration (<?=htmlspecialchars($tun_name)?>)</h2>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-sm-2 control-label">Enable</label>
                <div class="checkbox col-sm-10">
                    <label><input name="enabled" id="enabled" type="checkbox" value="yes" checked disabled> Enable Tunnel</label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="descr">Description</label>
                <div class="col-sm-10">
                    <input class="form-control" name="descr" id="descr" type="text" value="<?=htmlspecialchars($current_tun ? 'WireGuard Server & Client Tunnel' : 'Tunnel to VPS / Remote Peers')?>" placeholder="Description" />
                    <span class="help-block">Description for administrative reference (e.g. Server mode or Client uplink).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="address"><span class="element-required">*</span> Interface Address (CIDR)</label>
                <div class="col-sm-4">
                    <input class="form-control" name="address" id="address" type="text" value="10.10.99.1/24" required placeholder="e.g. 10.10.99.1/24" />
                    <span class="help-block">IP address assigned to this WireGuard interface.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="listenport"><span class="element-required">*</span> Listen Port</label>
                <div class="col-sm-4">
                    <input class="form-control" name="listenport" id="listenport" type="text" value="<?=htmlspecialchars($current_tun['listen_port'] ?? '51820')?>" required placeholder="51820" />
                    <span class="help-block">Port used by this tunnel for inbound/outbound communication.</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label" for="privatekey"><span class="element-required">*</span> Interface Keys</label>
                <div class="col-sm-4">
                    <input class="form-control" name="privatekey" id="privatekey" type="password" value="" placeholder="Private Key" autocomplete="new-password" required />
                    <span class="help-block">Private key for this tunnel. (Required)</span>
                </div>
                <div class="col-sm-4">
                    <input class="form-control" name="publickey" id="publickey" type="text" value="<?=htmlspecialchars($current_tun['public_key'] ?? '')?>" readonly placeholder="Public Key" />
                    <span class="help-block">Public key for this tunnel. Share this with your remote peers.</span>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-primary btn-sm" type="button" id="btn-genkeys"><i class="fa-solid fa-key"> </i> Generate Keys</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-10 col-sm-offset-2" style="margin-bottom: 20px;">
        <button class="btn btn-primary" type="submit" value="Save"><i class="fa-solid fa-save icon-embed-btn"></i> Save Tunnel</button>
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
