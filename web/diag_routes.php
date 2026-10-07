<?php
/*
 * diag_routes.php - MitraNet Routing Table Viewer
 * Adapted from pfSense diag_routes.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Diagnostics"), gettext("Routing Tables"));
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$routes = MitraNetApi::getRoutes();
$v4_routes = $routes['ipv4'] ?? [];
$v6_routes = $routes['ipv6'] ?? [];
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title"><?=gettext("IPv4 Routing Table")?></h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th><?=gettext("Destination")?></th>
						<th><?=gettext("Gateway")?></th>
						<th><?=gettext("Interface")?></th>
						<th><?=gettext("Protocol")?></th>
						<th><?=gettext("Scope")?></th>
						<th><?=gettext("Metric")?></th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($v4_routes)): ?>
					<tr><td colspan="6" class="text-center text-muted"><?=gettext("No IPv4 routes found.")?></td></tr>
				<?php else: ?>
					<?php foreach ($v4_routes as $r): ?>
					<tr>
						<td><strong><?=htmlspecialchars($r['destination'] ?? 'default')?></strong></td>
						<td><?=htmlspecialchars($r['gateway'] ?? '*')?></td>
						<td><span class="label label-default"><?=htmlspecialchars($r['interface'] ?? '-')?></span></td>
						<td><small class="text-muted"><?=htmlspecialchars($r['protocol'] ?? '-')?></small></td>
						<td><small class="text-muted"><?=htmlspecialchars($r['scope'] ?? '-')?></small></td>
						<td><?=htmlspecialchars($r['metric'] ?? '0')?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title"><?=gettext("IPv6 Routing Table")?></h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th>Destination</th>
						<th>Gateway</th>
						<th>Interface</th>
						<th>Protocol</th>
						<th>Metric</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($v6_routes)): ?>
					<tr><td colspan="5" class="text-center text-muted">No IPv6 routes found.</td></tr>
				<?php else: ?>
					<?php foreach ($v6_routes as $r): ?>
					<tr>
						<td><strong><?=htmlspecialchars($r['destination'] ?? 'default')?></strong></td>
						<td><?=htmlspecialchars($r['gateway'] ?? '*')?></td>
						<td><span class="label label-default"><?=htmlspecialchars($r['interface'] ?? '-')?></span></td>
						<td><small class="text-muted"><?=htmlspecialchars($r['protocol'] ?? '-')?></small></td>
						<td><?=htmlspecialchars($r['metric'] ?? '0')?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="diag_routes.php" role="button" class="btn btn-default btn-sm">
		<i class="fa-solid fa-rotate icon-embed-btn"></i>
		<?=gettext("Refresh")?>
	</a>
</nav>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
