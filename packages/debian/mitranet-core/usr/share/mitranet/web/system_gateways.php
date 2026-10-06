<?php
/*
 * system_gateways.php - MitraNet Gateway Management
 * Adapted from pfSense system_gateways.php
 */

$pgtitle = "System: Routing: Gateways";
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$gateways = MitraNetApi::getGateways();
?>

<h2>Routing</h2>

<ul class="nav nav-tabs" style="margin-bottom: 20px;">
	<li><a href="/system_routes.php">Static Routes</a></li>
	<li class="active"><a href="/system_gateways.php">Gateways</a></li>
</ul>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">Gateways</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Name</th>
					<th>Default</th>
					<th>Interface</th>
					<th>Gateway IP</th>
					<th>Monitor IP</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($gateways)): ?>
					<tr><td colspan="6" class="text-center text-muted">No default gateways detected</td></tr>
				<?php else: foreach ($gateways as $gw): ?>
					<tr>
						<td><strong><?=htmlspecialchars($gw['name'])?></strong></td>
						<td><span class="label label-primary">Default</span></td>
						<td><code><?=htmlspecialchars($gw['interface'])?></code></td>
						<td><?=htmlspecialchars($gw['gateway'])?></td>
						<td><?=htmlspecialchars($gw['gateway'])?></td>
						<td><span class="label label-success"><?=strtoupper(htmlspecialchars($gw['status']))?></span></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
