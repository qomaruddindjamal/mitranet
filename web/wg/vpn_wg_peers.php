<?php
/*
 * vpn_wg_peers.php - MitraNet WireGuard Peers
 * WinBox Esthetic & SweetAlert2 UX Standards
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

// Handle AJAX Actions (toggle, comment, delete)
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1') {
    header('Content-Type: application/json');
    $act = $_REQUEST['act'] ?? '';
    $tun = trim($_REQUEST['tun'] ?? 'wg0');
    $pubkey = trim($_REQUEST['pubkey'] ?? '');

    if ($act === 'toggle') {
        $enable = !empty($_REQUEST['enable']);
        $res = MitraNetApi::toggleWireGuardPeer($tun, $pubkey, $enable);
        echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API Error']);
        exit;
    }
    if ($act === 'comment') {
        $comment = trim($_REQUEST['comment'] ?? '');
        $res = MitraNetApi::commentWireGuardPeer($tun, $pubkey, $comment);
        echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API Error']);
        exit;
    }
    if ($act === 'delete') {
        $res = MitraNetApi::deleteWireGuardPeer($tun, $pubkey);
        echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API Error']);
        exit;
    }
}

$savemsg = $_GET['savemsg'] ?? '';
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['act']) && $_GET['act'] === 'delete')) {
    if (isset($_REQUEST['act']) && $_REQUEST['act'] === 'delete') {
        $tun           = trim($_REQUEST['tun'] ?? 'wg0');
        $target_pubkey = trim($_REQUEST['peer'] ?? '');

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

$badge_title = "WireGuard";
$pgtitle = array("VPN", "WireGuard", "Secrets");
$pglinks = array("", "/wg/vpn_wg_peers.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$tab_array = array(
    array("Interfaces", false, "/wg/vpn_wg_tunnels.php"),
    array("Secrets", true, "/wg/vpn_wg_peers.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">
<?php display_top_tabs($tab_array, false, 'tabs'); ?>
		
		<!-- TOOLBAR EXACT SCREENSHOT -->
		<div class="mitranet-toolbar">
			<div class="mitranet-toolbar-left">
				<a href="/wg/vpn_wg_peers_edit.php" class="mitranet-btn" id="btn-peer-new" title="Add New Secret / Peer">
					<i class="fa-regular fa-square-plus"></i> New
				</a>
				<button type="button" class="mitranet-btn" id="btn-peer-enable" disabled onclick="toggleSelectedPeer(true)" title="Enable Selected Peer">
					<i class="fa-solid fa-play text-muted"></i> Enable
				</button>
				<button type="button" class="mitranet-btn" id="btn-peer-disable" disabled onclick="toggleSelectedPeer(false)" title="Disable Selected Peer">
					<i class="fa-solid fa-pause text-muted"></i> Disable
				</button>
				<button type="button" class="mitranet-btn" id="btn-peer-remove" disabled onclick="removeSelectedPeer()" title="Remove Selected Peer">
					<i class="fa-solid fa-xmark text-muted"></i> Remove
				</button>
				<button type="button" class="mitranet-btn" id="btn-peer-comment" disabled onclick="commentSelectedPeer()" title="Set Comment">
					<i class="fa-regular fa-comment text-muted"></i> Comment
				</button>
				<button type="button" class="mitranet-btn" id="btn-peer-edit" disabled onclick="editSelectedPeer()" title="Edit Selected Peer (atau double-click baris)">
					<i class="fa-solid fa-pencil text-muted"></i> Edit
				</button>
				<button type="button" class="mitranet-btn" id="btn-peer-qr" disabled onclick="showSelectedPeerQR()" title="View QR Code &amp; Config">
					<i class="fa-solid fa-qrcode text-muted"></i> QR
				</button>
				<button type="button" class="mitranet-btn" id="btn-peer-ros" disabled onclick="showSelectedPeerROS()" title="Generate MikroTik ROS v7 Command">
					<i class="fa-solid fa-terminal text-muted"></i> ROS
				</button>
			</div>
			<div class="mitranet-toolbar-right">
				<div class="mitranet-search-wrapper">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="text" id="grid-search" placeholder="Find peer..." onkeyup="filterPeerGrid(this.value)">
				</div>
				<button type="button" class="mitranet-btn" onclick="$('#grid-search').focus()" title="Filter">
					<i class="fa-solid fa-filter text-muted"></i> Filter
				</button>
				<button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh">
					<i class="fa-solid fa-arrows-rotate text-muted"></i>
				</button>
			</div>
		</div>

		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="peer-grid-table">
				<thead>
					<tr>
						<th class="text-center col-flag" style="width: 28px;"><i class="fa-regular fa-flag text-muted"></i></th>
						<th>Name <i class="fa-solid fa-caret-up"></i></th>
						<th>Interface</th>
						<th>Public Key</th>
						<th>Endpoint</th>
						<th>Endpoint Port</th>
						<th>Allowed Address</th>
						<th>Persistent Keepalive</th>
						<th>Current Endpoint</th>
						<th>Current Endpoint Port</th>
						<th>Rx</th>
						<th>Tx</th>
						<th class="text-center col-menu" style="width: 24px;"><i class="fa-solid fa-bars text-muted"></i></th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($all_peers)): ?>
					<tr><td colspan="13" class="text-center text-muted p-20">Belum ada peer WireGuard yang dikonfigurasi. Klik tombol "New" di toolbar untuk menambah.</td></tr>
				<?php else: ?>
					<?php foreach ($all_peers as $p): ?>
					<?php 
						$pubkey = $p['public_key'];
						$pubkey_url = urlencode($pubkey);
						$tun_name = $p['tunnel_name'] ?? 'wg0';
						$desc = $p['description'] ?? 'Peer';
						$ep = $p['endpoint'] ?? '';
						$ep_host = '—';
						$ep_port = '—';
						if ($ep && $ep !== '(none)' && $ep !== 'Dynamic') {
							$parts = explode(':', $ep);
							$ep_host = $parts[0] ?? '—';
							$ep_port = $parts[1] ?? '—';
						}
						$keepalive = !empty($p['persistent_keepalive']) ? $p['persistent_keepalive'] . 's' : '—';
						$rx_bytes = floatval($p['transfer_rx'] ?? 0);
						$tx_bytes = floatval($p['transfer_tx'] ?? 0);
						$rx_fmt = $rx_bytes > 1048576 ? number_format($rx_bytes / 1048576, 2) . ' MiB' : number_format($rx_bytes / 1024, 2) . ' KiB';
						$tx_fmt = $tx_bytes > 1048576 ? number_format($tx_bytes / 1048576, 2) . ' MiB' : number_format($tx_bytes / 1024, 2) . ' KiB';
					?>
					<tr data-pubkey="<?=htmlspecialchars($pubkey)?>"
					    data-tun="<?=htmlspecialchars($tun_name)?>"
					    data-name="<?=htmlspecialchars($desc)?>"
					    data-allowed="<?=htmlspecialchars($p['allowed_ips'] ?? '')?>"
					    data-endpoint="<?=htmlspecialchars($ep)?>"
					    data-keepalive="<?=htmlspecialchars($p['persistent_keepalive'] ?? '25')?>"
					    onclick="selectPeerRow(this)"
					    ondblclick="document.location='/wg/vpn_wg_peers_edit.php?peer=<?=$pubkey_url?>&amp;tun=<?=urlencode($tun_name)?>';">
						<td class="text-center col-flag-cell"><i class="fa-regular fa-flag text-muted"></i></td>
						<td><strong><?=htmlspecialchars($desc)?></strong></td>
						<td><code><?=htmlspecialchars($tun_name)?></code></td>
						<td class="td-pubkey"
						    title="Klik untuk salin: <?=htmlspecialchars($pubkey)?>"
						    onclick="navigator.clipboard.writeText('<?=htmlspecialchars($pubkey)?>'); MitraNet.toast('Public key disalin ke clipboard', 'success'); event.stopPropagation();">
							<?=htmlspecialchars(substr($pubkey, 0, 16))?>...
						</td>
						<td><?=htmlspecialchars($ep_host)?></td>
						<td><?=htmlspecialchars($ep_port)?></td>
						<td><code><?=htmlspecialchars($p['allowed_ips'] ?? '—')?></code></td>
						<td><?=htmlspecialchars($keepalive)?></td>
						<td><?=htmlspecialchars($ep_host)?></td>
						<td><?=htmlspecialchars($ep_port)?></td>
						<td><span class="label label-info"><?=$rx_fmt?></span></td>
						<td><span class="label label-info"><?=$tx_fmt?></span></td>
						<td class="text-center col-menu"></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($all_peers)?> peers</span>
			</div>
			<div>
				<span class="text-muted">WireGuard Subsystem</span>
			</div>
		</div>

	</div><!-- /.mitranet-window -->
</div><!-- /.mitranet-page-container -->

<!-- Modal QR Code & Client Configuration -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="qrModalLabel"><i class="fa-solid fa-qrcode"></i> WireGuard Client Configuration &amp; QR Code</h4>
      </div>
      <div class="modal-body">
        <div id="qrLoading" class="text-center p-20">
            <i class="fa-solid fa-spinner fa-spin fa-3x text-primary"></i>
            <p style="margin-top: 10px;">Generating client configuration and QR code...</p>
        </div>

        <div id="qrContent" class="d-none">
            <div class="row">
                <div class="col-sm-6 text-center" style="margin-bottom: 15px;">
                    <div id="qrSvgContainer" style="background: white; padding: 10px; display: inline-block; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <p class="text-muted" style="font-size: 11px; margin-top: 6px;">
                        <i class="fa-solid fa-mobile-screen"></i> Scan menggunakan aplikasi WireGuard (iOS / Android)
                    </p>
                </div>
                <div class="col-sm-6">
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 600;">Server Endpoint (IP / Host Publik)</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="qrEndpointInput" placeholder="e.g. 103.93.162.168 or vpn.domain.com" />
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" id="btnUpdateEndpoint" title="Update QR Endpoint"><i class="fa-solid fa-rotate"></i></button>
                            </span>
                        </div>
                        <span class="help-block text-muted" style="font-size: 11px;">Ubah jika router berada di balik NAT publik atau DDNS.</span>
                    </div>

                    <div id="privateKeyWarning" class="alert alert-warning d-none" style="font-size: 11px; padding: 6px 10px;">
                        <i class="fa-solid fa-triangle-exclamation"></i> <strong>Perhatian:</strong> Private key client tidak tersimpan di router. Masukkan private key pada konfigurasi di bawah jika diperlukan.
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <label style="font-size: 12px; font-weight: 600;">File Konfigurasi Client (<code>wg-client.conf</code>):</label>
                <textarea class="form-control" id="qrConfigText" rows="7" style="font-family: monospace; font-size: 11px; background: #222; color: #a6e22e; resize: vertical;" readonly></textarea>
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

<script>
var selectedPeerRowData = null;

// Row Selection Handler
function selectPeerRow(tr) {
    $('#peer-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedPeerRowData = {
        pubkey: $(tr).data('pubkey'),
        tun: $(tr).data('tun'),
        name: $(tr).data('name'),
        allowed: $(tr).data('allowed'),
        endpoint: $(tr).data('endpoint'),
        keepalive: $(tr).data('keepalive')
    };

    // Enable toolbar buttons
    $('#btn-peer-edit, #btn-peer-enable, #btn-peer-disable, #btn-peer-remove, #btn-peer-comment, #btn-peer-qr, #btn-peer-ros').prop('disabled', false);
}

// Filter Grid
function filterPeerGrid(val) {
    val = (val || '').toLowerCase();
    $('#peer-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(val) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Edit Selected Peer
function editSelectedPeer() {
    if (!selectedPeerRowData) return;
    window.location.href = '/wg/vpn_wg_peers_edit.php?peer=' + encodeURIComponent(selectedPeerRowData.pubkey) + '&tun=' + encodeURIComponent(selectedPeerRowData.tun);
}

// Toggle Selected Peer
function toggleSelectedPeer(enable) {
    if (!selectedPeerRowData) return;
    var actStr = enable ? 'Enable' : 'Disable';
    MitraNet.confirmAction({
        title: actStr + ' Peer ' + selectedPeerRowData.name + '?',
        text: 'Peer WireGuard ini akan ' + (enable ? 'diaktifkan' : 'dinonaktifkan') + '.',
        icon: enable ? 'question' : 'warning',
        confirmButtonText: 'Ya, ' + actStr
    }).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Memproses ' + actStr.toLowerCase() + ' peer...', 'info');
            $.post('/wg/vpn_wg_peers.php', {
                ajax: '1',
                act: 'toggle',
                tun: selectedPeerRowData.tun,
                pubkey: selectedPeerRowData.pubkey,
                enable: enable ? '1' : '0'
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Peer ' + selectedPeerRowData.name + ' berhasil ' + (enable ? 'diaktifkan' : 'dinonaktifkan'), 'success');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    MitraNet.toast('Gagal: ' + (res.error || 'Terjadi kesalahan sistem'), 'error');
                }
            }, 'json');
        }
    });
}

// Comment Selected Peer
function commentSelectedPeer() {
    if (!selectedPeerRowData) return;
    MitraNet.promptInput({
        title: 'Set Comment',
        inputLabel: 'Komentar / Deskripsi untuk peer ini:',
        inputValue: selectedPeerRowData.name,
        inputPlaceholder: 'e.g. Phone John / Branch Router'
    }).then(function(result) {
        if (result.isConfirmed) {
            var newComment = (result.value || '').trim();
            MitraNet.toast('Menyimpan komentar...', 'info');
            $.post('/wg/vpn_wg_peers.php', {
                ajax: '1',
                act: 'comment',
                tun: selectedPeerRowData.tun,
                pubkey: selectedPeerRowData.pubkey,
                comment: newComment
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Komentar berhasil diperbarui', 'success');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    MitraNet.toast('Gagal: ' + (res.error || 'Terjadi kesalahan sistem'), 'error');
                }
            }, 'json');
        }
    });
}

// Remove Selected Peer
function removeSelectedPeer() {
    if (!selectedPeerRowData) return;
    confirmDeletePeer(selectedPeerRowData.pubkey, selectedPeerRowData.tun, selectedPeerRowData.pubkey.substr(0, 16) + '...');
}

function confirmDeletePeer(pubkey, tun, keySnippet) {
    MitraNet.confirmDelete(
        'Hapus WireGuard Peer?',
        'Peer dengan public key ' + keySnippet + ' pada tunnel ' + tun + ' akan dihapus secara permanen.'
    ).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Menghapus peer...', 'info');
            $.post('/wg/vpn_wg_peers.php', {
                ajax: '1',
                act: 'delete',
                tun: tun,
                pubkey: pubkey
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Peer berhasil dihapus', 'success');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    window.location.href = '?act=delete&peer=' + encodeURIComponent(pubkey) + '&tun=' + encodeURIComponent(tun);
                }
            }, 'json');
        }
    });
}

// Show QR Modal for Selected Peer
function showSelectedPeerQR() {
    if (!selectedPeerRowData) return;
    loadPeerQR(selectedPeerRowData.pubkey, selectedPeerRowData.tun, selectedPeerRowData.name, '');
}

function loadPeerQR(peerKey, tunName, descr, customEndpoint) {
    $('#qrModalLabel').html('<i class="fa-solid fa-qrcode"></i> Client Config: ' + descr);
    $('#qrLoading').removeClass('d-none');
    $('#qrContent').addClass('d-none');
    $('#qrModal').modal('show');

    var url = 'vpn_wg_peers.php?ajax=client_config&tun=' + encodeURIComponent(tunName) + '&peer=' + encodeURIComponent(peerKey);
    if (customEndpoint) {
        url += '&endpoint=' + encodeURIComponent(customEndpoint);
    }

    $.getJSON(url, function(data) {
        $('#qrLoading').addClass('d-none');
        if (data && data.success) {
            $('#qrContent').removeClass('d-none');
            $('#qrSvgContainer').html(data.qr_svg);
            $('#qrEndpointInput').val(data.server_endpoint || '');
            $('#qrConfigText').val(data.config_text || '');

            if (!data.client_private_key) {
                $('#privateKeyWarning').removeClass('d-none');
            } else {
                $('#privateKeyWarning').addClass('d-none');
            }
        } else {
            alert('Gagal memuat konfigurasi client: ' + (data.error || 'Unknown error'));
            $('#qrModal').modal('hide');
        }
    }).fail(function() {
        $('#qrLoading').addClass('d-none');
        alert('Terjadi kesalahan jaringan saat memuat konfigurasi.');
        $('#qrModal').modal('hide');
    });
}

$('#btnUpdateEndpoint').click(function() {
    if (!selectedPeerRowData) return;
    var ep = $('#qrEndpointInput').val().trim();
    loadPeerQR(selectedPeerRowData.pubkey, selectedPeerRowData.tun, selectedPeerRowData.name, ep);
});

$('#btnCopyConfig').click(function() {
    var text = $('#qrConfigText').val();
    navigator.clipboard.writeText(text).then(function() {
        MitraNet.toast('Konfigurasi berhasil disalin ke clipboard', 'success');
    });
});

$('#btnDownloadConfig').click(function() {
    var text = $('#qrConfigText').val();
    var blob = new Blob([text], {type: "text/plain;charset=utf-8"});
    var link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = (selectedPeerRowData ? selectedPeerRowData.name.replace(/[^a-zA-Z0-9_-]/g, '_') : 'wg-client') + ".conf";
    link.click();
});

// Show ROS MikroTik v7 Command
function showSelectedPeerROS() {
    if (!selectedPeerRowData) return;
    var p = selectedPeerRowData;
    var rosCmd = '/interface wireguard peers add interface=' + p.tun + ' public-key="' + p.pubkey + '" allowed-address=' + (p.allowed || '0.0.0.0/0');
    if (p.endpoint && p.endpoint !== '(none)' && p.endpoint !== 'Dynamic') {
        var epParts = p.endpoint.split(':');
        rosCmd += ' endpoint-address=' + epParts[0];
        if (epParts[1]) rosCmd += ' endpoint-port=' + epParts[1];
    }
    if (p.keepalive && parseInt(p.keepalive) > 0) {
        rosCmd += ' persistent-keepalive=' + p.keepalive + 's';
    }
    rosCmd += ' comment="' + p.name.replace(/"/g, '') + '"';

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
            MitraNet.toast('Perintah MikroTik berhasil disalin ke clipboard', 'success');
        }
    });
}
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
