<?php
/*
 * vpn_wg_tunnels.php - MitraNet WireGuard Interfaces
 * WinBox Esthetic & SweetAlert2 UX Standards
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = $_GET['savemsg'] ?? '';
$err_msg = "";

// Handle AJAX actions (toggle, comment, delete, keygen, save)
if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === '1') {
    header('Content-Type: application/json');
    $act = $_REQUEST['act'] ?? '';
    $tun = trim($_REQUEST['tun'] ?? '');

    if ($act === 'keygen') {
        $keys = MitraNetApi::generateWireGuardKeys();
        if (!empty($keys['data']['success'])) {
            echo json_encode(['success' => true, 'private_key' => $keys['data']['private_key'], 'public_key' => $keys['data']['public_key']]);
        } else {
            echo json_encode(['success' => false, 'error' => $keys['data']['error'] ?? 'Gagal membuat pasangan key']);
        }
        exit;
    }

    if ($act === 'save') {
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($_POST['name'] ?? 'wg0'));
        $listenport = trim($_POST['listenport'] ?? '51820');
        $privatekey = trim($_POST['privatekey'] ?? '');
        $descr = trim($_POST['descr'] ?? 'WireGuard Interface');
        $address = trim($_POST['address'] ?? '10.10.99.1/24');
        $mode = trim($_POST['mode'] ?? 'server');
        $route_interface = trim($_POST['route_interface'] ?? '');
        $enable_nat = isset($_POST['enable_nat']) && ($_POST['enable_nat'] === 'yes' || $_POST['enable_nat'] === '1' || $_POST['enable_nat'] === 'true');
        $dns = trim($_POST['dns'] ?? '');
        $mtu = trim($_POST['mtu'] ?? '1420');
        $dscp_class = trim($_POST['dscp_class'] ?? '');
        $clamp_mss = !isset($_POST['clamp_mss']) || ($_POST['clamp_mss'] === 'yes' || $_POST['clamp_mss'] === '1' || $_POST['clamp_mss'] === 'true');

        if (empty($name)) {
            echo json_encode(['success' => false, 'error' => 'Nama interface WireGuard wajib diisi (contoh: wg0).']);
            exit;
        }
        if (empty($address)) {
            echo json_encode(['success' => false, 'error' => 'Interface Address (CIDR) wajib diisi (contoh: 10.10.99.1/24).']);
            exit;
        }

        $res = MitraNetApi::saveWireGuardTunnel($name, $address, $listenport, $privatekey, $descr, $mode, $route_interface, $enable_nat, $dns, $mtu, $dscp_class, $clamp_mss);
        if ($res['status'] === 200 && !empty($res['data']['success'])) {
            echo json_encode(['success' => true, 'message' => "Interface {$name} berhasil disimpan."]);
        } else {
            echo json_encode(['success' => false, 'error' => $res['data']['error'] ?? 'Gagal menyimpan konfigurasi WireGuard.']);
        }
        exit;
    }

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
            header("Location: /wg/vpn_wg_tunnels.php?savemsg=" . urlencode("Interface {$tun_to_del} berhasil dihapus."));
            exit;
        } else {
            $err_msg = $res['data']['error'] ?? "Gagal menghapus interface {$tun_to_del}.";
        }
    }
}

$wg = MitraNetApi::getWireGuard();
$is_running = !empty($wg['running']);
$tunnels = $wg['tunnels'] ?? [];

// Available candidate interfaces for policy routing
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

$badge_title = "WireGuard";
$pgtitle = array("VPN", "WireGuard", "Interfaces");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$tab_array = array(
    array("Interfaces", true, "/wg/vpn_wg_tunnels.php"),
    array("Secrets", false, "/wg/vpn_wg_peers.php"),
    array("Settings", false, "/wg/vpn_wg_settings.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">
<?php display_top_tabs($tab_array, false, 'tabs'); ?>
		
		<!-- TOOLBAR (EXACT MATCH WINBOX BUTTONS) -->
		<div class="mitranet-toolbar">
			<div class="mitranet-toolbar-left">
				<button type="button" class="mitranet-btn" id="btn-new" onclick="openNewTunnelModal()" title="Add New WireGuard Interface">
					<i class="fa-regular fa-square-plus"></i> New
				</button>
				<button type="button" class="mitranet-btn" id="btn-enable" disabled onclick="toggleSelectedTunnel(true)" title="Enable Selected Interface">
					<i class="fa-solid fa-play text-muted"></i> Enable
				</button>
				<button type="button" class="mitranet-btn" id="btn-disable" disabled onclick="toggleSelectedTunnel(false)" title="Disable Selected Interface">
					<i class="fa-solid fa-pause text-muted"></i> Disable
				</button>
				<button type="button" class="mitranet-btn" id="btn-remove" disabled onclick="removeSelectedTunnel()" title="Remove Selected Interface">
					<i class="fa-solid fa-xmark text-muted"></i> Remove
				</button>
				<button type="button" class="mitranet-btn" id="btn-comment" disabled onclick="commentSelectedTunnel()" title="Set Comment">
					<i class="fa-regular fa-comment text-muted"></i> Comment
				</button>
				<button type="button" class="mitranet-btn" id="btn-edit" disabled onclick="editSelectedTunnel()" title="Edit Selected Interface (atau double-click baris)">
					<i class="fa-solid fa-pencil text-muted"></i> Edit
				</button>
			</div>
			<div class="mitranet-toolbar-right">
				<div class="mitranet-search-wrapper">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="text" id="grid-search" placeholder="Find interface..." onkeyup="filterWgGrid(this.value)">
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
						<th class="text-center col-flag" style="width: 28px;"><i class="fa-regular fa-flag text-muted"></i></th>
						<th>Name <i class="fa-solid fa-caret-up"></i></th>
						<th>Status</th>
						<th>Mode</th>
						<th>Description</th>
						<th>Interface Address</th>
						<th>Listen Port</th>
						<th>Public Key</th>
						<th>Rx</th>
						<th>Tx</th>
						<th class="text-center col-menu" style="width: 24px;"><i class="fa-solid fa-bars text-muted"></i></th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($tunnels)): ?>
					<tr><td colspan="11" class="text-center text-muted p-20">Belum ada interface WireGuard yang dikonfigurasi. Klik tombol "New" di toolbar untuk membuat.</td></tr>
				<?php else: ?>
					<?php foreach ($tunnels as $tun): ?>
					<?php 
						$tname = $tun['name'] ?? 'wg0'; 
						$is_srv = empty($tun['mode']) || $tun['mode'] === 'server';
						$tun_up = !empty($tun['enabled']);
						$desc = $tun['description'] ?? 'WireGuard Interface';
						$tun_json = htmlspecialchars(json_encode($tun), ENT_QUOTES, 'UTF-8');
						
						$rx_bytes = floatval($tun['transfer_rx'] ?? 0);
						$tx_bytes = floatval($tun['transfer_tx'] ?? 0);
						$rx_fmt = $rx_bytes > 1048576 ? number_format($rx_bytes / 1048576, 2) . ' MiB' : ($rx_bytes > 0 ? number_format($rx_bytes / 1024, 2) . ' KiB' : '0 B');
						$tx_fmt = $tx_bytes > 1048576 ? number_format($tx_bytes / 1048576, 2) . ' MiB' : ($tx_bytes > 0 ? number_format($tx_bytes / 1024, 2) . ' KiB' : '0 B');
					?>
					<tr data-name="<?=htmlspecialchars($tname)?>"
					    data-enabled="<?=$tun_up ? '1' : '0'?>"
					    data-comment="<?=htmlspecialchars($desc)?>"
					    data-tunnel="<?=$tun_json?>"
					    onclick="selectTunnelRow(this, '<?=htmlspecialchars($tname, ENT_QUOTES)?>')"
					    ondblclick="openEditTunnelModal('<?=htmlspecialchars($tname, ENT_QUOTES)?>');">
						<!-- Flag Icon Like Interfaces -->
						<td class="text-center col-flag-cell">
							<?php if ($tun_up): ?>
								<i class="fa-solid fa-check text-success" title="Running / Link UP"></i>
							<?php else: ?>
								<i class="fa-solid fa-minus text-muted" title="Disabled"></i>
							<?php endif; ?>
						</td>
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
						<!-- Blank Hamburger Context Cell Matching Interfaces -->
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
				<span><strong>Total:</strong> <?=count($tunnels)?> interfaces</span>
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

<!-- ============================================================== -->
<!-- WINBOX MODAL: NEW / EDIT WIREGUARD INTERFACE                   -->
<!-- ============================================================== -->
<div id="modal-winbox-tunnel" class="modal fade" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg winbox-modal-dialog">
        <form id="form-winbox-tunnel" class="form-horizontal">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="act" value="save">
            <input type="hidden" name="is_edit" id="wg-is-edit" value="0">
            <div class="modal-content winbox-window-popup">

                <!-- WINBOX WINDOW HEADER -->
                <div class="winbox-popup-header">
                    <div class="winbox-popup-title">
                        <i class="fa-solid fa-network-wired text-primary"></i>
                        <span id="wg-modal-title">New Interface &gt; WireGuard</span>
                    </div>
                    <div class="winbox-popup-controls">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                </div>

                <!-- WINBOX TABS BAR -->
                <div class="winbox-popup-tabs-bar">
                    <ul class="nav nav-tabs winbox-tabs-nav" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#wgtab-general" role="tab" data-toggle="tab">General</a>
                        </li>
                        <li role="presentation">
                            <a href="#wgtab-routing" role="tab" data-toggle="tab">Routing &amp; NAT</a>
                        </li>
                        <li role="presentation">
                            <a href="#wgtab-advanced" role="tab" data-toggle="tab">Advanced / QoS</a>
                        </li>
                        <li role="presentation" id="li-wgtab-status" style="display: none;">
                            <a href="#wgtab-status" role="tab" data-toggle="tab">Status</a>
                        </li>
                    </ul>
                </div>

                <!-- WINBOX BODY: 2-COLUMN (FORM + ACTIONS) -->
                <div class="modal-body winbox-popup-body">
                    <div class="winbox-body-columns">

                        <!-- LEFT: FORM FIELDS -->
                        <div class="winbox-content-left tab-content">

                            <!-- TAB 1: GENERAL -->
                            <div role="tabpanel" class="tab-pane active" id="wgtab-general">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Enabled</label>
                                    <div class="col-sm-9">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="enabled" id="wg-enabled" value="yes" checked>
                                                <span class="winbox-checkbox-custom"></span> Enable Interface Service
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Name <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" name="name" id="wg-name" class="form-control input-sm font-monospace" placeholder="wg0" required>
                                        <span class="help-block fs-11">Nama antarmuka kernel Linux (contoh: <code>wg0</code>, <code>wg1</code>).</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Mode</label>
                                    <div class="col-sm-9">
                                        <select name="mode" id="wg-mode" class="form-control input-sm">
                                            <option value="server">VPN Server (Menerima koneksi Remote Client / HP / PC)</option>
                                            <option value="client">Client / Uplink (MitraNet tersambung ke Server/VPS Eksternal)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Comment</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="descr" id="wg-descr" class="form-control input-sm" placeholder="WireGuard Interface">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Address (CIDR) <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" name="address" id="wg-address" class="form-control input-sm font-monospace" placeholder="10.10.99.1/24" required>
                                        <span class="help-block fs-11">IP address dan prefix subnet internal interface (contoh: <code>10.10.99.1/24</code>).</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Listen Port <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="listenport" id="wg-listenport" class="form-control font-monospace" value="51820" min="1" max="65535" required>
                                            <div class="input-group-btn">
                                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" title="Camouflage Port">
                                                    <i class="fa-solid fa-mask text-primary"></i> Preset <span class="caret"></span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-right">
                                                    <li><a href="javascript:void(0)" onclick="$('#wg-listenport').val('53')">Port 53 (DNS Bypass)</a></li>
                                                    <li><a href="javascript:void(0)" onclick="$('#wg-listenport').val('443')">Port 443 (QUIC/HTTPS Bypass)</a></li>
                                                    <li><a href="javascript:void(0)" onclick="$('#wg-listenport').val('123')">Port 123 (NTP Bypass)</a></li>
                                                    <li role="separator" class="divider"></li>
                                                    <li><a href="javascript:void(0)" onclick="$('#wg-listenport').val('51820')">Port 51820 (Default)</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Private Key <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <div class="input-group input-group-sm">
                                            <input type="password" name="privatekey" id="wg-privatekey" class="form-control font-monospace" placeholder="Private Key (leave blank on edit to keep)" autocomplete="new-password">
                                            <span class="input-group-btn">
                                                <button class="btn btn-default" type="button" id="btn-toggle-privkey" onclick="togglePrivKeyVisibility()" title="Lihat Key">
                                                    <i class="fa-regular fa-eye"></i>
                                                </button>
                                                <button class="btn btn-primary" type="button" id="btn-modal-keygen" onclick="generateModalKeys()" title="Generate Keypair">
                                                    <i class="fa-solid fa-key"></i> Keygen
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Public Key</label>
                                    <div class="col-sm-9">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="publickey" id="wg-publickey" class="form-control font-monospace" readonly placeholder="Auto-generated Public Key">
                                            <span class="input-group-btn">
                                                <button class="btn btn-default" type="button" onclick="copyModalPublicKey()" title="Copy Public Key">
                                                    <i class="fa-regular fa-copy"></i>
                                                </button>
                                            </span>
                                        </div>
                                        <span class="help-block fs-11">Public key untuk dipasang di Secrets / Peers client.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: ROUTING & NAT -->
                            <div role="tabpanel" class="tab-pane" id="wgtab-routing">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Route Interface</label>
                                    <div class="col-sm-9">
                                        <select name="route_interface" id="wg-route-interface" class="form-control input-sm">
                                            <option value="">-- Do Not Route Local Interface (Default Point-to-Point) --</option>
                                            <?php foreach ($candidate_ifaces as $if_name => $if_desc): ?>
                                                <option value="<?=htmlspecialchars($if_name)?>"><?=htmlspecialchars($if_name)?> (<?=htmlspecialchars($if_desc)?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <span class="help-block fs-11">Arahkan seluruh trafik interface lokal melewati WireGuard interface ini.</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Outbound NAT</label>
                                    <div class="col-sm-9">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="enable_nat" id="wg-enable-nat" value="yes" checked>
                                                <span class="winbox-checkbox-custom"></span> <strong>Enable Outbound NAT (Masquerade)</strong>
                                            </label>
                                        </div>
                                        <span class="help-block fs-11">Aktifkan SNAT / Masquerade agar client WireGuard dapat mengakses internet melalui gateway uplink.</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">DNS Server</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="dns" id="wg-dns" class="form-control input-sm" placeholder="e.g. 1.1.1.1, 8.8.8.8">
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: ADVANCED / QOS -->
                            <div role="tabpanel" class="tab-pane" id="wgtab-advanced">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">MTU</label>
                                    <div class="col-sm-9">
                                        <input type="number" name="mtu" id="wg-mtu" class="form-control input-sm font-monospace" value="1420" min="576" max="65535">
                                        <span class="help-block fs-11">Standard MTU untuk WireGuard tunnel adalah 1420 (mencegah overhead IP/UDP fragmentation).</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">DSCP Priority</label>
                                    <div class="col-sm-9">
                                        <select name="dscp_class" id="wg-dscp" class="form-control input-sm">
                                            <option value="">-- None / Default Best Effort --</option>
                                            <option value="EF">EF - Expedited Forwarding (Voice / VoIP)</option>
                                            <option value="CS6">CS6 - Network Control</option>
                                            <option value="CS7">CS7 - Network Operations</option>
                                            <option value="AF41">AF41 - Multimedia Streaming Video (High)</option>
                                            <option value="AF42">AF42 - Multimedia Streaming Video (Medium)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">MSS Clamping</label>
                                    <div class="col-sm-9">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="clamp_mss" id="wg-clamp-mss" value="yes" checked>
                                                <span class="winbox-checkbox-custom"></span> Clamp TCP MSS to Path MTU
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 4: STATUS (EDIT ONLY) -->
                            <div role="tabpanel" class="tab-pane" id="wgtab-status">
                                <table class="table table-condensed table-striped" style="font-size: 11px;">
                                    <tbody>
                                        <tr><th style="width: 35%;">Link State</th><td id="wg-stat-link"><span class="label label-success">UP</span></td></tr>
                                        <tr><th>Transfer Rx</th><td id="wg-stat-rx">0 B</td></tr>
                                        <tr><th>Transfer Tx</th><td id="wg-stat-tx">0 B</td></tr>
                                        <tr><th>Peers Connected</th><td id="wg-stat-peers">0</td></tr>
                                        <tr><th>Listen Port Active</th><td id="wg-stat-port">51820 / UDP</td></tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>

                        <!-- RIGHT: ACTIONS COLUMN (WINBOX STYLE) -->
                        <div class="winbox-content-right">
                            <div class="winbox-actions-header">
                                <i class="fa-solid fa-bolt"></i> Actions
                            </div>
                            <ul class="winbox-actions-list">
                                <li><a href="javascript:void(0)" onclick="submitTunnelForm(true)">OK</a></li>
                                <li><a href="javascript:void(0)" onclick="$('#modal-winbox-tunnel').modal('hide')">Cancel</a></li>
                                <li><a href="javascript:void(0)" onclick="submitTunnelForm(false)">Apply</a></li>
                                <li><a href="javascript:void(0)" onclick="resetTunnelForm()">Reset</a></li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- WINBOX FOOTER -->
                <div class="winbox-popup-footer">
                    <div class="winbox-footer-status">
                        <span class="badge winbox-running-badge" style="background:#27ae60;">READY</span>
                        <span class="winbox-link-msg">WireGuard Subsystem Driver Active</span>
                    </div>
                    <div class="winbox-footer-buttons">
                        <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-default" onclick="submitTunnelForm(false)">Apply</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="submitTunnelForm(true)">OK</button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
var selectedTunnelName = null;
var selectedTunnelData = null;
var allTunnelsData = <?=json_encode($tunnels)?>;

// Row Selection Handler
function selectTunnelRow(tr, name) {
    $('#wg-grid-table tbody tr').removeClass('selected');
    $(tr).addClass('selected');
    selectedTunnelName = name;
    
    var rawJson = $(tr).data('tunnel');
    if (typeof rawJson === 'object') {
        selectedTunnelData = rawJson;
    } else {
        selectedTunnelData = {
            name: name,
            enabled: $(tr).data('enabled') == 1,
            comment: $(tr).data('comment')
        };
    }

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

// Open WinBox Modal: Add New Tunnel
function openNewTunnelModal() {
    $('#form-winbox-tunnel')[0].reset();
    $('#wg-is-edit').val('0');
    $('#wg-modal-title').text('New Interface > WireGuard');
    $('#wg-name').prop('readonly', false);
    $('#wg-privatekey').prop('required', true);
    $('#li-wgtab-status').hide();
    $('.winbox-tabs-nav a[href="#wgtab-general"]').tab('show');

    // Suggest next name wg0, wg1, ...
    var existingNames = {};
    for (var i = 0; i < allTunnelsData.length; i++) {
        existingNames[allTunnelsData[i].name] = true;
    }
    var nextIdx = 0;
    while (existingNames['wg' + nextIdx]) {
        nextIdx++;
    }
    var sugName = 'wg' + nextIdx;
    var sugPort = 51820 + nextIdx;
    var sugAddr = '10.10.' + (99 + nextIdx) + '.1/24';

    $('#wg-name').val(sugName);
    $('#wg-listenport').val(sugPort);
    $('#wg-address').val(sugAddr);
    $('#wg-descr').val('WireGuard Interface');
    $('#wg-mode').val('server');
    $('#wg-mtu').val('1420');
    $('#wg-enabled').prop('checked', true);
    $('#wg-enable-nat').prop('checked', true);
    $('#wg-clamp-mss').prop('checked', true);

    // Auto generate keys for convenience
    generateModalKeys(false);

    $('#modal-winbox-tunnel').modal('show');
}

// Open WinBox Modal: Edit Tunnel
function openEditTunnelModal(tunName) {
    tunName = tunName || selectedTunnelName;
    if (!tunName) return;

    var targetTun = null;
    for (var i = 0; i < allTunnelsData.length; i++) {
        if (allTunnelsData[i].name === tunName) {
            targetTun = allTunnelsData[i];
            break;
        }
    }

    if (!targetTun && selectedTunnelData && selectedTunnelData.name === tunName) {
        targetTun = selectedTunnelData;
    }

    if (!targetTun) return;

    $('#form-winbox-tunnel')[0].reset();
    $('#wg-is-edit').val('1');
    $('#wg-modal-title').text('Interface <' + targetTun.name + '>');
    $('#wg-name').val(targetTun.name).prop('readonly', true);
    $('#wg-enabled').prop('checked', !!targetTun.enabled);
    $('#wg-mode').val(targetTun.mode || 'server');
    $('#wg-descr').val(targetTun.description || 'WireGuard Interface');
    $('#wg-address').val(targetTun.address || '10.10.99.1/24');
    $('#wg-listenport').val(targetTun.listen_port || '51820');
    $('#wg-publickey').val(targetTun.public_key || '');
    $('#wg-privatekey').val('').prop('required', false).attr('placeholder', 'Leave blank to keep existing key');
    $('#wg-route-interface').val(targetTun.route_interface || '');
    $('#wg-enable-nat').prop('checked', (targetTun.enable_nat !== undefined ? !!targetTun.enable_nat : true));
    $('#wg-dns').val(targetTun.dns || '');
    $('#wg-mtu').val(targetTun.mtu || '1420');
    $('#wg-dscp').val(targetTun.dscp_class || '');
    $('#wg-clamp-mss').prop('checked', targetTun.clamp_mss !== false);

    // Populate Status tab
    var isUp = !!targetTun.enabled;
    $('#wg-stat-link').html(isUp ? '<span class="label label-success">UP</span>' : '<span class="label label-default">DOWN</span>');
    var rxB = parseFloat(targetTun.transfer_rx || 0);
    var txB = parseFloat(targetTun.transfer_tx || 0);
    $('#wg-stat-rx').text(rxB > 1048576 ? (rxB / 1048576).toFixed(2) + ' MiB' : (rxB / 1024).toFixed(2) + ' KiB');
    $('#wg-stat-tx').text(txB > 1048576 ? (txB / 1048576).toFixed(2) + ' MiB' : (txB / 1024).toFixed(2) + ' KiB');
    $('#wg-stat-peers').text((targetTun.peers || []).length);
    $('#wg-stat-port').text((targetTun.listen_port || 51820) + ' / UDP');

    $('#li-wgtab-status').show();
    $('.winbox-tabs-nav a[href="#wgtab-general"]').tab('show');

    $('#modal-winbox-tunnel').modal('show');
}

// Edit Selected
function editSelectedTunnel() {
    if (!selectedTunnelName) return;
    openEditTunnelModal(selectedTunnelName);
}

// Toggle Private Key Visibility
function togglePrivKeyVisibility() {
    var inp = $('#wg-privatekey');
    var icon = $('#btn-toggle-privkey i');
    if (inp.attr('type') === 'password') {
        inp.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        inp.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

// Copy Public Key to Clipboard
function copyModalPublicKey() {
    var pk = $('#wg-publickey').val();
    if (!pk) return;
    navigator.clipboard.writeText(pk).then(function() {
        MitraNet.toast('Public key disalin ke clipboard', 'success');
    });
}

// Generate Keypair via AJAX
function generateModalKeys(notify) {
    if (notify === undefined) notify = true;
    $.post('/wg/vpn_wg_tunnels.php', {
        ajax: '1',
        act: 'keygen'
    }, function(res) {
        if (res && res.success) {
            $('#wg-privatekey').val(res.private_key);
            $('#wg-publickey').val(res.public_key);
            if (notify) MitraNet.toast('Keypair WireGuard berhasil di-generate', 'success');
        } else if (notify) {
            MitraNet.toast('Gagal generate keypair: ' + (res.error || 'Unknown error'), 'error');
        }
    }, 'json');
}

// Reset Form in Modal
function resetTunnelForm() {
    if ($('#wg-is-edit').val() === '1') {
        openEditTunnelModal(selectedTunnelName);
    } else {
        openNewTunnelModal();
    }
}

// Submit Form via AJAX
function submitTunnelForm(closeOnSuccess) {
    var form = $('#form-winbox-tunnel');
    var tunName = $('#wg-name').val().trim();
    var addr = $('#wg-address').val().trim();
    var isEdit = $('#wg-is-edit').val() === '1';

    if (!tunName) {
        MitraNet.toast('Nama interface WireGuard wajib diisi', 'warning');
        $('#wg-name').focus();
        return;
    }
    if (!addr) {
        MitraNet.toast('Interface Address CIDR wajib diisi', 'warning');
        $('#wg-address').focus();
        return;
    }
    if (!isEdit && !$('#wg-privatekey').val().trim()) {
        MitraNet.toast('Private key wajib diisi atau klik Keygen', 'warning');
        $('#wg-privatekey').focus();
        return;
    }

    var postData = form.serialize();

    MitraNet.toast('Menyimpan interface ' + tunName + '...', 'info');
    $.post('/wg/vpn_wg_tunnels.php', postData, function(res) {
        if (res && res.success) {
            MitraNet.toast('Interface ' + tunName + ' berhasil disimpan', 'success');
            if (closeOnSuccess) {
                $('#modal-winbox-tunnel').modal('hide');
                setTimeout(function() { location.reload(); }, 600);
            } else {
                setTimeout(function() { location.reload(); }, 800);
            }
        } else {
            MitraNet.toast('Gagal: ' + (res.error || 'Terjadi kesalahan sistem'), 'error');
        }
    }, 'json').fail(function() {
        MitraNet.toast('Gagal terhubung ke REST API WireGuard', 'error');
    });
}

// Toggle (Enable / Disable)
function toggleSelectedTunnel(enable) {
    if (!selectedTunnelName) return;
    var actStr = enable ? 'Enable' : 'Disable';
    MitraNet.confirmAction({
        title: actStr + ' Interface ' + selectedTunnelName + '?',
        text: 'Antarmuka WireGuard ' + selectedTunnelName + ' akan ' + (enable ? 'diaktifkan' : 'dinonaktifkan') + '.',
        icon: enable ? 'question' : 'warning',
        confirmButtonText: 'Ya, ' + actStr
    }).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Memproses ' + actStr.toLowerCase() + ' interface...', 'info');
            $.post('/wg/vpn_wg_tunnels.php', {
                ajax: '1',
                act: 'toggle',
                tun: selectedTunnelName,
                enable: enable ? '1' : '0'
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Interface ' + selectedTunnelName + ' berhasil ' + (enable ? 'diaktifkan' : 'dinonaktifkan'), 'success');
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
        'Hapus WireGuard Interface?',
        'Seluruh konfigurasi interface ' + tunnelName + ' dan peers yang terasosiasi akan dihapus secara permanen.'
    ).then(function(result) {
        if (result.isConfirmed) {
            MitraNet.toast('Menghapus interface ' + tunnelName + '...', 'info');
            $.post('/wg/vpn_wg_tunnels.php', {
                ajax: '1',
                act: 'delete',
                tun: tunnelName
            }, function(res) {
                if (res && res.success) {
                    MitraNet.toast('Interface ' + tunnelName + ' berhasil dihapus', 'success');
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
