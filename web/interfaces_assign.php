<?php
/*
 * interfaces_assign.php - MitraNet Interface Assignments
 * Adapted from pfSense interfaces_assign.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Interfaces"), gettext("Interface Assignments"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$ifaces = MitraNetApi::getInterfaces();
$vlans = MitraNetApi::getVlans();
$bridges = MitraNetApi::getBridges();
$bonds = MitraNetApi::getBonds();

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), true, "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "interfaces_lagg.php");
$tab_array[] = array(gettext("VRFs"), false, "interfaces_vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"), false, "interfaces_vethernet.php");
display_top_tabs($tab_array);
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Interface Assignments")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th><?=gettext("Interface Identifier")?></th>
						<th><?=gettext("Network Port")?></th>
						<th><?=gettext("MAC Address")?></th>
						<th><?=gettext("Status")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($ifaces as $idx => $if): ?>
					<tr>
						<td><a href="interfaces.php?if=<?=urlencode($if['name'])?>"><strong><?=htmlspecialchars(strtoupper($if['name']))?></strong></a></td>
						<td><?=htmlspecialchars($if['name'])?> (<?=htmlspecialchars($if['type'] ?? 'ether')?>)</td>
						<td><code><?=htmlspecialchars($if['mac_address'] ?? 'N/A')?></code></td>
						<td>
							<?php if ($if['operstate'] === 'up'): ?>
								<span class="label label-success">UP</span>
							<?php else: ?>
								<span class="label label-danger">DOWN</span>
							<?php endif; ?>
						</td>
						<td>
							<a href="interfaces.php?if=<?=urlencode($if['name'])?>" class="fa-solid fa-pencil" title="<?=gettext('Edit interface')?>"></a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="interfaces_assign.php" role="button" class="btn btn-default btn-sm">
		<i class="fa-solid fa-rotate icon-embed-btn"></i>
		<?=gettext("Refresh")?>
	</a>
</nav>

<div class="infoblock">
<?php
print_info_box(
	gettext("Interfaces that are configured as members of a LAGG or Bridge interface will have their traffic managed by their respective virtual interfaces.") .
	'<br/><br/>' .
	gettext("VLAN interfaces must be created on the VLANs tab before they can be assigned."), 'info', false);
?>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
