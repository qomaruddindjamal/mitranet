<?php
/*
 * firewall_nat.php - MitraNet Native Linux NAT Management
 * Adapted from pfSense firewall_nat.php
 * Strictly communicates via REST API -> MitraNet Native nftables Engine.
 */

$pgtitle = array(gettext("Firewall"), gettext("NAT"), gettext("Port Forward"));
$selected_menu = "firewall";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $rid = trim($_POST['id'] ?? '');
        $ntype = $_POST['nat_type'] ?? 'port_forward';
        $iface = $_POST['interface'] ?? 'enp0s8';
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
            if ($res['status'] === 200) {
                $msg = "NAT Rule '$rid' added to candidate configuration";
            } else {
                $err = $res['data']['error'] ?? 'Failed to add NAT rule';
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
            $msg = "NAT rules applied and committed to Linux kernel nftables successfully";
        } else {
            $err = $res['data']['error'] ?? 'Failed to apply NAT rules';
        }
    }
}

$fw = MitraNetApi::getFirewall();
$running_nat = $fw['config']['nat_rules'] ?? [];
$candidate_nat = $fw['candidate']['nat_rules'] ?? [];
$ifaces = MitraNetApi::getInterfaces();
$tab_array = array();
$tab_array[] = array(gettext("Port Forward"), true, "firewall_nat.php");
$tab_array[] = array(gettext("1:1"), false, "firewall_nat_1to1.php");
$tab_array[] = array(gettext("Outbound"), false, "firewall_nat_out.php");
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
	<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-list"></i> Active / Running NAT Rules</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>ID</th>
					<th>Type</th>
					<th>Interface</th>
					<th>Proto</th>
					<th>Ext Port</th>
					<th>Target IP</th>
					<th>Target Port</th>
					<th>Description</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($running_nat)): ?>
					<tr><td colspan="8" class="text-center text-muted">No custom NAT rules defined in running ruleset</td></tr>
				<?php else: foreach ($running_nat as $nr): ?>
					<tr>
						<td><strong><?=htmlspecialchars($nr['id'])?></strong></td>
						<td><span class="label label-info"><?=strtoupper(str_replace('_', ' ', $nr['nat_type']))?></span></td>
						<td><?=htmlspecialchars($nr['interface'])?></td>
						<td><?=strtoupper(htmlspecialchars($nr['protocol'] ?? 'TCP'))?></td>
						<td><?=htmlspecialchars($nr['dst_port'] ?? 'ANY')?></td>
						<td><code><?=htmlspecialchars($nr['target_ip'] ?? '-')?></code></td>
						<td><?=htmlspecialchars($nr['target_port'] ?? '-')?></td>
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
			<form method="post" class="form-inline-action">
				<input type="hidden" name="action" value="apply">
				<button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i> Apply Candidate Changes</button>
			</form>
		</div>
		<h3 class="panel-title"><i class="fa fa-edit"></i> Candidate NAT Rules</h3>
	</div>
	<div class="panel-body">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Rule ID</th>
					<th>Interface</th>
					<th>Proto</th>
					<th>Ext Port</th>
					<th>Target IP</th>
					<th>Target Port</th>
					<th>Description</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($candidate_nat)): ?>
					<tr><td colspan="8" class="text-center text-muted">Candidate NAT ruleset matches running ruleset</td></tr>
				<?php else: foreach ($candidate_nat as $nr): ?>
					<tr>
						<td><?=htmlspecialchars($nr['id'])?></td>
						<td><?=htmlspecialchars($nr['interface'])?></td>
						<td><?=strtoupper(htmlspecialchars($nr['protocol'] ?? 'TCP'))?></td>
						<td><?=htmlspecialchars($nr['dst_port'] ?? 'ANY')?></td>
						<td><code><?=htmlspecialchars($nr['target_ip'] ?? '-')?></code></td>
						<td><?=htmlspecialchars($nr['target_port'] ?? '-')?></td>
						<td><?=htmlspecialchars($nr['description'] ?? '')?></td>
						<td>
							<form method="post" class="form-inline-action">
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
		<h4>Add Port Forward (DNAT) Rule</h4>
		<form method="post" class="form-inline">
			<input type="hidden" name="action" value="add">
			<input type="hidden" name="nat_type" value="port_forward">
			<div class="form-group mb-10">
				<label>Rule ID</label><br>
				<input type="text" name="id" class="form-control input-sm" placeholder="e.g. pf_web_8080" required>
			</div>
			<div class="form-group mb-10">
				<label>Interface</label><br>
				<select name="interface" class="form-control input-sm">
					<?php foreach ($ifaces as $if): ?>
						<option value="<?=htmlspecialchars($if['name'])?>"><?=htmlspecialchars($if['name'])?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-group mb-10">
				<label>Protocol</label><br>
				<select name="protocol" class="form-control input-sm">
					<option value="tcp">TCP</option>
					<option value="udp">UDP</option>
					<option value="tcp_udp">TCP/UDP</option>
				</select>
			</div>
			<div class="form-group mb-10">
				<label>Ext Port</label><br>
				<input type="text" name="dst_port" class="form-control input-sm" placeholder="e.g. 8080" class="input-sm-w90" required>
			</div>
			<div class="form-group mb-10">
				<label>Target IP</label><br>
				<input type="text" name="target_ip" class="form-control input-sm" placeholder="e.g. 192.168.56.101" required>
			</div>
			<div class="form-group mb-10">
				<label>Target Port</label><br>
				<input type="text" name="target_port" class="form-control input-sm" placeholder="e.g. 8443" class="input-sm-w90">
			</div>
			<div class="form-group mb-10">
				<label>Description</label><br>
				<input type="text" name="descr" class="form-control input-sm" placeholder="Description">
			</div>
			<div class="form-group mb-10">
				<label>&nbsp;</label><br>
				<button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Add Port Forward</button>
			</div>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
