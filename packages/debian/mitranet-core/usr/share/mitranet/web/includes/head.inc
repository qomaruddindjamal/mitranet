<?php
/*
 * head.inc
 *
 * Adapted from pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2026 Rubicon Communications, LLC (Netgate)
 * Adapted for MitraNet Rinjani 1.0.2 Native Linux Appliance
 *
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/api.inc');

if (!MitraNetApi::isAuthenticated()) {
    header('Location: /login.php');
    exit;
}

$pagetitle = isset($pgtitle) ? $pgtitle : 'MitraNet Appliance';
$hostname = 'mitranet.local';
$sys = MitraNetApi::getSystem();
if (!empty($sys['hostname'])) {
    $hostname = $sys['hostname'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=htmlspecialchars($pagetitle)?> - MitraNet Rinjani</title>
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="/vendor/font-awesome/css/all.min.css">
	<link rel="stylesheet" href="/css/pfSense.css">
	<style>
		.navbar-brand { font-weight: bold; font-size: 20px; color: #fff !important; }
		.navbar-brand span { color: #00bcd4; }
		.badge-up { background-color: #5cb85c; }
		.badge-down { background-color: #d9534f; }
		.panel-title { font-weight: bold; }
	</style>
</head>
<body id="page-default">
<header class="header">
	<nav class="navbar navbar-inverse navbar-static-top" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-nav">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="/index.php">Mitra<span>Net</span></a>
			</div>

			<div class="collapse navbar-collapse" id="main-nav">
				<ul class="nav navbar-nav">
					<li class="<?=($selected_menu ?? '') === 'system' ? 'active dropdown' : 'dropdown'?>">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">System <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="/index.php">Dashboard</a></li>
							<li><a href="/system_routes.php">Routing</a></li>
							<li><a href="/system_gateways.php">Routing: Gateways</a></li>
							<li class="divider"></li>
							<li><a href="/system_advanced.php">Advanced Settings</a></li>
							<li><a href="/diag_backup.php">Configuration & Transactions</a></li>
						</ul>
					</li>

					<li class="<?=($selected_menu ?? '') === 'interfaces' ? 'active dropdown' : 'dropdown'?>">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">Interfaces <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="/interfaces_assign.php">Assignments</a></li>
							<li><a href="/interfaces.php">Interface Details</a></li>
							<li class="divider"></li>
							<li><a href="/interfaces_vlan.php">VLANs (802.1Q)</a></li>
							<li><a href="/interfaces_bridge.php">Bridges</a></li>
							<li><a href="/interfaces_lagg.php">LAGG / LACP Bonds</a></li>
							<li><a href="/interfaces_vrf.php">VRF Domains</a></li>
						</ul>
					</li>

					<li class="<?=($selected_menu ?? '') === 'firewall' ? 'active dropdown' : 'dropdown'?>">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">Firewall <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="/firewall_rules.php">Rules (nftables)</a></li>
							<li><a href="/firewall_nat.php">NAT (Coming Soon in 3B)</a></li>
							<li><a href="/firewall_aliases.php">Aliases</a></li>
						</ul>
					</li>

					<li class="<?=($selected_menu ?? '') === 'status' ? 'active dropdown' : 'dropdown'?>">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">Status <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="/index.php">Dashboard</a></li>
							<li><a href="/status_interfaces.php">Interfaces</a></li>
							<li><a href="/status_gateways.php">Gateways</a></li>
							<li class="divider"></li>
							<li><a href="/status_logs.php">System Logs</a></li>
						</ul>
					</li>

					<li class="<?=($selected_menu ?? '') === 'diagnostics' ? 'active dropdown' : 'dropdown'?>">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">Diagnostics <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a href="/diag_routes.php">Routing Table</a></li>
							<li><a href="/status_logs.php">Log Viewer</a></li>
							<li><a href="/diag_backup.php">Backup / Rollback</a></li>
						</ul>
					</li>
				</ul>

				<ul class="nav navbar-nav navbar-right">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
							<i class="fa fa-user"></i> <?=htmlspecialchars($_SESSION['username'] ?? 'admin')?> <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li><a href="/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</nav>
</header>
<div class="container-fluid" style="padding-top: 15px;">
