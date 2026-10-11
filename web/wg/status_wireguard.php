<?php
/*
 * status_wireguard.php - MitraNet WireGuard Live Telemetry
 * WinBox Esthetic, Real-time Subsystem Status & Management
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/../includes/api.inc');

// AJAX Actions: client_config, toggle, delete from Status tab
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'client_config') {
    header('Content-Type: application/json');
    $tun = trim($_REQUEST['tun'] ?? 'wg0');
    $peer = trim($_REQUEST['peer'] ?? '');
    $endpoint = trim($_REQUEST['endpoint'] ?? '');
    $res = MitraNetApi::getWireGuardClientConfig($tun, $peer, $endpoint);
    echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API Error']);
    exit;
}

if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1') {
    header('Content-Type: application/json');
    $act = $_REQUEST['act'] ?? '';
    $tun = trim($_REQUEST['tun'] ?? '');

    if ($act === 'toggle') {
        $enable = !empty($_REQUEST['enable']);
        $res = MitraNetApi::toggleWireGuardTunnel($tun, $enable);
        echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API error']);
        exit;
    }

    if ($act === 'delete') {
        $res = MitraNetApi::deleteWireGuardTunnel($tun);
        echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API error']);
        exit;
    }
}

$badge_title = "WireGuard";
$pgtitle = array("Status", "WireGuard");
$pglinks = array("", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

$total_peers = 0;
foreach ($tunnels as $t) {
    $total_peers += count($t['peers'] ?? []);
}

$tab_array = array(
    array("Interfaces", false, "/wg/vpn_wg_tunnels.php"),
    array("Secrets", false, "/wg/vpn_wg_peers.php"),
    array("Status", true, "/wg/status_wireguard.php")
);
?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">
<?php display_top_tabs($tab_array, false, 'tabs'); ?>
		
		<!-- TOOLBAR (WINBOX SUB-HEADER: QR, ROS, ENABLE, DISABLE, REMOVE, REFRESH) -->
		<div class="mitranet-toolbar">
			<div class="mitranet-toolbar-left">
				<button type="button" class="mitranet-btn" id="btn-status-qr" disabled onclick="showSelectedStatusQR()" title="View QR Code &amp; Config">
					<i class="fa-solid fa-qrcode text-muted"></i> QR
				</button>
				<button type="button" class="mitranet-btn" id="btn-status-ros" disabled onclick="showSelectedStatusROS()" title="Generate MikroTik ROS v7 Command">
					<i class="fa-solid fa-terminal text-muted"></i> ROS
				</button>
				<button type="button" class="mitranet-btn" id="btn-status-enable" disabled onclick="toggleSelectedStatusTunnel(true)" title="Enable Selected Interface">
					<i class="fa-solid fa-play text-muted"></i> Enable
				</button>
				<button type="button" class="mitranet-btn" id="btn-status-disable" disabled onclick="toggleSelectedStatusTunnel(false)" title="Disable Selected Interface">
					<i class="fa-solid fa-pause text-muted"></i> Disable
				</button>
				<button type="button" class="mitranet-btn" id="btn-status-remove" disabled onclick="removeSelectedStatusTunnel()" title="Remove Selected Interface">
					<i class="fa-solid fa-xmark text-muted"></i> Remove
				</button>
			</div>
			<div class="mitranet-toolbar-right">
				<div class="mitranet-search-wrapper">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="text" id="status-search" placeholder="Find status..." onkeyup="filterStatusGrid(this.value)">
				</div>
				<button type="button" class="mitranet-btn" onclick="$('#status-search').focus()" title="Filter">
					<i class="fa-solid fa-filter text-muted"></i> Filter
				</button>
				<button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh Telemetry">
					<i class="fa-solid fa-arrows-rotate text-muted"></i>
				</button>
			</div>
		</div>

		<!-- GRID TABLE (WINBOX CLEAN DESIGN) -->
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="status-grid-table">
				<thead>
					<tr>
						<th class="text-center col-flag" style="width: 28px;"><i class="fa-regular fa-flag text-muted"></i></th>
						<th>Tunnel <i class="fa-solid fa-caret-up"></i></th>
						<th>Status</th>
						<th>Mode</th>
						<th>Description</th>
						<th>Address / CIDR</th>
						<th>Listen Port</th>
						<th>Public Key</th>
						<th>Peers</th>
						<th>Rx</th>
						<th>Tx</th>
						<th class="text-center col-menu" style="width: 24px;"><i class="fa-solid fa-bars text-muted"></i></th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($tunnels)): ?>
					<tr><td colspan="12" class="text-center text-muted p-20">Tidak ada data telemetri WireGuard. Pastikan interface sudah dibuat dan subsystem aktif.</td></tr>
				<?php else: ?>
					<?php foreach ($tunnels as $tun): ?>
					<?php 
						$t_name = $tun['name'] ?? 'wg0';
						$t_desc = $tun['description'] ?: 'WireGuard Interface';
						$t_addr = !empty($tun['address']) ? $tun['address'] : ($tun['interface'] ?? strtoupper($t_name));
						$t_mtu = !empty($tun['mtu']) ? htmlspecialchars($tun['mtu']) : '1420';
						$is_up = !empty($tun['enabled']);
						$is_srv = empty($tun['mode']) || $tun['mode'] === 'server';
						$rx_bytes = floatval($tun['transfer_rx'] ?? 0);
						$tx_bytes = floatval($tun['transfer_tx'] ?? 0);
						$rx_fmt = $rx_bytes > 1048576 ? number_format($rx_bytes / 1048576, 2) . ' MiB' : ($rx_bytes > 0 ? number_format($rx_bytes / 1024, 2) . ' KiB' : '0 B');
						$tx_fmt = $tx_bytes > 1048576 ? number_format($tx_bytes / 1048576, 2) . ' MiB' : ($tx_bytes > 0 ? number_format($tx_bytes / 1024, 2) . ' KiB' : '0 B');
						$peer_count = count($tun['peers'] ?? []);
					?>
					<!-- PARENT TUNNEL ROW -->
					<tr class="status-tunnel-row"
					    data-name="<?=htmlspecialchars($t_name)?>"
					    data-enabled="<?=$is_up ? '1' : '0'?>"
					    data-comment="<?=htmlspecialchars($t_desc)?>"
					    onclick="selectStatusRow(this, '<?=htmlspecialchars($t_name, ENT_QUOTES)?>')"
					    ondblclick="document.location='/wg/vpn_wg_tunnels.php';">
						<td class="text-center col-flag-cell">
							<?php if ($is_up): ?>
								<i class="fa-solid fa-check text-success" title="Running / Link UP"></i>
							<?php else: ?>
								<i class="fa-solid fa-minus text-muted" title="Disabled"></i>
							<?php endif; ?>
						</td>
						<td><strong><code><?=htmlspecialchars($t_name)?></code></strong></td>
						<td>
							<?php if ($is_up): ?>
								<span class="label label-success"><i class="fa-solid fa-circle-check"></i> UP</span>
							<?php else: ?>
								<span class="label label-default"><i class="fa-solid fa-circle-stop"></i> DOWN</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($is_srv): ?>
								<span class="label label-primary"><i class="fa-solid fa-server"></i> Server</span>
							<?php else: ?>
								<span class="label label-info"><i class="fa-solid fa-network-wired"></i> Client</span>
							<?php endif; ?>
						</td>
						<td class="td-comment"><?=htmlspecialchars($t_desc)?></td>
						<td><code><?=htmlspecialchars($t_addr)?></code></td>
						<td><?=htmlspecialchars($tun['listen_port'] ?? '51820')?></td>
						<td class="td-pubkey"
						    title="Klik untuk salin: <?=htmlspecialchars($tun['public_key'] ?? '')?>"
						    onclick="navigator.clipboard.writeText('<?=htmlspecialchars($tun['public_key'] ?? '')?>'); MitraNet.toast('Public key disalin ke clipboard', 'success'); event.stopPropagation();">
							<?=htmlspecialchars(substr($tun['public_key'] ?? '', 0, 16))?>...
						</td>
						<td>
							<span class="badge" style="background:#2b476c; font-weight:normal;"><?=$peer_count?> peer<?=$peer_count === 1 ? '' : 's'?></span>
						</td>
						<td><span class="label label-info"><?=$rx_fmt?></span></td>
						<td><span class="label label-info"><?=$tx_fmt?></span></td>
						<td class="text-center col-menu"></td>
					</tr>

					<!-- NESTED PEERS SUB-TABLE (CLEAN & MINIMALIST) -->
					<?php if (!empty($tun['peers'])): ?>
					<tr class="status-peers-row" data-parent="<?=htmlspecialchars($t_name)?>">
						<td colspan="12" style="background: #fdfefe; padding: 6px 15px 10px 38px; border-top: none; border-bottom: 2px solid #adc5dd;">
							<div style="background: #ffffff; border: 1px solid #d1dfed; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
								<div style="background: #eef5fb; padding: 4px 8px; font-size: 11px; font-weight: 700; color: #1e395b; border-bottom: 1px solid #d1dfed; display:flex; justify-content:space-between; align-items:center;">
									<span><i class="fa-solid fa-users text-primary"></i> Associated Peers / Secrets for <code><?=htmlspecialchars($t_name)?></code></span>
									<span class="text-muted" style="font-weight:normal; font-size:10px;">Subsystem Client Sessions</span>
								</div>
								<table class="table table-condensed table-hover" style="margin-bottom: 0; font-size: 11px;">
									<thead>
										<tr style="background: #fafcfe; color: #4a6382;">
											<th style="width: 180px;">Peer / Description</th>
											<th>Public Key</th>
											<th>Endpoint Host:Port</th>
											<th>Allowed IPs</th>
											<th>Handshake</th>
											<th>Rx</th>
											<th>Tx</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($tun['peers'] as $peer): ?>
									<?php 
										$p_rx = floatval($peer['transfer_rx'] ?? 0);
										$p_tx = floatval($peer['transfer_tx'] ?? 0);
										$p_rx_fmt = $p_rx > 1048576 ? number_format($p_rx / 1048576, 2) . ' MiB' : number_format($p_rx / 1024, 2) . ' KiB';
										$p_tx_fmt = $p_tx > 1048576 ? number_format($p_tx / 1048576, 2) . ' MiB' : number_format($p_tx / 1024, 2) . ' KiB';
										$has_hs = !empty($peer['latest_handshake']) && intval($peer['latest_handshake']) > 0;
									?>
										<tr class="status-peer-item-row"
										    style="cursor: pointer;"
										    data-pubkey="<?=htmlspecialchars($peer['public_key'])?>"
										    data-tun="<?=htmlspecialchars($t_name)?>"
										    data-name="<?=htmlspecialchars($peer['description'] ?? 'Peer')?>"
										    data-endpoint="<?=htmlspecialchars($peer['endpoint'] ?? '')?>"
										    data-allowed="<?=htmlspecialchars($peer['allowed_ips'] ?? '')?>"
										    data-keepalive="<?=htmlspecialchars($peer['persistent_keepalive'] ?? '25')?>"
										    onclick="selectStatusPeerRow(this, event)">
											<td><strong><?=htmlspecialchars($peer['description'] ?? 'Peer')?></strong></td>
											<td class="pubkey" title="<?=htmlspecialchars($peer['public_key'])?>">
												<code><?=htmlspecialchars(substr($peer['public_key'], 0, 16))?>...</code>
											</td>
											<td><code><?=htmlspecialchars($peer['endpoint'] ?: 'Dynamic')?></code></td>
											<td><span class="badge" style="background:#e2ecf5; color:#1e395b; font-weight:normal; font-size:10px;"><?=htmlspecialchars($peer['allowed_ips'])?></span></td>
											<td>
												<?php if ($has_hs): ?>
													<span class="text-success"><i class="fa-solid fa-circle-check"></i> Connected</span>
												<?php else: ?>
													<span class="text-muted"><i class="fa-regular fa-clock"></i> Idle / Never</span>
												<?php endif; ?>
											</td>
											<td><span class="text-primary"><?=$p_rx_fmt?></span></td>
											<td><span class="text-primary"><?=$p_tx_fmt?></span></td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</td>
					</tr>
					<?php endif; ?>

					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Interfaces:</strong> <?=count($tunnels)?> active</span> &nbsp;|&nbsp;
				<span><strong>Total Peers:</strong> <?=$total_peers?> registered</span>
			</div>
			<div>
				<span class="text-muted">WireGuard Subsystem Driver Active</span>
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
var selectedStatusTunnelName = null;
var selectedStatusPeerData = null;

// Tunnel Row Selection Handler
function selectStatusRow(tr, name) {
    $('#status-grid-table tbody tr.status-tunnel-row').removeClass('selected');
    $('#status-grid-table tbody tr.status-peer-item-row').removeClass('selected-peer-row');
    $(tr).addClass('selected');
    selectedStatusTunnelName = name;

    // Enable toolbar action buttons
    $('#btn-status-enable, #btn-status-disable, #btn-status-remove').prop('disabled', false);

    // Cek apakah tunnel memiliki peer baris pertama
    var firstPeerTr = $('#status-grid-table tbody tr[data-parent="' + name + '"] tr.status-peer-item-row:first');
    if (firstPeerTr.length > 0) {
        selectedStatusPeerData = {
            pubkey: firstPeerTr.data('pubkey'),
            tun: firstPeerTr.data('tun'),
            name: firstPeerTr.data('name'),
            endpoint: firstPeerTr.data('endpoint'),
            allowed: firstPeerTr.data('allowed'),
            keepalive: firstPeerTr.data('keepalive')
        };
        $('#btn-status-qr, #btn-status-ros').prop('disabled', false);
    } else {
        selectedStatusPeerData = null;
        $('#btn-status-qr, #btn-status-ros').prop('disabled', true);
    }
}

// Peer Row Selection Handler in Nested Table
function selectStatusPeerRow(tr, e) {
    if (e) e.stopPropagation();
    $('#status-grid-table tbody tr.status-peer-item-row').removeClass('selected-peer-row');
    $(tr).addClass('selected-peer-row');

    selectedStatusPeerData = {
        pubkey: $(tr).data('pubkey'),
        tun: $(tr).data('tun'),
        name: $(tr).data('name'),
        endpoint: $(tr).data('endpoint'),
        allowed: $(tr).data('allowed'),
        keepalive: $(tr).data('keepalive')
    };

    selectedStatusTunnelName = selectedStatusPeerData.tun;
    $('#status-grid-table tbody tr.status-tunnel-row').removeClass('selected');
    $('#status-grid-table tbody tr.status-tunnel-row[data-name="' + selectedStatusTunnelName + '"]').addClass('selected');

    $('#btn-status-enable, #btn-status-disable, #btn-status-remove, #btn-status-qr, #btn-status-ros').prop('disabled', false);
}

// Show QR Modal for Selected Peer
function showSelectedStatusQR() {
    if (!selectedStatusPeerData) {
        MitraNet.toast('Pilih peer terlebih dahulu', 'warning');
        return;
    }
    loadStatusPeerQR(selectedStatusPeerData.pubkey, selectedStatusPeerData.tun, selectedStatusPeerData.name, '');
}

function loadStatusPeerQR(peerKey, tunName, descr, customEndpoint) {
    $('#qrModalLabel').html('<i class="fa-solid fa-qrcode"></i> Client Config: ' + descr);
    $('#qrLoading').removeClass('d-none');
    $('#qrContent').addClass('d-none');
    $('#qrModal').modal('show');

    var url = 'status_wireguard.php?ajax=client_config&tun=' + encodeURIComponent(tunName) + '&peer=' + encodeURIComponent(peerKey);
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
    if (!selectedStatusPeerData) return;
    var ep = $('#qrEndpointInput').val().trim();
    loadStatusPeerQR(selectedStatusPeerData.pubkey, selectedStatusPeerData.tun, selectedStatusPeerData.name, ep);
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
    link.download = (selectedStatusPeerData ? selectedStatusPeerData.name.replace(/[^a-zA-Z0-9_-]/g, '_') : 'wg-client') + ".conf";
    link.click();
});

// Show ROS MikroTik v7 Command
function showSelectedStatusROS() {
    if (!selectedStatusPeerData) {
        MitraNet.toast('Pilih peer terlebih dahulu', 'warning');
        return;
    }
    var p = selectedStatusPeerData;
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

// Filter Status Table
function filterStatusGrid(val) {
    val = (val || '').toLowerCase();
    $('#status-grid-table tbody tr.status-tunnel-row').each(function() {
        var text = $(this).text().toLowerCase();
        var tunName = $(this).data('name');
        var peerRow = $('#status-grid-table tbody tr[data-parent="' + tunName + '"]');
        if (text.indexOf(val) !== -1) {
            $(this).show();
            peerRow.show();
        } else {
            $(this).hide();
            peerRow.hide();
        }
    });
}

// Toggle Selected Tunnel (Enable / Disable)
function toggleSelectedStatusTunnel(enable) {
    if (!selectedStatusTunnelName) return;
    var actStr = enable ? 'Enable' : 'Disable';
    MitraNet.confirmAction({
        title: actStr + ' Interface ' + selectedStatusTunnelName + '?',
        text: 'Antarmuka WireGuard ' + selectedStatusTunnelName + ' akan ' + (enable ? 'diaktifkan' : 'dinonaktifkan') + '.',
        icon: enable ? 'question' : 'warning',
        confirmButtonText: 'Ya, ' + actStr
    }).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Memproses ' + actStr.toLowerCase() + ' interface...', 'info');
            $.post('/wg/status_wireguard.php', {
                ajax: '1',
                act: 'toggle',
                tun: selectedStatusTunnelName,
                enable: enable ? '1' : '0'
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Interface ' + selectedStatusTunnelName + ' berhasil ' + (enable ? 'diaktifkan' : 'dinonaktifkan'), 'success');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    MitraNet.toast('Gagal: ' + (res.error || 'Terjadi kesalahan sistem'), 'error');
                }
            }, 'json');
        }
    });
}

// Remove Selected Tunnel
function removeSelectedStatusTunnel() {
    if (!selectedStatusTunnelName) return;
    MitraNet.confirmDelete(
        'Hapus WireGuard Interface?',
        'Seluruh konfigurasi interface ' + selectedStatusTunnelName + ' dan peers yang terasosiasi akan dihapus secara permanen.'
    ).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Menghapus interface ' + selectedStatusTunnelName + '...', 'info');
            $.post('/wg/status_wireguard.php', {
                ajax: '1',
                act: 'delete',
                tun: selectedStatusTunnelName
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Interface ' + selectedStatusTunnelName + ' berhasil dihapus', 'success');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    MitraNet.toast('Gagal: ' + (res.error || 'Terjadi kesalahan sistem'), 'error');
                }
            }, 'json');
        }
    });
}
</script>

<style>
.selected-peer-row td {
    background-color: #dbeafe !important;
    color: #1e3a8a !important;
}
</style>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
