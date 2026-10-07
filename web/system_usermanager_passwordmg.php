<?php
/*
 * system_usermanager_passwordmg.php - MitraNet System: User Password Manager
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "User Password Manager");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation"><a href="/system_usermanager.php" >Users</a></li><li role="presentation"><a href="/system_groupmanager.php" >Groups</a></li><li role="presentation"><a href="/system_usermanager_settings.php" >Settings</a></li><li role="presentation" class="active"><a href="/system_usermanager_passwordmg.php" >Change Password</a></li><li role="presentation"><a href="/system_authservers.php" >Authentication Servers</a></li></ul>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Change Password</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			
		</label>
			<div class="col-sm-10">
		
		This page changes the password for the current user in the local configuration. This affects all services which utilize the Local Authentication database (User Manager).<br/><br/>This page cannot change passwords for users from other authentication sources such as LDAP or RADIUS.
		

		
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
