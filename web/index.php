<?php
/*
 * index.php - MitraNet System Dashboard
 * Adapted from pfSense index.php
 */

$pgtitle = "Dashboard";
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$sys = MitraNetApi::getSystem();
$ifaces = MitraNetApi::getInterfaces();
$gateways = MitraNetApi::getGateways();
$cfg = MitraNetApi::getConfigStatus();
?>

<div class="row">
	<!-- System Information Widget -->
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-server"></i> System Information</h3></div>
			<div class="panel-body">
				<table class="table table-striped table-condensed">
					<tbody>
						<tr>
							<td style="width: 35%;"><strong>Name</strong></td>
							<td><?=htmlspecialchars($sys['hostname'] ?? 'mitranet')?></td>
						</tr>
						<tr>
							<td><strong>System Version</strong></td>
							<td><?=htmlspecialchars($sys['os'] ?? 'MitraNet')?> <?=htmlspecialchars($sys['version'] ?? '1.0.2')?> (<?=htmlspecialchars($sys['codename'] ?? 'Rinjani')?>)</td>
						</tr>
						<tr>
							<td><strong>Kernel / OS</strong></td>
							<td><?=htmlspecialchars($sys['kernel'] ?? 'Linux')?> (<?=htmlspecialchars($sys['arch'] ?? 'amd64')?>)</td>
						</tr>
						<tr>
							<td><strong>Uptime</strong></td>
							<td><?=htmlspecialchars($sys['uptime'] ?? '--')?></td>
						</tr>
						<tr>
							<td><strong>CPU Model / Cores</strong></td>
							<td><?=htmlspecialchars($sys['cpu']['model'] ?? 'x86_64 CPU')?> (<?=htmlspecialchars($sys['cpu']['cores'] ?? 1)?> cores)</td>
						</tr>
						<tr>
							<td><strong>Memory Usage</strong></td>
							<td>
								<?php
								$mem = $sys['memory'] ?? [];
								$pct = $mem['used_percent'] ?? 0;
								?>
								<div class="progress" style="margin-bottom: 2px;">
									<div class="progress-bar progress-bar-info" role="progressbar" style="width: <?=$pct?>%;">
										<?=$pct?>%
									</div>
								</div>
								<small class="text-muted"><?=round(($mem['used_kb'] ?? 0)/1024)?> MiB / <?=round(($mem['total_kb'] ?? 0)/1024)?> MiB</small>
							</td>
						</tr>
						<tr>
							<td><strong>Config Transaction</strong></td>
							<td>
								<span class="label label-success">Version <?=htmlspecialchars($cfg['running_version'] ?? 1)?></span>
								&nbsp;Candidate: <span class="label label-info">v<?=htmlspecialchars($cfg['candidate_version'] ?? 1)?></span>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Interfaces Widget -->
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-network-wired"></i> Interfaces</h3></div>
			<div class="panel-body">
				<table class="table table-striped table-condensed">
					<thead>
						<tr>
							<th>Interface</th>
							<th>Status</th>
							<th>MAC Address</th>
							<th>IP Addresses</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($ifaces)): ?>
							<tr><td colspan="4" class="text-center text-muted">No interfaces detected</td></tr>
						<?php else: foreach ($ifaces as $if): ?>
							<tr>
								<td><strong><a href="/interfaces.php?if=<?=urlencode($if['name'])?>"><?=htmlspecialchars($if['name'])?></a></strong></td>
								<td>
									<?php if ($if['is_up']): ?>
										<span class="label label-success">UP</span>
									<?php else: ?>
										<span class="label label-danger">DOWN</span>
									<?php endif; ?>
								</td>
								<td><code><?=htmlspecialchars($if['mac_address'] ?? '--')?></code></td>
								<td>
									<?php
									$ips = array_merge($if['ipv4_addresses'] ?? [], $if['ipv6_addresses'] ?? []);
									echo empty($ips) ? '<span class="text-muted">None</span>' : htmlspecialchars(implode(', ', $ips));
									?>
								</td>
							</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Gateways Widget -->
		<div class="panel panel-default">
			<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-globe"></i> Gateways</h3></div>
			<div class="panel-body">
				<table class="table table-striped table-condensed">
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
							<tr><td colspan="4" class="text-center text-muted">No default gateways detected</td></tr>
						<?php else: foreach ($gateways as $gw): ?>
							<tr>
								<td><strong><?=htmlspecialchars($gw['name'])?></strong></td>
								<td><?=htmlspecialchars($gw['gateway'])?></td>
								<td><?=htmlspecialchars($gw['interface'])?></td>
								<td><span class="label label-success"><?=strtoupper(htmlspecialchars($gw['status']))?></span></td>
							</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
