<?php
/*
 * status_ipsec.php - MitraNet Status: IPsec
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "IPsec");
$selected_menu = "status";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills" role="tablist">
	<li role="presentation" class="active"><a href="ipsec.php" role="tab" aria-selected="true"><?=gettext("Overview")?></a></li>
	<li role="presentation" class="disabled"><a href="#" role="tab" aria-disabled="true" tabindex="-1" onclick="return false;" title="<?=gettext("Leases status is currently unavailable")?>"><?=gettext("Leases")?> <span class="badge"><?=gettext("Unavailable")?></span></a></li>
	<li role="presentation" class="disabled"><a href="#" role="tab" aria-disabled="true" tabindex="-1" onclick="return false;" title="<?=gettext("SADs status is currently unavailable")?>"><?=gettext("SADs")?> <span class="badge"><?=gettext("Unavailable")?></span></a></li>
	<li role="presentation" class="disabled"><a href="#" role="tab" aria-disabled="true" tabindex="-1" onclick="return false;" title="<?=gettext("SPDs status is currently unavailable")?>"><?=gettext("SPDs")?> <span class="badge"><?=gettext("Unavailable")?></span></a></li>
</ul>

<div class="alert alert-info clearfix" role="alert">
	<div class="pull-left">
		<i class="fa-solid fa-circle-info icon-embed-btn"></i>
		<?=gettext("IPsec service is operating in read-only status monitoring mode. Interactive tunnel connection controls are disabled on this platform.")?>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("IPsec Status")?></h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-condensed table-hover sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th><?=gettext("ID")?></th>
					<th><?=gettext("Description")?></th>
					<th><?=gettext("Local")?></th>
					<th><?=gettext("Remote")?></th>
					<th><?=gettext("Role")?></th>
					<th><?=gettext("Timers")?></th>
					<th><?=gettext("Algo")?></th>
					<th><?=gettext("Status")?></th>
				</tr>
			</thead>
			<tbody id="ipsec-body">
				<tr>
					<td colspan="8" class="text-center text-muted">
						<i class="fa-solid fa-circle-info"></i> <?=gettext("IPsec status data is unavailable. Interactive tunnel controls are disabled.")?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
