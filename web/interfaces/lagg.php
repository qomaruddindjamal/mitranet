<?php
/*
 * interfaces_lagg.php - MitraNet Bond & LACP Interfaces
 * Adapted from pfSense interfaces_lagg.php
 */

$pgtitle = array(gettext("Interfaces"), gettext("LAGGs"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/../includes/head.inc');

$bonds = MitraNetApi::getBonds();

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"), false, "vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "bridge.php");
$tab_array[] = array(gettext("LAGGs"), true, "lagg.php");
$tab_array[] = array(gettext("VRFs"), false, "vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"), false, "vethernet.php");
display_top_tabs($tab_array);
?>

<div class="panel panel-default panel-mitranet">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("LAGG Interfaces")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th><?=gettext("Bond Name")?></th>
						<th><?=gettext("Mode")?></th>
						<th><?=gettext("Active Members")?></th>
						<th><?=gettext("Link State")?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($bonds)): ?>
						<tr><td colspan="4" class="text-center text-muted"><?=gettext("No bond/LAGG interfaces configured")?></td></tr>
					<?php else: foreach ($bonds as $b): ?>
						<tr>
							<td><strong><?=htmlspecialchars($b['name'])?></strong></td>
							<td><?=strtoupper(htmlspecialchars($b['mode'] ?? '802.3ad'))?></td>
							<td><code><?=htmlspecialchars(implode(', ', $b['members'] ?? []))?></code></td>
							<td><span class="label label-success">UP</span></td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="lagg.php" role="button" class="btn btn-default btn-sm">
		<i class="fa-solid fa-rotate icon-embed-btn"></i>
		<?=gettext("Refresh")?>
	</a>
</nav>

<div class="infoblock">
<?php
print_info_box(
	gettext("LAGG (Link Aggregation) allows grouping multiple physical ethernet ports into a single logical channel using LACP (802.3ad), active-backup, or balance-rr modes."), 'info', false);
?>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
