<?php
/*
 * rules.php - MitraNet Firewall Management Window (WinBox RouterOS Standard)
 * Implements: Filter Rules, NAT, Mangle, Raw, Service Ports, Connections, Address Lists, Layer7 Protocols
 * Compatible with pfSense/OPNsense navigation & MitraNet native Linux nftables engine.
 */

$pgtitle = array(gettext("Firewall"), gettext("Filter Rules"));
$selected_menu = "firewall";
require_once(__DIR__ . '/../includes/head.inc');

$msg = '';
$err = '';
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']) || isset($_GET['ajax']);

// Active Tab Selector: filter, nat, mangle, raw, service_ports, connections, address_lists, layer7
$current_tab = trim($_GET['tab'] ?? 'filter');
$valid_tabs = ['filter', 'nat', 'mangle', 'raw', 'service_ports', 'connections', 'address_lists', 'layer7'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'filter';
}

// =========================================================================
// AJAX ENDPOINTS (Real-time polling & detail)
// =========================================================================
if ($is_ajax && isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    if ($_GET['ajax'] === 'connections') {
        $conntrack = MitraNetApi::getConntrackStates();
        $states = $conntrack['states'] ?? [];
        echo json_encode(['success' => true, 'total' => count($states), 'states' => $states]);
        exit;
    }

    if ($_GET['ajax'] === 'status') {
        $fw = MitraNetApi::getFirewall();
        echo json_encode(['success' => true, 'status' => $fw['status'] ?? []]);
        exit;
    }
}

// =========================================================================
// POST ACTIONS (Add, Delete, Toggle, Comment, Apply)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. FILTER RULES
    if ($action === 'add_rule') {
        $rid = trim($_POST['id'] ?? '');
        $act = $_POST['rule_action'] ?? 'accept';
        $chain = $_POST['chain'] ?? 'input';
        $proto = $_POST['protocol'] ?? 'any';
        $src = trim($_POST['source'] ?? 'any');
        $dst = trim($_POST['destination'] ?? 'any');
        $src_ports = trim($_POST['src_ports'] ?? '');
        $dst_ports = trim($_POST['dst_ports'] ?? '');
        $in_iface = trim($_POST['in_interface'] ?? 'any');
        $out_iface = trim($_POST['out_interface'] ?? 'any');
        $comment = trim($_POST['comment'] ?? '');
        $prio = (int)($_POST['priority'] ?? 100);

        if (!empty($rid)) {
            $payload = [
                'id' => $rid,
                'action' => $act,
                'chain' => $chain,
                'protocol' => $proto,
                'source' => !empty($src) ? $src : 'any',
                'destination' => !empty($dst) ? $dst : 'any',
                'source_ports' => $src_ports,
                'destination_ports' => $dst_ports,
                'interface' => $in_iface,
                'out_interface' => $out_iface,
                'priority' => $prio,
                'description' => $comment,
                'enabled' => true
            ];
            $res = MitraNetApi::request('/firewall/rule/add', 'POST', $payload);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Rule '$rid' berhasil ditambahkan ke kandidat.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menambahkan rule.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        } else {
            $err = 'Rule ID wajib diisi.';
            if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
        }
    } elseif ($action === 'delete_rule') {
        $rid = trim($_POST['id'] ?? '');
        if (!empty($rid)) {
            $res = MitraNetApi::request('/firewall/rule/delete', 'POST', ['id' => $rid]);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Rule '$rid' berhasil dihapus.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menghapus rule.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'toggle_rule') {
        $rid = trim($_POST['id'] ?? '');
        $en = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : null;
        if (!empty($rid)) {
            $res = MitraNetApi::toggleFirewallRule($rid, $en);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Status rule '$rid' berhasil diubah.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal mengubah status rule.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'comment_rule') {
        $rid = trim($_POST['id'] ?? '');
        $comment = trim($_POST['comment'] ?? '');
        if (!empty($rid)) {
            $res = MitraNetApi::commentFirewallRule($rid, $comment);
            if (($res['status'] ?? 0) === 200) {
                $msg = "Komentar rule '$rid' berhasil disimpan.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menyimpan komentar rule.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'apply_firewall') {
        $res = MitraNetApi::request('/firewall/apply', 'POST', []);
        if (($res['status'] ?? 0) === 200) {
            $msg = "Kandidat firewall berhasil diterapkan ke kernel Linux nftables.";
            if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
        } else {
            $err = $res['data']['error'] ?? 'Gagal menerapkan firewall.';
            if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
        }
    }

    // 2. NAT RULES
    elseif ($action === 'add_nat') {
        $rid = trim($_POST['id'] ?? '');
        $ntype = $_POST['nat_type'] ?? 'port_forward';
        $iface = $_POST['interface'] ?? 'any';
        $proto = $_POST['protocol'] ?? 'tcp';
        $src_ip = trim($_POST['src_ip'] ?? 'any');
        $dst_ip = trim($_POST['dst_ip'] ?? 'any');
        $dst_port = trim($_POST['dst_port'] ?? '');
        $target_ip = trim($_POST['target_ip'] ?? '');
        $target_port = trim($_POST['target_port'] ?? '');
        $descr = trim($_POST['descr'] ?? '');
        $prio = (int)($_POST['priority'] ?? 100);

        if (!empty($rid)) {
            $res = MitraNetApi::request('/firewall/nat/add', 'POST', [
                'id' => $rid,
                'nat_type' => $ntype,
                'interface' => $iface,
                'protocol' => $proto,
                'src_ip' => !empty($src_ip) ? $src_ip : 'any',
                'dst_ip' => !empty($dst_ip) ? $dst_ip : 'any',
                'dst_port' => !empty($dst_port) ? $dst_port : null,
                'target_ip' => !empty($target_ip) ? $target_ip : null,
                'target_port' => !empty($target_port) ? $target_port : null,
                'priority' => $prio,
                'description' => $descr
            ]);
            if (($res['status'] ?? 0) === 200) {
                $msg = "NAT Rule '$rid' berhasil ditambahkan.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menambahkan NAT rule.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    } elseif ($action === 'delete_nat') {
        $rid = trim($_POST['id'] ?? '');
        if (!empty($rid)) {
            $res = MitraNetApi::request('/firewall/nat/delete', 'POST', ['id' => $rid]);
            if (($res['status'] ?? 0) === 200) {
                $msg = "NAT Rule '$rid' berhasil dihapus.";
                if ($is_ajax) { echo json_encode(['success' => true, 'message' => $msg]); exit; }
            } else {
                $err = $res['data']['error'] ?? 'Gagal menghapus NAT rule.';
                if ($is_ajax) { echo json_encode(['success' => false, 'error' => $err]); exit; }
            }
        }
    }
}

// =========================================================================
// DATA FETCHING
// =========================================================================
$fw = MitraNetApi::getFirewall();
$status = $fw['status'] ?? [];
$running_rules = $fw['config']['rules'] ?? [];
$candidate_rules = $fw['candidate']['rules'] ?? [];
$running_nat = $fw['config']['nat_rules'] ?? [];
$candidate_nat = $fw['candidate']['nat_rules'] ?? [];

// Merge running and candidate rules for full visibility
$all_rules = !empty($candidate_rules) ? $candidate_rules : $running_rules;
$all_nat = !empty($candidate_nat) ? $candidate_nat : $running_nat;

// Interfaces list for selects
$ifaces_raw = MitraNetApi::getInterfaces();
$ifaces = is_array($ifaces_raw) ? $ifaces_raw : [];

// Address Lists / Aliases definition
$address_lists = [
    ['name' => 'RFC1918_Subnets', 'address' => '10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16', 'comment' => 'Private IPv4 Address Space (RFC 1918)', 'dynamic' => false],
    ['name' => 'Management_Net', 'address' => '192.168.56.0/24', 'comment' => 'MitraNet Host-Only Management Network', 'dynamic' => false],
    ['name' => 'WebGUI_Ports', 'address' => '8443, 8080, 80, 443', 'comment' => 'MitraNet WebUI & HTTPS Ports', 'dynamic' => false],
    ['name' => 'DNS_Servers', 'address' => '1.1.1.1, 8.8.8.8, 9.9.9.9', 'comment' => 'Upstream Trusted Public DNS Resolvers', 'dynamic' => false],
];

// Service Ports (Helpers)
$service_ports = [
    ['name' => 'ftp', 'ports' => '21', 'helper' => 'nf_nat_ftp / nf_conntrack_ftp', 'status' => 'active'],
    ['name' => 'tftp', 'ports' => '69', 'helper' => 'nf_conntrack_tftp', 'status' => 'active'],
    ['name' => 'irc', 'ports' => '6667', 'helper' => 'nf_conntrack_irc', 'status' => 'inactive'],
    ['name' => 'sip', 'ports' => '5060, 5061', 'helper' => 'nf_nat_sip / nf_conntrack_sip', 'status' => 'active'],
    ['name' => 'pptp', 'ports' => '1723', 'helper' => 'nf_nat_pptp / nf_conntrack_pptp', 'status' => 'active'],
    ['name' => 'snmp', 'ports' => '161, 162', 'helper' => 'nf_conntrack_snmp', 'status' => 'active'],
    ['name' => 'h323', 'ports' => '1720', 'helper' => 'nf_conntrack_h323', 'status' => 'inactive']
];

// Layer7 Protocols (DPI / L7 Regex signatures)
$layer7_protocols = [
    ['name' => 'http', 'regexp' => '^http/(0\\.9|1\\.0|1\\.1) [1-5][0-9][0-9]|get|post|head', 'comment' => 'HTTP Hypertext Transfer Protocol'],
    ['name' => 'tls_sni', 'regexp' => '^[\\x16\\x03][\\x00-\\x03].*[\\x00-\\x02]', 'comment' => 'TLS/SSL Client Hello Server Name Indication'],
    ['name' => 'dns', 'regexp' => '^.?.?.?.?[\\x01\\x02].?.?.?.?.?[\\x01-z][a-z0-9\\-_]{1,63}', 'comment' => 'DNS Standard Query / Response'],
    ['name' => 'bittorrent', 'regexp' => '^(\\x13bittorrent protocol|azver\\x01)', 'comment' => 'BitTorrent Peer-to-Peer Transfer Protocol'],
    ['name' => 'rdp', 'regexp' => '^[\\x03\\x00\\x00].*[\\x00-\\x04]', 'comment' => 'Remote Desktop Protocol (MS-RDP)']
];

// Conntrack Connections
$conntrack_data = MitraNetApi::getConntrackStates();
$conntrack_states = $conntrack_data['states'] ?? [];

// Mangle & Raw rules placeholders (Linux nftables inet mangle / raw tables)
$mangle_rules = [
    ['id' => 'm_clamp_mss_pmtu', 'action' => 'change-mss', 'chain' => 'forward', 'protocol' => 'tcp', 'src' => 'any', 'dst' => 'any', 'in_if' => 'any', 'out_if' => 'wan', 'bytes' => '1.2 MB', 'packets' => '14.2K', 'comment' => 'Clamp TCP MSS to PMTU', 'enabled' => true],
    ['id' => 'm_dscp_voip_high', 'action' => 'set-dscp', 'chain' => 'prerouting', 'protocol' => 'udp', 'src' => 'any', 'dst' => 'any', 'in_if' => 'lan', 'out_if' => 'any', 'bytes' => '840 KB', 'packets' => '5.6K', 'comment' => 'Mark VoIP SIP/RTP with DSCP EF', 'enabled' => true]
];

$raw_rules = [
    ['id' => 'raw_notrack_lo', 'action' => 'notrack', 'chain' => 'prerouting', 'src' => '127.0.0.0/8', 'dst' => '127.0.0.0/8', 'in_if' => 'lo', 'bytes' => '45.1 MB', 'packets' => '182.4K', 'comment' => 'Bypass connection tracking on loopback', 'enabled' => true],
    ['id' => 'raw_drop_bogon_syn', 'action' => 'drop', 'chain' => 'prerouting', 'src' => '0.0.0.0/8', 'dst' => 'any', 'in_if' => 'wan', 'bytes' => '0 B', 'packets' => '0', 'comment' => 'Drop Bogon IP source packets', 'enabled' => true]
];

if (!function_exists('fmt_bytes')) {
    function fmt_bytes(int $b): string {
        if ($b >= 1073741824) return number_format($b / 1073741824, 2) . ' GB';
        if ($b >= 1048576)    return number_format($b / 1048576, 1)    . ' MB';
        if ($b >= 1024)       return number_format($b / 1024, 1)       . ' KB';
        return $b . ' B';
    }
}
?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">

		<!-- ========================================================================= -->
		<!-- HEADER: BADGE + TABS (MATCHING SCREENSHOT)                                -->
		<!-- ========================================================================= -->
		<div class="mitranet-header">
			<div class="mitranet-title-badge">
				<i class="fa-solid fa-shield-halved"></i> Firewall
				<i class="fa-solid fa-caret-down"></i>
			</div>
			<ul class="mitranet-tabs">
				<li class="<?=($current_tab === 'filter') ? 'active' : ''?>">
					<a href="rules.php?tab=filter">Filter Rules</a>
				</li>
				<li class="<?=($current_tab === 'nat') ? 'active' : ''?>">
					<a href="rules.php?tab=nat">NAT</a>
				</li>
				<li class="<?=($current_tab === 'mangle') ? 'active' : ''?>">
					<a href="rules.php?tab=mangle">Mangle</a>
				</li>
				<li class="<?=($current_tab === 'raw') ? 'active' : ''?>">
					<a href="rules.php?tab=raw">Raw</a>
				</li>
				<li class="<?=($current_tab === 'service_ports') ? 'active' : ''?>">
					<a href="rules.php?tab=service_ports">Service Ports</a>
				</li>
				<li class="<?=($current_tab === 'connections') ? 'active' : ''?>">
					<a href="rules.php?tab=connections">Connections</a>
				</li>
				<li class="<?=($current_tab === 'address_lists') ? 'active' : ''?>">
					<a href="rules.php?tab=address_lists">Address Lists</a>
				</li>
				<li class="<?=($current_tab === 'layer7') ? 'active' : ''?>">
					<a href="rules.php?tab=layer7">Layer7 Protocols</a>
				</li>
			</ul>
		</div>

		<!-- ========================================================================= -->
		<!-- TOOLBAR (MATCHING SCREENSHOT)                                            -->
		<!-- ========================================================================= -->
		<div class="mitranet-toolbar">
			<div class="mitranet-toolbar-left">
				<?php if (in_array($current_tab, ['filter', 'nat', 'mangle', 'raw', 'address_lists', 'layer7'])): ?>
				<button type="button" class="mitranet-btn" onclick="openNewRuleModal()" title="Add New Item">
					<i class="fa-solid fa-folder-plus text-primary"></i> <strong>New</strong>
				</button>
				<?php endif; ?>
				<button type="button" class="mitranet-btn" id="btn-enable" disabled onclick="toggleSelectedRule(true)" title="Enable Selected">
					<i class="fa-solid fa-play text-muted"></i> Enable
				</button>
				<button type="button" class="mitranet-btn" id="btn-disable" disabled onclick="toggleSelectedRule(false)" title="Disable Selected">
					<i class="fa-solid fa-pause text-muted"></i> Disable
				</button>
				<button type="button" class="mitranet-btn" id="btn-remove" disabled onclick="removeSelectedRule()" title="Remove Selected">
					<i class="fa-solid fa-xmark text-muted"></i> Remove
				</button>
				<button type="button" class="mitranet-btn" id="btn-comment" disabled onclick="commentSelectedRule()" title="Comment">
					<i class="fa-regular fa-flag text-muted"></i> Comment
				</button>
				<?php if (!empty($candidate_rules) || !empty($candidate_nat)): ?>
				<button type="button" class="mitranet-btn btn-success text-white" onclick="applyFirewallCandidate()" title="Apply candidate changes to kernel nftables">
					<i class="fa-solid fa-check"></i> <strong>Apply Changes</strong>
				</button>
				<?php endif; ?>
			</div>
			<div class="mitranet-toolbar-right">
				<div class="mitranet-search-wrapper">
					<i class="fa-solid fa-magnifying-glass"></i>
					<input type="text" id="grid-search" placeholder="Find" onkeyup="filterFirewallGrid(this.value)">
				</div>
				<button type="button" class="mitranet-btn" onclick="$('#grid-search').focus()" title="Filter">
					<i class="fa-solid fa-filter text-muted"></i> Filter
				</button>
				<?php if ($current_tab === 'filter'): ?>
				<select id="chain-filter-select" class="form-control input-sm mitranet-toolbar-select" onchange="filterByChain(this.value)" title="Filter by Chain">
					<option value="all">all</option>
					<option value="input">input</option>
					<option value="forward">forward</option>
					<option value="output">output</option>
				</select>
				<?php endif; ?>
				<button type="button" class="mitranet-btn" onclick="location.reload()" title="Refresh">
					<i class="fa-solid fa-arrows-rotate"></i>
				</button>
			</div>
		</div>

		<!-- ========================================================================= -->
		<!-- TAB 1: FILTER RULES DATA GRID (MATCHING SCREENSHOT COLUMNS)               -->
		<!-- Columns: # | Flag | Action | Chain | Src. Address | Dst. Address |       -->
		<!--          Src. Port | Dst. Port | Any. Port | In. Interface |              -->
		<!--          Out. Interface | Bytes | Packets                                 -->
		<!-- ========================================================================= -->
		<?php if ($current_tab === 'filter'): ?>
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="firewall-grid-table">
				<thead>
					<tr>
						<th class="sortable text-center" style="width: 32px;"># <i class="fa-solid fa-caret-up"></i></th>
						<th class="text-center col-flag" style="width: 30px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 90px;">Action</th>
						<th class="sortable" style="width: 80px;">Chain</th>
						<th class="sortable" style="width: 140px;">Src. Address</th>
						<th class="sortable" style="width: 140px;">Dst. Address</th>
						<th class="sortable" style="width: 80px;">Protocol</th>
						<th class="sortable" style="width: 90px;">Src. Port</th>
						<th class="sortable" style="width: 90px;">Dst. Port</th>
						<th class="sortable" style="width: 90px;">Any. Port</th>
						<th class="sortable" style="width: 110px;">In. Interface</th>
						<th class="sortable" style="width: 110px;">Out. Interface</th>
						<th class="sortable text-right" style="width: 85px;">Bytes</th>
						<th class="sortable text-right" style="width: 85px;">Packets</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($all_rules)): ?>
					<tr>
						<td colspan="14" class="text-center text-muted" style="padding: 24px;">
							<i class="fa-solid fa-shield-halved fs-18 mb-2"></i><br>
							Belum ada filter rules yang dikonfigurasi. Klik <strong>New</strong> untuk menambahkan rule packet filtering.
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($all_rules as $idx => $r): ?>
					<?php
					$r_id = htmlspecialchars($r['id'] ?? '');
					$r_enabled = !empty($r['enabled']);
					$r_action = strtolower($r['action'] ?? 'accept');
					$r_chain = htmlspecialchars($r['chain'] ?? 'input');
					$r_proto = strtoupper($r['protocol'] ?? 'any');
					$r_src = htmlspecialchars($r['source'] ?? 'any');
					$r_dst = htmlspecialchars($r['destination'] ?? 'any');
					$r_src_ports = !empty($r['source_ports']) ? htmlspecialchars(implode(',', (array)$r['source_ports'])) : '';
					$r_dst_ports = !empty($r['destination_ports']) ? htmlspecialchars(implode(',', (array)$r['destination_ports'])) : '';
					$r_any_port = '';
					$r_in_if = htmlspecialchars($r['interface'] ?? 'any');
					$r_out_if = htmlspecialchars($r['out_interface'] ?? 'any');
					$r_comment = htmlspecialchars($r['description'] ?? '');
					$r_bytes = (int)($r['bytes'] ?? 0);
					$r_packets = (int)($r['packets'] ?? 0);

					$action_badge = 'label-success';
					if ($r_action === 'drop') $action_badge = 'label-danger';
					elseif ($r_action === 'reject') $action_badge = 'label-warning';
					?>
					<tr data-ruleid="<?=$r_id?>" data-chain="<?=$r_chain?>" data-enabled="<?=$r_enabled ? '1' : '0'?>" onclick="selectRuleRow(this, '<?=$r_id?>', '<?=addslashes($r_comment)?>', <?=$r_enabled ? 'true' : 'false'?>)" ondblclick="openEditRuleModal('<?=$r_id?>')" style="cursor: pointer;" class="<?=$r_enabled ? '' : 'text-muted'?>">
						<!-- # (Index) -->
						<td class="text-center font-monospace"><?=$idx?></td>
						<!-- Flag (Flag / Disabled status) -->
						<td class="text-center col-flag-cell">
							<?php if (!$r_enabled): ?>
								<span class="badge" style="background: #e2e8f0; color: #64748b; font-size: 10px; font-weight: 700; padding: 1px 4px; border: 1px solid #cbd5e1;" title="Disabled (X)">X</span>
							<?php elseif (!empty($r_comment)): ?>
								<i class="fa-solid fa-flag text-primary" title="<?=htmlspecialchars($r_comment)?>"></i>
							<?php else: ?>
								<span class="text-muted" style="font-size: 10px;">-</span>
							<?php endif; ?>
						</td>
						<!-- Action -->
						<td>
							<span class="label <?=$action_badge?>" style="font-size: 10px; text-transform: lowercase; font-weight: 600; padding: 2px 6px;">
								<?=htmlspecialchars($r_action)?>
							</span>
						</td>
						<!-- Chain -->
						<td><strong class="font-monospace text-primary"><?=$r_chain?></strong></td>
						<!-- Src. Address -->
						<td class="font-monospace"><?=$r_src === 'any' ? '' : $r_src?></td>
						<!-- Dst. Address -->
						<td class="font-monospace"><?=$r_dst === 'any' ? '' : $r_dst?></td>
						<!-- Protocol -->
						<td><span class="badge badge-default" style="font-size: 10px;"><?=htmlspecialchars($r_proto)?></span></td>
						<!-- Src. Port -->
						<td class="font-monospace text-muted"><?=$r_src_ports?></td>
						<!-- Dst. Port -->
						<td class="font-monospace text-muted"><?=$r_dst_ports?></td>
						<!-- Any. Port -->
						<td class="font-monospace text-muted"><?=$r_any_port?></td>
						<!-- In. Interface -->
						<td><?=$r_in_if === 'any' ? '' : '<strong>' . $r_in_if . '</strong>'?></td>
						<!-- Out. Interface -->
						<td><?=$r_out_if === 'any' ? '' : '<strong>' . $r_out_if . '</strong>'?></td>
						<!-- Bytes -->
						<td class="text-right font-monospace"><?=$r_bytes > 0 ? fmt_bytes($r_bytes) : '0'?></td>
						<!-- Packets -->
						<td class="text-right font-monospace"><?=$r_packets > 0 ? number_format($r_packets) : '0'?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR FILTER RULES -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($all_rules)?> items (<?=count($candidate_rules)?> candidate, <?=count($running_rules)?> active)</span>
			</div>
			<div>
				<span class="text-muted">Linux nftables Filter Engine • Default Policies: input <code><?=$status['input_policy'] ?? 'drop'?></code>, forward <code><?=$status['forward_policy'] ?? 'drop'?></code></span>
			</div>
		</div>

		<!-- ========================================================================= -->
		<!-- TAB 2: NAT DATA GRID                                                      -->
		<!-- ========================================================================= -->
		<?php elseif ($current_tab === 'nat'): ?>
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="nat-grid-table">
				<thead>
					<tr>
						<th class="sortable text-center" style="width: 32px;">#</th>
						<th class="text-center col-flag" style="width: 30px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 100px;">Action / Type</th>
						<th class="sortable" style="width: 80px;">Chain</th>
						<th class="sortable" style="width: 140px;">Src. Address</th>
						<th class="sortable" style="width: 140px;">Dst. Address</th>
						<th class="sortable" style="width: 80px;">Protocol</th>
						<th class="sortable" style="width: 90px;">Dst. Port</th>
						<th class="sortable" style="width: 140px;">To Addresses</th>
						<th class="sortable" style="width: 90px;">To Ports</th>
						<th class="sortable" style="width: 110px;">In. Interface</th>
						<th class="sortable" style="width: 110px;">Out. Interface</th>
						<th class="sortable">Comment</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($all_nat)): ?>
					<tr>
						<td colspan="13" class="text-center text-muted" style="padding: 24px;">
							<i class="fa-solid fa-arrows-split-up-and-left fs-18 mb-2"></i><br>
							Belum ada NAT rules yang dikonfigurasi. Klik <strong>New</strong> untuk menambahkan Port Forward (dstnat) atau Masquerade (srcnat).
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($all_nat as $idx => $n): ?>
					<?php
					$n_id = htmlspecialchars($n['id'] ?? '');
					$n_type = $n['nat_type'] ?? 'port_forward';
					$n_chain = ($n_type === 'outbound' || !empty($n['masquerade'])) ? 'srcnat' : 'dstnat';
					$n_action = ($n_type === 'outbound' || !empty($n['masquerade'])) ? 'masquerade' : 'dst-nat';
					$n_proto = strtoupper($n['protocol'] ?? 'tcp');
					$n_src = htmlspecialchars($n['src_ip'] ?? 'any');
					$n_dst = htmlspecialchars($n['dst_ip'] ?? 'any');
					$n_dst_port = htmlspecialchars((string)($n['dst_port'] ?? ''));
					$n_to_ip = htmlspecialchars((string)($n['target_ip'] ?? ''));
					$n_to_port = htmlspecialchars((string)($n['target_port'] ?? ''));
					$n_iface = htmlspecialchars($n['interface'] ?? 'any');
					$n_comment = htmlspecialchars($n['description'] ?? '');
					?>
					<tr data-natid="<?=$n_id?>" onclick="selectNatRow(this, '<?=$n_id?>')" style="cursor: pointer;">
						<td class="text-center font-monospace"><?=$idx?></td>
						<td class="text-center col-flag-cell">
							<?php if (!empty($n_comment)): ?>
								<i class="fa-solid fa-flag text-primary" title="<?=$n_comment?>"></i>
							<?php else: ?>
								<span class="text-muted">-</span>
							<?php endif; ?>
						</td>
						<td><span class="label label-info" style="font-size: 10px;"><?=$n_action?></span></td>
						<td><strong class="font-monospace text-primary"><?=$n_chain?></strong></td>
						<td class="font-monospace"><?=$n_src === 'any' ? '' : $n_src?></td>
						<td class="font-monospace"><?=$n_dst === 'any' ? '' : $n_dst?></td>
						<td><span class="badge badge-default" style="font-size: 10px;"><?=$n_proto?></span></td>
						<td class="font-monospace"><?=$n_dst_port?></td>
						<td class="font-monospace text-primary"><strong><?=$n_to_ip?></strong></td>
						<td class="font-monospace text-primary"><strong><?=$n_to_port?></strong></td>
						<td><?=$n_chain === 'dstnat' && $n_iface !== 'any' ? $n_iface : ''?></td>
						<td><?=$n_chain === 'srcnat' && $n_iface !== 'any' ? $n_iface : ''?></td>
						<td class="text-muted"><?=$n_comment?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- STATUSBAR NAT -->
		<div class="mitranet-statusbar">
			<div>
				<span><strong>Total:</strong> <?=count($all_nat)?> NAT rules</span>
			</div>
			<div>
				<span class="text-muted">Network Address Translation (DNAT / SNAT / Masquerade)</span>
			</div>
		</div>

		<!-- ========================================================================= -->
		<!-- TAB 3: MANGLE DATA GRID                                                   -->
		<!-- ========================================================================= -->
		<?php elseif ($current_tab === 'mangle'): ?>
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="mangle-grid-table">
				<thead>
					<tr>
						<th class="sortable text-center" style="width: 32px;">#</th>
						<th class="text-center col-flag" style="width: 30px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 110px;">Action</th>
						<th class="sortable" style="width: 90px;">Chain</th>
						<th class="sortable" style="width: 140px;">Src. Address</th>
						<th class="sortable" style="width: 140px;">Dst. Address</th>
						<th class="sortable" style="width: 80px;">Protocol</th>
						<th class="sortable" style="width: 110px;">In. Interface</th>
						<th class="sortable" style="width: 110px;">Out. Interface</th>
						<th class="sortable text-right" style="width: 85px;">Bytes</th>
						<th class="sortable text-right" style="width: 85px;">Packets</th>
						<th class="sortable">Comment</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($mangle_rules as $idx => $m): ?>
					<tr style="cursor: pointer;">
						<td class="text-center font-monospace"><?=$idx?></td>
						<td class="text-center col-flag-cell"><i class="fa-solid fa-flag text-primary"></i></td>
						<td><span class="label label-warning" style="font-size: 10px;"><?=$m['action']?></span></td>
						<td><strong class="font-monospace text-primary"><?=$m['chain']?></strong></td>
						<td class="font-monospace"><?=$m['src'] === 'any' ? '' : $m['src']?></td>
						<td class="font-monospace"><?=$m['dst'] === 'any' ? '' : $m['dst']?></td>
						<td><span class="badge badge-default" style="font-size: 10px;"><?=strtoupper($m['protocol'])?></span></td>
						<td><?=$m['in_if'] === 'any' ? '' : $m['in_if']?></td>
						<td><?=$m['out_if'] === 'any' ? '' : $m['out_if']?></td>
						<td class="text-right font-monospace"><?=$m['bytes']?></td>
						<td class="text-right font-monospace"><?=$m['packets']?></td>
						<td class="text-muted"><?=$m['comment']?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="mitranet-statusbar">
			<div><span><strong>Total:</strong> <?=count($mangle_rules)?> mangle rules</span></div>
			<div><span class="text-muted">Packet Modification, DSCP QoS Marking &amp; MSS Clamping</span></div>
		</div>

		<!-- ========================================================================= -->
		<!-- TAB 4: RAW DATA GRID                                                      -->
		<!-- ========================================================================= -->
		<?php elseif ($current_tab === 'raw'): ?>
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="raw-grid-table">
				<thead>
					<tr>
						<th class="sortable text-center" style="width: 32px;">#</th>
						<th class="text-center col-flag" style="width: 30px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 100px;">Action</th>
						<th class="sortable" style="width: 90px;">Chain</th>
						<th class="sortable" style="width: 140px;">Src. Address</th>
						<th class="sortable" style="width: 140px;">Dst. Address</th>
						<th class="sortable" style="width: 110px;">In. Interface</th>
						<th class="sortable text-right" style="width: 85px;">Bytes</th>
						<th class="sortable text-right" style="width: 85px;">Packets</th>
						<th class="sortable">Comment</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($raw_rules as $idx => $rw): ?>
					<tr style="cursor: pointer;">
						<td class="text-center font-monospace"><?=$idx?></td>
						<td class="text-center col-flag-cell"><i class="fa-solid fa-flag text-primary"></i></td>
						<td><span class="label <?=($rw['action'] === 'drop' ? 'label-danger' : 'label-default')?>" style="font-size: 10px;"><?=$rw['action']?></span></td>
						<td><strong class="font-monospace text-primary"><?=$rw['chain']?></strong></td>
						<td class="font-monospace"><?=$rw['src']?></td>
						<td class="font-monospace"><?=$rw['dst'] === 'any' ? '' : $rw['dst']?></td>
						<td><?=$rw['in_if'] === 'any' ? '' : $rw['in_if']?></td>
						<td class="text-right font-monospace"><?=$rw['bytes']?></td>
						<td class="text-right font-monospace"><?=$rw['packets']?></td>
						<td class="text-muted"><?=$rw['comment']?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="mitranet-statusbar">
			<div><span><strong>Total:</strong> <?=count($raw_rules)?> raw rules</span></div>
			<div><span class="text-muted">Pre-routing State Bypass &amp; High Performance Anti-DoS</span></div>
		</div>

		<!-- ========================================================================= -->
		<!-- TAB 5: SERVICE PORTS (HELPERS)                                            -->
		<!-- ========================================================================= -->
		<?php elseif ($current_tab === 'service_ports'): ?>
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="service-ports-grid-table">
				<thead>
					<tr>
						<th class="sortable text-center" style="width: 32px;">#</th>
						<th class="text-center col-flag" style="width: 30px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 140px;">Name</th>
						<th class="sortable" style="width: 140px;">Ports</th>
						<th class="sortable" style="width: 260px;">Kernel Netfilter Helper Module</th>
						<th class="sortable" style="width: 100px;">Status</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($service_ports as $idx => $sp): ?>
					<tr style="cursor: pointer;">
						<td class="text-center font-monospace"><?=$idx?></td>
						<td class="text-center col-flag-cell">
							<i class="fa-solid fa-check text-success"></i>
						</td>
						<td><strong class="text-primary"><?=$sp['name']?></strong></td>
						<td class="font-monospace"><?=$sp['ports']?></td>
						<td class="font-monospace text-muted"><?=$sp['helper']?></td>
						<td>
							<span class="badge <?=($sp['status'] === 'active' ? 'badge-success' : 'badge-default')?>">
								<?=strtoupper($sp['status'])?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="mitranet-statusbar">
			<div><span><strong>Total:</strong> <?=count($service_ports)?> helper services</span></div>
			<div><span class="text-muted">Application Layer Gateways (ALG) Connection Tracking Helpers</span></div>
		</div>

		<!-- ========================================================================= -->
		<!-- TAB 6: CONNECTIONS (CONNTRACK STATE TABLE)                                -->
		<!-- ========================================================================= -->
		<?php elseif ($current_tab === 'connections'): ?>
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="connections-grid-table">
				<thead>
					<tr>
						<th class="sortable text-center" style="width: 32px;">#</th>
						<th class="sortable" style="width: 80px;">Protocol</th>
						<th class="sortable" style="width: 160px;">Src. Address</th>
						<th class="sortable" style="width: 80px;">Src. Port</th>
						<th class="sortable" style="width: 160px;">Dst. Address</th>
						<th class="sortable" style="width: 80px;">Dst. Port</th>
						<th class="sortable" style="width: 110px;">TCP State</th>
						<th class="sortable">Raw Conntrack Tuple</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($conntrack_states)): ?>
					<tr>
						<td colspan="8" class="text-center text-muted" style="padding: 24px;">
							<i class="fa-solid fa-network-wired fs-18 mb-2"></i><br>
							Tidak ada state koneksi aktif dalam netfilter table (/proc/net/nf_conntrack).
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($conntrack_states as $idx => $st): ?>
					<?php
					$c_raw = $st['raw'] ?? '';
					$c_proto = strtoupper($st['protocol'] ?? 'TCP');
					$c_src = '-';
					$c_dst = '-';
					$c_sport = '-';
					$c_dport = '-';
					$c_state = '-';

					// Parse conntrack raw line
					if (preg_match('/src=([0-9a-fA-F\\.:]+)/', $c_raw, $m)) $c_src = $m[1];
					if (preg_match('/dst=([0-9a-fA-F\\.:]+)/', $c_raw, $m)) $c_dst = $m[1];
					if (preg_match('/sport=([0-9]+)/', $c_raw, $m)) $c_sport = $m[1];
					if (preg_match('/dport=([0-9]+)/', $c_raw, $m)) $c_dport = $m[1];
					if (preg_match('/(ESTABLISHED|SYN_SENT|SYN_RECV|TIME_WAIT|CLOSE|CLOSE_WAIT|FIN_WAIT|LAST_ACK)/', $c_raw, $m)) $c_state = $m[1];
					?>
					<tr>
						<td class="text-center font-monospace"><?=$idx?></td>
						<td><span class="badge badge-primary font-monospace" style="font-size: 10px;"><?=$c_proto?></span></td>
						<td class="font-monospace"><?=$c_src?></td>
						<td class="font-monospace text-muted"><?=$c_sport?></td>
						<td class="font-monospace"><?=$c_dst?></td>
						<td class="font-monospace text-muted"><?=$c_dport?></td>
						<td>
							<span class="label <?=($c_state === 'ESTABLISHED' ? 'label-success' : 'label-default')?>" style="font-size: 10px;">
								<?=$c_state?>
							</span>
						</td>
						<td class="font-monospace text-muted fs-11" style="max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
							<?=htmlspecialchars($c_raw)?>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<div class="mitranet-statusbar">
			<div><span><strong>Total:</strong> <?=count($conntrack_states)?> active connections</span></div>
			<div><span class="text-muted">Live Linux Netfilter Connection Tracking Table (/proc/net/nf_conntrack)</span></div>
		</div>

		<!-- ========================================================================= -->
		<!-- TAB 7: ADDRESS LISTS (ALIASES & IP SETS)                                  -->
		<!-- ========================================================================= -->
		<?php elseif ($current_tab === 'address_lists'): ?>
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="address-lists-grid-table">
				<thead>
					<tr>
						<th class="sortable text-center" style="width: 32px;">#</th>
						<th class="text-center col-flag" style="width: 30px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 180px;">Name</th>
						<th class="sortable" style="width: 320px;">Address / Network</th>
						<th class="sortable" style="width: 100px;">Dynamic</th>
						<th class="sortable">Comment</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($address_lists as $idx => $al): ?>
					<tr style="cursor: pointer;">
						<td class="text-center font-monospace"><?=$idx?></td>
						<td class="text-center col-flag-cell"><i class="fa-solid fa-flag text-primary"></i></td>
						<td><strong class="text-primary font-monospace"><?=$al['name']?></strong></td>
						<td class="font-monospace"><code><?=$al['address']?></code></td>
						<td><span class="badge badge-default"><?=!empty($al['dynamic']) ? 'yes' : 'no'?></span></td>
						<td class="text-muted"><?=$al['comment']?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="mitranet-statusbar">
			<div><span><strong>Total:</strong> <?=count($address_lists)?> address list groups</span></div>
			<div><span class="text-muted">Firewall IP Sets &amp; Subnet Aliases (Reusable in Rules and NAT)</span></div>
		</div>

		<!-- ========================================================================= -->
		<!-- TAB 8: LAYER7 PROTOCOLS (DPI REGEX)                                       -->
		<!-- ========================================================================= -->
		<?php elseif ($current_tab === 'layer7'): ?>
		<div class="mitranet-grid-container">
			<table class="mitranet-grid" id="layer7-grid-table">
				<thead>
					<tr>
						<th class="sortable text-center" style="width: 32px;">#</th>
						<th class="text-center col-flag" style="width: 30px;"><i class="fa-regular fa-flag"></i></th>
						<th class="sortable" style="width: 160px;">Name</th>
						<th class="sortable" style="width: 360px;">Regexp Pattern</th>
						<th class="sortable">Comment</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($layer7_protocols as $idx => $l7): ?>
					<tr style="cursor: pointer;">
						<td class="text-center font-monospace"><?=$idx?></td>
						<td class="text-center col-flag-cell"><i class="fa-solid fa-flag text-primary"></i></td>
						<td><strong class="text-primary font-monospace"><?=$l7['name']?></strong></td>
						<td class="font-monospace"><code><?=htmlspecialchars($l7['regexp'])?></code></td>
						<td class="text-muted"><?=$l7['comment']?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="mitranet-statusbar">
			<div><span><strong>Total:</strong> <?=count($layer7_protocols)?> Layer7 patterns</span></div>
			<div><span class="text-muted">Application Layer Deep Packet Inspection Regexp Patterns</span></div>
		</div>

		<?php endif; ?>

	</div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: ADD / EDIT FILTER RULE (WINBOX STYLE TWO-COLUMN)                   -->
<!-- ========================================================================= -->
<div class="modal fade" id="modal-rule-winbox" tabindex="-1" role="dialog" aria-labelledby="modal-rule-title">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content" style="border-radius: 4px; box-shadow: 0 5px 25px rgba(0,0,0,0.3);">
			<form id="form-rule-winbox" method="post" action="rules.php?tab=filter">
				<input type="hidden" name="action" value="add_rule">
				<div class="modal-header" style="background: #eef4f9; border-bottom: 1px solid #abc1da; padding: 10px 15px;">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="modal-rule-title" style="font-size: 13px; font-weight: 700; color: #1e395b;">
						<i class="fa-solid fa-shield-halved text-primary mr-1"></i> New Firewall Rule
					</h4>
				</div>
				<div class="modal-body" style="padding: 15px; background: #fbfcfe;">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group mb-10">
								<label class="control-label fs-11">Rule ID *</label>
								<input type="text" name="id" id="modal-rule-id" class="form-control input-sm font-monospace" placeholder="e.g. allow_lan_web" required>
							</div>
							<div class="form-group mb-10">
								<label class="control-label fs-11">Chain *</label>
								<select name="chain" id="modal-rule-chain" class="form-control input-sm">
									<option value="input" selected>input</option>
									<option value="forward">forward</option>
									<option value="output">output</option>
								</select>
							</div>
							<div class="form-group mb-10">
								<label class="control-label fs-11">Action *</label>
								<select name="rule_action" id="modal-rule-action" class="form-control input-sm">
									<option value="accept" selected>accept</option>
									<option value="drop">drop</option>
									<option value="reject">reject</option>
								</select>
							</div>
							<div class="form-group mb-10">
								<label class="control-label fs-11">Protocol</label>
								<select name="protocol" id="modal-rule-proto" class="form-control input-sm">
									<option value="any" selected>any</option>
									<option value="tcp">tcp</option>
									<option value="udp">udp</option>
									<option value="icmp">icmp</option>
									<option value="gre">gre</option>
									<option value="esp">esp</option>
								</select>
							</div>
							<div class="form-group mb-10">
								<label class="control-label fs-11">Priority</label>
								<input type="number" name="priority" id="modal-rule-priority" class="form-control input-sm" value="100" min="1" max="65535">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group mb-10">
								<label class="control-label fs-11">Src. Address</label>
								<input type="text" name="source" id="modal-rule-src" class="form-control input-sm font-monospace" placeholder="any or 192.168.1.0/24">
							</div>
							<div class="form-group mb-10">
								<label class="control-label fs-11">Dst. Address</label>
								<input type="text" name="destination" id="modal-rule-dst" class="form-control input-sm font-monospace" placeholder="any or 10.0.0.1">
							</div>
							<div class="form-group mb-10">
								<label class="control-label fs-11">Dst. Port(s)</label>
								<input type="text" name="dst_ports" id="modal-rule-dst-ports" class="form-control input-sm font-monospace" placeholder="e.g. 80,443">
							</div>
							<div class="form-group mb-10">
								<label class="control-label fs-11">In. Interface</label>
								<select name="in_interface" id="modal-rule-in-if" class="form-control input-sm">
									<option value="any" selected>any</option>
									<?php foreach ($ifaces as $i): ?>
										<option value="<?=htmlspecialchars($i['name'])?>"><?=htmlspecialchars(strtoupper($i['name']))?> (<?=htmlspecialchars($i['name'])?>)</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="form-group mb-10">
								<label class="control-label fs-11">Comment</label>
								<input type="text" name="comment" id="modal-rule-comment" class="form-control input-sm" placeholder="Catatan deskripsi rule...">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer" style="background: #eef4f9; border-top: 1px solid #abc1da; padding: 8px 15px;">
					<button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-check"></i> OK</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
window.selectedRuleId = null;
window.selectedRuleComment = '';
window.selectedRuleEnabled = true;

// Grid Row Selection
window.selectRuleRow = function(tr, ruleId, comment, isEnabled) {
	$('#firewall-grid-table tbody tr').removeClass('selected');
	$(tr).addClass('selected');
	selectedRuleId = ruleId;
	selectedRuleComment = comment || '';
	selectedRuleEnabled = (isEnabled === true || isEnabled === 1);

	$('#btn-remove, #btn-comment').prop('disabled', false);

	if (selectedRuleEnabled) {
		$('#btn-enable').prop('disabled', true);
		$('#btn-disable').prop('disabled', false);
	} else {
		$('#btn-enable').prop('disabled', false);
		$('#btn-disable').prop('disabled', true);
	}
};

window.openNewRuleModal = function() {
	$('#form-rule-winbox')[0].reset();
	$('#modal-rule-id').prop('readonly', false);
	$('#modal-rule-title').html('<i class="fa-solid fa-shield-halved text-primary mr-1"></i> New Firewall Rule');
	$('#modal-rule-winbox').modal('show');
};

window.openEditRuleModal = function(ruleId) {
	if (!ruleId) ruleId = selectedRuleId;
	if (!ruleId) return;
	// Populate existing attributes from tr
	var $tr = $('tr[data-ruleid="' + ruleId + '"]');
	if ($tr.length) {
		$('#modal-rule-id').val(ruleId).prop('readonly', true);
		$('#modal-rule-chain').val($tr.attr('data-chain') || 'input');
		$('#modal-rule-comment').val(selectedRuleComment);
		$('#modal-rule-title').html('<i class="fa-solid fa-pencil text-primary mr-1"></i> Firewall Rule &lt;' + ruleId + '&gt;');
		$('#modal-rule-winbox').modal('show');
	}
};

window.toggleSelectedRule = function(enable) {
	if (!selectedRuleId) return;
	$.post('rules.php?tab=filter', {
		ajax: 1,
		action: 'toggle_rule',
		id: selectedRuleId,
		enabled: enable ? 1 : 0
	}, function(res) {
		if (res && res.success) {
			MitraNet.toast('success', "Rule '" + selectedRuleId + "' " + (enable ? 'diaktifkan' : 'dinonaktifkan'));
			setTimeout(function() { location.reload(); }, 600);
		} else {
			MitraNet.toast('error', res && res.error ? res.error : 'Gagal mengubah status rule');
		}
	}, 'json');
};

window.removeSelectedRule = function() {
	if (!selectedRuleId) return;
	MitraNet.confirmDelete({
		title: 'Hapus Firewall Rule?',
		name: selectedRuleId,
		warning: 'Rule akan dihapus dari konfigurasi kandidat firewall.',
		url: 'rules.php?tab=filter',
		data: { action: 'delete_rule', id: selectedRuleId },
		onSuccess: function() {
			$('tr[data-ruleid="' + selectedRuleId + '"]').fadeOut(300, function() { $(this).remove(); });
			$('#btn-enable, #btn-disable, #btn-remove, #btn-comment').prop('disabled', true);
			selectedRuleId = null;
		}
	});
};

window.commentSelectedRule = function() {
	if (!selectedRuleId) return;
	MitraNet.promptInput({
		title: 'Komentar Rule ' + selectedRuleId,
		value: selectedRuleComment,
		placeholder: 'Tulis komentar deskripsi...',
		url: 'rules.php?tab=filter',
		data: { action: 'comment_rule', id: selectedRuleId },
		inputKey: 'comment',
		onSuccess: function() {
			location.reload();
		}
	});
};

window.applyFirewallCandidate = function() {
	$.post('rules.php?tab=filter', {
		ajax: 1,
		action: 'apply_firewall'
	}, function(res) {
		if (res && res.success) {
			MitraNet.toast('success', 'Konfigurasi firewall berhasil diterapkan ke Linux nftables!');
			setTimeout(function() { location.reload(); }, 800);
		} else {
			MitraNet.toast('error', res && res.error ? res.error : 'Gagal menerapkan perubahan firewall');
		}
	}, 'json');
};

window.filterFirewallGrid = function(query) {
	query = (query || '').toLowerCase().trim();
	$('.mitranet-grid tbody tr').each(function() {
		var text = $(this).text().toLowerCase();
		$(this).toggle(text.indexOf(query) !== -1);
	});
};

window.filterByChain = function(chain) {
	if (!chain || chain === 'all') {
		$('#firewall-grid-table tbody tr').show();
	} else {
		$('#firewall-grid-table tbody tr').each(function() {
			var rowChain = ($(this).attr('data-chain') || '').toLowerCase();
			$(this).toggle(rowChain === chain.toLowerCase());
		});
	}
};
</script>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
