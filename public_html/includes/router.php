<?php
/**
 * MitraNet Network OS - Enterprise Master Layout Router
 * Unified single-point layout routing engine (Header -> View Content -> Footer)
 * Enforces session authentication & Role-Based Access Control (RBAC)
 */
require_once __DIR__ . '/guiconfig.php';
require_once __DIR__ . '/auth.php';

// Prevent recursive routing loops
if (defined('MITRANET_ROUTING_ACTIVE')) {
    return;
}
define('MITRANET_ROUTING_ACTIVE', true);

$baseDir = realpath(__DIR__ . '/..');

// 1. Resolve Target View File
$targetFile = null;
if (!empty($_SERVER['SCRIPT_FILENAME'])) {
    $script = realpath($_SERVER['SCRIPT_FILENAME']);
    if ($script && strpos($script, $baseDir) === 0 && is_file($script) && basename($script) !== 'router.php') {
        $targetFile = $script;
    }
}

if (!$targetFile) {
    $uri = $_GET['r'] ?? $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    $rel = trim($path, '/');

    if (empty($rel) || $rel === 'index.php') {
        $targetFile = $baseDir . DIRECTORY_SEPARATOR . 'index.php';
    } else {
        $cand = realpath($baseDir . DIRECTORY_SEPARATOR . $rel);
        if ($cand && strpos($cand, $baseDir) === 0) {
            if (is_dir($cand) && file_exists($cand . DIRECTORY_SEPARATOR . 'index.php')) {
                $targetFile = $cand . DIRECTORY_SEPARATOR . 'index.php';
            } elseif (is_file($cand) && substr($cand, -4) === '.php') {
                $targetFile = $cand;
            }
        }
        if (!$targetFile) {
            $candPhp = realpath($baseDir . DIRECTORY_SEPARATOR . $rel . '.php');
            if ($candPhp && strpos($candPhp, $baseDir) === 0 && is_file($candPhp)) {
                $targetFile = $candPhp;
            }
        }
    }
}

// 2. Compute Environment Variables ($webRoot, $currentDir, $currentPage, $packageJs)
if ($targetFile) {
    $targetDir = dirname($targetFile);
    if ($targetDir === $baseDir) {
        $webRoot = '';
        $currentDir = '';
        $packageJs = 'index.js';
    } else {
        $webRoot = '../';
        $currentDir = basename($targetDir);
        $expectedJs = $currentDir . '.js';
        if (file_exists($targetDir . DIRECTORY_SEPARATOR . $expectedJs)) {
            $packageJs = $expectedJs;
        } else {
            $packageJs = '';
        }
    }
    $currentPage = basename($targetFile);
} else {
    $webRoot = '';
    $currentDir = '';
    $packageJs = '';
    $currentPage = '';
}

// 3. Session Authentication Enforcement
// Allow unauthenticated access ONLY to login.php, logout.php, and manifest/assets
$scriptBaseName = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($scriptBaseName !== 'login.php' && $scriptBaseName !== 'logout.php') {
    if (!is_user_authenticated()) {
        $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . $webRoot . 'login.php?redirect=' . urlencode($reqUri));
        exit;
    }

    // Role-Based Access Control (RBAC) Enforcement
    $userRole = get_current_user_role();

    // Restricted for non-administrators
    if ($targetFile) {
        // Web Terminal: Administrator only
        if (strpos($targetFile, DIRECTORY_SEPARATOR . 'terminal' . DIRECTORY_SEPARATOR) !== false && !has_role_permission('can_access_terminal')) {
            enforce_permission('can_access_terminal');
        }

        // Users Management: Administrator only
        if (strpos($targetFile, DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'users.php') !== false && !has_role_permission('can_manage_users')) {
            enforce_permission('can_manage_users');
        }
    }

    // Read-only viewer restriction on mutation POST requests
    if ($userRole === 'viewer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Action rejected: Read-only viewer accounts cannot alter configuration.']);
        exit;
    }
}

// 4. 404 Handler if file not found
if (!$targetFile || !file_exists($targetFile)) {
    http_response_code(404);
    $pageTitle = 'Module Not Found';
    $pageSubtitle = 'The requested system module does not exist on this appliance.';
    include __DIR__ . '/header.php';
    echo '<div class="card"><div class="card-body"><h3>404 - Module Not Found</h3><p>The requested route could not be found.</p></div></div>';
    include __DIR__ . '/footer.php';
    exit;
}

// Set default titles before view
$pageTitle = 'MitraNet Network OS';
$pageSubtitle = 'Enterprise Routing, Firewall & Security Appliance';

// 5. Capture View Content (View can override $pageTitle, $pageSubtitle, $packageJs)
define('MITRANET_ROUTED', true);
ob_start();
include $targetFile;
$pageContent = ob_get_clean();

// 6. Render Unified Master Layout (Single Routing Point)
include __DIR__ . '/header.php';
echo $pageContent;
include __DIR__ . '/footer.php';
exit;
