<?php
/*
 * vpn_wg_peers.php - MitraNet WireGuard Peers
 * Faithful port from pfSense /wg/vpn_wg_peers.php
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

// Handle AJAX Client Config & QR
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'client_config') {
    header('Content-Type: application/json');
    $tun = trim($_REQUEST['tun'] ?? 'wg0');
    $peer = trim($_REQUEST['peer'] ?? '');
    $endpoint = trim($_REQUEST['endpoint'] ?? '');
    $res = MitraNetApi::getWireGuardClientConfig($tun, $peer, $endpoint);
    echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API Error']);
    exit;
}

$savemsg = $_GET['savemsg'] ?? '';
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['act']) && $_GET['act'] === 'delete')) {
    if (isset($_REQUEST['act']) && $_REQUEST['act'] === 'delete') {
        $tun           = trim($_REQUEST['tun'] ?? 'wg0');
        // peer param carries the actual public key (already URL-decoded by PHP)
        $target_pubkey = trim($_REQUEST['peer'] ?? '');

        // Legacy numeric index fallback
        if (ctype_digit($target_pubkey)) {
            $wg_cur = MitraNetApi::getWireGuard();
            foreach ($wg_cur['tunnels'] ?? [] as $t) {
                foreach ($t['peers'] ?? [] as $idx => $p) {
                    if ((string)$idx === $target_pubkey) {
                        $target_pubkey = $p['public_key'];
                        $tun = $t['name'] ?? 'wg0';
                        break 2;
                    }
                }
            }
        }

        if ($target_pubkey) {
            $res = MitraNetApi::deleteWireGuardPeer($tun, $target_pubkey);
            if (!empty($res['data']['success'])) {
                header("Location: /wg/vpn_wg_peers.php?savemsg=" . urlencode("WireGuard peer berhasil dihapus."));
                exit;
            } else {
                $err_msg = $res['data']['error'] ?? "Gagal menghapus WireGuard peer.";
            }
        } else {
            $err_msg = "Public key peer tidak valid.";
        }
    }
}

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

$all_peers = [];
foreach ($tunnels as $t) {
    foreach ($t['peers'] ?? [] as $idx => $p) {
        $p['tunnel_name'] = $t['name'];
        $p['idx'] = $idx;
        $all_peers[] = $p;
    }
}

$pgtitle = array("VPN", "WireGuard", "Peers");
$pglinks = array("", "/wg/vpn_wg_peers.php", "@self");
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

<?php if ($savemsg): ?>
<div class="alert alert-success clearfix" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<div class="pull-left"><i class="fa-solid fa-check"></i> <?=htmlspecialchars($savemsg)?></div>
</div>
<?php endif; ?>

<?php if ($err_msg): ?>
<div class="alert alert-danger clearfix" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<div class="pull-left"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($err_msg)?></div>
</div>
<?php endif; ?>

<?php if (!$is_running): ?>
<div class="alert alert-warning" role="alert">
	<i class="fa-solid fa-triangle-exclamation"></i>
	<strong>WireGuard tidak aktif.</strong> Layanan wg-quick@wg0 sedang tidak berjalan. Data peer berasal dari konfigurasi file.
</div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Peers</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>Description</th>
					<th>Public key</th>
					<th>Tunnel</th>
					<th>Allowed IPs</th>
					<th>Endpoint</th>
					<th>Last Handshake</th>
					<th class="col-w-140">Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php if (empty($all_peers)): ?>
				<tr><td colspan="7" class="text-center text-muted">Belum ada peer WireGuard yang dikonfigurasi. Klik "Add Peer" di bawah untuk menambah.</td></tr>
			<?php else: ?>
				<?php foreach ($all_peers as $p): ?>
				<?php $pubkey_url = urlencode($p['public_key']); ?>
				<tr ondblclick="document.location='vpn_wg_peers_edit.php?peer=<?=htmlspecialchars($pubkey_url)?>';">
					<td><strong><?=htmlspecialchars($p['description'] ?? 'Peer')?></strong></td>
					<td class="td-pubkey"
					    title="Klik untuk salin: <?=htmlspecialchars($p['public_key'])?>"
					    onclick="navigator.clipboard.writeText('<?=htmlspecialchars($p['public_key'])?>');this.style.color='green';">
						<?=htmlspecialchars(substr($p['public_key'], 0, 16))?>...
					</td>
					<td><code><?=htmlspecialchars($p['tunnel_name'] ?? 'wg0')?></code></td>
					<td><code><?=htmlspecialchars($p['allowed_ips'] ?? '—')?></code></td>
					<td><?=htmlspecialchars($p['endpoint'] ?: 'Dynamic')?></td>
					<td>
					<?php
					$ts = intval($p['latest_handshake'] ?? 0);
					if ($ts > 0) {
						$diff = time() - $ts;
						if ($diff < 180)       echo "<span class='label label-success'>" . $diff . "s ago</span>";
						elseif ($diff < 3600)  echo "<span class='label label-success'>" . floor($diff/60) . "m ago</span>";
						elseif ($diff < 86400) echo "<span class='label label-warning'>" . floor($diff/3600) . "h ago</span>";
						else                   echo "<span class='label label-danger'>" . floor($diff/86400) . "d ago</span>";
					} else {
						echo "<span class='text-muted'>Never</span>";
					}
					?>
					</td>
					<td class="td-nowrap">
						<button class="btn btn-xs btn-default btn-show-ros"
						        type="button"
						        data-pubkey="<?=htmlspecialchars($p['public_key'])?>"
						        data-allowed="<?=htmlspecialchars($p['allowed_ips'])?>"
						        data-endpoint="<?=htmlspecialchars($p['endpoint'])?>"
						        data-descr="<?=htmlspecialchars($p['description'] ?? 'Peer')?>"
						        data-keepalive="<?=htmlspecialchars($p['persistent_keepalive'] ?? '25')?>"
						        title="Copy MikroTik RouterOS v7 WireGuard Peer Command">
							<i class="fa-solid fa-terminal"></i> ROS
						</button>
						<button class="btn btn-xs btn-info btn-show-qr" 
						        type="button" 
						        data-peer="<?=htmlspecialchars($p['public_key'])?>" 
						        data-tun="<?=htmlspecialchars($p['tunnel_name'] ?? 'wg0')?>" 
						        data-descr="<?=htmlspecialchars($p['description'] ?? 'Peer')?>" 
						        title="Tampilkan QR Code &amp; Config Client">
							<i class="fa-solid fa-qrcode"></i> QR
						</button>
						<a class="btn btn-xs btn-default" href="/wg/vpn_wg_peers_edit.php?peer=<?=$pubkey_url?>&amp;tun=<?=urlencode($p['tunnel_name'] ?? 'wg0')?>" title="Edit Peer"><i class="fa-solid fa-pencil"></i></a>
						<a class="btn btn-xs btn-danger"
						   href="?act=delete&amp;peer=<?=$pubkey_url?>&amp;tun=<?=htmlspecialchars(urlencode($p['tunnel_name'] ?? 'wg0'))?>"
						   onclick="return confirm('Hapus peer WireGuard ini?\n\n<?=htmlspecialchars(substr($p['public_key'],0,32))?>...');"
						   title="Delete Peer"><i class="fa-solid fa-trash-can"></i></a>
					</td>
				</tr>
				<?php endforeach; ?>

			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<nav class="action-buttons">
    <a href="/wg/vpn_wg_peers_edit.php" class="btn btn-success btn-sm">
        <i class="fa-solid fa-plus icon-embed-btn"></i> Add Peer
    </a>
</nav>

<!-- Modal QR Code & Client Configuration -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="qrModalLabel"><i class="fa-solid fa-qrcode"></i> WireGuard Client Configuration &amp; QR Code</h4>
      </div>
      <div class="modal-body">
        <div id="qrLoading" class="qr-loading-box">
            <i class="fa-solid fa-spinner fa-spin fa-3x text-primary"></i>
            <p>Generating client configuration and QR code...</p>
        </div>

        <div id="qrContent" class="d-none">
            <div class="row">
                <div class="col-sm-6 text-center qr-col-mb">
                    <div id="qrSvgContainer" class="qr-svg-box">
                        <!-- SVG QR rendered here -->
                    </div>
                    <p class="text-muted qr-caption">
                        <i class="fa-solid fa-mobile-screen"></i> Scan menggunakan aplikasi WireGuard (iOS / Android)
                    </p>
                </div>
                <div class="col-sm-6">
                    <div class="form-group qr-form-group">
                        <label class="qr-config-label">Server Endpoint (IP / Host Publik)</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="qrEndpointInput" placeholder="e.g. 103.93.162.168 or vpn.domain.com" />
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" id="btnUpdateEndpoint" title="Update QR Endpoint"><i class="fa-solid fa-rotate"></i></button>
                            </span>
                        </div>
                        <span class="help-block text-muted-sm">Ubah jika router berada di balik NAT publik atau DDNS.</span>
                    </div>

                    <div id="privateKeyWarning" class="alert alert-warning qr-private-warning d-none">
                        <i class="fa-solid fa-triangle-exclamation"></i> <strong>Perhatian:</strong> Private key client tidak tersimpan di router. Silakan masukkan private key client pada konfigurasi di bawah jika diperlukan.
                    </div>
                </div>
            </div>

            <div class="form-group qr-mt">
                <label class="qr-config-label">File Konfigurasi Client (<code>wg-client.conf</code>):</label>
                <textarea class="form-control qr-config-textarea" id="qrConfigText" rows="7" readonly></textarea>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default btn-sm" id="btnCopyConfig"><i class="fa-solid fa-copy"></i> Copy Config</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnDownloadConfig"><i class="fa-solid fa-download"></i> Download .conf</button>
        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var curTun = 'wg0';
    var curPeer = '';
    var curDescr = 'peer';

    $('.btn-show-qr').click(function(e) {
        e.preventDefault();
        curPeer = $(this).data('peer');
        curTun = $(this).data('tun');
        curDescr = $(this).data('descr') || 'peer';
        
        $('#qrModalLabel').html('<i class="fa-solid fa-qrcode"></i> QR Code &amp; Client Config: <strong>' + curDescr + '</strong>');
        $('#qrModal').modal('show');
        loadClientConfig('');
    });

    function loadClientConfig(endpointOverride) {
        $('#qrLoading').show();
        $('#qrContent').hide();

        var url = 'vpn_wg_peers.php?ajax=client_config&tun=' + encodeURIComponent(curTun) + '&peer=' + encodeURIComponent(curPeer);
        if (endpointOverride) {
            url += '&endpoint=' + encodeURIComponent(endpointOverride);
        }

        $.getJSON(url, function(res) {
            $('#qrLoading').hide();
            if (res && res.success) {
                $('#qrContent').show();
                if (res.qr_svg) {
                    $('#qrSvgContainer').html(res.qr_svg);
                    $('#qrSvgContainer svg').css({width: '100%', height: '100%'});
                } else {
                    $('#qrSvgContainer').html('<div class="text-danger qr-unavailable">QR code tidak tersedia</div>');
                }
                $('#qrConfigText').val(res.config);
                if (!endpointOverride && res.server_endpoint) {
                    var parts = res.server_endpoint.split(':');
                    $('#qrEndpointInput').val(parts[0]);
                }
                if (!res.has_private_key) {
                    $('#privateKeyWarning').show();
                } else {
                    $('#privateKeyWarning').hide();
                }
            } else {
                alert('Gagal mengambil konfigurasi client: ' + (res.error || 'Unknown error'));
                $('#qrModal').modal('hide');
            }
        }).fail(function() {
            $('#qrLoading').hide();
            alert('Gagal menghubungi backend API.');
            $('#qrModal').modal('hide');
        });
    }

    $('#btnUpdateEndpoint').click(function() {
        var ep = $('#qrEndpointInput').val().trim();
        loadClientConfig(ep);
    });

    $('#btnCopyConfig').click(function() {
        var txt = $('#qrConfigText').val();
        navigator.clipboard.writeText(txt).then(function() {
            var btn = $('#btnCopyConfig');
            var oldHtml = btn.html();
            btn.html('<i class="fa-solid fa-check text-success"></i> Copied!');
            setTimeout(function() { btn.html(oldHtml); }, 2000);
        });
    });

    $('#btnDownloadConfig').click(function() {
        var txt = $('#qrConfigText').val();
        var safeName = curDescr.toLowerCase().replace(/[^a-z0-9_-]/g, '_');
        var filename = safeName + '.conf';
        var blob = new Blob([txt], {type: 'text/plain;charset=utf-8'});
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });

    $('.btn-show-ros').click(function(e) {
        e.preventDefault();
        var pubkey = $(this).data('pubkey') || '';
        var allowed = $(this).data('allowed') || '';
        var ep = $(this).data('endpoint') || '';
        var descr = $(this).data('descr') || 'WireGuard-Peer';
        var keepalive = $(this).data('keepalive') || '25';

        // Format MikroTik RouterOS v7 syntax
        var rosCmd = '/interface wireguard peers add interface=wg0 public-key="' + pubkey + '" allowed-address=' + allowed;
        if (ep && ep !== 'Dynamic' && ep !== '(none)') {
            var epParts = ep.split(':');
            rosCmd += ' endpoint-address=' + epParts[0];
            if (epParts[1]) {
                rosCmd += ' endpoint-port=' + epParts[1];
            }
        }
        if (keepalive && parseInt(keepalive) > 0) {
            rosCmd += ' persistent-keepalive=' + keepalive + 's';
        }
        rosCmd += ' comment="' + descr.replace(/"/g, '') + '"';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '<i class="fa-solid fa-terminal"></i> MikroTik RouterOS v7 Command',
                html: '<p class="text-left text-muted small">Jalankan perintah ini di Terminal MikroTik untuk menambahkan peer:</p>' +
                      '<textarea id="swalRosCmd" class="form-control" rows="4" style="font-family: monospace; font-size: 12px; background: #222; color: #a6e22e; resize: vertical;" readonly>' + rosCmd + '</textarea>',
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-copy"></i> Copy Command',
                cancelButtonText: 'Tutup',
                preConfirm: function() {
                    var copyText = document.getElementById('swalRosCmd').value;
                    navigator.clipboard.writeText(copyText);
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    if (typeof MitraNet !== 'undefined' && MitraNet.toast) {
                        MitraNet.toast('success', 'Perintah MikroTik berhasil disalin ke clipboard');
                    } else {
                        alert('Perintah berhasil disalin ke clipboard!');
                    }
                }
            });
        } else {
            prompt('Salin perintah MikroTik RouterOS v7 berikut:', rosCmd);
        }
    });
});
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
