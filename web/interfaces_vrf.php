<?php
/*
 * interfaces_vrf.php - MitraNet VRF Domains
 * Adapted from pfSense interfaces/vrf
 */

$pgtitle = array(gettext("Interfaces"), gettext("VRFs"));
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

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "interfaces_lagg.php");
$tab_array[] = array(gettext("VRFs"), true, "interfaces_vrf.php");
display_top_tabs($tab_array);

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Active VRF Instances")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th><?=gettext("VRF Name")?></th>
						<th><?=gettext("Routing Table ID")?></th>
						<th><?=gettext("Bound Interfaces")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($vrfs)): ?>
						<tr><td colspan="4" class="text-center text-muted"><?=gettext("No VRF instances configured")?></td></tr>
					<?php else: foreach ($vrfs as $v): ?>
						<tr>
							<td><strong><?=htmlspecialchars($v['name'])?></strong></td>
							<td><span class="label label-info"><?=htmlspecialchars($v['table_id'])?></span></td>
							<td><?=htmlspecialchars(implode(', ', $v['interfaces'] ?? []))?></td>
							<td>
								<form method="post" style="display:inline;">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="name" value="<?=htmlspecialchars($v['name'])?>">
									<button type="submit" class="btn btn-xs btn-danger" title="<?=gettext('Delete VRF')?>"><i class="fa-solid fa-trash-can"></i></button>
								</form>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Create VRF Instance")?></h2></div>
	<div class="panel-body">
		<form method="post" class="form-horizontal">
			<input type="hidden" name="action" value="create">
			<div class="form-group">
				<label class="col-sm-2 control-label"><span class="element-required">*</span><?=gettext("VRF Name")?></label>
				<div class="col-sm-10">
					<input type="text" name="name" class="form-control" placeholder="vrf_cust1" required>
					<span class="help-block"><?=gettext("Unique routing domain identifier.")?></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label"><span class="element-required">*</span><?=gettext("Routing Table ID")?></label>
				<div class="col-sm-10">
					<input type="number" name="table" class="form-control" min="1" max="1000" placeholder="100" required>
					<span class="help-block"><?=gettext("Linux kernel routing table identifier (1-1000).")?></span>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i><?=gettext("Create VRF")?></button>
				</div>
			</div>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>

