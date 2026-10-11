<?php
/*
 * vpn_wg_tunnels.php - MitraNet WireGuard Tunnels
 * WinBox Esthetic & SweetAlert2 UX Standards
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = $_GET['savemsg'] ?? '';
$err_msg = "";

// Handle AJAX actions (toggle, comment, delete)
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
    if ($act === 'comment') {
        $comment = trim($_REQUEST['comment'] ?? '');
        $res = MitraNetApi::commentWireGuardTunnel($tun, $comment);
        echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API error']);
        exit;
    }
    if ($act === 'delete') {
        $res = MitraNetApi::deleteWireGuardTunnel($tun);
        echo json_encode($res['data'] ?? ['success' => false, 'error' => 'API error']);
        exit;
    }
}

// Fallback Standard HTTP Handle Delete Tunnel
if (isset($_REQUEST['act']) && $_REQUEST['act'] === 'delete') {
    $tun_to_del = trim($_REQUEST['tun'] ?? '');
    if ($tun_to_del) {
        $res = MitraNetApi::deleteWireGuardTunnel($tun_to_del);
        if (!empty($res['data']['success'])) {
            header("Location: /wg/vpn_wg_tunnels.php?savemsg=" . urlencode("Tunnel {$tun_to_del} berhasil dihapus."));
            exit;
        } else {
            $err_msg = $res['data']['error'] ?? "Gagal menghapus tunnel {$tun_to_del}.";
        }
    }
}

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

$badge_title = "WireGuard";
$pgtitle = array("VPN", "WireGuard", "Tunnels");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$tab_array = array(
    array("Tunnels", true, "/wg/vpn_wg_tunnels.php"),
    array("Peers", false, "/wg/vpn_wg_peers.php"),
    array("Settings", false, "/wg/vpn_wg_settings.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">
<?php display_top_tabs($tab_array, false, 'tabs'); ?>
		
		<!-- TOOLBAR (EXACT MATCH WINBOX BUTTONS IN USER SCREENSHOT) -->
		<div class="mitranet-toolbar">
			<div class="mitranet-toolbar-left">
				<a href="/wg/vpn_wg_tunnels_edit.php" class="mitranet-btn" id="btn-new" title="Add New Tunnel">
					<i class="fa-regular fa-square-plus"></i> New
				</a>
				<button type="button" class="mitranet-btn" id="btn-enable" disabled onclick="toggleSelectedTunnel(true)" title="Enable Selected Tunnel">
					<i class="fa-solid fa-play text-muted"></i> Enable
				</button>
				<button type="button" class="mitranet-btn" id="btn-disable" disabled onclick="toggleSelectedTunnel(false)" title="Disable Selected Tunnel">
					<i class="fa-solid fa-pause text-muted"></i> Disable
				</button>
				<button type="button" class="mitranet-btn" id="btn-remove" disabled onclick="removeSelectedTunnel()" title="Remove Selected Tunnel">
					<i class="fa-solid fa-xmark text-muted"></i> Remove
				</button>
				<button type="button" class="mitranet-btn" id="btn-comment" disabled onclick="commentSelectedTunnel()" title="Set Comment">
					<i class="fa-regular fa-comment text-muted"></i> Comment
				</button>
				<button type="button" class="mitranet-btn" id="btn-edit" disabled onclick="editSelectedTunnel()" title="Edit Selected Tunnel (atau double-click baris)">
					<i class="fa-solid fa-pencil text-muted"></i> Edit
				</button>
			</div>
			<div class="mitranet-toolbar-right">
				<div class="mitranet-search-wrapper">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="text" id="grid-search" placeholder="Find tunnel..." onkeyup="filterWgGrid(this.value)">
				</div>
				<button type="button" class="mitranet-btn" onclick="$('#grid-search').focus()" title="Filter">
					<i class="fa-solid fa-filter text-muted"></i> Filter
				</button>
				<button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh">
					<i class="fa-solid fa-arrows-rotate text-muted"></i>
				</button>
			</div>
		</div>

		<!-- GRID TABLE -->
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="wg-grid-table">
				<thead>
					<tr>
						<th>Name</th>
						<th>Status</th>
						<th>Mode</th>
						<th>Description</th>
						<th>Interface Address</th>
						<th>Listen Port</th>
						<th>Public Key</th>
						<th>Rx</th>
						<th>Tx</th>
						<th style="width: 24px; text-align: center;"><i class="fa-solid fa-bars text-muted"></i></th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($tunnels)): ?>
					<tr><td colspan="10" class="text-center text-muted p-20">Belum ada tunnel WireGuard yang dikonfigurasi. Klik tombol "New" di toolbar untuk membuat.</td></tr>
				<?php else: ?>
					<?php foreach ($tunnels as $tun): ?>
					<?php 
						$tname = $tun['name'] ?? 'wg0'; 
						$is_srv = empty($tun['mode']) || $tun['mode'] === 'server';
						$tun_up = !empty($tun['enabled']);
						$desc = $tun['description'] ?? 'WireGuard Tunnel';
						
						$rx_bytes = floatval($tun['transfer_rx'] ?? 0);
						$tx_bytes = floatval($tun['transfer_tx'] ?? 0);
						$rx_fmt = $rx_bytes > 1048576 ? number_format($rx_bytes / 1048576, 2) . ' MiB' : ($rx_bytes > 0 ? number_format($rx_bytes / 1024, 2) . ' KiB' : '0 B');
						$tx_fmt = $tx_bytes > 1048576 ? number_format($tx_bytes / 1048576, 2) . ' MiB' : ($tx_bytes > 0 ? number_format($tx_bytes / 1024, 2) . ' KiB' : '0 B');
					?>
					<tr data-name="<?=htmlspecialchars($tname)?>"
					    data-enabled="<?=$tun_up ? '1' : '0'?>"
					    data-comment="<?=htmlspecialchars($desc)?>"
					    onclick="selectTunnelRow(this, '<?=htmlspecialchars($tname, ENT_QUOTES)?>')"
					    ondblclick="document.location='/wg/vpn_wg_tunnels_edit.php?tun=<?=urlencode($tname)?>';">
						<td><strong><code><?=htmlspecialchars($tname)?></code></strong></td>
						<td>
							<?php if ($tun_up): ?>
								<span class="label label-success"><i class="fa-solid fa-circle-check"></i> UP</span>
							<?php else: ?>
								<span class="label label-default"><i class="fa-solid fa-circle-stop"></i> DOWN</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($is_srv): ?>
								<span class="label label-primary"><i class="fa-solid fa-server"></i> Server</span>
							<?php else: ?>
								<span class="label label-info"><i class="fa-solid fa-network-wired"></i> Client Uplink</span>
							<?php endif; ?>
						</td>
						<td class="td-comment"><?=htmlspecialchars($desc)?></td>
						<td><code><?=htmlspecialchars($tun['address'] ?: '—')?></code></td>
						<td><?=htmlspecialchars($tun['listen_port'] ?? '51820')?></td>
						<td class="td-pubkey"
						    title="Klik untuk salin: <?=htmlspecialchars($tun['public_key'])?>"
						    onclick="navigator.clipboard.writeText('<?=htmlspecialchars($tun['public_key'])?>'); MitraNet.toast('Public key disalin ke clipboard', 'success'); event.stopPropagation();">
							<?=htmlspecialchars(substr($tun['public_key'], 0, 16))?>...
						</td>
						<td><span class="label label-info"><?=$rx_fmt?></span></td>
						<td><span class="label label-info"><?=$tx_fmt?></span></td>
						<td style="text-align: center;"><i class="fa-solid fa-bars text-muted" style="cursor: pointer;" title="Double-click untuk edit"></i></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($tunnels)?> tunnels</span>
			</div>
			<div>
				<span class="text-muted">WireGuard Subsystem</span>
			</div>
		</div>

	</div>
</div>

<div class="infoblock">
	<i class="fa-solid fa-circle-info text-info"></i> Layanan service daemon WireGuard (Start / Stop / Restart) dikelola secara terpusat di menu <a href="/status_services.php"><strong>Status: Services</strong></a>.
</div>

<script>
var selectedTunnelName = null;
var selectedTunnelData = null;

// Row Selection Handler
function selectTunnelRow(tr, name) {
    $('#wg-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedTunnelName = name;
    selectedTunnelData = {
        name: name,
        enabled: $(tr).data('enabled') == 1,
        comment: $(tr).data('comment')
    };

    // Enable toolbar action buttons
    $('#btn-edit, #btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', false);
}

// Filter Grid
function filterWgGrid(val) {
    val = (val || '').toLowerCase();
    $('#wg-grid-table tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf(val) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Edit Selected
function editSelectedTunnel() {
    if (!selectedTunnelName) return;
    window.location.href = '/wg/vpn_wg_tunnels_edit.php?tun=' + encodeURIComponent(selectedTunnelName);
}

// Toggle (Enable / Disable)
function toggleSelectedTunnel(enable) {
    if (!selectedTunnelName) return;
    var actStr = enable ? 'Enable' : 'Disable';
    MitraNet.confirmAction({
        title: actStr + ' Tunnel ' + selectedTunnelName + '?',
        text: 'Antarmuka WireGuard ' + selectedTunnelName + ' akan ' + (enable ? 'diaktifkan' : 'dinonaktifkan') + '.',
        icon: enable ? 'question' : 'warning',
        confirmButtonText: 'Ya, ' + actStr
    }).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Memproses ' + actStr.toLowerCase() + ' tunnel...', 'info');
            $.post('/wg/vpn_wg_tunnels.php', {
                ajax: '1',
                act: 'toggle',
                tun: selectedTunnelName,
                enable: enable ? '1' : '0'
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Tunnel ' + selectedTunnelName + ' berhasil ' + (enable ? 'diaktifkan' : 'dinonaktifkan'), 'success');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    MitraNet.toast('Gagal: ' + (res.error || 'Terjadi kesalahan sistem'), 'error');
                }
            }, 'json');
        }
    });
}

// Comment Prompt
function commentSelectedTunnel() {
    if (!selectedTunnelName) return;
    var currentComment = selectedTunnelData ? selectedTunnelData.comment : '';
    MitraNet.promptInput({
        title: 'Set Comment',
        inputLabel: 'Komentar / Deskripsi untuk ' + selectedTunnelName + ':',
        inputValue: currentComment,
        inputPlaceholder: 'e.g. Uplink Main Gateway / Office Road Warrior'
    }).then(function(result) {
        if (result.isConfirmed) {
            var newComment = (result.value || '').trim();
            MitraNet.toast('Menyimpan komentar...', 'info');
            $.post('/wg/vpn_wg_tunnels.php', {
                ajax: '1',
                act: 'comment',
                tun: selectedTunnelName,
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

// Remove Selected Tunnel
function removeSelectedTunnel() {
    if (!selectedTunnelName) return;
    confirmDeleteTunnel(selectedTunnelName);
}

// Confirm Delete Dialog
function confirmDeleteTunnel(tunnelName) {
    MitraNet.confirmDelete(
        'Hapus WireGuard Tunnel?',
        'Seluruh konfigurasi interface ' + tunnelName + ' dan peers yang terasosiasi akan dihapus secara permanen.'
    ).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Menghapus tunnel ' + tunnelName + '...', 'info');
            $.post('/wg/vpn_wg_tunnels.php', {
                ajax: '1',
                act: 'delete',
                tun: tunnelName
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Tunnel ' + tunnelName + ' berhasil dihapus', 'success');
                    setTimeout(function() { location.reload(); }, 600);
                } else {
                    window.location.href = '?act=delete&tun=' + encodeURIComponent(tunnelName);
                }
            }, 'json');
        }
    });
}
</script>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
