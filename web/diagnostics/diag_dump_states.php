<?php
/*
 * diag_dump_states.php - MitraNet Connection Tracking & States
 * Adapted from pfSense diag_dump_states.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Diagnostics"), gettext("States"));
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$conntrack = MitraNetApi::getConntrackStates();
$is_error = true;
$states = [];
$total_display = gettext("Unavailable");

if (is_array($conntrack)
    && isset($conntrack['states'])
    && is_array($conntrack['states'])
    && array_key_exists('total', $conntrack)) {

    $total_val = $conntrack['total'];
    $valid_total =
        (is_int($total_val) && $total_val >= 0)
        || (is_string($total_val) && $total_val !== '' && ctype_digit($total_val));

    if ($valid_total) {
        $is_error = false;
        $states = $conntrack['states'];
        $total_display = (string)$total_val;
    }
}

$tab_array = array();
$tab_array[] = array(gettext("States"), true, "diag_dump_states.php");
$tab_array[] = array(gettext("Reset States"), false, "diag_dump_states.php#reset");
display_top_tabs($tab_array);

if ($is_error) {
	print_info_box(gettext("Unable to retrieve connection tracking state table from MitraNet API."), "danger");
}
?>

<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title"><?=sprintf(gettext("Active Netfilter Conntrack States (Total: %s)"), htmlspecialchars($total_display))?></h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed" class="pre-log">
				<thead>
					<tr>
						<th><?=gettext("Proto")?></th>
						<th><?=gettext("State Details")?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ($is_error): ?>
					<tr><td colspan="2" class="text-center text-danger"><?=gettext("Connection tracking state data is currently unavailable.")?></td></tr>
				<?php elseif (empty($states)): ?>
					<tr><td colspan="2" class="text-center text-muted"><?=gettext("No active connection tracking states recorded.")?></td></tr>
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
	<a href="diag_dump_states.php" role="button" class="btn btn-default btn-sm">
		<i class="fa-solid fa-rotate icon-embed-btn"></i>
		<?=gettext("Refresh")?>
	</a>
</nav>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
