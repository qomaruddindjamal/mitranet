<?php
/*
 * diag_defaults.php - MitraNet Diagnostics: Factory Defaults
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Factory Defaults");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title">Factory Defaults Reset</h2>
	</div>
	<div class="panel-body">
		<div class="content">
			<form action="diag_defaults.php" method="post">
				<p><strong>Resetting the system to factory defaults will remove all user configuration and apply the following settings:</strong></p>
				<ul>
					<li>Reset to factory defaults</li>
					<li>LAN IP address will be reset to 192.168.1.1</li>
					<li>System will be configured as a DHCP server on the default LAN interface</li>
					<li>Reboot after changes are installed</li>
					<li>WAN interface will be set to obtain an address automatically from a DHCP server</li>
					<li>webConfigurator admin username will be reset to 'admin'</li>
					<li>webConfigurator admin password will be reset to 'pfsense'</li>
				</ul>
				<p><strong>Are you sure you want to proceed?</strong></p>
				<p>
					<button name="Submit" type="submit" class="btn btn-sm btn-danger" value=" Yes " title="Perform a factory reset">
						<i class="fa-solid fa-undo"></i>
						Factory Reset					</button>
					<button name="Submit" type="submit" class="btn btn-sm btn-success" value=" No " title="Return to the dashboard">
						<i class="fa-solid fa-save"></i>
						Keep Configuration					</button>
				</p>
			</form>
		</div>
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
