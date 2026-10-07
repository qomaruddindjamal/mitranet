<?php
/*
 * diag_system_activity.php - MitraNet Diagnostics: System Activity
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "System Activity");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">CPU Activity</h2></div>
	<div class="panel panel-body">
		<pre id="xhrOutput">Gathering CPU activity, please wait...</pre>
	</div>
</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
