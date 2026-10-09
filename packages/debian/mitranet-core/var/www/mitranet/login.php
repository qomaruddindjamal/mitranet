<?php
/*
 * login.php
 *
 * Adapted from pfSense login interface
 * Adapted for MitraNet Rinjani 1.0.2 Native Linux Appliance
 */

require_once(__DIR__ . '/includes/api.inc');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        if (MitraNetApi::login($username, $password)) {
            header('Location: /index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Login - MitraNet Rinjani</title>
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="/css/login.css">
	<style>
		body { background-color: #2b303a; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
		.login-card { background: #fff; width: 380px; padding: 30px; border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
		.login-header { text-align: center; margin-bottom: 25px; }
		.login-header h2 { margin: 0; font-size: 26px; font-weight: bold; color: #222; }
		.login-header h2 span { color: #00bcd4; }
		.login-header p { margin: 5px 0 0; color: #777; font-size: 13px; }
		.btn-signin { background-color: #00bcd4; color: #fff; font-weight: bold; width: 100%; border: none; padding: 10px; margin-top: 15px; }
		.btn-signin:hover { background-color: #0097a7; color: #fff; }
	</style>
</head>
<body>
<div class="login-card">
	<div class="login-header">
		<h2>Mitra<span>Net</span></h2>
		<p>Rinjani 1.0.2 Network Operating System</p>
	</div>

	<?php if (!empty($error)): ?>
		<div class="alert alert-danger" role="alert">
			<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
			<?=htmlspecialchars($error)?>
		</div>
	<?php endif; ?>

	<form method="post" action="/login.php">
		<div class="form-group">
			<label for="username">Username</label>
			<input type="text" class="form-control" id="username" name="username" value="admin" required autofocus autocomplete="off">
		</div>
		<div class="form-group">
			<label for="password">Password</label>
			<input type="password" class="form-control" id="password" name="password" value="mitranet" required autocomplete="off">
		</div>
		<button type="submit" class="btn btn-signin">Sign In</button>
	</form>
</div>
</body>
</html>
