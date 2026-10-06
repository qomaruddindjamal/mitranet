<?php
/*
 * diag_arp.php - MitraNet ARP Table
 * Adapted from pfSense diag_arp.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "ARP Table");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$arp_entries = MitraNetApi::getArpTable();
?>

<h2>Diagnostics: ARP Table</h2>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa fa-network-wired"></i> IPv4 Address Resolution Protocol (ARP) Cache</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th>Interface</th>
						<th>IP Address</th>
						<th>MAC Address</th>
						<th>Status</th>
						<th>Flags</th>
					</tr>
				</thead>
				<tbody>
				<?php if (empty($arp_entries)): ?>
					<tr><td colspan="5" class="text-center text-muted">No ARP entries currently present in cache.</td></tr>
				<?php else: ?>
					<?php foreach ($arp_entries as $entry): ?>
					<tr>
						<td><span class="label label-default"><?=htmlspecialchars($entry['interface'] ?? '-')?></span></td>
						<td><strong><?=htmlspecialchars($entry['ip'] ?? '-')?></strong></td>
						<td><code><?=htmlspecialchars($entry['mac'] ?? '-')?></code></td>
						<td>
							<?php if (($entry['status'] ?? '') === 'active'): ?>
								<span class="label label-success">Active / Resolved</span>
							<?php else: ?>
								<span class="label label-warning">Incomplete</span>
							<?php endif; ?>
						</td>
						<td><small class="text-muted"><?=htmlspecialchars($entry['flags'] ?? '-')?></small></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<nav class="action-buttons">
	<a href="diag_arp.php" class="btn btn-default"><i class="fa fa-sync"></i> Refresh ARP Table</a>
</nav>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
