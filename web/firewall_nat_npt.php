<?php
/*
 * firewall_nat_npt.php - MitraNet IPv6 NPt Management
 * Adapted from pfSense firewall_nat_npt.php
 */

$pgtitle = "Firewall: NAT: NPt (Network Prefix Translation IPv6)";
$selected_menu = "firewall";
require_once(__DIR__ . '/includes/head.inc');
?>

<!-- Tab Navigation identical to pfSense -->
<ul class="nav nav-tabs" style="margin-bottom: 20px;">
	<li><a href="/firewall_nat.php">Port Forward</a></li>
	<li><a href="/firewall_nat_1to1.php">1:1</a></li>
	<li><a href="/firewall_nat_out.php">Outbound</a></li>
	<li class="active"><a href="/firewall_nat_npt.php">NPt</a></li>
</ul>

<h2>Firewall: NAT: NPt (IPv6 Prefix Translation)</h2>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-info-circle"></i> IPv6 Prefix Translation</h3></div>
	<div class="panel-body">
		<p class="text-muted">IPv6 Network Prefix Translation (RFC 6296) maps an internal IPv6 prefix to an external IPv6 prefix 1:1 statelessly.</p>
		<table class="table table-striped">
			<thead>
				<tr>
					<th>Interface</th>
					<th>Internal IPv6 Prefix</th>
					<th>External IPv6 Prefix</th>
					<th>Description</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td colspan="4" class="text-center text-muted">No IPv6 NPt mappings configured.</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
