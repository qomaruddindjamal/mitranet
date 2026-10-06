<?php
/*
 * interfaces_lagg.php - MitraNet Bond & LACP Interfaces
 * Adapted from pfSense interfaces_lagg.php
 */

$pgtitle = "Interfaces: LAGG / LACP";
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$bonds = MitraNetApi::getBonds();
?>

<h2>Bond / LACP (Link Aggregation)</h2>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">LAGG Interfaces</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Bond Name</th>
					<th>Mode</th>
					<th>Active Members</th>
					<th>Link State</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($bonds)): ?>
					<tr><td colspan="4" class="text-center text-muted">No bond/LAGG interfaces configured</td></tr>
				<?php else: foreach ($bonds as $b): ?>
					<tr>
						<td><strong><?=htmlspecialchars($b['name'])?></strong></td>
						<td><?=strtoupper(htmlspecialchars($b['mode'] ?? '802.3ad'))?></td>
						<td><?=htmlspecialchars(implode(', ', $b['members'] ?? []))?></td>
						<td><span class="label label-success">UP</span></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
