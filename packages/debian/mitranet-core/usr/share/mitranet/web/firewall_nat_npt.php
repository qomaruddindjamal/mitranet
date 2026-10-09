<?php
/*
 * firewall_nat_npt.php - MitraNet IPv6 NPt Management
 * Adapted from pfSense firewall_nat_npt.php
 */

$pgtitle = array(gettext("Firewall"), gettext("NAT"), gettext("NPt"));
$selected_menu = "firewall";
require_once(__DIR__ . '/includes/head.inc');

$tab_array = array();
$tab_array[] = array(gettext("Port Forward"), false, "firewall_nat.php");
$tab_array[] = array(gettext("1:1"), false, "firewall_nat_1to1.php");
$tab_array[] = array(gettext("Outbound"), false, "firewall_nat_out.php");
$tab_array[] = array(gettext("NPt"), true, "firewall_nat_npt.php");
display_top_tabs($tab_array);
?>

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
