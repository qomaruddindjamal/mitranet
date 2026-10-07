<?php
/*
 * diag_limiter_info.php - MitraNet Diagnostics: Limiter Info
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Limiter Info");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Limiter Information</h2></div>
	<div class="panel-body">
		<pre id="xhrOutput">Gathering Limiter information, please wait...</pre>
	</div>
</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
