<?php
/*
 * status_interfaces.php - MitraNet Interface Status & Metrics
 * Adapted from pfSense status_interfaces.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "Interfaces");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$ifaces = MitraNetApi::getInterfaces();
?>

<h2>Status: Interfaces</h2>

<div class="row">
<?php foreach ($ifaces as $if): ?>
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					<i class="fa fa-ethernet"></i> <strong><?=htmlspecialchars($if['name'])?></strong>
					<?php if ($if['operstate'] === 'up'): ?>
						<span class="label label-success pull-right">UP</span>
					<?php else: ?>
						<span class="label label-danger pull-right">DOWN</span>
					<?php endif; ?>
				</h3>
			</div>
			<div class="panel-body">
				<table class="table table-condensed table-striped">
					<tr>
						<th style="width: 35%;">Status:</th>
						<td>
							<?php if ($if['is_up']): ?>
								<span class="text-success"><i class="fa fa-arrow-up"></i> Running / Admin UP</span>
							<?php else: ?>
								<span class="text-danger"><i class="fa fa-arrow-down"></i> Admin DOWN</span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>MAC Address:</th>
						<td><code><?=htmlspecialchars($if['mac_address'] ?? 'N/A')?></code></td>
					</tr>
					<tr>
						<th>MTU:</th>
						<td><?=htmlspecialchars($if['mtu'])?> bytes</td>
					</tr>
					<tr>
						<th>IPv4 Addresses:</th>
						<td>
							<?php if (empty($if['ipv4_addresses'])): ?>
								<span class="text-muted">None assigned</span>
							<?php else: ?>
								<?php foreach ($if['ipv4_addresses'] as $addr): ?>
									<span class="label label-primary"><?=htmlspecialchars($addr)?></span>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>IPv6 Addresses:</th>
						<td>
							<?php if (empty($if['ipv6_addresses'])): ?>
								<span class="text-muted">None assigned</span>
							<?php else: ?>
								<?php foreach ($if['ipv6_addresses'] as $addr): ?>
									<span class="label label-info" style="font-size:10px;"><?=htmlspecialchars($addr)?></span>
								<?php endforeach; ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>In/Out Packets:</th>
						<td>
							RX: <?=number_format($if['traffic']['rx_packets'] ?? 0)?> pkts (<?=number_format(($if['traffic']['rx_bytes'] ?? 0)/1024, 1)?> KB) | 
							TX: <?=number_format($if['traffic']['tx_packets'] ?? 0)?> pkts (<?=number_format(($if['traffic']['tx_bytes'] ?? 0)/1024, 1)?> KB)
						</td>
					</tr>
				</table>
			</div>
			<div class="panel-footer">
				<a href="interfaces.php?if=<?=urlencode($if['name'])?>" class="btn btn-default btn-xs"><i class="fa fa-cog"></i> Configure Interface</a>
			</div>
		</div>
	</div>
<?php endforeach; ?>
</div>

<nav class="action-buttons">
	<a href="status_interfaces.php" class="btn btn-default"><i class="fa fa-sync"></i> Refresh Interfaces</a>
</nav>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
