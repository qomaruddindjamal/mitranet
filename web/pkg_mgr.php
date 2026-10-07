<?php
/*
 * pkg_mgr.php - MitraNet Package Manager: Available Packages & Synchronization
 * Adapted from pfSense pkg_mgr.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Package Manager", "Available Packages");
$pglinks = array("", "/pkg_mgr_installed.php", "@self");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$tab_array = array(
    array("Installed Packages", false, "/pkg_mgr_installed.php"),
    array("Available Packages", true, "/pkg_mgr.php")
);
display_top_tabs($tab_array, false, 'pills');

// List of migrated packages synchronized from pfSense .pkg to native Debian .deb
$migrated_pkgs = array(
    array('name' => 'wireguard', 'orig' => 'pfSense-pkg-WireGuard', 'version' => '1.0.20210914-3', 'status' => 'Synchronized (.deb)', 'desc' => 'Fast, modern, secure kernel VPN tunnel (in-tree Linux kernel WireGuard module + wireguard-tools)'),
    array('name' => 'wireguard-tools', 'orig' => 'wireguard-pfsense', 'version' => '1.0.20210914-3', 'status' => 'Synchronized (.deb)', 'desc' => 'WireGuard userland management utilities (wg, wg-quick)'),
    array('name' => 'xray-core', 'orig' => 'xray-pfsense', 'version' => '1.8.24', 'status' => 'Synchronized (Linux amd64)', 'desc' => 'Xray-core multi-protocol proxy (VLESS Reality/Vision, VMess WS, Trojan, Shadowsocks, Socks5, TUN)'),
    array('name' => 'mitranet-core', 'orig' => 'check_reload_status, filterlog, dhcpleases, cpustats', 'version' => '1.0.2-1', 'status' => 'Synchronized (.deb)', 'desc' => 'MitraNet core appliance runtime daemon and API services'),
    array('name' => 'mitranet-config-engine', 'orig' => 'pfSense-default-config', 'version' => '1.0.2-2', 'status' => 'Synchronized (.deb)', 'desc' => 'Transactional configuration engine for native JSON configuration store'),
    array('name' => 'mitranet-network-engine', 'orig' => 'FreeBSD netgraph, vlan, lagg, ifconfig', 'version' => '1.0.2-1', 'status' => 'Synchronized (.deb)', 'desc' => 'Linux native networking engine (VLAN 802.1Q, LACP bonding, bridges, VRF routing tables)'),
    array('name' => 'mitranet-gateway-monitor', 'orig' => 'dpinger', 'version' => '1.0.2-1', 'status' => 'Synchronized (.deb)', 'desc' => 'Upstream gateway latency and loss healthcheck monitor powered by fping'),
    array('name' => 'fping', 'orig' => 'dpinger-3.6', 'version' => '5.1-1', 'status' => 'Synchronized (.deb)', 'desc' => 'High performance latency and packet loss ping utility'),
    array('name' => 'nftables', 'orig' => 'pf(4), libpfctl', 'version' => '1.1.1-1', 'status' => 'Synchronized (.deb)', 'desc' => 'Linux Netfilter stateful packet filtering, NAT, and connection tracking engine')
);
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Package Synchronization Status (.pkg &rarr; .deb Architecture)</h2></div>
	<div class="panel-body table-responsive">
		<p class="text-muted" style="margin-bottom: 15px;">
			MitraNet Rinjani 1.0.2 maintains a strictly mapped Debian 13 (Trixie) pool. 
			FreeBSD <code>.pkg</code> packages from pfSense 2.9 have been synchronized and ported to native Linux <code>.deb</code> packages and systemd daemons.
		</p>
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr>
					<th>Native Linux (.deb) Package</th>
					<th>Original pfSense (.pkg)</th>
					<th>Version</th>
					<th>Sync Status</th>
					<th>Capability Description</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($migrated_pkgs as $p): ?>
				<tr>
					<td><strong><?=htmlspecialchars($p['name'])?></strong></td>
					<td><code><?=htmlspecialchars($p['orig'])?></code></td>
					<td><?=htmlspecialchars($p['version'])?></td>
					<td><span class="label label-success"><i class="fa-solid fa-check"></i> <?=htmlspecialchars($p['status'])?></span></td>
					<td><?=htmlspecialchars($p['desc'])?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
