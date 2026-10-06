<?php
/*
 * diag_dump_states.php - MitraNet Connection Tracking & States
 * Adapted from pfSense diag_dump_states.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "States");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$conntrack = MitraNetApi::getConntrackStates();
$states = $conntrack['states'] ?? [];
$total = $conntrack['total'] ?? count($states);
?>

<h2>Diagnostics: States & Connection Tracking</h2>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title"><i class="fa fa-exchange-alt"></i> Active Netfilter Conntrack States (Total: <?=intval($total)?>)</h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed" style="font-family: monospace; font-size: 12px;">
				<thead>
					<tr>
						<th>Proto</th>
						<th>State Details</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($states)): ?>
					<tr><td colspan="2" class="text-center text-muted">No active connection tracking states recorded.</td></tr>
				<?php else: ?>
					<?php foreach ($states as $s): ?>
					<tr>
						<td><span class="label label-info"><?=htmlspecialchars(strtoupper($s['protocol'] ?? 'IP'))?></span></td>
						<td><?=htmlspecialchars($s['raw'] ?? '')?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="diag_dump_states.php" class="btn btn-default"><i class="fa fa-sync"></i> Refresh States</a>
</nav>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
