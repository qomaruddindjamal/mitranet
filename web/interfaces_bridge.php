<?php
/*
 * interfaces_bridge.php - MitraNet Bridge Interfaces
 * Adapted from pfSense interfaces_bridge.php
 */

$pgtitle = array(gettext("Interfaces"), gettext("Bridges"));
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

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("Bridges"), true, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "interfaces_lagg.php");
$tab_array[] = array(gettext("VRFs"), false, "interfaces_vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"), false, "interfaces_vethernet.php");
display_top_tabs($tab_array);

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Configured Bridges")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th><?=gettext("Bridge Interface")?></th>
						<th><?=gettext("Member Interfaces")?></th>
						<th><?=gettext("STP Enabled")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($bridges)): ?>
						<tr><td colspan="4" class="text-center text-muted"><?=gettext("No bridges configured")?></td></tr>
					<?php else: foreach ($bridges as $b): ?>
						<tr>
							<td><strong><?=htmlspecialchars($b['name'])?></strong></td>
							<td><?=htmlspecialchars(implode(', ', $b['members'] ?? []))?></td>
							<td><?=!empty($b['stp']) ? 'Yes' : 'No'?></td>
							<td>
								<form method="post" style="display:inline;">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="name" value="<?=htmlspecialchars($b['name'])?>">
									<button type="submit" class="btn btn-xs btn-danger" title="<?=gettext('Delete bridge')?>"><i class="fa-solid fa-trash-can"></i></button>
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
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Create Bridge Interface")?></h2></div>
	<div class="panel-body">
		<form method="post" class="form-horizontal">
			<input type="hidden" name="action" value="create">
			<div class="form-group">
				<label class="col-sm-2 control-label"><span class="element-required">*</span><?=gettext("Bridge Name")?></label>
				<div class="col-sm-10">
					<input type="text" name="name" class="form-control" placeholder="br0" required>
					<span class="help-block"><?=gettext("Name identifier for the Linux bridge device (e.g. br0).")?></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label"><span class="element-required">*</span><?=gettext("Member Interfaces")?></label>
				<div class="col-sm-10">
					<input type="text" name="members" class="form-control" placeholder="enp0s8, enp0s9">
					<span class="help-block"><?=gettext("Comma-separated list of member interfaces to bridge together.")?></span>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i><?=gettext("Add Bridge")?></button>
				</div>
			</div>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>

