<?php
/*
 * firewall_rules.php - MitraNet Firewall Rules Management
 * Adapted from pfSense firewall_rules.php
 */

$pgtitle = "Firewall: Rules";
$selected_menu = "firewall";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $rid = trim($_POST['id'] ?? '');
        $act = $_POST['rule_action'] ?? 'accept';
        $proto = $_POST['protocol'] ?? 'tcp';
        $prio = (int)($_POST['priority'] ?? 100);

        if (!empty($rid)) {
            $res = MitraNetApi::request('/firewall/rule/add', 'POST', [
                'id' => $rid,
                'action' => $act,
                'protocol' => $proto,
                'source' => 'any',
                'destination' => 'any',
                'priority' => $prio
            ]);
            if ($res['status'] === 200) {
                $msg = "Rule '$rid' added to candidate configuration";
            } else {
                $err = $res['data']['error'] ?? 'Failed to add rule';
            }
        } else {
            $err = 'Rule ID is required';
        }
    } elseif ($action === 'delete') {
        $rid = trim($_POST['id'] ?? '');
        if (!empty($rid)) {
            $res = MitraNetApi::request('/firewall/rule/delete', 'POST', ['id' => $rid]);
            if ($res['status'] === 200) {
                $msg = "Rule '$rid' deleted from candidate configuration";
            } else {
                $err = $res['data']['error'] ?? 'Failed to delete rule';
            }
        }
    } elseif ($action === 'apply') {
        $res = MitraNetApi::request('/firewall/apply', 'POST', []);
        if ($res['status'] === 200) {
            $msg = "Firewall candidate applied and committed to Linux kernel nftables successfully";
        } else {
            $err = $res['data']['error'] ?? 'Failed to apply firewall rules';
        }
    }
}

$fw = MitraNetApi::getFirewall();
$status = $fw['status'] ?? [];
$running_rules = $fw['config']['rules'] ?? [];
$candidate_rules = $fw['candidate']['rules'] ?? [];
?>

<h2>Firewall Rules (nftables)</h2>

<?php if (!empty($msg)): ?>
	<div class="alert alert-success"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
	<div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
<?php endif; ?>

<!-- Status & Base Policies -->
<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-shield-alt"></i> Engine Status & Base Policies</h3></div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-3"><strong>Status:</strong> <span class="label label-success"><?=strtoupper($status['status'] ?? 'ACTIVE')?></span></div>
			<div class="col-md-3"><strong>Input Policy:</strong> <code><?=htmlspecialchars($status['input_policy'] ?? 'drop')?></code></div>
			<div class="col-md-3"><strong>Forward Policy:</strong> <code><?=htmlspecialchars($status['forward_policy'] ?? 'drop')?></code></div>
			<div class="col-md-3"><strong>Output Policy:</strong> <code><?=htmlspecialchars($status['output_policy'] ?? 'accept')?></code></div>
		</div>
	</div>
</div>

<!-- Running Ruleset -->
<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-list"></i> Active / Running Filter Rules</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>ID</th>
					<th>Action</th>
					<th>Proto</th>
					<th>Source</th>
					<th>Destination</th>
					<th>Priority</th>
					<th>Enabled</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($running_rules)): ?>
					<tr><td colspan="7" class="text-center text-muted">No custom filter rules defined in running ruleset</td></tr>
				<?php else: foreach ($running_rules as $r): ?>
					<tr>
						<td><strong><?=htmlspecialchars($r['id'])?></strong></td>
						<td><span class="label <?=($r['action']==='accept')?'label-success':'label-danger'?>"><?=strtoupper($r['action'])?></span></td>
						<td><?=strtoupper(htmlspecialchars($r['protocol'] ?? 'ANY'))?></td>
						<td><?=htmlspecialchars($r['source'] ?? 'any')?></td>
						<td><?=htmlspecialchars($r['destination'] ?? 'any')?></td>
						<td><?=htmlspecialchars($r['priority'] ?? 100)?></td>
						<td><?=!empty($r['enabled']) ? '<span class="text-success"><i class="fa fa-check"></i></span>' : '<span class="text-muted"><i class="fa fa-times"></i></span>'?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<!-- Candidate Ruleset & Management -->
<div class="panel panel-info">
	<div class="panel-heading">
		<div class="pull-right">
			<form method="post" style="display:inline;">
				<input type="hidden" name="action" value="apply">
				<button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i> Apply Candidate Changes</button>
			</form>
		</div>
		<h3 class="panel-title"><i class="fa fa-edit"></i> Candidate Configuration & New Rules</h3>
	</div>
	<div class="panel-body">
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Rule ID</th>
					<th>Action</th>
					<th>Proto</th>
					<th>Source</th>
					<th>Destination</th>
					<th>Priority</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($candidate_rules)): ?>
					<tr><td colspan="7" class="text-center text-muted">Candidate ruleset matches running ruleset</td></tr>
				<?php else: foreach ($candidate_rules as $r): ?>
					<tr>
						<td><?=htmlspecialchars($r['id'])?></td>
						<td><span class="label <?=($r['action']==='accept')?'label-success':'label-danger'?>"><?=strtoupper($r['action'])?></span></td>
						<td><?=strtoupper(htmlspecialchars($r['protocol'] ?? 'ANY'))?></td>
						<td><?=htmlspecialchars($r['source'] ?? 'any')?></td>
						<td><?=htmlspecialchars($r['destination'] ?? 'any')?></td>
						<td><?=htmlspecialchars($r['priority'] ?? 100)?></td>
						<td>
							<form method="post" style="display:inline;">
								<input type="hidden" name="action" value="delete">
								<input type="hidden" name="id" value="<?=htmlspecialchars($r['id'])?>">
								<button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
							</form>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>

		<hr>
		<h4>Add Filter Rule to Candidate</h4>
		<form method="post" class="form-inline">
			<input type="hidden" name="action" value="add">
			<div class="form-group">
				<label>Rule ID</label>
				<input type="text" name="id" class="form-control" placeholder="e.g. allow_test_tcp" required>
			</div>
			<div class="form-group">
				<label>Action</label>
				<select name="rule_action" class="form-control">
					<option value="accept">Accept</option>
					<option value="drop">Drop</option>
					<option value="reject">Reject</option>
				</select>
			</div>
			<div class="form-group">
				<label>Protocol</label>
				<select name="protocol" class="form-control">
					<option value="tcp">TCP</option>
					<option value="udp">UDP</option>
					<option value="icmp">ICMP</option>
					<option value="any">ANY</option>
				</select>
			</div>
			<div class="form-group">
				<label>Priority</label>
				<input type="number" name="priority" class="form-control" value="200" style="width: 80px;">
			</div>
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Rule</button>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
