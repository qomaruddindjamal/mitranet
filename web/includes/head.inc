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
require_once(__DIR__ . '/guiconfig.inc');

if (!MitraNetApi::isAuthenticated()) {
    header('Location: /login.php');
    exit;
}

$pagetitle = 'MitraNet Appliance';
if (isset($pgtitle)) {
    if (is_array($pgtitle)) {
        $pagetitle = implode(': ', $pgtitle);
    } else {
        $pagetitle = $pgtitle;
    }
}

$hostname = 'mitranet';
$system_url = 'mitranet.localdomain';
$sys = MitraNetApi::getSystem();
if (!empty($sys['hostname'])) {
    $hostname = $sys['hostname'];
    $system_url = $hostname . '.localdomain';
}

$tabtitle = $pagetitle . " - " . htmlspecialchars($system_url);

// Build pfSense-standard navigation menus
$system_menu = array(
    array("Advanced", "/system_advanced.php"),
    array("General Setup", "/system.php"),
    array("Routing", "/system_gateways.php"),
    array("Configuration & Transactions", "/diag_backup.php"),
    array("Logout (" . htmlspecialchars($_SESSION['username'] ?? 'admin') . ")", "/logout.php")
);

$interfaces_menu = array(
    array("Assignments", "/interfaces_assign.php"),
    array("-DIVIDER-", "")
);
$ifaces_list = MitraNetApi::getInterfaces();
if (!empty($ifaces_list)) {
    foreach ($ifaces_list as $i_entry) {
        $interfaces_menu[] = array(strtoupper($i_entry['name']) . " (" . $i_entry['name'] . ")", "/interfaces.php?if=" . urlencode($i_entry['name']));
    }
}
$interfaces_menu[] = array("-DIVIDER-", "");
$interfaces_menu[] = array("VLANs", "/interfaces_vlan.php");
$interfaces_menu[] = array("Bridges", "/interfaces_bridge.php");
$interfaces_menu[] = array("LAGGs", "/interfaces_lagg.php");
$interfaces_menu[] = array("VRFs", "/interfaces_vrf.php");

$firewall_menu = array(
    array("Aliases", "/firewall_aliases.php"),
    array("NAT", "/firewall_nat.php"),
    array("Rules", "/firewall_rules.php")
);

$services_menu = array(
    array("Auto Config Backup", "/services_acb_settings.php"),
    array("DNS Resolver", "/services_unbound.php"),
    array("NTP", "/services_ntpd.php")
);

$vpn_menu = array(
    array("IPsec", "/vpn_ipsec.php"),
    array("OpenVPN", "/vpn_openvpn_server.php")
);

$status_menu = array(
    array("Dashboard", "/index.php"),
    array("Gateways", "/status_gateways.php"),
    array("Interfaces", "/status_interfaces.php"),
    array("Services", "/status_services.php"),
    array("System Logs", "/status_logs.php")
);

$diagnostics_menu = array(
    array("ARP Table", "/diag_arp.php"),
    array("Backup & Restore", "/diag_backup.php"),
    array("Ping", "/diag_ping.php"),
    array("Routes", "/diag_routes.php"),
    array("States", "/diag_dump_states.php"),
    array("Traceroute", "/diag_traceroute.php")
);

$nav_categories = array(
    array('name' => 'System', 'menu' => $system_menu),
    array('name' => 'Interfaces', 'menu' => $interfaces_menu),
    array('name' => 'Firewall', 'menu' => $firewall_menu),
    array('name' => 'Services', 'menu' => $services_menu),
    array('name' => 'VPN', 'menu' => $vpn_menu),
    array('name' => 'Status', 'menu' => $status_menu),
    array('name' => 'Diagnostics', 'menu' => $diagnostics_menu)
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<meta name="theme-color" content="#ffffff">

	<link rel="stylesheet" href="/vendor/font-awesome/css/all.min.css">
	<link rel="stylesheet" href="/vendor/sortable/sortable-theme-bootstrap.css">
	<link rel="stylesheet" href="/css/pfSense.css">

	<title><?=$tabtitle?></title>
</head>
<body id="page-default">
<nav id="topmenu" class="navbar navbar-static-top navbar-inverse">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#pf-navbar">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="/index.php">
				<?php
				$logo_p = __DIR__ . '/../images/logo.svg';
				if (file_exists($logo_p)) {
					include($logo_p);
				} else {
					echo '<span style="font-weight:900;font-size:22px;color:#fff;">MITRA<span style="color:#00bcd4;">NET</span></span>';
				}
				?>
				<span style="color:white;font-size:.5em;text-transform:uppercase;letter-spacing:1px;display:block;">Community Edition</span>
			</a>
		</div>

		<div class="collapse navbar-collapse" id="pf-navbar">
			<ul class="nav navbar-nav">
			<?php foreach ($nav_categories as $cat): ?>
				<li class="dropdown<?=((isset($selected_menu) && strtolower($selected_menu) === strtolower($cat['name'])) ? ' active' : '')?>">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
						<?=htmlspecialchars($cat['name'])?>
						<span class="caret"></span>
					</a>
					<ul class="dropdown-menu" role="menu">
					<?php foreach ($cat['menu'] as $m_item): ?>
						<?php if ($m_item[0] === '-DIVIDER-'): ?>
							<li class="divider"></li>
						<?php else: ?>
							<li><a href="<?=htmlspecialchars($m_item[1])?>"><?=htmlspecialchars($m_item[0])?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
					</ul>
				</li>
			<?php endforeach; ?>
			</ul>

			<ul class="nav navbar-nav navbar-right">
				<li class="dropdown">
					<a href="/logout.php">
						<i class="fa-solid fa-right-from-bracket" title="Logout (<?=htmlspecialchars($_SESSION['username'] ?? 'admin')?>@<?=htmlspecialchars($system_url)?>)"></i>
					</a>
				</li>
			</ul>
		</div>
	</div>
</nav>

<div class="container static">
	<header class="header">
		<?php
		if (isset($pglinks)) {
			echo genhtmltitle($pgtitle, $pglinks);
		} else {
			echo genhtmltitle(isset($pgtitle) ? $pgtitle : $pagetitle);
		}
		?>
		<ul class="context-links">
			<?php if (basename($_SERVER['SCRIPT_NAME']) === 'index.php'): ?>
			<li>
				<a href="#" title="Save dashboard layout" id="btnstore" class="invisible">
					<i class="fa-solid fa-save icon-pointer"></i>
				</a>
			</li>
			<?php endif; ?>
		</ul>
	</header>
