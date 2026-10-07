<?php
/*
 * system_usermanager.php - MitraNet System: User Manager
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "User Manager");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/system_usermanager.php" >Users</a></li><li role="presentation"><a href="/system_groupmanager.php" >Groups</a></li><li role="presentation"><a href="/system_usermanager_settings.php" >Settings</a></li><li role="presentation"><a href="/system_usermanager_passwordmg.php" >Change Password</a></li><li role="presentation"><a href="/system_authservers.php" >Authentication Servers</a></li></ul>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Users</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th>Username</th>
						<th>Full name</th>
						<th>Status</th>
						<th>Groups</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<input type="checkbox" id="frc0" name="delete_check[]" value="0" disabled/>
						</td>
						<td>
							<i class="fa-regular fa-eye" title="Scope: system"></i>
							admin						</td>
						<td>System Administrator</td>
						<td><i class="fa-solid fa-check" title="Enabled""><span style='display: none'>Enabled</span></i></td>
						<td>admins</td>
						<td>
							<a class="fa-solid fa-pencil" title="Edit user" href="?act=edit&amp;userid=0"></a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

<nav class="action-buttons">
	
	<a href="/?act=new" class="btn btn-sm btn-success">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>

	<button type="submit" class="btn btn-sm btn-danger" name="dellall" value="dellall" title="Delete selected users">
		<i class="fa-solid fa-trash-can icon-embed-btn"></i>
		Delete	</button>
	
</nav>

<div class="infoblock">
<div class="bs-callout bs-callout-info"><p>Additional users can be added here. User permissions for accessing the webConfigurator can be assigned directly or inherited from group memberships. Some system object properties can be modified but they cannot be deleted.</p><p>Accounts added here are also used for other parts of the system such as OpenVPN, IPsec, and Captive Portal.</p></div></div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
