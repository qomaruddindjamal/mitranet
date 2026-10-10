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
    array("Advanced", "/system/advanced_admin.php"),
    array("Certificates", "/system/camanager.php"),
    array("General Setup", "/system/system.php"),
    array("High Availability", "/system/hasync.php"),
    array("Package Manager", "/packages/pkg_mgr_installed.php"),
    array("Routing", "/system/gateways.php"),
    array("Setup Wizard", "/wizard.php?xml=setup_wizard.xml"),
    array("Update", "/packages/pkg_mgr_install.php?id=firmware"),
    array("User Manager", "/system/usermanager.php"),
    array("User Password Manager", "/system/usermanager_passwordmg.php"),
    array("-DIVIDER-", ""),
    array("Restart", "/system/reboot.php"),
    array("Shutdown", "/system/halt.php")
);


$interfaces_menu = array(
    array("Interfaces", "/interfaces/interfaces.php"),
    array("-DIVIDER-", "")
);
$ifaces_list = MitraNetApi::getInterfaces();
if (!empty($ifaces_list)) {
    foreach ($ifaces_list as $i_entry) {
        $interfaces_menu[] = array(strtoupper($i_entry['name']) . " (" . $i_entry['name'] . ")", "/interfaces/interfaces.php?if=" . urlencode($i_entry['name']));
    }
}
$interfaces_menu[] = array("-DIVIDER-", "");
$interfaces_menu[] = array("VLANs", "/interfaces/vlan.php");
$interfaces_menu[] = array("Bridges", "/interfaces/bridge.php");
$interfaces_menu[] = array("LAGGs", "/interfaces/lagg.php");
$interfaces_menu[] = array("MACVLANs", "/interfaces/macVlan.php");
$interfaces_menu[] = array("VRFs", "/interfaces/vrf.php");
$interfaces_menu[] = array("vEthernet (KVM)", "/interfaces/vethernet.php");

$firewall_menu = array(
    array("Aliases", "/firewall/aliases.php"),
    array("NAT", "/firewall/nat.php"),
    array("Rules", "/firewall/rules.php"),
    array("Schedules", "/firewall/schedule.php"),
    array("Traffic Shaper", "/firewall/shaper.php"),
    array("Virtual IPs", "/firewall/virtual_ip.php")
);

$services_menu = array(
    array("Captive Portal", "/services/captiveportal.php"),
    array("DHCP Relay", "/services/dhcp_relay.php"),
    array("DHCP Server", "/services/dhcp_settings.php"),
    array("DHCPv6 Relay", "/services/dhcpv6_relay.php"),
    array("DHCPv6 Server", "/services/dhcpv6_settings.php"),
    array("DNS Server", "/services/dns_server.php"),
    array("Dynamic DNS", "/services/dyndns.php"),
    array("IGMP Proxy", "/services/igmpproxy.php"),
    array("NTP", "/services/ntpd.php"),
    array("Router Advertisement", "/services/radvd.php"),
    array("Security Services", "/services/services_security.php"),
    array("SNMP", "/services/snmp.php"),
    array("Wake-on-LAN", "/services/wol.php"),
    array("KVM Services", "/services/kvm.php")
);

$vpn_menu = array(
    array("Cloud Speed Booster", "/vpn/vpn_booster.php"),
    array("IPsec", "/vpn/ipsec.php"),
    array("L2TP", "/vpn/l2tp_server.php"),
    array("OpenVPN", "/vpn/openvpn_server.php"),
    array("PPTP", "/vpn/pptp_server.php")
);

$status_menu = array(
    array("Captive Portal", "/status/captiveportal.php"),
    array("CARP (failover)", "/status/carp.php"),
    array("Dashboard", "/index.php"),
    array("DHCP Leases", "/status/dhcp_leases.php"),
    array("DHCPv6 Leases", "/status/dhcpv6_leases.php"),
    array("DNS Resolver", "/status/unbound.php"),
    array("Filter Reload", "/status/filter_reload.php?user=true"),
    array("Gateways", "/status/gateways.php"),
    array("Interfaces", "/status/interfaces.php"),
    array("IPsec", "/status/ipsec.php"),
    array("Monitoring", "/status/monitoring.php"),
    array("NTP", "/status/ntpd.php"),
    array("OpenVPN", "/status/openvpn.php"),
    array("Queues", "/status/queues.php"),
    array("Services", "/status/services.php"),
    array("System Logs", "/status/logs.php"),
    array("Traffic Graph", "/status/graph.php"),
    array("UPnP IGD &amp; PCP", "/status/upnp.php"),
    array("WireGuard", "/wg/status_wireguard.php")
);

$diagnostics_menu = array(
    array("ARP Table", "/diagnostics/diag_arp.php"),
    array("Authentication", "/diagnostics/diag_authentication.php"),
    array("Backup &amp; Restore", "/diagnostics/diag_backup.php"),
    array("Command Prompt", "/diagnostics/diag_command.php"),
    array("DNS Lookup", "/diagnostics/diag_dns.php"),
    array("Edit File", "/diagnostics/diag_edit.php"),
    array("Factory Defaults", "/diagnostics/diag_defaults.php"),
    array("Halt System", "/diagnostics/diag_halt.php"),
    array("Limiter Info", "/diagnostics/diag_limiter_info.php"),
    array("NDP Table", "/diagnostics/diag_ndp.php"),
    array("Packet Capture", "/diagnostics/diag_packet_capture.php"),
    array("pfInfo", "/diagnostics/diag_pf_info.php"),
    array("pfTop", "/diagnostics/diag_pftop.php"),
    array("Ping", "/diagnostics/diag_ping.php"),
    array("Reboot", "/diagnostics/diag_reboot.php"),
    array("Routes", "/diagnostics/diag_routes.php"),
    array("S.M.A.R.T. Status", "/diagnostics/diag_smart.php"),
    array("Sockets", "/diagnostics/diag_sockets.php"),
    array("States", "/diagnostics/diag_dump_states.php"),
    array("States Summary", "/diagnostics/diag_states_summary.php"),
    array("System Activity", "/diagnostics/diag_system_activity.php"),
    array("Tables", "/diagnostics/diag_tables.php"),
    array("Terminal", "/terminal/terminal.php"),
    array("Test Port", "/diagnostics/diag_testport.php"),
    array("Traceroute", "/diagnostics/diag_traceroute.php")
);

$tools_menu = array(
    array("Speedtest", "/tools/speedtest.php")
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
    array('id' => 'volt', 'name' => 'vOLT', 'icon' => 'fa-network-wired', 'url' => '/vOlt/vOlt.php', 'direct' => true),
    array('id' => 'wifi', 'name' => 'Wireless', 'icon' => 'fa-wifi', 'url' => '/wifi/wifi.php', 'direct' => true),
    array('id' => 'interfaces', 'name' => 'Interfaces', 'icon' => 'fa-network-wired', 'url' => '/interfaces/interfaces.php', 'direct' => true),
    array('id' => 'wireguard', 'name' => 'WireGuard', 'icon' => 'fa-circle-nodes', 'url' => '/wg/vpn_wg_tunnels.php', 'direct' => true),
    array('id' => 'firewall', 'name' => 'Firewall', 'icon' => 'fa-shield-halved', 'menu' => $firewall_menu),
    array('id' => 'services', 'name' => 'Services', 'icon' => 'fa-cubes', 'menu' => $services_menu),
    array('id' => 'vpn', 'name' => 'VPN', 'icon' => 'fa-key', 'url' => '/vpn/vpn.php', 'direct' => true),
    array('id' => 'xray', 'name' => 'Xray-core', 'icon' => 'fa-bolt', 'url' => '/xray/xray.php', 'direct' => true),
    array('id' => 'qos', 'name' => 'QoS Manager', 'icon' => 'fa-gauge-high', 'url' => '/qos/qos.php', 'direct' => true)
);

if (MitraNetApi::isKvmEnabled()) {
    $sidebar_structure[] = array('id' => 'kvm', 'name' => 'KVM', 'icon' => 'fa-server', 'url' => '/services/kvm.php', 'direct' => true);
}

$sidebar_structure = array_merge($sidebar_structure, array(
    array('id' => 'diagnostics', 'name' => 'Diagnostics', 'icon' => 'fa-toolbox', 'menu' => $diagnostics_menu),
    array('id' => 'terminal', 'name' => 'Terminal', 'icon' => 'fa-terminal', 'url' => '/terminal/terminal.php', 'direct' => true),
    array('id' => 'tools', 'name' => 'Tools', 'icon' => 'fa-screwdriver-wrench', 'menu' => $tools_menu),
    array('id' => 'status', 'name' => 'Status', 'icon' => 'fa-chart-line', 'menu' => $status_menu),
    array('id' => 'system', 'name' => 'System', 'icon' => 'fa-gears', 'menu' => $system_menu)
));

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" type="image/x-icon" href="/favicon.ico">
	<meta name="theme-color" content="#ffffff">

	<link rel="stylesheet" href="/vendor/font-awesome/css/all.min.css">
	<link rel="stylesheet" href="/vendor/sortable/sortable-theme-bootstrap.css">
	<link rel="stylesheet" href="/vendor/sweetalert2/sweetalert2.min.css">
	<link rel="stylesheet" href="/css/mitranet.css">

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
						echo '<span class="logo-fallback-text">MITRA<span class="logo-fallback-accent">NET</span></span>';
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
		</ul>
	</div>
</nav>

<!-- Backdrop overlay for mobile devices -->
<div id="sidebar-backdrop"></div>

<!-- Left Sidebar Navigation (Matching pfSense 2.9 modern sidebar layout) -->
<aside id="pf-sidebar">
	<div class="sidebar-header-box">
		<div class="sidebar-brand-title">
			<i class="fa-solid fa-shield-halved sidebar-brand-icon"></i>
			<span>MitraNet</span>
		</div>
		<button id="sidebar-close-btn" class="sidebar-close-btn" aria-label="Close Navigation">
			<i class="fa-solid fa-xmark"></i>
		</button>
	</div>
	<div class="sidebar-section-title">Navigation</div>
	<ul class="sidebar-menu">
	<?php foreach ($sidebar_structure as $s_item): 
		$is_act = (isset($selected_menu) && $selected_menu === $s_item['id']) || (isset($s_item['url']) && basename($_SERVER['SCRIPT_NAME']) === basename($s_item['url']));
		$active_cls = $is_act ? ' active' : '';
	?>
		<?php if (!empty($s_item['direct'])): ?>
			<li class="sidebar-item<?=$active_cls?>">
				<a href="<?=htmlspecialchars($s_item['url'])?>" class="sidebar-link direct-link" title="<?=htmlspecialchars($s_item['name'])?>">
					<i class="fa-solid <?=htmlspecialchars($s_item['icon'])?> sidebar-icon"></i>
					<span class="sidebar-text"><?=htmlspecialchars($s_item['name'])?></span>
				</a>
			</li>
		<?php else: ?>
			<li class="sidebar-item<?=$active_cls?>">
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
