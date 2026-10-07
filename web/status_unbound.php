<?php
/*
 * status_unbound.php - MitraNet Status: DNS Resolver
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "DNS Resolver");
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">DNS Resolver Infrastructure Cache Speed</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
					<tr>
						<th>Server</th>
						<th>Zone</th>
						<th>TTL</th>
						<th>Ping</th>
						<th>Var</th>
						<th>RTT</th>
						<th>RTO</th>
						<th>Timeout A</th>
						<th>Timeout AAAA</th>
						<th>Timeout Other</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							192.36.148.17						</td>
						<td>
							.						</td>
						<td>
							897						</td>
						<td>
							3						</td>
						<td>
							78						</td>
						<td>
							315						</td>
						<td>
							315						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:500:2d::d						</td>
						<td>
							.						</td>
						<td>
							895						</td>
						<td>
							0						</td>
						<td>
							94						</td>
						<td>
							376						</td>
						<td>
							752						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							208.123.73.80						</td>
						<td>
							netgate.com.						</td>
						<td>
							891						</td>
						<td>
							32						</td>
						<td>
							136						</td>
						<td>
							576						</td>
						<td>
							576						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:7fd::1						</td>
						<td>
							.						</td>
						<td>
							896						</td>
						<td>
							0						</td>
						<td>
							94						</td>
						<td>
							376						</td>
						<td>
							752						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:500:2::c						</td>
						<td>
							.						</td>
						<td>
							892						</td>
						<td>
							0						</td>
						<td>
							94						</td>
						<td>
							376						</td>
						<td>
							752						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2620:4f:8000::6						</td>
						<td>
							home.arpa.						</td>
						<td>
							894						</td>
						<td>
							0						</td>
						<td>
							94						</td>
						<td>
							376						</td>
						<td>
							752						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							1						</td>
					</tr>

					<tr>
						<td>
							192.175.48.42						</td>
						<td>
							home.arpa.						</td>
						<td>
							894						</td>
						<td>
							8						</td>
						<td>
							23						</td>
						<td>
							100						</td>
						<td>
							100						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.43.172.30						</td>
						<td>
							com.						</td>
						<td>
							890						</td>
						<td>
							24						</td>
						<td>
							118						</td>
						<td>
							496						</td>
						<td>
							496						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.31.80.30						</td>
						<td>
							com.						</td>
						<td>
							890						</td>
						<td>
							24						</td>
						<td>
							120						</td>
						<td>
							504						</td>
						<td>
							504						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							199.7.83.42						</td>
						<td>
							.						</td>
						<td>
							893						</td>
						<td>
							3						</td>
						<td>
							77						</td>
						<td>
							311						</td>
						<td>
							311						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							34.197.184.5						</td>
						<td>
							netgate.com.						</td>
						<td>
							889						</td>
						<td>
							59						</td>
						<td>
							155						</td>
						<td>
							679						</td>
						<td>
							679						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:500:d937::30						</td>
						<td>
							com.						</td>
						<td>
							891						</td>
						<td>
							0						</td>
						<td>
							94						</td>
						<td>
							376						</td>
						<td>
							752						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							1						</td>
					</tr>

					<tr>
						<td>
							2620:4f:8000::42						</td>
						<td>
							home.arpa.						</td>
						<td>
							893						</td>
						<td>
							0						</td>
						<td>
							94						</td>
						<td>
							376						</td>
						<td>
							752						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							1						</td>
					</tr>

					<tr>
						<td>
							192.54.112.30						</td>
						<td>
							com.						</td>
						<td>
							891						</td>
						<td>
							24						</td>
						<td>
							118						</td>
						<td>
							496						</td>
						<td>
							496						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:502:8cc::30						</td>
						<td>
							com.						</td>
						<td>
							890						</td>
						<td>
							0						</td>
						<td>
							94						</td>
						<td>
							376						</td>
						<td>
							752						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							1						</td>
					</tr>

					<tr>
						<td>
							192.33.14.30						</td>
						<td>
							com.						</td>
						<td>
							892						</td>
						<td>
							2						</td>
						<td>
							75						</td>
						<td>
							302						</td>
						<td>
							302						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.175.48.6						</td>
						<td>
							home.arpa.						</td>
						<td>
							892						</td>
						<td>
							8						</td>
						<td>
							20						</td>
						<td>
							88						</td>
						<td>
							88						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.58.128.30						</td>
						<td>
							.						</td>
						<td>
							895						</td>
						<td>
							23						</td>
						<td>
							117						</td>
						<td>
							491						</td>
						<td>
							491						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							199.7.91.13						</td>
						<td>
							.						</td>
						<td>
							894						</td>
						<td>
							1						</td>
						<td>
							74						</td>
						<td>
							297						</td>
						<td>
							297						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:503:c27::2:30						</td>
						<td>
							.						</td>
						<td>
							896						</td>
						<td>
							0						</td>
						<td>
							94						</td>
						<td>
							376						</td>
						<td>
							752						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">DNS Resolver Infrastructure Cache Stats</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
					<tr>
						<th>Server</th>
						<th>Zone</th>
						<th>eDNS Lame Known</th>
						<th>eDNS Version</th>
						<th>Probe Delay</th>
						<th>Lame DNSSEC</th>
						<th>Lame Rec</th>
						<th>Lame A</th>
						<th>Lame Other</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							192.36.148.17						</td>
						<td>
							.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:500:2d::d						</td>
						<td>
							.						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							208.123.73.80						</td>
						<td>
							netgate.com.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:7fd::1						</td>
						<td>
							.						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:500:2::c						</td>
						<td>
							.						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2620:4f:8000::6						</td>
						<td>
							home.arpa.						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.175.48.42						</td>
						<td>
							home.arpa.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.43.172.30						</td>
						<td>
							com.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.31.80.30						</td>
						<td>
							com.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							199.7.83.42						</td>
						<td>
							.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							34.197.184.5						</td>
						<td>
							netgate.com.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:500:d937::30						</td>
						<td>
							com.						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2620:4f:8000::42						</td>
						<td>
							home.arpa.						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.54.112.30						</td>
						<td>
							com.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:502:8cc::30						</td>
						<td>
							com.						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.33.14.30						</td>
						<td>
							com.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.175.48.6						</td>
						<td>
							home.arpa.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							192.58.128.30						</td>
						<td>
							.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							199.7.91.13						</td>
						<td>
							.						</td>
						<td>
							1						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

					<tr>
						<td>
							2001:503:c27::2:30						</td>
						<td>
							.						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
						<td>
							0						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
