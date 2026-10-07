<?php
/*
 * wizard.php - MitraNet System: Setup Wizard
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Setup Wizard");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>









<?php include(__DIR__ . '/includes/foot.inc'); ?>
