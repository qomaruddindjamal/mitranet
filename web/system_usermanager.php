<?php
/*
 * system_usermanager.php - MitraNet System: User Manager
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$savemsg = "";
$err_msg = "";

// Handle user deletion or creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['act']) && $_POST['act'] === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        if ($username === '' || $password === '') {
            $err_msg = "Username and password cannot be empty.";
        } elseif ($password !== $confirm) {
            $err_msg = "Passwords do not match.";
        } else {
            $res = MitraNetApi::createUser($username, $password);
            if ($res['status'] === 200 && !empty($res['data']['success'])) {
                $savemsg = "User '{$username}' successfully created.";
            } else {
                $err_msg = $res['data']['error'] ?? 'Failed to create user.';
            }
        }
    } elseif (isset($_POST['dellall']) || isset($_POST['act']) && $_POST['act'] === 'del') {
        $del_users = $_POST['delete_check'] ?? [];
        if (isset($_POST['del_user'])) {
            $del_users[] = $_POST['del_user'];
        }
        foreach ($del_users as $u) {
            if ($u !== 'admin') {
                MitraNetApi::deleteUser($u);
            }
        }
        $savemsg = "Selected users deleted.";
    }
}

$pgtitle = array("System", "User Manager");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$users = MitraNetApi::getUsers();
$is_add = isset($_GET['act']) && $_GET['act'] === 'new';
?>

<ul class="nav nav-pills">
    <li role="presentation" class="active"><a href="system_usermanager.php">Users</a></li>
    <li role="presentation"><a href="system_groupmanager.php">Groups</a></li>
    <li role="presentation"><a href="system_usermanager_settings.php">Settings</a></li>
    <li role="presentation"><a href="system_usermanager_passwordmg.php">Change Password</a></li>
    <li role="presentation"><a href="system_authservers.php">Authentication Servers</a></li>
</ul>

<?php if (!empty($savemsg)): ?>
    <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?=htmlspecialchars($savemsg)?></div>
<?php endif; ?>
<?php if (!empty($err_msg)): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?=htmlspecialchars($err_msg)?></div>
<?php endif; ?>

<?php if ($is_add): ?>
<div class="panel panel-default">
    <div class="panel-heading"><h2 class="panel-title">Add User</h2></div>
    <div class="panel-body">
        <form method="post" action="system_usermanager.php" class="form-horizontal">
            <input type="hidden" name="act" value="add" />
            <div class="form-group">
                <label class="col-sm-2 control-label" for="username"><span class="element-required">*</span> Username</label>
                <div class="col-sm-4">
                    <input type="text" class="form-control" id="username" name="username" required autocomplete="off" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="password"><span class="element-required">*</span> Password</label>
                <div class="col-sm-4">
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="password_confirm"><span class="element-required">*</span> Confirm Password</label>
                <div class="col-sm-4">
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required autocomplete="new-password" />
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button>
                    <a href="system_usermanager.php" class="btn btn-default"><i class="fa-solid fa-times"></i> Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<form method="post" action="system_usermanager.php">
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Users</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th style="width: 5%;">&nbsp;</th>
						<th>Username</th>
						<th>Scope</th>
						<th>Status</th>
						<th>Groups</th>
						<th style="text-align: right;">Actions</th>
					</tr>
				</thead>
				<tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <?php if ($u['username'] !== 'admin'): ?>
                                        <input type="checkbox" name="delete_check[]" value="<?=htmlspecialchars($u['username'])?>" />
                                    <?php else: ?>
                                        <input type="checkbox" disabled />
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fa-regular fa-user" style="margin-right: 5px;"></i>
                                    <strong><?=htmlspecialchars($u['username'])?></strong>
                                </td>
                                <td>
                                    <span class="label label-<?=($u['scope'] === 'system') ? 'primary' : 'default'?>">
                                        <?=htmlspecialchars($u['scope'])?>
                                    </span>
                                </td>
                                <td>
                                    <i class="fa-solid fa-check text-success" title="Enabled"></i> Enabled
                                </td>
                                <td>
                                    <?=htmlspecialchars(implode(', ', $u['groups'] ?? ['users']))?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($u['username'] !== 'admin'): ?>
                                        <form method="post" action="system_usermanager.php" style="display: inline-block;">
                                            <input type="hidden" name="act" value="del" />
                                            <input type="hidden" name="del_user" value="<?=htmlspecialchars($u['username'])?>" />
                                            <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Delete user?');">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="system_usermanager_passwordmg.php" class="btn btn-xs btn-info" title="Change Password">
                                            <i class="fa-solid fa-key"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                    <?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<nav class="action-buttons">
	<a href="?act=new" class="btn btn-sm btn-success">
		<i class="fa-solid fa-plus icon-embed-btn"></i> Add
	</a>
	<button type="submit" class="btn btn-sm btn-danger" name="dellall" value="dellall" title="Delete selected users" onclick="return confirm('Hapus pengguna yang dipilih?');">
		<i class="fa-solid fa-trash-can icon-embed-btn"></i> Delete
	</button>
</nav>
</form>

<div class="infoblock">
    <div class="bs-callout bs-callout-info">
        <p>Pengguna tambahan dapat ditambahkan di sini. Kredensial akun dikelola secara aman menggunakan PBKDF2-HMAC-SHA256.</p>
    </div>
</div>
<?php endif; ?>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
