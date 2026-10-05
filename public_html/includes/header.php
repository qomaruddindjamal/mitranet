<?php
/**
 * MitraNet Network OS - Modular Topbar & Head Include
 * Modern White Enterprise Design System
 */
if (!defined('MITRANET_ROOT')) {
    require_once(__DIR__ . '/guiconfig.php');
}

$pageTitle = $pageTitle ?? 'MitraNet Rinjani Network OS';
$pageSubtitle = $pageSubtitle ?? 'Debian Powered Enterprise Network Appliance • High-Throughput Wire-Speed (1G/10G/100G)';
  $webRoot = $webRoot ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= html_esc($pageTitle) ?> - MitraNet</title>
  <meta name="description" content="MitraNet Rinjani Network Operating System - Enterprise Edge Router & Firewall">
  <!-- PWA (Progressive Web App) & Mobile App Meta -->
  <meta name="theme-color" content="#2563eb">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="MitraNet">
  <link rel="manifest" href="<?= $webRoot ?>manifest.json">
  <link rel="icon" type="image/svg+xml" href="<?= $webRoot ?>assets/icons/icon-192.svg">
  <link rel="apple-touch-icon" href="<?= $webRoot ?>assets/icons/icon-192.svg">
  <!-- 100% Offline Air-Gapped Ready: Local Bootstrap 5 & System Fonts (Zero External Network Calls) -->
  <link href="<?= $webRoot ?>vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $webRoot ?>style.css">
</head>

<body>
  <div class="app-layout" id="app-layout">
    <?php include(__DIR__ . '/sidebar.php'); ?>

    <!-- Main Content Area -->
    <main class="main-content">
      <!-- Fixed Header Topbar -->
      <header class="topbar fixed-header" id="topbar">
        <div class="topbar-left">
          <button class="sidebar-toggle-btn" id="sidebar-toggle-btn" onclick="toggleSidebar()"
            title="Toggle Sidebar (Collapse/Expand)">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2"
              stroke-linecap="round" stroke-linejoin="round">
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
          </button>
          <div class="topbar-titles">
            <h1 id="page-title"><?= html_esc($pageTitle) ?></h1>
            <p class="subtitle"><?= html_esc($pageSubtitle) ?></p>
          </div>
        </div>
        <div class="topbar-right">
          <button class="btn btn-primary" id="btn-reoptimize" onclick="triggerOptimize()">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            Optimize Network Stack
          </button>
          <?php
            $currUser = $_SESSION['mitranet_user'] ?? 'admin';
            $currRole = $_SESSION['mitranet_role'] ?? 'administrator';
            $roleBadgeClass = 'badge-indigo';
            if ($currRole === 'teknisi') $roleBadgeClass = 'badge-cyan';
            elseif ($currRole === 'viewer') $roleBadgeClass = 'badge-emerald';
          ?>
          <div class="user-pill dropdown">
            <span class="avatar"><?= strtoupper(substr($currUser, 0, 2)) ?></span>
            <span class="user-name"><?= html_esc($currUser) ?> <span class="badge <?= $roleBadgeClass ?>" style="font-size: 0.65rem; margin-left: 0.25rem;"><?= strtoupper(html_esc($currRole)) ?></span></span>
            <a href="<?= $webRoot ?>logout.php" class="btn btn-sm btn-outline text-danger" style="margin-left: 0.5rem; padding: 0.2rem 0.6rem; font-size: 0.75rem; text-decoration: none;" title="Sign out of MitraNet">
              Logout
            </a>
          </div>
        </div>
      </header>

      <!-- Content Body -->
      <div class="content-body">
        <!-- Notification Banner -->
        <div id="alert-banner" class="alert-banner hidden"></div>
