<?php
/*
 * interfaces_bridge.php - MitraNet Bridge Interfaces
 * Adapted from pfSense interfaces_bridge.php
 */

$pgtitle = "Interfaces: Bridges";
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $members = array_filter(array_map('trim', explode(',', $_POST['members'] ?? '')));
        if ($name) {
            $res = MitraNetApi::request('/bridges/create', 'POST', [
                'name' => $name,
                'members' => $members
            ]);
            if ($res['status'] === 200) {
                $msg = "Bridge interface '$name' created successfully";
            } else {
                $err = $res['data']['error'] ?? 'Failed to create bridge';
            }
        }
    } elseif ($action === 'delete') {
        $name = $_POST['name'] ?? '';
        if ($name) {
            $res = MitraNetApi::request('/bridges/delete', 'POST', ['name' => $name]);
            if ($res['status'] === 200) {
                $msg = "Bridge '$name' deleted successfully";
            } else {
                $err = $res['data']['error'] ?? 'Failed to delete bridge';
            }
        }
    }
}

$bridges = MitraNetApi::getBridges();
?>

<h2>Bridges</h2>

<?php if (!empty($msg)): ?>
	<div class="alert alert-success"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
	<div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">Configured Bridges</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Bridge Interface</th>
					<th>Member Interfaces</th>
					<th>STP Enabled</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($bridges)): ?>
					<tr><td colspan="4" class="text-center text-muted">No bridges configured</td></tr>
				<?php else: foreach ($bridges as $b): ?>
					<tr>
						<td><strong><?=htmlspecialchars($b['name'])?></strong></td>
						<td><?=htmlspecialchars(implode(', ', $b['members'] ?? []))?></td>
						<td><?=!empty($b['stp']) ? 'Yes' : 'No'?></td>
						<td>
							<form method="post" style="display:inline;">
								<input type="hidden" name="action" value="delete">
								<input type="hidden" name="name" value="<?=htmlspecialchars($b['name'])?>">
								<button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
							</form>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>

		<hr>
		<h4>Create Bridge</h4>
		<form method="post" class="form-inline">
			<input type="hidden" name="action" value="create">
			<div class="form-group">
				<label>Bridge Name</label>
				<input type="text" name="name" class="form-control" placeholder="br0" required>
			</div>
			<div class="form-group">
				<label>Members (comma separated)</label>
				<input type="text" name="members" class="form-control" placeholder="enp0s8">
			</div>
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Bridge</button>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
