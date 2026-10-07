<?php
/*
 * system_gateways.php - MitraNet Gateway Management
 * Adapted from pfSense system_gateways.php
 */

$pgtitle = array(gettext("System"), gettext("Routing"), gettext("Gateways"));
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$gateways = MitraNetApi::getGateways();

$tab_array = array();
$tab_array[] = array(gettext("Gateways"), true, "system_gateways.php");
$tab_array[] = array(gettext("Static Routes"), false, "system_routes.php");
display_top_tabs($tab_array);
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Gateways")?></h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th></th>
						<th><?=gettext("Name")?></th>
						<th><?=gettext("Default")?></th>
						<th><?=gettext("Interface")?></th>
						<th><?=gettext("Gateway")?></th>
						<th><?=gettext("Monitor IP")?></th>
						<th><?=gettext("Status")?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($gateways)): ?>
						<tr><td colspan="7" class="text-center text-muted"><?=gettext("No default gateways detected")?></td></tr>
					<?php else: foreach ($gateways as $gw): ?>
						<tr>
							<td title="<?=gettext('Gateway enabled')?>"><i class="fa-regular fa-circle-check"></i></td>
							<td><strong><?=htmlspecialchars($gw['name'])?></strong> <i class="fa-solid fa-globe"></i></td>
							<td><span class="label label-primary"><?=gettext("Default")?></span></td>
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
</div>

<nav class="action-buttons">
	<a href="system_gateways.php" role="button" class="btn btn-default btn-sm">
		<i class="fa-solid fa-rotate icon-embed-btn"></i>
		<?=gettext("Refresh")?>
	</a>
</nav>

<div class="infoblock">
<?php
print_info_box(
	sprintf(gettext('%1$s Default gateway is automatically selected based on interface priorities.'), '<strong><i class="fa-solid fa-globe"></i></strong> ') . '<br />' .
	sprintf(gettext('%1$s Gateway online and responsive to probe ICMP ping.'), '<strong><i class="fa-regular fa-circle-check"></i></strong> '), 'info', false);
?>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
