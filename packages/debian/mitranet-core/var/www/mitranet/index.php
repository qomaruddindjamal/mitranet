<?php
/*
 * index.php - MitraNet System Dashboard
 * Adapted from pfSense index.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "Dashboard");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$sys = MitraNetApi::getSystem();
$ifaces = MitraNetApi::getInterfaces();
$gateways = MitraNetApi::getGateways();
$cfg = MitraNetApi::getConfigStatus();
$conntrack = MitraNetApi::getConntrackStates();

// Format uptime string matching pfSense format
$uptime_sec = intval($sys['uptime_seconds'] ?? 0);
$updays = (int)($uptime_sec / 86400);
$uphours = (int)(($uptime_sec % 86400) / 3600);
$upmins = (int)(($uptime_sec % 3600) / 60);
$upsecs = (int)($uptime_sec % 60);
$uptimestr = "";
if ($updays > 1) {
    $uptimestr .= "{$updays} Days ";
} elseif ($updays == 1) {
    $uptimestr .= "1 Day ";
}
$uptimestr .= sprintf("%02d Hours %02d Minutes %02d Seconds", $uphours, $upmins, $upsecs);
?>

<div class="row">
	<!-- Column 1: System Information Widget -->
	<div class="col-md-6" id="widgets-col1">
		<div class="panel panel-default" id="widget-system_information">
			<div class="panel-heading">
				<h2 class="panel-title">
					<i class="fa-solid fa-server"></i> System Information
					<span class="widget-heading-icon">
						<a href="/system.php" title="Configure System"><i class="fa-solid fa-cog"></i></a>
					</span>
				</h2>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-hover table-striped table-condensed">
						<tbody>
							<tr>
								<th class="col-w-35">Name</th>
								<td><strong><?=htmlspecialchars($sys['hostname'] ?? 'mitranet')?>.localdomain</strong></td>
							</tr>
							<tr>
								<th>System</th>
								<td>MitraNet Rinjani Appliance (Debian GNU/Linux 13)</td>
							</tr>
							<tr>
								<th>Version</th>
								<td>
									<strong><?=htmlspecialchars($sys['version'] ?? '1.0.2')?>-RELEASE</strong> (amd64)<br>
									<small class="text-muted">built on Linux kernel <?=htmlspecialchars($sys['kernel'] ?? '6.12')?></small>
								</td>
							</tr>
							<tr>
								<th>Platform</th>
								<td>MitraNet Appliance (amd64)</td>
							</tr>
							<tr>
								<th>Uptime</th>
								<td><?=htmlspecialchars($uptimestr)?></td>
							</tr>
							<tr>
								<th>Current Date/Time</th>
								<td><?=date("D M j H:i:s T Y")?></td>
							</tr>
							<tr>
								<th>State Table Size</th>
								<td>
									<?php
									$states_cnt = count($conntrack['states'] ?? []);
									$states_max = 100000;
									$states_pct = round(($states_cnt / $states_max) * 100, 1);
									?>
									<div class="progress">
										<div class="progress-bar progress-bar-striped" role="progressbar" style="width: <?=$states_pct?>%;"></div>
									</div>
									<span><?=$states_pct?>% (<?=number_format($states_cnt)?>/<?=number_format($states_max)?>)</span>
									&nbsp;<span><a href="/diag_dump_states.php">Show states</a></span>
								</td>
							</tr>
							<tr>
								<th>CPU Usage</th>
								<td>
									<?php
									$cpu = $sys['cpu'] ?? [];
									$cpu_used = max(0, 100 - ($cpu['idle'] ?? 100));
									?>
									<div class="progress">
										<div class="progress-bar progress-bar-striped progress-bar-danger" role="progressbar" style="width: <?=$cpu_used?>%;"></div>
									</div>
									<span><?=$cpu_used?>%</span>
								</td>
							</tr>
							<tr>
								<th>Memory Usage</th>
								<td>
									<?php
									$mem = $sys['memory'] ?? [];
									$mem_pct = $mem['used_percent'] ?? 0;
									?>
									<div class="progress">
										<div class="progress-bar progress-bar-striped progress-bar-info" role="progressbar" style="width: <?=$mem_pct?>%;"></div>
									</div>
									<span><?=$mem_pct?>% of <?=round(($mem['total_kb'] ?? 0)/1024)?> MiB</span>
								</td>
							</tr>
							<tr>
								<th>Config Transaction</th>
								<td>
									<span class="label label-success">Running: v<?=htmlspecialchars($cfg['running_version'] ?? 1)?></span>
									&nbsp;<span class="label label-info">Candidate: v<?=htmlspecialchars($cfg['candidate_version'] ?? 1)?></span>
									&nbsp;<span><a href="/diag_backup.php">History</a></span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Column 2: Interfaces & Gateways Widgets -->
	<div class="col-md-6" id="widgets-col2">
		<!-- Interfaces Widget -->
		<div class="panel panel-default" id="widget-interfaces">
			<div class="panel-heading">
				<h2 class="panel-title">
					<i class="fa-solid fa-network-wired"></i> Interfaces
					<span class="widget-heading-icon">
						<a href="/interfaces/interfaces.php" title="Manage Interfaces"><i class="fa-solid fa-cog"></i></a>
					</span>
				</h2>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover table-condensed">
						<thead>
							<tr>
								<th>Interface</th>
								<th>Status</th>
								<th>Media / MAC</th>
								<th>IP Address</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($ifaces)): ?>
								<tr><td colspan="4" class="text-center text-muted">No interfaces found.</td></tr>
							<?php else: foreach ($ifaces as $if): ?>
								<tr>
									<td class="td-nowrap">
										<i class="fa-solid fa-sitemap"></i>
										<a href="/interfaces.php?if=<?=urlencode($if['name'])?>">
											<strong><?=htmlspecialchars(strtoupper($if['name']))?></strong>
										</a>
									</td>
									<td>
										<?php if ($if['is_up']): ?>
											<i class="fa-solid fa-arrow-up text-success" title="up"></i>
										<?php else: ?>
											<i class="fa-solid fa-arrow-down text-danger" title="down"></i>
										<?php endif; ?>
									</td>
									<td>
										<code><?=htmlspecialchars($if['mac_address'] ?? 'n/a')?></code>
									</td>
									<td>
										<?php
										$v4 = $if['ipv4_addresses'] ?? [];
										if (!empty($v4)) {
											echo htmlspecialchars(implode(', ', $v4));
										} else {
											echo 'n/a';
										}
										?>
									</td>
								</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Gateways Widget -->
		<div class="panel panel-default" id="widget-gateways">
			<div class="panel-heading">
				<h2 class="panel-title">
					<i class="fa-solid fa-globe"></i> Gateways
					<span class="widget-heading-icon">
						<a href="/system_gateways.php" title="Manage Gateways"><i class="fa-solid fa-cog"></i></a>
					</span>
				</h2>
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover table-condensed">
						<thead>
							<tr>
								<th>Name</th>
								<th>Gateway</th>
								<th>Interface</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php if (empty($gateways)): ?>
								<tr><td colspan="4" class="text-center text-muted">No gateways configured.</td></tr>
							<?php else: foreach ($gateways as $gw): ?>
								<tr>
									<td>
										<i class="fa-regular fa-circle-check text-success"></i>
										<strong><?=htmlspecialchars($gw['name'])?></strong>
									</td>
									<td><?=htmlspecialchars($gw['gateway'])?></td>
									<td><span class="label label-default"><?=htmlspecialchars($gw['interface'])?></span></td>
									<td><span class="label label-success"><i class="fa-solid fa-check"></i> Online</span></td>
								</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
