<?php
/*
 * pkg_mgr_install.php - MitraNet System: Update
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Update");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/pkg_mgr_installed.php" >Installed Packages</a></li><li role="presentation"><a href="/pkg_mgr.php" >Available Packages</a></li></ul>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Installed Packages</h2></div>
	<div id="pkgtbl" class="panel-body">
		<div id="waitmsg">
			<div class="alert alert-warning clearfix" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><div class="pull-left">Please wait while the list of packages is retrieved and formatted.&nbsp;<i class="fa-solid fa-cog fa-spin"></i></div></div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
