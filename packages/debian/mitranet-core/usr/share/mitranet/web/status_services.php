<?php
/*
 * status_services.php - MitraNet Status: Services
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

// Handle AJAX service control
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $mode = $_POST['mode'] ?? '';
    $service = $_POST['service'] ?? '';
    $act = 'restart';
    if ($mode === 'startservice') $act = 'start';
    elseif ($mode === 'stopservice') $act = 'stop';
    elseif ($mode === 'restartservice') $act = 'restart';

    require_once(__DIR__ . '/includes/api.inc');
    if ($service === 'wireguard') {
        $res = MitraNetApi::controlWireGuard($act);
        echo json_encode($res);
        exit;
    } elseif ($service === 'xray') {
        $res = MitraNetApi::controlXray($act);
        echo json_encode($res);
        exit;
    } elseif ($service === 'virtual') {
        $res = MitraNetApi::vmAction('aapanel', $act);
        echo json_encode($res);
        exit;
    } else {
        $res = MitraNetApi::controlSystemService($service, $act);
        echo json_encode($res);
        exit;
    }
}

require_once(__DIR__ . '/includes/api.inc');

$pgtitle = array("Status", "Services");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

// Query dynamic service statuses
$wg = MitraNetApi::getWireGuard();
$wg_running = !empty($wg['running']);

$xr = MitraNetApi::getXray();
$xray_running = !empty($xr['running']);

$vms = MitraNetApi::getKvmVms();
$vm_running = false;
foreach ($vms as $v) {
    if (($v['id'] ?? '') === 'aapanel' && ($v['status'] ?? '') === 'RUNNING') {
        $vm_running = true;
        break;
    }
}
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">System Services Status &amp; Controls</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
					<tr>
						<th>Service</th>
						<th>Description</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<!-- WireGuard VPN -->
					<tr>
						<td><strong>wireguard</strong></td>
						<td>WireGuard Fast Kernel VPN Service (wg-quick)</td>
						<td>
							<?php if ($wg_running): ?>
								<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
							<?php else: ?>
								<i class="text-danger fa-solid fa-times-circle fa-1x" title="Stopped"><span class="sr-only">Stopped</span></i>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($wg_running): ?>
								<a href="#" id="restartservice-wireguard" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
								<a href="#" id="stopservice-wireguard" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							<?php else: ?>
								<a href="#" id="startservice-wireguard" title="Start Service"><i class="fa-solid fa-play-circle text-success"></i></a>
							<?php endif; ?>
							&nbsp;<a href="/wg/vpn_wg_tunnels.php" title="WireGuard Tunnels"><i class="fa-solid fa-sliders"></i></a>
							&nbsp;<a href="/wg/status_wireguard.php" title="WireGuard Status"><i class="fa-regular fa-chart-bar"></i></a>
						</td>
					</tr>

					<!-- Xray Core -->
					<tr>
						<td><strong>xray</strong></td>
						<td>Xray-core Multi-Protocol Anti-Censorship Service</td>
						<td>
							<?php if ($xray_running): ?>
								<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
							<?php else: ?>
								<i class="text-danger fa-solid fa-times-circle fa-1x" title="Stopped"><span class="sr-only">Stopped</span></i>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($xray_running): ?>
								<a href="#" id="restartservice-xray" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
								<a href="#" id="stopservice-xray" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							<?php else: ?>
								<a href="#" id="startservice-xray" title="Start Service"><i class="fa-solid fa-play-circle text-success"></i></a>
							<?php endif; ?>
							&nbsp;<a href="/services_xray.php" title="Xray Settings"><i class="fa-solid fa-sliders"></i></a>
						</td>
					</tr>

					<!-- DHCP Server -->
					<tr>
						<td><strong>dhcpd</strong></td>
						<td>ISC / dnsmasq DHCP Server</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
						</td>
						<td>
							<a href="#" id="restartservice-dhcpd" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
							<a href="#" id="stopservice-dhcpd" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							&nbsp;<a href="/services_dhcp.php" title="DHCP Settings"><i class="fa-solid fa-sliders"></i></a>
							&nbsp;<a href="/status_dhcp_leases.php" title="DHCP Leases"><i class="fa-regular fa-chart-bar"></i></a>
							&nbsp;<a href="/status_logs.php?logfile=dhcpd" title="DHCP Logs"><i class="fa-regular fa-rectangle-list"></i></a>
						</td>
					</tr>

					<!-- Gateway Monitor -->
					<tr>
						<td><strong>dpinger</strong></td>
						<td>Gateway Monitoring Daemon (mitranet-gateway-monitor)</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
						</td>
						<td>
							<a href="#" id="restartservice-dpinger" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
							<a href="#" id="stopservice-dpinger" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							&nbsp;<a href="/system_gateways.php" title="Gateways"><i class="fa-solid fa-sliders"></i></a>
							&nbsp;<a href="/status_gateways.php" title="Gateways Status"><i class="fa-regular fa-chart-bar"></i></a>
						</td>
					</tr>

					<!-- NTP Service -->
					<tr>
						<td><strong>ntpd</strong></td>
						<td>NTP Clock Synchronization (chrony)</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
						</td>
						<td>
							<a href="#" id="restartservice-ntpd" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
							<a href="#" id="stopservice-ntpd" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							&nbsp;<a href="/services_ntpd.php" title="NTP Settings"><i class="fa-solid fa-sliders"></i></a>
							&nbsp;<a href="/status_ntpd.php" title="NTP Status"><i class="fa-regular fa-chart-bar"></i></a>
						</td>
					</tr>

					<!-- SSH Daemon -->
					<tr>
						<td><strong>sshd</strong></td>
						<td>OpenSSH Secure Shell Daemon</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
						</td>
						<td>
							<a href="#" id="restartservice-sshd" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
							<a href="#" id="stopservice-sshd" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							&nbsp;<a href="/system_advanced_admin.php" title="Admin Access"><i class="fa-solid fa-sliders"></i></a>
						</td>
					</tr>

					<!-- System Logger -->
					<tr>
						<td><strong>syslogd</strong></td>
						<td>Systemd Journal / System Logger Daemon</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
						</td>
						<td>
							<a href="#" id="restartservice-syslogd" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
							<a href="#" id="stopservice-syslogd" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							&nbsp;<a href="/status_logs.php" title="System Logs"><i class="fa-regular fa-rectangle-list"></i></a>
						</td>
					</tr>

					<!-- DNS Resolver -->
					<tr>
						<td><strong>unbound</strong></td>
						<td>Unbound DNS Validating Recursive Resolver</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
						</td>
						<td>
							<a href="#" id="restartservice-unbound" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
							<a href="#" id="stopservice-unbound" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							&nbsp;<a href="/services_unbound.php" title="Unbound Settings"><i class="fa-solid fa-sliders"></i></a>
							&nbsp;<a href="/status_unbound.php" title="DNS Status"><i class="fa-regular fa-chart-bar"></i></a>
						</td>
					</tr>

					<!-- Virtual Machine / aaPanel -->
					<tr>
						<td><strong>virtual</strong></td>
						<td>Virtual Machine and aaPanel Micro-KVM Service</td>
						<td>
							<?php if ($vm_running): ?>
								<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span class="sr-only">Running</span></i>
							<?php else: ?>
								<i class="text-danger fa-solid fa-times-circle fa-1x" title="Stopped"><span class="sr-only">Stopped</span></i>
							<?php endif; ?>
						</td>
						<td>
							<?php if ($vm_running): ?>
								<a href="#" id="restartservice-virtual" title="Restart Service"><i class="fa-solid fa-arrow-rotate-right"></i></a>
								<a href="#" id="stopservice-virtual" title="Stop Service"><i class="fa-regular fa-circle-stop"></i></a>
							<?php else: ?>
								<a href="#" id="startservice-virtual" title="Start Service"><i class="fa-solid fa-play-circle text-success"></i></a>
							<?php endif; ?>
							&nbsp;<a href="/services_virtual.php" title="VM Settings"><i class="fa-solid fa-sliders"></i></a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
