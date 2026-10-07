<?php
/*
 * services_dhcp_settings.php - MitraNet Services: DHCP Server
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "DHCP Server");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div style="background: #1e3f75;" class="pagebody">
				<div class="col-sm-4"></div>
				<div class="col-sm-4 logoCol">
					<div class="loginCont center-block text-center">
						<strong><a href="/">Return to Dashboard</a></strong>
					</div>
				</div>
				<div class="col-sm-4"></div>
			</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
