<?php
/*
 * status_gateways.php - MitraNet Gateway Status
 * Adapted from pfSense status_gateways.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "Gateways");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$gateways = MitraNetApi::getGateways();
?>

<h2>Status: Gateways</h2>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa fa-sitemap"></i> Configured Upstream Gateways</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th>Name</th>
						<th>Gateway IP</th>
						<th>Interface</th>
						<th>Metric</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($gateways)): ?>
					<tr><td colspan="5" class="text-center text-muted">No default gateways currently active.</td></tr>
				<?php else: ?>
					<?php foreach ($gateways as $gw): ?>
					<tr>
						<td><strong><?=htmlspecialchars($gw['name'])?></strong></td>
						<td><?=htmlspecialchars($gw['gateway'])?></td>
						<td><span class="label label-default"><?=htmlspecialchars($gw['interface'])?></span></td>
						<td><?=htmlspecialchars($gw['metric'])?></td>
						<td><span class="label label-success"><i class="fa fa-check"></i> <?=htmlspecialchars(strtoupper($gw['status']))?></span></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="status_gateways.php" class="btn btn-default"><i class="fa fa-sync"></i> Refresh Gateways</a>
</nav>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
