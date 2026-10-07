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

// Build pfSense-standard navigation menus matching live pfSense 2.9 sidebar
$system_menu = array(
    array("Advanced", "/system_advanced.php"),
    array("Certificates", "#"),
    array("General Setup", "/system.php"),
    array("High Availability", "#"),
    array("Package Manager", "#"),
    array("Register", "#"),
    array("Routing", "/system_gateways.php"),
    array("Setup Wizard", "#"),
    array("Update", "#"),
    array("User Manager", "#"),
    array("User Password Manager", "#"),
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
    array("Rules", "/firewall_rules.php"),
    array("Schedules", "#"),
    array("Traffic Shaper", "#"),
    array("Virtual IPs", "#")
);

$services_menu = array(
    array("Auto Config Backup", "#"),
    array("Captive Portal", "#"),
    array("DHCP Relay", "#"),
    array("DHCP Server", "#"),
    array("DHCPv6 Relay", "#"),
    array("DHCPv6 Server", "#"),
    array("DNS Forwarder", "#"),
    array("DNS Resolver", "#"),
    array("Dynamic DNS", "#"),
    array("IGMP Proxy", "#"),
    array("NTP", "#"),
    array("PPPoE Server", "#"),
    array("Router Advertisement", "#"),
    array("SNMP", "#"),
    array("UPnP IGD &amp; PCP", "#"),
    array("Wake-on-LAN", "#")
);

$vpn_menu = array(
    array("IPsec", "#"),
    array("L2TP", "#"),
    array("OpenVPN", "#"),
    array("Xray-core", "#")
);

$status_menu = array(
    array("Captive Portal", "#"),
    array("CARP (failover)", "#"),
    array("Dashboard", "/index.php"),
    array("DHCP Leases", "#"),
    array("DHCPv6 Leases", "#"),
    array("DNS Resolver", "#"),
    array("Filter Reload", "#"),
    array("Gateways", "/status_gateways.php"),
    array("Interfaces", "/status_interfaces.php"),
    array("IPsec", "#"),
    array("Monitoring", "#"),
    array("NTP", "#"),
    array("OpenVPN", "#"),
    array("Queues", "#"),
    array("Services", "#"),
    array("System Logs", "/status_logs.php"),
    array("Traffic Graph", "#"),
    array("UPnP IGD &amp; PCP", "#")
);

$diagnostics_menu = array(
    array("ARP Table", "/diag_arp.php"),
    array("Authentication", "#"),
    array("Backup &amp; Restore", "/diag_backup.php"),
    array("Command Prompt", "#"),
    array("DNS Lookup", "#"),
    array("Edit File", "#"),
    array("Factory Defaults", "#"),
    array("Halt System", "#"),
    array("Limiter Info", "#"),
    array("NDP Table", "#"),
    array("Packet Capture", "#"),
    array("pfInfo", "#"),
    array("pfTop", "#"),
    array("Ping", "/diag_ping.php"),
    array("Reboot", "#"),
    array("Routes", "/diag_routes.php"),
    array("S.M.A.R.T. Status", "#"),
    array("Sockets", "#"),
    array("States", "/diag_dump_states.php"),
    array("States Summary", "#"),
    array("System Activity", "#"),
    array("Tables", "#"),
    array("Terminal", "#"),
    array("Test Port", "#"),
    array("Traceroute", "/diag_traceroute.php")
);

$tools_menu = array(
    array("Speedtest", "#")
);

$help_menu = array(
    array("About this Page", "/help.php?page=" . urlencode(basename($_SERVER['SCRIPT_NAME'])), "_blank"),
    array("Bug Database", "https://redirects.netgate.com/issues", "_blank"),
    array("Documentation", "https://redirects.netgate.com/docs", "_blank"),
    array("FreeBSD Handbook", "https://redirects.netgate.com/fbsdhandbook", "_blank"),
    array("Paid Support", "https://redirects.netgate.com/support", "_blank"),
    array("pfSense Book", "https://redirects.netgate.com/book", "_blank"),
    array("Upgrade to pfSense Plus", "https://redirects.netgate.com/upgrade", "_blank"),
    array("User Forum", "https://redirects.netgate.com/forum", "_blank")
);

$sidebar_structure = array(
    array('id' => 'system', 'name' => 'System', 'icon' => 'fa-gears', 'menu' => $system_menu),
    array('id' => 'interfaces', 'name' => 'Interfaces', 'icon' => 'fa-network-wired', 'menu' => $interfaces_menu),
    array('id' => 'firewall', 'name' => 'Firewall', 'icon' => 'fa-shield-halved', 'menu' => $firewall_menu),
    array('id' => 'services', 'name' => 'Services', 'icon' => 'fa-cubes', 'menu' => $services_menu),
    array('id' => 'vpn', 'name' => 'VPN', 'icon' => 'fa-key', 'menu' => $vpn_menu),
    array('id' => 'kvm', 'name' => 'KVM', 'icon' => 'fa-server', 'url' => '/services_virtual.php', 'direct' => true),
    array('id' => 'diagnostics', 'name' => 'Diagnostics', 'icon' => 'fa-toolbox', 'menu' => $diagnostics_menu),
    array('id' => 'terminal', 'name' => 'Terminal', 'icon' => 'fa-terminal', 'url' => '/diag_terminal.php', 'direct' => true),
    array('id' => 'tools', 'name' => 'Tools', 'icon' => 'fa-screwdriver-wrench', 'menu' => $tools_menu),
    array('id' => 'status', 'name' => 'Status', 'icon' => 'fa-chart-line', 'menu' => $status_menu),
    array('id' => 'help', 'name' => 'Help', 'icon' => 'fa-circle-question', 'menu' => $help_menu)
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
	<link rel="stylesheet" href="/css/sidebar-layout.css">

	<title><?=$tabtitle?></title>
</head>
<body id="page-default">
<nav id="topmenu" class="navbar navbar-static-top navbar-inverse">
	<div class="header-inner">
		<div class="header-left">
			<div class="header-brand-area">
				<a class="navbar-brand" href="/index.php" title="MitraNet Home">
					<?php
					$logo_p = __DIR__ . '/../images/logo.svg';
					if (!file_exists($logo_p)) {
						$logo_p = __DIR__ . '/../logo.svg';
					}
					if (file_exists($logo_p)) {
						include($logo_p);
					} else {
						echo '<span style="font-weight:900;font-size:22px;color:#fff;">MITRA<span style="color:#00bcd4;">NET</span></span>';
					}
					?>
				</a>
			</div>
			<div class="header-nav-actions">
				<button type="button" id="sidebar-toggle-btn" title="Toggle Navigation">
					<i class="fa-solid fa-bars"></i>
				</button>
				<a href="/index.php" class="header-home-btn" title="Home">
					<i class="fa-solid fa-house"></i>
					<span>Home</span>
				</a>
			</div>
		</div>
		<ul class="header-right-nav">
			<li class="header-host-info">
				<i class="fa-solid fa-server"></i>
				<span><?=htmlspecialchars($system_url)?></span>
			</li>
			<li>
				<a href="#" data-toggle="modal" data-target="#notices" class="header-btn-link" title="Notices">
					<i class="fa-solid fa-bell"></i>
				</a>
			</li>
			<li>
				<a href="/logout.php" class="header-btn-link" title="Logout (<?=htmlspecialchars($_SESSION['username'] ?? 'admin')?>@<?=htmlspecialchars($system_url)?>)">
					<i class="fa-solid fa-right-from-bracket"></i>
				</a>
			</li>
		</ul>
	</div>
</nav>

<!-- Backdrop overlay for mobile devices -->
<div id="sidebar-backdrop"></div>

<!-- Left Sidebar Navigation (Matching pfSense 2.9 modern sidebar layout) -->
<aside id="pf-sidebar">
	<div class="sidebar-header-box">
		<div class="sidebar-brand-title">
			<i class="fa-solid fa-shield-halved" style="color: #337ab7; margin-right: 8px;"></i>
			<span>MitraNet</span>
		</div>
		<button id="sidebar-close-btn" class="sidebar-close-btn" aria-label="Close Navigation">
			<i class="fa-solid fa-xmark"></i>
		</button>
	</div>
	<div class="sidebar-section-title">Navigation</div>
	<ul class="sidebar-menu">
	<?php foreach ($sidebar_structure as $s_item): ?>
		<?php if (!empty($s_item['direct'])): ?>
			<li class="sidebar-item">
				<a href="<?=htmlspecialchars($s_item['url'])?>" class="sidebar-link direct-link" title="<?=htmlspecialchars($s_item['name'])?>">
					<i class="fa-solid <?=htmlspecialchars($s_item['icon'])?> sidebar-icon"></i>
					<span class="sidebar-text"><?=htmlspecialchars($s_item['name'])?></span>
				</a>
			</li>
		<?php else: ?>
			<li class="sidebar-item">
				<a href="#sub-<?=htmlspecialchars($s_item['id'])?>" class="sidebar-link" aria-expanded="false">
					<i class="fa-solid <?=htmlspecialchars($s_item['icon'])?> sidebar-icon"></i>
					<span class="sidebar-text"><?=htmlspecialchars($s_item['name'])?></span>
					<i class="fa-solid fa-caret-right sidebar-arrow"></i>
				</a>
				<div id="sub-<?=htmlspecialchars($s_item['id'])?>" class="sidebar-flyout">
					<div class="flyout-header">
						<i class="fa-solid <?=htmlspecialchars($s_item['icon'])?>"></i>
						<span><?=htmlspecialchars($s_item['name'])?></span>
					</div>
					<ul class="sidebar-submenu">
					<?php foreach ($s_item['menu'] as $m): ?>
						<?php if ($m[0] === '-DIVIDER-'): ?>
							<li class="divider"></li>
						<?php else: ?>
							<?php $tgt = isset($m[2]) ? ' target="' . htmlspecialchars($m[2]) . '"' : ''; ?>
							<li><a href="<?=htmlspecialchars($m[1])?>" class="navlnk"<?=$tgt?>><?=htmlspecialchars($m[0])?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
					</ul>
				</div>
			</li>
		<?php endif; ?>
	<?php endforeach; ?>
	</ul>
</aside>

<div id="pf-main-content" class="container static">
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
			<li>
				<a onclick="$('#widget-available').toggle(360);" title="Available widgets">
					<i class="fa-solid fa-plus icon-pointer"></i>
				</a>
			</li>
			<?php endif; ?>
			<li>
				<a href="/help.php?page=<?=urlencode(basename($_SERVER['SCRIPT_NAME']))?>" target="_blank" title="Help for items on this page">
					<i class="fa-solid fa-question-circle"></i>
				</a>
			</li>
		</ul>
	</header>
