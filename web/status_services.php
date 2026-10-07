<?php
/*
 * status_services.php - MitraNet Status: Services
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "Services");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Services</h2></div>
	<div class="panel-body">

	<div class="panel-body panel-default">
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
					<tr>
						<td>
							dhcpd						</td>
						<td>
							ISC DHCP Server						</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span style="display: none">Running</span></i>						</td>
						<td>
							<a href="#" id="restartservice-dhcpd" ><i class="fa-solid fa-arrow-rotate-right" title="Restart Service"></i></a>
<a href="#" id="stopservice-dhcpd"><i class="fa-regular fa-circle-stop" title="Stop Service"></i></a>
&nbsp;<a href="/services_dhcp.php" title="Related settings"><i class="fa-solid fa-sliders"></i></a>&nbsp;<a href="/status_dhcp_leases.php" title="Related status"><i class="fa-regular fa-chart-bar"></i></a>&nbsp;<a href="status_logs.php?logfile=dhcpd" title="Related log entries"><i class="fa-regular fa-rectangle-list"></i></a>						</td>
					</tr>
					<tr>
						<td>
							dpinger						</td>
						<td>
							Gateway Monitoring Daemon						</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span style="display: none">Running</span></i>						</td>
						<td>
							<a href="#" id="restartservice-dpinger" ><i class="fa-solid fa-arrow-rotate-right" title="Restart Service"></i></a>
<a href="#" id="stopservice-dpinger"><i class="fa-regular fa-circle-stop" title="Stop Service"></i></a>
&nbsp;<a href="/system_gateways.php" title="Related settings"><i class="fa-solid fa-sliders"></i></a>&nbsp;<a href="/status_gateways.php" title="Related status"><i class="fa-regular fa-chart-bar"></i></a>&nbsp;<a href="status_logs.php?logfile=gateways" title="Related log entries"><i class="fa-regular fa-rectangle-list"></i></a>						</td>
					</tr>
					<tr>
						<td>
							ntpd						</td>
						<td>
							NTP clock sync						</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span style="display: none">Running</span></i>						</td>
						<td>
							<a href="#" id="restartservice-ntpd" ><i class="fa-solid fa-arrow-rotate-right" title="Restart Service"></i></a>
<a href="#" id="stopservice-ntpd"><i class="fa-regular fa-circle-stop" title="Stop Service"></i></a>
&nbsp;<a href="/services_ntpd.php" title="Related settings"><i class="fa-solid fa-sliders"></i></a>&nbsp;<a href="/status_ntpd.php" title="Related status"><i class="fa-regular fa-chart-bar"></i></a>&nbsp;<a href="status_logs.php?logfile=ntpd" title="Related log entries"><i class="fa-regular fa-rectangle-list"></i></a>						</td>
					</tr>
					<tr>
						<td>
							sshd						</td>
						<td>
							Secure Shell Daemon						</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span style="display: none">Running</span></i>						</td>
						<td>
							<a href="#" id="restartservice-sshd" ><i class="fa-solid fa-arrow-rotate-right" title="Restart Service"></i></a>
<a href="#" id="stopservice-sshd"><i class="fa-regular fa-circle-stop" title="Stop Service"></i></a>
&nbsp;<a href="/system_advanced_admin.php" title="Related settings"><i class="fa-solid fa-sliders"></i></a>						</td>
					</tr>
					<tr>
						<td>
							syslogd						</td>
						<td>
							System Logger Daemon						</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span style="display: none">Running</span></i>						</td>
						<td>
							<a href="#" id="restartservice-syslogd" ><i class="fa-solid fa-arrow-rotate-right" title="Restart Service"></i></a>
<a href="#" id="stopservice-syslogd"><i class="fa-regular fa-circle-stop" title="Stop Service"></i></a>
&nbsp;<a href="/status_logs_settings.php" title="Related settings"><i class="fa-solid fa-sliders"></i></a>&nbsp;<a href="/status_logs.php" title="Related log entries"><i class="fa-regular fa-rectangle-list"></i></a>						</td>
					</tr>
					<tr>
						<td>
							unbound						</td>
						<td>
							DNS Resolver						</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span style="display: none">Running</span></i>						</td>
						<td>
							<a href="#" id="restartservice-unbound" ><i class="fa-solid fa-arrow-rotate-right" title="Restart Service"></i></a>
<a href="#" id="stopservice-unbound"><i class="fa-regular fa-circle-stop" title="Stop Service"></i></a>
&nbsp;<a href="/services_unbound.php" title="Related settings"><i class="fa-solid fa-sliders"></i></a>&nbsp;<a href="/status_unbound.php" title="Related status"><i class="fa-regular fa-chart-bar"></i></a>&nbsp;<a href="status_logs.php?logfile=resolver" title="Related log entries"><i class="fa-regular fa-rectangle-list"></i></a>						</td>
					</tr>
					<tr>
						<td>
							virtual						</td>
						<td>
							Virtual Machine and aaPanel Service						</td>
						<td>
							<i class="text-danger fa-solid fa-times-circle fa-1x" title="Stopped"><span style="display: none">Stopped</span></i>						</td>
						<td>
							<a title="Start Service" href="#" id="startservice-virtual"><i class="fa-solid fa-play-circle"></i></a> 
						</td>
					</tr>
					<tr>
						<td>
							wireguard						</td>
						<td>
							WireGuard Fast VPN Service						</td>
						<td>
							<i class="text-danger fa-solid fa-times-circle fa-1x" title="Stopped"><span style="display: none">Stopped</span></i>						</td>
						<td>
							<a title="Start Service" href="#" id="startservice-wireguard"><i class="fa-solid fa-play-circle"></i></a> 
&nbsp;<a href="/wg/vpn_wg_settings.php" title="Related settings"><i class="fa-solid fa-sliders"></i></a>&nbsp;<a href="/wg/status_wireguard.php" title="Related status"><i class="fa-regular fa-chart-bar"></i></a>						</td>
					</tr>
					<tr>
						<td>
							xray						</td>
						<td>
							Xray-core Multi-Protocol Anti-Censorship Service						</td>
						<td>
							<i class="text-success fa-solid fa-check-circle fa-1x" title="Running"><span style="display: none">Running</span></i>						</td>
						<td>
							<a href="#" id="restartservice-xray" ><i class="fa-solid fa-arrow-rotate-right" title="Restart Service"></i></a>
<a href="#" id="stopservice-xray"><i class="fa-regular fa-circle-stop" title="Stop Service"></i></a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
