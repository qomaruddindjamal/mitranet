<?php
/*
 * pkg_mgr_installed.php - MitraNet Package Manager: Installed Packages
 * Adapted from pfSense pkg_mgr_installed.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Package Manager", "Installed Packages");
$pglinks = array("", "/pkg_mgr_installed.php", "@self");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$packages = MitraNetApi::getPackages();

$tab_array = array(
    array("Installed Packages", true, "/pkg_mgr_installed.php"),
    array("Available Packages", false, "/pkg_mgr.php")
);
display_top_tabs($tab_array, false, 'pills');
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Installed Native Debian (.deb) Appliance Packages</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr>
					<th style="width: 30px;"><!-- Status Icon --></th>
					<th>Name</th>
					<th>Category</th>
					<th>Version</th>
					<th>Status</th>
					<th>Description</th>
				</tr>
			</thead>
			<tbody>
			<?php if (empty($packages)): ?>
				<tr><td colspan="6" class="text-center text-muted">No packages found or package list is empty.</td></tr>
			<?php else: ?>
				<?php foreach ($packages as $pkg): ?>
				<tr>
					<td><i class="fa-solid fa-check text-success" title="Installed & Synchronized"></i></td>
					<td><strong><?=htmlspecialchars($pkg['name'])?></strong></td>
					<td><span class="label label-info"><?=htmlspecialchars($pkg['category'] ?? 'system')?></span></td>
					<td><code><?=htmlspecialchars($pkg['version'])?></code></td>
					<td><span class="label label-success"><i class="fa-solid fa-check"></i> <?=htmlspecialchars(strtoupper($pkg['status']))?></span></td>
					<td><?=htmlspecialchars($pkg['description'] ?? '')?></td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div id="legend" class="alert-info text-center" style="padding: 10px; font-size: 12px;">
		<span><i class="fa-solid fa-check text-success"></i> = Installed &bull; Native Debian GNU/Linux 13 (Trixie) amd64 Repository Pool</span>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
