<?php
/*
 * firewall_nat_out.php - MitraNet Outbound NAT (SNAT / Masquerade) Management
 * Adapted from pfSense firewall_nat_out.php
 * Strictly communicates via REST API -> MitraNet Native nftables Engine.
 */

$pgtitle = array(gettext("Firewall"), gettext("NAT"), gettext("Outbound"));
$selected_menu = "firewall";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $rid = trim($_POST['id'] ?? '');
        $iface = $_POST['interface'] ?? 'enp0s3';
        $proto = $_POST['protocol'] ?? 'any';
        $src_ip = trim($_POST['src_ip'] ?? '192.168.56.0/24');
        $dst_ip = trim($_POST['dst_ip'] ?? 'any');
        $target_ip = trim($_POST['target_ip'] ?? '');
        $masq = isset($_POST['masquerade']) ? true : false;
        $descr = trim($_POST['descr'] ?? '');
        $prio = (int)($_POST['priority'] ?? 100);

        if (!empty($rid)) {
            $res = MitraNetApi::request('/firewall/nat/add', 'POST', [
                'id' => $rid,
                'nat_type' => 'outbound',
                'interface' => $iface,
                'protocol' => $proto,
                'src_ip' => !empty($src_ip) ? $src_ip : 'any',
                'dst_ip' => !empty($dst_ip) ? $dst_ip : 'any',
                'target_ip' => !empty($target_ip) ? $target_ip : null,
                'masquerade' => $masq || empty($target_ip),
                'priority' => $prio,
                'description' => $descr
            ]);
            if ($res['status'] === 200) {
                $msg = "Outbound NAT Rule '$rid' added to candidate configuration";
            } else {
                $err = $res['data']['error'] ?? 'Failed to add Outbound NAT rule';
            }
        } else {
            $err = 'NAT Rule ID is required';
        }
    } elseif ($action === 'delete') {
        $rid = trim($_POST['id'] ?? '');
        if (!empty($rid)) {
            $res = MitraNetApi::request('/firewall/nat/delete', 'POST', ['id' => $rid]);
            if ($res['status'] === 200) {
                $msg = "NAT Rule '$rid' deleted from candidate configuration";
            } else {
                $err = $res['data']['error'] ?? 'Failed to delete NAT rule';
            }
        }
    } elseif ($action === 'apply') {
        $res = MitraNetApi::request('/firewall/apply', 'POST', []);
        if ($res['status'] === 200) {
            $msg = "Outbound NAT rules applied and committed to Linux kernel nftables successfully";
        } else {
            $err = $res['data']['error'] ?? 'Failed to apply NAT rules';
        }
    }
}

$fw = MitraNetApi::getFirewall();
$running_nat = array_filter($fw['config']['nat_rules'] ?? [], fn($r) => ($r['nat_type'] ?? '') === 'outbound');
$candidate_nat = array_filter($fw['candidate']['nat_rules'] ?? [], fn($r) => ($r['nat_type'] ?? '') === 'outbound');
$ifaces = MitraNetApi::getInterfaces();
$tab_array = array();
$tab_array[] = array(gettext("Port Forward"), false, "firewall_nat.php");
$tab_array[] = array(gettext("1:1"), false, "firewall_nat_1to1.php");
$tab_array[] = array(gettext("Outbound"), true, "firewall_nat_out.php");
$tab_array[] = array(gettext("NPt"), false, "firewall_nat_npt.php");
display_top_tabs($tab_array);

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<!-- Running NAT Ruleset -->
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Active Outbound NAT Rules (SNAT / Masquerade)")?></h2></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>ID</th>
					<th>Interface</th>
					<th>Source Subnet</th>
					<th>Destination</th>
					<th>Translation / SNAT</th>
					<th>Description</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($running_nat)): ?>
					<tr><td colspan="6" class="text-center text-muted">No custom outbound NAT rules defined in running ruleset</td></tr>
				<?php else: foreach ($running_nat as $nr): ?>
					<tr>
						<td><strong><?=htmlspecialchars($nr['id'])?></strong></td>
						<td><?=htmlspecialchars($nr['interface'])?></td>
						<td><code><?=htmlspecialchars($nr['src_ip'])?></code></td>
						<td><?=htmlspecialchars($nr['dst_ip'])?></td>
						<td><span class="label label-success"><?=!empty($nr['masquerade']) ? 'MASQUERADE' : htmlspecialchars($nr['target_ip'])?></span></td>
						<td><?=htmlspecialchars($nr['description'] ?? '')?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<!-- Candidate NAT Ruleset -->
<div class="panel panel-info">
	<div class="panel-heading">
		<div class="pull-right">
			<form method="post" style="display:inline;">
				<input type="hidden" name="action" value="apply">
				<button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i> Apply Candidate Changes</button>
			</form>
		</div>
		<h3 class="panel-title"><i class="fa fa-edit"></i> Candidate Outbound Rules</h3>
	</div>
	<div class="panel-body">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Rule ID</th>
					<th>Interface</th>
					<th>Source Subnet</th>
					<th>Destination</th>
					<th>Translation</th>
					<th>Description</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($candidate_nat)): ?>
					<tr><td colspan="7" class="text-center text-muted">Candidate outbound ruleset matches running ruleset</td></tr>
				<?php else: foreach ($candidate_nat as $nr): ?>
					<tr>
						<td><?=htmlspecialchars($nr['id'])?></td>
						<td><?=htmlspecialchars($nr['interface'])?></td>
						<td><code><?=htmlspecialchars($nr['src_ip'])?></code></td>
						<td><?=htmlspecialchars($nr['dst_ip'])?></td>
						<td><span class="label label-info"><?=!empty($nr['masquerade']) ? 'MASQUERADE' : htmlspecialchars($nr['target_ip'])?></span></td>
						<td><?=htmlspecialchars($nr['description'] ?? '')?></td>
						<td>
							<form method="post" style="display:inline;">
								<input type="hidden" name="action" value="delete">
								<input type="hidden" name="id" value="<?=htmlspecialchars($nr['id'])?>">
								<button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
							</form>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>

		<hr>
		<h4>Add Outbound NAT (SNAT) Rule</h4>
		<form method="post" class="form-inline">
			<input type="hidden" name="action" value="add">
			<div class="form-group" style="margin-bottom: 10px;">
				<label>Rule ID</label><br>
				<input type="text" name="id" class="form-control input-sm" placeholder="e.g. out_nat_lan" required>
			</div>
			<div class="form-group" style="margin-bottom: 10px;">
				<label>Egress Interface</label><br>
				<select name="interface" class="form-control input-sm">
					<?php foreach ($ifaces as $if): ?>
						<option value="<?=htmlspecialchars($if['name'])?>"><?=htmlspecialchars($if['name'])?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-group" style="margin-bottom: 10px;">
				<label>Source Subnet</label><br>
				<input type="text" name="src_ip" class="form-control input-sm" placeholder="e.g. 192.168.56.0/24" required>
			</div>
			<div class="form-group" style="margin-bottom: 10px;">
				<label>Destination</label><br>
				<input type="text" name="dst_ip" class="form-control input-sm" value="any">
			</div>
			<div class="form-group" style="margin-bottom: 10px;">
				<label>Translation Type</label><br>
				<div class="checkbox" style="padding-top: 5px;">
					<label><input type="checkbox" name="masquerade" value="1" checked> <strong>Masquerade (Interface IP)</strong></label>
				</div>
			</div>
			<div class="form-group" style="margin-bottom: 10px;">
				<label>Description</label><br>
				<input type="text" name="descr" class="form-control input-sm" placeholder="Description">
			</div>
			<div class="form-group" style="margin-bottom: 10px;">
				<label>&nbsp;</label><br>
				<button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Add Outbound Rule</button>
			</div>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
