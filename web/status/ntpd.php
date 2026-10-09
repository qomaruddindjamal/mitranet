<?php
/*
 * status_ntpd.php - MitraNet Status: NTP
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "NTP");
$selected_menu = "status";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Network Time Protocol Status</h2></div>
	<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th>Status</th>
					<th>Server</th>
					<th>Ref ID</th>
					<th>Stratum</th>
					<th>Type</th>
					<th>When</th>
					<th>Poll (s)</th>
					<th>Reach</th>
					<th>Delay (ms)</th>
					<th>Offset (ms)</th>
					<th>Jitter (ms)</th>
					<th>AssocID</th>
					<th>Status Word</th>
					<th>Auth</th>
				</tr>
			</thead>
			<tbody id="ntpbody">
				<tr>
<td>Pool Placeholder</td>
<td>2.pfsense.pool.ntp.org</td>
<td>.POOL.</td>
<td>16</td>
<td>p</td>
<td>-</td>
<td>64</td>
<td>0</td>
<td>0.000</td>
<td>+0.000</td>
<td>0.000</td>
<td>17935</td>
<td>8811</td>
<td>none</td>
</tr>
<tr>
<td>Candidate</td>
<td>14.102.153.110</td>
<td>133.243.238.243</td>
<td>2</td>
<td>u</td>
<td>120</td>
<td>128</td>
<td>377</td>
<td>13.818</td>
<td>-1.330</td>
<td>1.646</td>
<td>17937</td>
<td>141a</td>
<td>none</td>
</tr>
<tr>
<td>Outlier</td>
<td>185.125.190.58</td>
<td>29.88.99.4</td>
<td>2</td>
<td>u</td>
<td>108</td>
<td>256</td>
<td>377</td>
<td>174.743</td>
<td>+0.024</td>
<td>0.652</td>
<td>17938</td>
<td>1314</td>
<td>none</td>
</tr>
<tr>
<td>Active Peer</td>
<td>172.232.235.123</td>
<td>216.239.35.4</td>
<td>2</td>
<td>u</td>
<td>102</td>
<td>128</td>
<td>377</td>
<td>13.462</td>
<td>-0.072</td>
<td>3.998</td>
<td>17939</td>
<td>161a</td>
<td>none</td>
</tr>
<tr>
<td>Candidate</td>
<td>203.89.31.13</td>
<td>160.250.227.196</td>
<td>3</td>
<td>u</td>
<td>103</td>
<td>128</td>
<td>377</td>
<td>27.197</td>
<td>+0.638</td>
<td>0.550</td>
<td>17940</td>
<td>1414</td>
<td>none</td>
</tr>
			</tbody>
		</table>
	</div>
</div>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
