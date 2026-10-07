<?php
/*
 * diag_halt.php - MitraNet Diagnostics: Halt System
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Halt System");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title">System Halt Confirmation</h2>
	</div>
	<div class="panel-body">
		<div class="content">
			<p>Click "Halt" to halt the system immediately, or "Cancel" to go to the system dashboard. (There will be a brief delay before the dashboard appears.)</p>
			<form action="diag_halt.php" method="post">
				<button type="submit" class="btn btn-danger pull-center" name="save" value="Halt" title="Halt the system and power off">
					<i class="fa-solid fa-stop-circle"></i>
					Halt				</button>
				<a href="/" class="btn btn-info">
					<i class="fa-solid fa-undo"></i>
					Cancel				</a>
			</form>
		</div>
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
