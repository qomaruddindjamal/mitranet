<?php
/*
 * interfaces_vlan.php - MitraNet 802.1Q VLAN Interfaces
 * Adapted from pfSense interfaces_vlan.php
 */

$pgtitle = "Interfaces: VLANs";
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $parent = $_POST['parent'] ?? '';
        $tag = (int)($_POST['tag'] ?? 0);
        if ($parent && $tag > 0 && $tag <= 4094) {
            $res = MitraNetApi::request('/vlans/create', 'POST', [
                'parent_interface' => $parent,
                'vlan_id' => $tag
            ]);
            if ($res['status'] === 200) {
                $msg = "VLAN interface '$parent.$tag' created successfully";
            } else {
                $err = $res['data']['error'] ?? 'Failed to create VLAN';
            }
        }
    } elseif ($action === 'delete') {
        $name = $_POST['name'] ?? '';
        if ($name) {
            $res = MitraNetApi::request('/vlans/delete', 'POST', ['name' => $name]);
            if ($res['status'] === 200) {
                $msg = "VLAN interface '$name' deleted successfully";
            } else {
                $err = $res['data']['error'] ?? 'Failed to delete VLAN';
            }
        }
    }
}

$vlans = MitraNetApi::getVlans();
$ifaces = MitraNetApi::getInterfaces();
?>

<h2>VLANs</h2>

<?php if (!empty($msg)): ?>
	<div class="alert alert-success"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
	<div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">802.1Q VLAN Interfaces</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Interface</th>
					<th>Parent Interface</th>
					<th>VLAN Tag</th>
					<th>Description</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($vlans)): ?>
					<tr><td colspan="5" class="text-center text-muted">No VLAN interfaces configured</td></tr>
				<?php else: foreach ($vlans as $v): ?>
					<tr>
						<td><strong><?=htmlspecialchars($v['name'])?></strong></td>
						<td><?=htmlspecialchars($v['parent_interface'])?></td>
						<td><span class="label label-info"><?=htmlspecialchars($v['vlan_id'])?></span></td>
						<td><?=htmlspecialchars($v['description'] ?? '')?></td>
						<td>
							<form method="post" style="display:inline;">
								<input type="hidden" name="action" value="delete">
								<input type="hidden" name="name" value="<?=htmlspecialchars($v['name'])?>">
								<button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
							</form>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>

		<hr>
		<h4>Create VLAN Interface</h4>
		<form method="post" class="form-inline">
			<input type="hidden" name="action" value="create">
			<div class="form-group">
				<label>Parent Interface</label>
				<select name="parent" class="form-control" required>
					<?php foreach ($ifaces as $i): if ($i['name']!=='lo'): ?>
						<option value="<?=htmlspecialchars($i['name'])?>"><?=htmlspecialchars($i['name'])?></option>
					<?php endif; endforeach; ?>
				</select>
			</div>
			<div class="form-group">
				<label>VLAN Tag (1-4094)</label>
				<input type="number" name="tag" class="form-control" min="1" max="4094" placeholder="100" required>
			</div>
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add VLAN</button>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
