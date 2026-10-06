<?php
/*
 * interfaces_assign.php - MitraNet Interface Assignments
 * Adapted from pfSense interfaces_assign.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Interfaces", "Assignments");
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$ifaces = MitraNetApi::getInterfaces();
$vlans = MitraNetApi::getVlans();
$bridges = MitraNetApi::getBridges();
$bonds = MitraNetApi::getBonds();
?>

<h2>Interfaces: Interface Assignments</h2>

<ul class="nav nav-tabs">
	<li class="active"><a href="interfaces_assign.php">Interface Assignments</a></li>
	<li><a href="interfaces_vlan.php">VLANs</a></li>
	<li><a href="interfaces_bridge.php">Bridges</a></li>
	<li><a href="interfaces_lagg.php">LAGGs</a></li>
	<li><a href="interfaces_vrf.php">VRFs</a></li>
</ul>

<div class="panel panel-default" style="margin-top: 15px;">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa fa-network-wired"></i> Assigned Network Ports</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th>Interface Identifier</th>
						<th>Network Port</th>
						<th>MAC Address</th>
						<th>OperState</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($ifaces as $idx => $if): ?>
					<tr>
						<td><strong><?=htmlspecialchars(strtoupper($if['name']))?></strong></td>
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
							<a href="interfaces.php?name=<?=urlencode($if['name'])?>" class="btn btn-default btn-xs" title="Configure"><i class="fa fa-pencil-alt"></i> Edit</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
