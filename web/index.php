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

<!-- DASHBOARD TOOLBAR -->
<div class="row" style="margin-bottom: 12px;">
	<div class="col-xs-12">
		<div class="pull-right" style="display: flex; gap: 8px; align-items: center;">
			<button type="button" class="btn btn-sm btn-default" id="btn-manage-widgets" data-toggle="modal" data-target="#modal-manage-widgets" title="Manage Visible Widgets">
				<i class="fa-solid fa-table-cells-large text-info"></i> Manage Widgets
			</button>
			<button type="button" class="btn btn-sm btn-default" id="btn-reset-dashboard" title="Reset to Default Layout">
				<i class="fa-solid fa-rotate-left text-muted"></i> Reset Layout
			</button>
		</div>
		<div class="clearfix"></div>
	</div>
</div>

<div class="row" id="dashboard-grid-container">
	<!-- Column 1: System Information Widget -->
	<div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 dashboard-col" id="widgets-col1">
		<div class="panel panel-default dashboard-widget" id="widget-system_information" data-widget="widget-system_information">
			<div class="panel-heading">
				<h2 class="panel-title">
					<span class="widget-drag-handle" title="Drag to move" style="cursor: move; margin-right: 6px; color: #8faecf;"><i class="fa-solid fa-grip-vertical"></i></span>
					<i class="fa-solid fa-server text-info"></i> System Information
					<span class="widget-heading-icon pull-right" style="display: flex; gap: 10px; align-items: center;">
						<a href="/system/system.php" title="Configure System"><i class="fa-solid fa-cog"></i></a>
						<a href="javascript:void(0)" class="widget-toggle-btn text-muted" title="Toggle Widget"><i class="fa-solid fa-chevron-up"></i></a>
					</span>
				</h2>
			</div>
			<div class="panel-body widget-content-body">
				<div class="table-responsive">
					<table class="table table-hover table-striped table-condensed">
						<tbody>
							<tr>
								<th class="col-w-35" style="width: 35%;">Name</th>
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
								<td><span id="sys-uptime-text"><?=htmlspecialchars($uptimestr)?></span></td>
							</tr>
							<tr>
								<th>Current Date/Time</th>
								<td><span id="sys-datetime-text"><?=date("D M j H:i:s T Y")?></span></td>
							</tr>
							<tr>
								<th>State Table Size</th>
								<td>
									<?php
									$is_conntrack_valid = is_array($conntrack) && isset($conntrack['total']) && is_numeric($conntrack['total']);
									$states_cnt = $is_conntrack_valid ? intval($conntrack['total']) : (is_array($conntrack) ? count($conntrack['states'] ?? []) : null);
									$states_max = 100000;
									if ($states_cnt !== null) {
										$states_pct = round(($states_cnt / $states_max) * 100, 1);
										$states_bar_width = $states_pct . '%';
										$states_text = $states_pct . '% (' . number_format($states_cnt) . '/' . number_format($states_max) . ')';
									} else {
										$states_bar_width = '0%';
										$states_text = 'n/a';
									}
									?>
									<div class="progress" style="margin-bottom: 5px;">
										<div id="sys-states-bar" class="progress-bar progress-bar-striped" role="progressbar" style="width: <?=$states_bar_width?>;"></div>
									</div>
									<span id="sys-states-text"><?=$states_text?></span>
									&nbsp;<span><a href="/diagnostics/diag_dump_states.php" class="text-info"><i class="fa-solid fa-arrow-up-right-from-square"></i> Show states</a></span>
								</td>
							</tr>
							<tr>
								<th>CPU Usage</th>
								<td>
									<?php
									$cpu = $sys['cpu'] ?? [];
									$cpu_used = max(0, 100 - ($cpu['idle'] ?? 100));
									?>
									<div class="progress" style="margin-bottom: 5px;">
										<div id="sys-cpu-bar" class="progress-bar progress-bar-striped progress-bar-danger" role="progressbar" style="width: <?=$cpu_used?>%;"></div>
									</div>
									<span id="sys-cpu-text"><?=$cpu_used?>%</span>
								</td>
							</tr>
							<tr>
								<th>Memory Usage</th>
								<td>
									<?php
									$mem = $sys['memory'] ?? [];
									$mem_pct = $mem['used_percent'] ?? 0;
									?>
									<div class="progress" style="margin-bottom: 5px;">
										<div id="sys-mem-bar" class="progress-bar progress-bar-striped progress-bar-info" role="progressbar" style="width: <?=$mem_pct?>%;"></div>
									</div>
									<span id="sys-mem-text"><?=$mem_pct?>% of <?=round(($mem['total_kb'] ?? 0)/1024)?> MiB</span>
								</td>
							</tr>
							<tr>
								<th>Config Transaction</th>
								<td>
									<span class="label label-success">Running: v<?=htmlspecialchars($cfg['running_version'] ?? 1)?></span>
									&nbsp;<span class="label label-info">Candidate: v<?=htmlspecialchars($cfg['candidate_version'] ?? 1)?></span>
									&nbsp;<span><a href="/diagnostics/diag_backup.php" class="text-info"><i class="fa-solid fa-clock-rotate-left"></i> History</a></span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Traffic Graph Widget (Fase 3: Real-Time HTML5 Canvas) -->
		<div class="panel panel-default dashboard-widget" id="widget-traffic_graph" data-widget="widget-traffic_graph">
			<div class="panel-heading">
				<h2 class="panel-title">
					<span class="widget-drag-handle" title="Drag to move" style="cursor: move; margin-right: 6px; color: #8faecf;"><i class="fa-solid fa-grip-vertical"></i></span>
					<i class="fa-solid fa-chart-area text-info"></i> Traffic Graph
					<span class="widget-heading-icon pull-right" style="display: flex; gap: 10px; align-items: center;">
						<a href="/interfaces/interfaces.php" title="Manage Interfaces"><i class="fa-solid fa-cog"></i></a>
						<a href="javascript:void(0)" class="widget-toggle-btn text-muted" title="Toggle Widget"><i class="fa-solid fa-chevron-up"></i></a>
					</span>
				</h2>
			</div>
			<div class="panel-body widget-content-body" style="padding: 10px 15px;">
				<!-- Controls: Interface select & Unit toggle -->
				<div class="row" style="margin-bottom: 8px;">
					<div class="col-xs-7 col-sm-8">
						<div class="input-group input-group-sm">
							<span class="input-group-addon" style="padding: 3px 8px; font-size: 11px; background: #eef4fb; font-weight: 600;">Interface</span>
							<select id="tg-interface-select" class="form-control input-sm" style="height: 28px; padding: 2px 6px; font-size: 12px;">
								<?php if (!empty($ifaces)): foreach ($ifaces as $idx => $ifi): ?>
									<option value="<?=htmlspecialchars($ifi['name'])?>" <?=$idx === 0 ? 'selected' : ''?>><?=htmlspecialchars(strtoupper($ifi['name']))?> (<?=htmlspecialchars($ifi['name'])?>)</option>
								<?php endforeach; else: ?>
									<option value="enp1s0">ENP1S0</option>
								<?php endif; ?>
							</select>
						</div>
					</div>
					<div class="col-xs-5 col-sm-4 text-right">
						<div class="btn-group btn-group-sm" role="group">
							<button type="button" class="btn btn-default btn-xs active" id="tg-unit-bits" style="font-size: 11px; padding: 3px 8px;">bps</button>
							<button type="button" class="btn btn-default btn-xs" id="tg-unit-bytes" style="font-size: 11px; padding: 3px 8px;">Bps</button>
						</div>
					</div>
				</div>

				<!-- Canvas Graph Container -->
				<div style="position: relative; width: 100%; height: 160px; background: #0f172a; border-radius: 4px; overflow: hidden; border: 1px solid #1e293b;">
					<canvas id="tg-canvas" width="500" height="160" style="display: block; width: 100%; height: 100%;"></canvas>
					<div id="tg-status-overlay" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.85); color: #94a3b8; font-size: 12px; display: flex; align-items: center; justify-content: center;">
						<span id="tg-status-text"><i class="fa-solid fa-spinner fa-spin"></i> Initializing stream...</span>
					</div>
				</div>

				<!-- Metrics Ticker Bar -->
				<div class="row" style="margin-top: 8px; font-size: 11px; color: #475569;">
					<div class="col-xs-4 text-left">
						<span style="display: inline-block; width: 8px; height: 8px; background: #0ea5e9; border-radius: 2px; margin-right: 4px;"></span>
						<strong>In (Rx):</strong> <span id="tg-val-rx" style="font-weight: 600; color: #0284c7;">0 bps</span>
					</div>
					<div class="col-xs-4 text-center">
						<span style="display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 2px; margin-right: 4px;"></span>
						<strong>Out (Tx):</strong> <span id="tg-val-tx" style="font-weight: 600; color: #059669;">0 bps</span>
					</div>
					<div class="col-xs-4 text-right">
						<strong>Peak:</strong> <span id="tg-val-peak" style="font-weight: 600; color: #475569;">0 bps</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Column 2: Interfaces & Gateways Widgets -->
	<div class="col-xs-12 col-sm-12 col-md-6 col-lg-6 dashboard-col" id="widgets-col2">
		<!-- Interfaces Widget -->
		<div class="panel panel-default dashboard-widget" id="widget-interfaces" data-widget="widget-interfaces">
			<div class="panel-heading">
				<h2 class="panel-title">
					<span class="widget-drag-handle" title="Drag to move" style="cursor: move; margin-right: 6px; color: #8faecf;"><i class="fa-solid fa-grip-vertical"></i></span>
					<i class="fa-solid fa-network-wired text-info"></i> Interfaces
					<span class="widget-heading-icon pull-right" style="display: flex; gap: 10px; align-items: center;">
						<a href="/interfaces/interfaces.php" title="Manage Interfaces"><i class="fa-solid fa-cog"></i></a>
						<a href="javascript:void(0)" class="widget-toggle-btn text-muted" title="Toggle Widget"><i class="fa-solid fa-chevron-up"></i></a>
					</span>
				</h2>
			</div>
			<div class="panel-body widget-content-body">
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
										<i class="fa-solid fa-sitemap text-muted"></i>
										<a href="/interfaces/interfaces.php?if=<?=urlencode($if['name'])?>">
											<strong><?=htmlspecialchars(strtoupper($if['name']))?></strong>
										</a>
									</td>
									<td>
										<?php if ($if['is_up']): ?>
											<span class="label label-success if-status-badge" data-if-name="<?=htmlspecialchars($if['name'])?>"><i class="fa-solid fa-arrow-up"></i> UP</span>
										<?php else: ?>
											<span class="label label-danger if-status-badge" data-if-name="<?=htmlspecialchars($if['name'])?>"><i class="fa-solid fa-arrow-down"></i> DOWN</span>
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
											echo '<span class="text-muted">n/a</span>';
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
		<div class="panel panel-default dashboard-widget" id="widget-gateways" data-widget="widget-gateways">
			<div class="panel-heading">
				<h2 class="panel-title">
					<span class="widget-drag-handle" title="Drag to move" style="cursor: move; margin-right: 6px; color: #8faecf;"><i class="fa-solid fa-grip-vertical"></i></span>
					<i class="fa-solid fa-globe text-info"></i> Gateways
					<span class="widget-heading-icon pull-right" style="display: flex; gap: 10px; align-items: center;">
						<a href="/system/gateways.php" title="Manage Gateways"><i class="fa-solid fa-cog"></i></a>
						<a href="javascript:void(0)" class="widget-toggle-btn text-muted" title="Toggle Widget"><i class="fa-solid fa-chevron-up"></i></a>
					</span>
				</h2>
			</div>
			<div class="panel-body widget-content-body">
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

<!-- MODAL: MANAGE DASHBOARD WIDGETS -->
<div class="modal fade" id="modal-manage-widgets" tabindex="-1" role="dialog" aria-labelledby="manageWidgetsModalLabel">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="manageWidgetsModalLabel"><i class="fa-solid fa-table-cells-large text-info"></i> Manage Widgets</h4>
			</div>
			<div class="modal-body">
				<p class="text-muted fs-12" style="font-size: 12px; margin-bottom: 12px;">Toggle widgets to show or hide on the dashboard:</p>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="toggle-widget-system_information" checked disabled>
						<strong>System Information</strong> <small class="text-muted">(Required)</small>
					</label>
				</div>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="toggle-widget-traffic_graph" class="widget-visibility-toggle" data-target="widget-traffic_graph" checked>
						<strong>Traffic Graph</strong>
					</label>
				</div>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="toggle-widget-interfaces" class="widget-visibility-toggle" data-target="widget-interfaces" checked>
						<strong>Interfaces</strong>
					</label>
				</div>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="toggle-widget-gateways" class="widget-visibility-toggle" data-target="widget-gateways" checked>
						<strong>Gateways</strong>
					</label>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
	var KEY_ORDER = 'mitranet_dashboard_order';
	var KEY_COLLAPSED = 'mitranet_dashboard_collapsed';
	var KEY_VISIBILITY = 'mitranet_dashboard_visibility';

	var defaultOrder = {
		'widgets-col1': ['widget-system_information', 'widget-traffic_graph'],
		'widgets-col2': ['widget-interfaces', 'widget-gateways']
	};

	// 1. Safe LocalStorage Getters/Setters
	function getStoredJSON(key, defaultVal) {
		try {
			var raw = localStorage.getItem(key);
			return raw ? JSON.parse(raw) : defaultVal;
		} catch (e) {
			console.warn('LocalStorage error reading ' + key, e);
			return defaultVal;
		}
	}

	function setStoredJSON(key, val) {
		try {
			localStorage.setItem(key, JSON.stringify(val));
		} catch (e) {
			console.warn('LocalStorage error writing ' + key, e);
		}
	}

	// 2. Restore Visibility State
	function restoreVisibility() {
		var vis = getStoredJSON(KEY_VISIBILITY, {});
		$('.widget-visibility-toggle').each(function() {
			var wid = $(this).data('target');
			if (vis[wid] === false) {
				$('#' + wid).hide();
				$(this).prop('checked', false);
			} else {
				$('#' + wid).show();
				$(this).prop('checked', true);
			}
		});
	}

	// 3. Restore Collapsed State
	function restoreCollapsed() {
		var col = getStoredJSON(KEY_COLLAPSED, {});
		$('.dashboard-widget').each(function() {
			var wid = $(this).data('widget');
			var $btn = $(this).find('.widget-toggle-btn i');
			var $body = $(this).find('.widget-content-body');
			if (col[wid] === true) {
				$body.hide();
				$btn.removeClass('fa-chevron-up').addClass('fa-chevron-down');
			} else {
				$body.show();
				$btn.removeClass('fa-chevron-down').addClass('fa-chevron-up');
			}
		});
	}

	// 4. Restore Reordered Positions Across Columns
	function restoreOrder() {
		var order = getStoredJSON(KEY_ORDER, defaultOrder);
		var allWidgets = {};
		$('.dashboard-widget').each(function() {
			allWidgets[$(this).attr('id')] = $(this);
		});

		$.each(['widgets-col1', 'widgets-col2'], function(_, colId) {
			var $col = $('#' + colId);
			if (order[colId] && Array.isArray(order[colId])) {
				$.each(order[colId], function(_, wid) {
					if (allWidgets[wid]) {
						$col.append(allWidgets[wid]);
						delete allWidgets[wid];
					}
				});
			}
		});

		// Append any remaining widgets not in stored config to col2
		$.each(allWidgets, function(_, $w) {
			$('#widgets-col2').append($w);
		});
	}

	function saveCurrentOrder() {
		var order = {
			'widgets-col1': [],
			'widgets-col2': []
		};
		$('#widgets-col1 .dashboard-widget').each(function() {
			order['widgets-col1'].push($(this).data('widget'));
		});
		$('#widgets-col2 .dashboard-widget').each(function() {
			order['widgets-col2'].push($(this).data('widget'));
		});
		setStoredJSON(KEY_ORDER, order);
	}

	// Initialize Layout
	restoreOrder();
	restoreCollapsed();
	restoreVisibility();

	// 5. Initialize jQuery UI Sortable with Connected Containers
	if (typeof $.fn.sortable !== 'undefined') {
		$('.dashboard-col').sortable({
			connectWith: '.dashboard-col',
			handle: '.widget-drag-handle',
			placeholder: 'panel panel-default dashboard-widget-placeholder',
			forcePlaceholderSize: true,
			opacity: 0.8,
			cursor: 'move',
			tolerance: 'pointer',
			start: function(e, ui) {
				ui.placeholder.css({
					'height': ui.item.outerHeight() + 'px',
					'border': '2px dashed #00bcd4',
					'background': '#eef6fc',
					'margin-bottom': '15px'
				});
			},
			stop: function() {
				saveCurrentOrder();
				if (typeof resizeTrafficCanvas === 'function') {
					resizeTrafficCanvas();
				}
			}
		});
	}

	// 6. Handle Minimize / Expand Toggle
	$(document).on('click', '.widget-toggle-btn', function(e) {
		e.preventDefault();
		var $panel = $(this).closest('.dashboard-widget');
		var wid = $panel.data('widget');
		var $body = $panel.find('.widget-content-body');
		var $icon = $(this).find('i');
		var isClosed = $body.is(':hidden');

		if (isClosed) {
			$body.slideDown(150, function() {
				if (wid === 'widget-traffic_graph' && typeof resizeTrafficCanvas === 'function') {
					resizeTrafficCanvas();
				}
			});
			$icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
		} else {
			$body.slideUp(150);
			$icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
		}

		var col = getStoredJSON(KEY_COLLAPSED, {});
		col[wid] = !isClosed;
		setStoredJSON(KEY_COLLAPSED, col);
	});

	// 7. Handle Manage Widgets Visibility Toggles
	$(document).on('change', '.widget-visibility-toggle', function() {
		var wid = $(this).data('target');
		var isChecked = $(this).is(':checked');
		if (isChecked) {
			$('#' + wid).fadeIn(150, function() {
				if (wid === 'widget-traffic_graph' && typeof resizeTrafficCanvas === 'function') {
					resizeTrafficCanvas();
				}
			});
		} else {
			$('#' + wid).fadeOut(150);
		}
		var vis = getStoredJSON(KEY_VISIBILITY, {});
		vis[wid] = isChecked;
		setStoredJSON(KEY_VISIBILITY, vis);
	});

	// 8. Handle Reset Layout Button
	$('#btn-reset-dashboard').on('click', function(e) {
		e.preventDefault();
		try {
			localStorage.removeItem(KEY_ORDER);
			localStorage.removeItem(KEY_COLLAPSED);
			localStorage.removeItem(KEY_VISIBILITY);
		} catch (err) {}

		// Restore default order in DOM immediately
		$('#widgets-col1').append($('#widget-system_information')).append($('#widget-traffic_graph'));
		$('#widgets-col2').append($('#widget-interfaces')).append($('#widget-gateways'));
		$('.dashboard-widget').show();
		$('.widget-content-body').show();
		$('.widget-toggle-btn i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
		$('.widget-visibility-toggle').prop('checked', true);

		if (typeof resizeTrafficCanvas === 'function') {
			resizeTrafficCanvas();
		}

		if (typeof MitraNet !== 'undefined' && typeof MitraNet.toast === 'function') {
			MitraNet.toast('success', 'Dashboard layout has been reset to default.');
		}
	});

	// =========================================================================
	// 9. Real-Time Traffic Graph Engine (HTML5 Canvas)
	// =========================================================================
	var tgCanvas = document.getElementById('tg-canvas');
	var tgCtx = tgCanvas ? tgCanvas.getContext('2d') : null;
	var tgOverlay = $('#tg-status-overlay');
	var tgStatusText = $('#tg-status-text');

	var tgConfig = {
		maxSamples: 60,
		pollIntervalMs: 1500,
		unit: 'bits', // 'bits' or 'bytes'
		currentIf: $('#tg-interface-select').val() || 'enp1s0'
	};

	var tgHistory = {
		rx: [],
		tx: []
	};

	// Fill initial zero points to maintain steady waveform width
	for (var i = 0; i < tgConfig.maxSamples; i++) {
		tgHistory.rx.push(0);
		tgHistory.tx.push(0);
	}

	var tgPrevSample = null; // { time: float, rx_bytes: float, tx_bytes: float }
	var tgPeakRate = 0;
	var tgPollTimer = null;
	var tgIsPolling = false;
	var tgConsecutiveErrors = 0;
	var tgActiveXhr = null;
	var tgPollRequestId = 0;

	function toStrictNonNegativeNumber(val) {
		if (typeof val === 'number') {
			return (isFinite(val) && val >= 0) ? val : null;
		}
		if (typeof val === 'string') {
			var trimmed = val.trim();
			if (trimmed === '' || !/^[0-9]+(\.[0-9]+)?$/.test(trimmed)) {
				return null;
			}
			var num = Number(trimmed);
			return (isFinite(num) && num >= 0) ? num : null;
		}
		return null;
	}

	function formatRate(bytesPerSec, unitType) {
		if (isNaN(bytesPerSec) || !isFinite(bytesPerSec) || bytesPerSec < 0) bytesPerSec = 0;
		if (unitType === 'bits') {
			var bits = bytesPerSec * 8;
			if (bits >= 1e9) return (bits / 1e9).toFixed(2) + ' Gbps';
			if (bits >= 1e6) return (bits / 1e6).toFixed(2) + ' Mbps';
			if (bits >= 1e3) return (bits / 1e3).toFixed(1) + ' Kbps';
			return bits.toFixed(0) + ' bps';
		} else {
			if (bytesPerSec >= 1e9) return (bytesPerSec / 1e9).toFixed(2) + ' GB/s';
			if (bytesPerSec >= 1e6) return (bytesPerSec / 1e6).toFixed(2) + ' MB/s';
			if (bytesPerSec >= 1e3) return (bytesPerSec / 1e3).toFixed(1) + ' KB/s';
			return bytesPerSec.toFixed(0) + ' B/s';
		}
	}

	function resizeTrafficCanvas() {
		if (!tgCanvas || !tgCanvas.parentElement) return;
		var rect = tgCanvas.parentElement.getBoundingClientRect();
		var w = Math.floor(rect.width);
		var h = Math.floor(rect.height);
		if (w > 0 && h > 0 && (tgCanvas.width !== w || tgCanvas.height !== h)) {
			tgCanvas.width = w;
			tgCanvas.height = h;
			drawTrafficGraph();
		}
	}

	function drawTrafficGraph() {
		if (!tgCtx || !tgCanvas) return;
		var w = tgCanvas.width;
		var h = tgCanvas.height;
		if (w <= 0 || h <= 0) return;

		// 1. Clear Canvas Background
		tgCtx.fillStyle = '#0f172a';
		tgCtx.fillRect(0, 0, w, h);

		// 2. Draw Subtle Grid Lines (4 horizontal bands)
		tgCtx.strokeStyle = '#1e293b';
		tgCtx.lineWidth = 1;
		var gridSteps = 4;
		for (var g = 1; g < gridSteps; g++) {
			var y = Math.floor((h / gridSteps) * g) + 0.5;
			tgCtx.beginPath();
			tgCtx.moveTo(0, y);
			tgCtx.lineTo(w, y);
			tgCtx.stroke();
		}

		// 3. Determine Dynamic Maximum Scale (minimum 1000 to avoid div by zero)
		var maxVal = 1000;
		for (var k = 0; k < tgConfig.maxSamples; k++) {
			if (tgHistory.rx[k] > maxVal) maxVal = tgHistory.rx[k];
			if (tgHistory.tx[k] > maxVal) maxVal = tgHistory.tx[k];
		}
		// Add 15% headroom for peak visuals
		maxVal = maxVal * 1.15;

		// Render helper for single area curve
		function drawSeries(data, strokeColor, fillColor) {
			var len = data.length;
			if (len < 2) return;
			var step = w / (len - 1);

			tgCtx.beginPath();
			tgCtx.moveTo(0, h);
			for (var idx = 0; idx < len; idx++) {
				var val = data[idx];
				var curY = h - (val / maxVal) * (h - 10);
				if (curY < 4) curY = 4;
				var curX = idx * step;
				if (idx === 0) {
					tgCtx.lineTo(curX, curY);
				} else {
					tgCtx.lineTo(curX, curY);
				}
			}
			tgCtx.lineTo(w, h);
			tgCtx.closePath();

			tgCtx.fillStyle = fillColor;
			tgCtx.fill();

			// Stroke top border line
			tgCtx.beginPath();
			for (var sIdx = 0; sIdx < len; sIdx++) {
				var sVal = data[sIdx];
				var sY = h - (sVal / maxVal) * (h - 10);
				if (sY < 4) sY = 4;
				var sX = sIdx * step;
				if (sIdx === 0) tgCtx.moveTo(sX, sY);
				else tgCtx.lineTo(sX, sY);
			}
			tgCtx.strokeStyle = strokeColor;
			tgCtx.lineWidth = 1.75;
			tgCtx.stroke();
		}

		// Draw Rx (Download) - Blue #0ea5e9
		drawSeries(tgHistory.rx, '#0ea5e9', 'rgba(14, 165, 233, 0.25)');
		// Draw Tx (Upload) - Green #10b981
		drawSeries(tgHistory.tx, '#10b981', 'rgba(16, 185, 129, 0.35)');

		// Draw dynamic scale legend in top-right corner
		tgCtx.fillStyle = '#64748b';
		tgCtx.font = '10px monospace';
		tgCtx.textAlign = 'right';
		tgCtx.fillText('Max: ' + formatRate(maxVal / 1.15, tgConfig.unit), w - 8, 14);
	}

	function pollTrafficStats() {
		if (tgIsPolling) return;
		if (document.hidden) return; // Do not poll when browser tab is inactive

		tgPollRequestId++;
		var thisRequestId = tgPollRequestId;
		var requestedIf = tgConfig.currentIf;
		tgIsPolling = true;

		tgActiveXhr = $.ajax({
			url: 'ifstats.php',
			type: 'GET',
			dataType: 'json',
			cache: false,
			timeout: 3000
		}).done(function(res) {
			// Ignore response if this request is superseded or interface changed
			if (thisRequestId !== tgPollRequestId || requestedIf !== tgConfig.currentIf) {
				return;
			}

			tgConsecutiveErrors = 0;
			tgOverlay.hide();

			if (!res || typeof res !== 'object' || !res.interfaces || res.timestamp === undefined) {
				tgOverlay.show();
				tgStatusText.html('<i class="fa-solid fa-triangle-exclamation text-warning"></i> Invalid data format');
				return;
			}

			var curTime = toStrictNonNegativeNumber(res.timestamp);
			if (curTime === null || curTime <= 0) {
				tgOverlay.show();
				tgStatusText.html('<i class="fa-solid fa-triangle-exclamation text-warning"></i> Invalid timestamp');
				return;
			}

			var ifData = res.interfaces[requestedIf];
			if (!ifData || typeof ifData !== 'object') {
				tgOverlay.show();
				tgStatusText.html('<i class="fa-solid fa-circle-info text-info"></i> Interface ' + requestedIf + ' not active');
				$('#tg-val-rx').text('N/A');
				$('#tg-val-tx').text('N/A');
				return;
			}

			if (ifData.rx_bytes === undefined || ifData.tx_bytes === undefined) {
				tgOverlay.show();
				tgStatusText.html('<i class="fa-solid fa-triangle-exclamation text-warning"></i> Missing counter data');
				$('#tg-val-rx').text('N/A');
				$('#tg-val-tx').text('N/A');
				return;
			}

			var curRx = toStrictNonNegativeNumber(ifData.rx_bytes);
			var curTx = toStrictNonNegativeNumber(ifData.tx_bytes);

			if (curRx === null || curTx === null) {
				tgOverlay.show();
				tgStatusText.html('<i class="fa-solid fa-triangle-exclamation text-warning"></i> Invalid counter values');
				$('#tg-val-rx').text('N/A');
				$('#tg-val-tx').text('N/A');
				return;
			}

			if (tgPrevSample !== null && tgPrevSample.ifName === requestedIf) {
				var dt = curTime - tgPrevSample.time;
				if (dt > 0.1) {
					var dRx = curRx - tgPrevSample.rx_bytes;
					var dTx = curTx - tgPrevSample.tx_bytes;

					// Detect counter reset / reboot / overflow
					if (dRx < 0) dRx = 0;
					if (dTx < 0) dTx = 0;

					var rateRx = dRx / dt; // bytes per second
					var rateTx = dTx / dt; // bytes per second

					if (!isFinite(rateRx) || rateRx < 0) rateRx = 0;
					if (!isFinite(rateTx) || rateTx < 0) rateTx = 0;

					// Append to ring buffer
					tgHistory.rx.shift();
					tgHistory.rx.push(rateRx);
					tgHistory.tx.shift();
					tgHistory.tx.push(rateTx);

					var higherRate = Math.max(rateRx, rateTx);
					if (higherRate > tgPeakRate) tgPeakRate = higherRate;

					// Update UI ticker values
					$('#tg-val-rx').text(formatRate(rateRx, tgConfig.unit));
					$('#tg-val-tx').text(formatRate(rateTx, tgConfig.unit));
					$('#tg-val-peak').text(formatRate(tgPeakRate, tgConfig.unit));

					drawTrafficGraph();
				}
			} else {
				// First baseline sample
				tgOverlay.hide();
			}

			tgPrevSample = {
				ifName: requestedIf,
				time: curTime,
				rx_bytes: curRx,
				tx_bytes: curTx
			};
		}).fail(function(jqXHR, statusText) {
			if (thisRequestId !== tgPollRequestId) {
				return;
			}
			if (statusText === 'abort') {
				return;
			}
			tgConsecutiveErrors++;
			if (tgConsecutiveErrors >= 2) {
				tgOverlay.show();
				tgStatusText.html('<i class="fa-solid fa-plug-circle-xmark text-danger"></i> Connection stale / ' + (statusText || 'error'));
			}
		}).always(function() {
			if (thisRequestId === tgPollRequestId) {
				tgIsPolling = false;
				tgActiveXhr = null;
			}
		});
	}

	function resetGraphBuffer() {
		tgHistory.rx = [];
		tgHistory.tx = [];
		for (var j = 0; j < tgConfig.maxSamples; j++) {
			tgHistory.rx.push(0);
			tgHistory.tx.push(0);
		}
		tgPrevSample = null;
		tgPeakRate = 0;
		$('#tg-val-rx').text('0 ' + (tgConfig.unit === 'bits' ? 'bps' : 'B/s'));
		$('#tg-val-tx').text('0 ' + (tgConfig.unit === 'bits' ? 'bps' : 'B/s'));
		$('#tg-val-peak').text('0 ' + (tgConfig.unit === 'bits' ? 'bps' : 'B/s'));
		drawTrafficGraph();
	}

	// Control Events: Interface Selection
	$('#tg-interface-select').on('change', function() {
		tgConfig.currentIf = $(this).val();
		if (tgActiveXhr && typeof tgActiveXhr.abort === 'function') {
			tgActiveXhr.abort();
		}
		tgPollRequestId++; // Invalidate any in-flight request ID
		tgIsPolling = false;
		tgActiveXhr = null;
		resetGraphBuffer();
		pollTrafficStats();
	});

	// Control Events: Unit Toggle (bps vs Bps)
	$('#tg-unit-bits').on('click', function() {
		$(this).addClass('active');
		$('#tg-unit-bytes').removeClass('active');
		tgConfig.unit = 'bits';
		drawTrafficGraph();
		if (tgHistory.rx.length > 0) {
			var lastRx = tgHistory.rx[tgHistory.rx.length - 1];
			var lastTx = tgHistory.tx[tgHistory.tx.length - 1];
			$('#tg-val-rx').text(formatRate(lastRx, tgConfig.unit));
			$('#tg-val-tx').text(formatRate(lastTx, tgConfig.unit));
			$('#tg-val-peak').text(formatRate(tgPeakRate, tgConfig.unit));
		}
	});

	$('#tg-unit-bytes').on('click', function() {
		$(this).addClass('active');
		$('#tg-unit-bits').removeClass('active');
		tgConfig.unit = 'bytes';
		drawTrafficGraph();
		if (tgHistory.rx.length > 0) {
			var lastRx = tgHistory.rx[tgHistory.rx.length - 1];
			var lastTx = tgHistory.tx[tgHistory.tx.length - 1];
			$('#tg-val-rx').text(formatRate(lastRx, tgConfig.unit));
			$('#tg-val-tx').text(formatRate(lastTx, tgConfig.unit));
			$('#tg-val-peak').text(formatRate(tgPeakRate, tgConfig.unit));
		}
	});

	// =========================================================================
	// 10. Real-Time System Metrics Polling Engine (Fase 4: CPU, RAM, States, IF)
	// =========================================================================
	var smConfig = {
		pollIntervalMs: 4000
	};
	var smPollTimer = null;
	var smIsPolling = false;
	var smActiveXhr = null;
	var smPollRequestId = 0;
	var smPrevCpuSample = null; // { user, system, idle, total, time }

	function formatUptimeDuration(totalSeconds) {
		if (totalSeconds === null || !isFinite(totalSeconds) || totalSeconds < 0) return 'n/a';
		var sec = Math.floor(totalSeconds);
		var days = Math.floor(sec / 86400);
		var hours = Math.floor((sec % 86400) / 3600);
		var mins = Math.floor((sec % 3600) / 60);
		var s = Math.floor(sec % 60);
		var str = '';
		if (days > 1) {
			str += days + ' Days ';
		} else if (days === 1) {
			str += '1 Day ';
		}
		var pad = function(n) { return (n < 10 ? '0' : '') + n; };
		str += pad(hours) + ' Hours ' + pad(mins) + ' Minutes ' + pad(s) + ' Seconds';
		return str;
	}

	function pollSystemMetrics() {
		if (smIsPolling) return;
		if (document.hidden) return; // Do not poll when tab is inactive

		smPollRequestId++;
		var thisReqId = smPollRequestId;
		smIsPolling = true;

		smActiveXhr = $.ajax({
			url: 'sysstats.php',
			type: 'GET',
			dataType: 'json',
			cache: false,
			timeout: 5000
		}).done(function(res) {
			if (thisReqId !== smPollRequestId) return;
			if (!res || typeof res !== 'object' || res.status !== 200 || !res.system) {
				return;
			}

			// 1. CPU Usage (Two-sample delta calculation)
			var cpu = res.system.cpu;
			if (cpu && typeof cpu === 'object' && typeof cpu.idle === 'number' && typeof cpu.total === 'number') {
				var curIdle = cpu.idle;
				var curTotal = cpu.total;

				if (smPrevCpuSample !== null) {
					var dIdle = curIdle - smPrevCpuSample.idle;
					var dTotal = curTotal - smPrevCpuSample.total;

					if (dTotal > 0 && dIdle >= 0) {
						var cpuUsagePct = Math.round((1 - (dIdle / dTotal)) * 100);
						if (cpuUsagePct < 0) cpuUsagePct = 0;
						if (cpuUsagePct > 100) cpuUsagePct = 100;

						$('#sys-cpu-bar').css('width', cpuUsagePct + '%');
						$('#sys-cpu-text').text(cpuUsagePct + '%');
					}
				}

				smPrevCpuSample = {
					idle: curIdle,
					total: curTotal,
					time: res.timestamp || Date.now() / 1000
				};
			}

			// 2. Memory Usage
			var mem = res.system.memory;
			if (mem && typeof mem === 'object' && typeof mem.used_percent === 'number') {
				var memPct = mem.used_percent;
				if (memPct < 0) memPct = 0;
				if (memPct > 100) memPct = 100;
				var totalMib = Math.round((mem.total_kb || 0) / 1024);

				$('#sys-mem-bar').css('width', memPct + '%');
				$('#sys-mem-text').text(memPct + '% of ' + totalMib + ' MiB');
			}

			// 3. System Uptime
			if (res.system.uptime_seconds !== undefined && res.system.uptime_seconds !== null) {
				var upText = formatUptimeDuration(res.system.uptime_seconds);
				$('#sys-uptime-text').text(upText);
			}

			// 4. Conntrack State Table
			if (res.conntrack && typeof res.conntrack.total === 'number') {
				var cnt = res.conntrack.total;
				var maxStates = 100000;
				var statesPct = Math.round((cnt / maxStates) * 1000) / 10;
				if (statesPct > 100) statesPct = 100;

				$('#sys-states-bar').css('width', statesPct + '%');
				$('#sys-states-text').text(statesPct + '% (' + cnt.toLocaleString() + '/' + maxStates.toLocaleString() + ')');
			}

			// 5. Interface Status Badges
			if (res.interfaces && typeof res.interfaces === 'object') {
				$.each(res.interfaces, function(ifKey, ifVal) {
					var $badge = $('.if-status-badge[data-if-name="' + ifKey + '"]');
					if ($badge.length) {
						if (ifVal.is_up) {
							if (!$badge.hasClass('label-success')) {
								$badge.removeClass('label-danger').addClass('label-success');
								$badge.html('<i class="fa-solid fa-arrow-up"></i> UP');
							}
						} else {
							if (!$badge.hasClass('label-danger')) {
								$badge.removeClass('label-success').addClass('label-danger');
								$badge.html('<i class="fa-solid fa-arrow-down"></i> DOWN');
							}
						}
					}
				});
			}
		}).fail(function(jqXHR, statusText) {
			if (thisReqId !== smPollRequestId) return;
			if (statusText === 'abort') return;
			// Retain last known values, no reset to zero
		}).always(function() {
			if (thisReqId === smPollRequestId) {
				smIsPolling = false;
				smActiveXhr = null;
			}
		});
	}

	// Page Visibility API: Pause polling when tab is hidden, resume immediately when visible
	document.addEventListener('visibilitychange', function() {
		if (document.hidden) {
			if (tgPollTimer) {
				clearInterval(tgPollTimer);
				tgPollTimer = null;
			}
			if (smPollTimer) {
				clearInterval(smPollTimer);
				smPollTimer = null;
			}
		} else {
			tgPrevSample = null; // reset sample baseline on tab return to avoid spike
			pollTrafficStats();
			if (!tgPollTimer) {
				tgPollTimer = setInterval(pollTrafficStats, tgConfig.pollIntervalMs);
			}

			smPrevCpuSample = null; // reset CPU sample baseline on tab return
			pollSystemMetrics();
			if (!smPollTimer) {
				smPollTimer = setInterval(pollSystemMetrics, smConfig.pollIntervalMs);
			}
		}
	});

	// Window resize handler
	$(window).on('resize', function() {
		resizeTrafficCanvas();
	});

	// Start Engines
	resizeTrafficCanvas();
	pollTrafficStats();
	tgPollTimer = setInterval(pollTrafficStats, tgConfig.pollIntervalMs);

	pollSystemMetrics();
	smPollTimer = setInterval(pollSystemMetrics, smConfig.pollIntervalMs);
});
//]]>
</script>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
