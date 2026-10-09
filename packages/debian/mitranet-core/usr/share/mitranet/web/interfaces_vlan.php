<?php
/*
 * interfaces_vlan.php - MitraNet 802.1Q VLAN Interfaces
 * Adapted from pfSense interfaces_vlan.php
 */

$pgtitle = array(gettext("Interfaces"), gettext("VLANs"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $parent = $_POST['parent'] ?? '';
        $tag = (int)($_POST['tag'] ?? 0);
        $descr = trim($_POST['descr'] ?? '');
        if ($parent && $tag > 0 && $tag <= 4094) {
            $res = MitraNetApi::request('/vlans/create', 'POST', [
                'parent_interface' => $parent,
                'vlan_id' => $tag,
                'description' => $descr
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

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"), true, "interfaces_vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "interfaces_bridge.php");
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

<div class="panel panel-default panel-mitranet">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("VLAN Interfaces")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th><?=gettext("Interface")?></th>
						<th><?=gettext("Parent Interface")?></th>
						<th><?=gettext("VLAN Tag")?></th>
						<th><?=gettext("Description")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($vlans)): ?>
						<tr><td colspan="5" class="text-center text-muted"><?=gettext("No VLAN interfaces configured")?></td></tr>
					<?php else: foreach ($vlans as $v): ?>
						<tr>
							<td><strong><?=htmlspecialchars($v['name'])?></strong></td>
							<td><?=htmlspecialchars($v['parent_interface'])?></td>
							<td><span class="label label-info"><?=htmlspecialchars($v['vlan_id'])?></span></td>
							<td><?=htmlspecialchars($v['description'] ?? '')?></td>
							<td>
								<form method="post" class="form-inline-action">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="name" value="<?=htmlspecialchars($v['name'])?>">
									<button type="submit" class="btn btn-xs btn-danger" title="<?=gettext('Delete VLAN')?>"><i class="fa-solid fa-trash-can"></i></button>
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
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Create 802.1Q VLAN Interface")?></h2></div>
	<div class="panel-body">
		<form method="post" class="form-horizontal">
			<input type="hidden" name="action" value="create">
			<div class="form-group">
				<label class="col-sm-2 control-label"><span class="element-required">*</span><?=gettext("Parent Interface")?></label>
				<div class="col-sm-10">
					<select name="parent" class="form-control" required>
						<?php foreach ($ifaces as $i): if ($i['name']!=='lo'): ?>
							<option value="<?=htmlspecialchars($i['name'])?>"><?=htmlspecialchars($i['name'])?> (<?=htmlspecialchars($i['type'] ?? 'ether')?>)</option>
						<?php endif; endforeach; ?>
					</select>
					<span class="help-block"><?=gettext("Physical interface on which the 802.1Q tag will be encapsulated.")?></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label"><span class="element-required">*</span><?=gettext("VLAN Tag")?></label>
				<div class="col-sm-10">
					<input type="number" name="tag" class="form-control" min="1" max="4094" placeholder="100" required>
					<span class="help-block"><?=gettext("802.1Q VLAN tag (integer between 1 and 4094).")?></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label"><?=gettext("Description")?></label>
				<div class="col-sm-10">
					<input type="text" name="descr" class="form-control" placeholder="Office VLAN">
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i><?=gettext("Add VLAN")?></button>
				</div>
			</div>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
