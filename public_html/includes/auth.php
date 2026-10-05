<?php
/**
 * MitraNet Network OS - Enterprise Authentication & RBAC Engine
 * 3 Role Levels: Administrator, Teknisi, Viewer
 * Password hashing: Cryptographic bcrypt (PHP password_hash)
 */

if (session_status() === PHP_SESSION_NONE) {
    // Hardened session cookie parameters
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!defined('MITRANET_ROOT')) {
    define('MITRANET_ROOT', dirname(__DIR__, 2));
}

define('MITRANET_USERS_FILE', '/etc/mitranet/users.json');
define('MITRANET_USERS_FALLBACK', MITRANET_ROOT . '/rootfs/etc/mitranet/users.json');
define('MITRANET_AUTH_CONF', '/etc/mitranet/auth.conf');
define('MITRANET_AUTH_FALLBACK', MITRANET_ROOT . '/rootfs/etc/mitranet/auth.conf');

/**
 * Loads user database configuration
 */
function get_users_config(): array {
    $path = file_exists(MITRANET_USERS_FILE) ? MITRANET_USERS_FILE : MITRANET_USERS_FALLBACK;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        if (is_array($data) && !empty($data['users'])) {
            return $data;
        }
    }

    // Default template if file doesn't exist
    return [
        'roles' => [
            'administrator' => [
                'name' => 'Administrator',
                'description' => 'Full access to all configurations, terminal and user management',
                'can_manage_users' => true,
                'can_access_terminal' => true,
                'can_modify_network' => true,
                'can_modify_firewall' => true,
                'is_read_only' => false
            ],
            'teknisi' => [
                'name' => 'Teknisi',
                'description' => 'Operational access to interfaces, firewall rules, QoS, VPN, routing, and diagnostic tools',
                'can_manage_users' => false,
                'can_access_terminal' => false,
                'can_modify_network' => true,
                'can_modify_firewall' => true,
                'is_read_only' => false
            ],
            'viewer' => [
                'name' => 'Viewer',
                'description' => 'Read-only audit & monitoring access to dashboard, graphs, rules, and telemetry',
                'can_manage_users' => false,
                'can_access_terminal' => false,
                'can_modify_network' => false,
                'can_modify_firewall' => false,
                'is_read_only' => true
            ]
        ],
        'users' => [
            [
                'username' => 'admin',
                'fullname' => 'System Administrator',
                'role' => 'administrator',
                'password_hash' => '$2y$10$5e2kv/Ir7mDVrAVfdFI/V.LHuaedAMYBHWCsxdde/eeJUL8UxRaUK', // mitranet
                'created_at' => '2026-10-05 00:00:00',
                'last_login' => null
            ]
        ]
    ];
}

/**
 * Saves user database configuration
 */
function save_users_config(array $config): bool {
    $path = file_exists(MITRANET_USERS_FILE) ? MITRANET_USERS_FILE : MITRANET_USERS_FALLBACK;
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $res = file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    // Also sync admin password to auth.conf for Python server & CLI compatibility
    foreach ($config['users'] as $u) {
        if ($u['username'] === 'admin') {
            $authPath = file_exists(MITRANET_AUTH_CONF) ? MITRANET_AUTH_CONF : MITRANET_AUTH_FALLBACK;
            $authDir = dirname($authPath);
            if (!is_dir($authDir)) {
                @mkdir($authDir, 0755, true);
            }
            // Store hash in auth.conf or sync format
            @file_put_contents($authPath, "admin:{$u['password_hash']}\n");
            break;
        }
    }

    return $res !== false;
}

/**
 * Validates login credentials and starts user session
 */
function authenticate_user(string $username, string $password): array {
    $username = trim($username);
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Username and password are required.'];
    }

    $config = get_users_config();
    $matchedUser = null;
    $userIndex = null;

    foreach ($config['users'] as $idx => $u) {
        if (strcasecmp($u['username'], $username) === 0) {
            $matchedUser = $u;
            $userIndex = $idx;
            break;
        }
    }

    if (!$matchedUser) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    // Verify cryptographic bcrypt hash
    $valid = password_verify($password, $matchedUser['password_hash']);

    // Fallback: If password in config is plaintext (legacy migration)
    if (!$valid && $matchedUser['password_hash'] === $password) {
        $valid = true;
        // Automatically upgrade to bcrypt hash
        $config['users'][$userIndex]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if (!$valid) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    // Update last login timestamp
    $config['users'][$userIndex]['last_login'] = date('Y-m-d H:i:s');
    save_users_config($config);

    // Initialize session state
    session_regenerate_id(true);
    $_SESSION['mitranet_logged_in'] = true;
    $_SESSION['mitranet_user'] = $matchedUser['username'];
    $_SESSION['mitranet_fullname'] = $matchedUser['fullname'] ?? $matchedUser['username'];
    $_SESSION['mitranet_role'] = $matchedUser['role'] ?? 'viewer';
    $_SESSION['mitranet_login_time'] = time();

    return [
        'success' => true,
        'user' => [
            'username' => $matchedUser['username'],
            'fullname' => $matchedUser['fullname'],
            'role' => $matchedUser['role']
        ]
    ];
}

/**
 * Checks if current request is from an authenticated user
 */
function is_user_authenticated(): bool {
    return !empty($_SESSION['mitranet_logged_in']) && !empty($_SESSION['mitranet_user']);
}

/**
 * Gets currently logged-in user role (administrator, teknisi, viewer)
 */
function get_current_user_role(): string {
    return $_SESSION['mitranet_role'] ?? 'viewer';
}

/**
 * Checks role permissions
 */
function has_role_permission(string $perm): bool {
    $role = get_current_user_role();
    $config = get_users_config();
    $roleInfo = $config['roles'][$role] ?? null;

    if (!$roleInfo) {
        return false;
    }

    return !empty($roleInfo[$perm]);
}

/**
 * Enforces permission; aborts with 403 Forbidden if not allowed
 */
function enforce_permission(string $perm) {
    if (!has_role_permission($perm)) {
        http_response_code(403);
        $pageTitle = '403 Forbidden';
        $pageSubtitle = 'Access Denied • Insufficient Role Privileges';
        include __DIR__ . '/header.php';
        echo '<div class="card" style="margin: 2rem auto; max-width: 600px; text-align: center;">';
        echo '  <div class="card-body" style="padding: 3rem 2rem;">';
        echo '    <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>';
        echo '    <h2 style="color: var(--accent-rose); margin-bottom: 0.5rem;">Access Denied</h2>';
        echo '    <p style="color: var(--text-dim); margin-bottom: 1.5rem;">Your account role (<strong>' . htmlspecialchars(get_current_user_role()) . '</strong>) does not have permission to access or modify this resource.</p>';
        echo '    <a href="../index.php" class="btn btn-primary">Return to Dashboard</a>';
        echo '  </div>';
        echo '</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

/**
 * Clears user session and logs out
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Adds or updates user
 */
function save_user(string $username, string $fullname, string $role, ?string $newPassword = null): array {
    $username = strtolower(trim($username));
    $fullname = trim($fullname);
    $validRoles = ['administrator', 'teknisi', 'viewer'];

    if (!in_array($role, $validRoles, true)) {
        return ['success' => false, 'message' => 'Invalid role specified. Must be administrator, teknisi, or viewer.'];
    }

    if (empty($username) || !preg_match('/^[a-z0-9_\-\.]{3,32}$/', $username)) {
        return ['success' => false, 'message' => 'Username must be 3-32 alphanumeric characters.'];
    }

    $config = get_users_config();
    $found = false;

    foreach ($config['users'] as $idx => &$u) {
        if ($u['username'] === $username) {
            $found = true;
            $u['fullname'] = $fullname ?: $u['fullname'];
            $u['role'] = $role;
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
                }
                $u['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            break;
        }
    }

    if (!$found) {
        if (empty($newPassword) || strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New user requires a password of at least 6 characters.'];
        }
        $config['users'][] = [
            'username' => $username,
            'fullname' => $fullname ?: ucfirst($username),
            'role' => $role,
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ];
    }

    save_users_config($config);
    return ['success' => true, 'message' => "User {$username} successfully saved."];
}

/**
 * Deletes user account
 */
function delete_user(string $username): array {
    $username = strtolower(trim($username));

    // Prevent deleting own logged in account
    if (isset($_SESSION['mitranet_user']) && strtolower($_SESSION['mitranet_user']) === $username) {
        return ['success' => false, 'message' => 'Cannot delete your own currently logged-in account.'];
    }

    $config = get_users_config();
    $adminCount = 0;
    $targetIdx = null;

    foreach ($config['users'] as $idx => $u) {
        if ($u['role'] === 'administrator') {
            $adminCount++;
        }
        if ($u['username'] === $username) {
            $targetIdx = $idx;
        }
    }

    if ($targetIdx === null) {
        return ['success' => false, 'message' => 'User not found.'];
    }

    if ($config['users'][$targetIdx]['role'] === 'administrator' && $adminCount <= 1) {
        return ['success' => false, 'message' => 'Cannot delete the last remaining administrator account.'];
    }

    array_splice($config['users'], $targetIdx, 1);
    save_users_config($config);

    return ['success' => true, 'message' => "User {$username} deleted successfully."];
}
