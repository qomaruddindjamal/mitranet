<?php
/*
 * interfaces_vrf.php - MitraNet VRF Domains
 * Adapted from pfSense interfaces/vrf
 */

$pgtitle = "Interfaces: VRF";
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $table = (int)($_POST['table'] ?? 100);
        if ($name && $table > 0) {
            $res = MitraNetApi::request('/vrfs/create', 'POST', [
                'name' => $name,
                'table_id' => $table
            ]);
            if ($res['status'] === 200) {
                $msg = "VRF '$name' (Table $table) created successfully";
            } else {
                $err = $res['data']['error'] ?? 'Failed to create VRF';
            }
        }
    } elseif ($action === 'delete') {
        $name = $_POST['name'] ?? '';
        if ($name) {
            $res = MitraNetApi::request('/vrfs/delete', 'POST', ['name' => $name]);
            if ($res['status'] === 200) {
                $msg = "VRF '$name' deleted successfully";
            } else {
                $err = $res['data']['error'] ?? 'Failed to delete VRF';
            }
        }
    }
}

$vrfs = MitraNetApi::getVrfs();
?>

<h2>VRF Routing Domains</h2>

<?php if (!empty($msg)): ?>
	<div class="alert alert-success"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
	<div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">Active VRF Instances</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>VRF Name</th>
					<th>Routing Table ID</th>
					<th>Bound Interfaces</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($vrfs)): ?>
					<tr><td colspan="4" class="text-center text-muted">No VRF instances configured</td></tr>
				<?php else: foreach ($vrfs as $v): ?>
					<tr>
						<td><strong><?=htmlspecialchars($v['name'])?></strong></td>
						<td><span class="label label-info"><?=htmlspecialchars($v['table_id'])?></span></td>
						<td><?=htmlspecialchars(implode(', ', $v['interfaces'] ?? []))?></td>
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
		<h4>Create VRF Instance</h4>
		<form method="post" class="form-inline">
			<input type="hidden" name="action" value="create">
			<div class="form-group">
				<label>VRF Name</label>
				<input type="text" name="name" class="form-control" placeholder="vrf_cust1" required>
			</div>
			<div class="form-group">
				<label>Table ID</label>
				<input type="number" name="table" class="form-control" min="1" max="1000" placeholder="100" required>
			</div>
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Create VRF</button>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
