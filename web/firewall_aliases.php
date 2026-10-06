<?php
/*
 * firewall_aliases.php - MitraNet Firewall Aliases
 * Adapted from pfSense firewall_aliases.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Firewall", "Aliases");
$selected_menu = "firewall";
require_once(__DIR__ . '/includes/head.inc');

$fw = MitraNetApi::getFirewall();
$rules = $fw['candidate']['rules'] ?? [];
?>

<h2>Firewall: Aliases</h2>

<ul class="nav nav-tabs">
	<li class="active"><a href="firewall_aliases.php">IP</a></li>
	<li><a href="firewall_aliases.php">Ports</a></li>
	<li><a href="firewall_aliases.php">All</a></li>
</ul>

<div class="panel panel-default" style="margin-top: 15px;">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa fa-list-alt"></i> Configured Firewall Aliases</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th>Name</th>
						<th>Values / Network</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong>RFC1918_Subnets</strong></td>
						<td><code>10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16</code></td>
						<td>Private IPv4 Address Space (RFC 1918)</td>
					</tr>
					<tr>
						<td><strong>Management_Net</strong></td>
						<td><code>192.168.56.0/24</code></td>
						<td>MitraNet Host-Only Management Network</td>
					</tr>
					<tr>
						<td><strong>WebGUI_Ports</strong></td>
						<td><code>8443, 8080, 80, 443</code></td>
						<td>MitraNet WebUI & HTTP Ports</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="alert alert-info">
	<i class="fa fa-info-circle"></i> Aliases can be referenced when defining packet filtering rules in <a href="firewall_rules.php"><strong>Firewall: Rules</strong></a> and NAT translations in <a href="firewall_nat.php"><strong>Firewall: NAT</strong></a>.
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
