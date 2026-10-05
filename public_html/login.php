<?php
/**
 * MitraNet Network OS - Enterprise WebUI Authentication Portal
 * Modern White Enterprise Design System
 */
require_once __DIR__ . '/includes/auth.php';

$error = '';
$redirect = $_GET['redirect'] ?? 'index.php';

// If already logged in, redirect straight to requested destination
if (is_user_authenticated()) {
    header('Location: ' . (empty($redirect) ? 'index.php' : $redirect));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $redir = $_POST['redirect'] ?? 'index.php';

    $res = authenticate_user($user, $pass);
    if ($res['success']) {
        // Prevent open redirect vulnerabilities
        if (empty($redir) || strpos($redir, '://') !== false) {
            $redir = 'index.php';
        }
        header('Location: ' . $redir);
        exit;
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - MitraNet Network OS</title>
  <meta name="theme-color" content="#2563eb">
  <link rel="icon" type="image/svg+xml" href="assets/icons/icon-192.svg">
  <link rel="apple-touch-icon" href="assets/icons/icon-192.svg">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      margin: 0;
      font-family: var(--font-sans);
    }
    .login-container {
      width: 100%;
      max-width: 440px;
    }
    .login-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-xl);
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
      padding: 2.5rem 2.25rem;
    }
    .login-brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.85rem;
      margin-bottom: 1.75rem;
    }
    .login-brand .logo-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, var(--accent-primary) 0%, #1d4ed8 100%);
      border-radius: var(--radius-lg);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.25);
    }
    .login-brand-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-main);
      letter-spacing: -0.025em;
    }
    .login-brand-badge {
      font-size: 0.7rem;
      font-weight: 700;
      color: var(--accent-primary);
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: var(--radius-full);
      padding: 0.15rem 0.6rem;
      text-transform: uppercase;
      margin-left: 0.35rem;
    }
    .login-desc {
      text-align: center;
      color: var(--text-muted);
      font-size: 0.875rem;
      margin-bottom: 2rem;
    }
    .form-group {
      margin-bottom: 1.25rem;
    }
    .form-group label {
      display: block;
      font-size: 0.8125rem;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 0.4rem;
    }
    .form-control-input {
      width: 100%;
      padding: 0.75rem 1rem;
      font-size: 0.9375rem;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      background: #f8fafc;
      transition: all 0.15s ease-in-out;
      outline: none;
    }
    .form-control-input:focus {
      background: #ffffff;
      border-color: var(--accent-primary);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .btn-login {
      width: 100%;
      padding: 0.85rem;
      font-size: 0.9375rem;
      font-weight: 600;
      background: var(--accent-primary);
      color: #ffffff;
      border: none;
      border-radius: var(--radius-md);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      transition: all 0.15s ease-in-out;
      box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }
    .btn-login:hover {
      background: var(--accent-hover);
      box-shadow: 0 6px 8px -1px rgba(37, 99, 235, 0.3);
    }
    .login-footer {
      margin-top: 1.75rem;
      text-align: center;
      font-size: 0.75rem;
      color: var(--text-dim);
    }
    .default-creds {
      background: #f1f5f9;
      border-radius: var(--radius-md);
      padding: 0.75rem;
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-top: 1.5rem;
      border: 1px dashed #cbd5e1;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-brand">
        <div class="logo-icon">
          <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
            <line x1="6" y1="6" x2="6.01" y2="6"></line>
            <line x1="6" y1="18" x2="6.01" y2="18"></line>
          </svg>
        </div>
        <div>
          <span class="login-brand-title">MitraNet</span>
          <span class="login-brand-badge">Rinjani</span>
        </div>
      </div>

      <p class="login-desc">Sign in to access Enterprise Network &amp; Security Controller</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="font-size: 0.8125rem; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.25rem;">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($_GET['logged_out'])): ?>
        <div class="alert alert-success" style="font-size: 0.8125rem; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.25rem;">
          You have been securely signed out.
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control-input" placeholder="admin" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control-input" placeholder="••••••••" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-login" style="margin-top: 1.5rem;">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
            <polyline points="10 17 15 12 10 7"></polyline>
            <line x1="15" y1="12" x2="3" y2="12"></line>
          </svg>
          Authenticate Session
        </button>
      </form>

      <div class="default-creds">
        <strong>Default Credentials:</strong><br>
        Admin: <code>admin</code> / <code>mitranet</code> • Teknisi: <code>teknisi</code> / <code>teknisi</code> • Viewer: <code>viewer</code> / <code>viewer</code>
      </div>
    </div>

    <div class="login-footer">
      MitraNet Rinjani Network OS • Debian 13 Core • Wire-Speed FastPath
    </div>
  </div>
</body>
</html>
