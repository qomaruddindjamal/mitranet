<?php
/*
 * diag_states_summary.php - MitraNet Diagnostics: States Summary
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "States Summary");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">By Source IP</h2>
		</div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-hover table-condensed table-striped">
					<thead>
						<tr>
							<th></th><th></th><th></th>
							<th colspan="3" class="text-center colspanth">Protocol counts</th>
						</tr>

						<tr>
							<th >IP</th>
							<th class="text-center"># States</th>
							<th >Protocol</th>
							<th class="text-center"># States</th>
							<th class="text-center">Source Ports</th>
							<th class="text-center">Dest. Ports</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td rowspan="3">fe80::a00:27ff:fe27:309e</td>
							<td rowspan="3" class="text-center">19</td>

							<td>ipv6-icmp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="ipv6-icmp/16621: 1">1</span></td>
							<td class="text-center" ><span title="ipv6-icmp/135: 1, ipv6-icmp/128: 1">2</span></td>
							</tr><tr>
							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/34119: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
							</tr><tr>
							<td>udp</td>
							<td class="text-center" >16</td>
							<td class="text-center" ><span title="udp/14236: 1, udp/39593: 1, udp/47439: 1, udp/37733: 1, udp/38613: 1, udp/11837: 1, udp/61279: 1, udp/21484: 1, udp/52408: 1, udp/46855: 1, udp/21763: 1, udp/64964: 1, udp/55107: 1, udp/38338: 1, udp/60061: 1, udp/31656: 1">16</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 16">1</span></td>
						</tr>
						<tr>
							<td>::1</td>
							<td class="text-center">8</td>

							<td>udp</td>
							<td class="text-center" >8</td>
							<td class="text-center" ><span title="udp/55700: 2, udp/12973: 2, udp/31505: 2, udp/56264: 2">4</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 8">1</span></td>
						</tr>
						<tr>
							<td rowspan="3">10.10.66.47</td>
							<td rowspan="3" class="text-center">27</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16117: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
							</tr><tr>
							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/26656: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
							</tr><tr>
							<td>udp</td>
							<td class="text-center" >25</td>
							<td class="text-center" ><span title="udp/27496: 2, udp/11514: 1, udp/42348: 1, udp/11811: 1, udp/26330: 1, udp/33676: 1, udp/56883: 1, udp/48326: 1, udp/35284: 1, udp/45261: 1, udp/25885: 1, udp/47475: 1, udp/59238: 1, udp/11825: 1, udp/58266: 1, udp/29080: 1, udp/15618: 1, udp/27041: 1, udp/46645: 1, udp/24077: 1, udp/47121: 1, udp/54675: 1, udp/61302: 1, udp/20595: 1">24</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 25">1</span></td>
						</tr>
						<tr>
							<td rowspan="2">10.10.66.51</td>
							<td rowspan="2" class="text-center">94</td>

							<td>tcp</td>
							<td class="text-center" >93</td>
							<td class="text-center" ><span title="tcp/49986: 1, tcp/49983: 1, tcp/55116: 1, tcp/55111: 1, tcp/55110: 1, tcp/55109: 1, tcp/55108: 1, tcp/55107: 1, tcp/55106: 1, tcp/55105: 1, tcp/55104: 1, tcp/55103: 1, tcp/55102: 1, tcp/55101: 1, tcp/55100: 1, tcp/55099: 1, tcp/55098: 1, tcp/55097: 1, tcp/55096: 1, tcp/55095: 1, tcp/55094: 1, tcp/55093: 1, tcp/55092: 1, tcp/55091: 1, tcp/55090: 1, tcp/55089: 1, tcp/55088: 1, tcp/55087: 1, tcp/55086: 1, tcp/55085: 1, tcp/55084: 1, tcp/55083: 1, tcp/55082: 1, tcp/55081: 1, tcp/55080: 1, tcp/55079: 1, tcp/55078: 1, tcp/55077: 1, tcp/55076: 1, tcp/55075: 1, tcp/55074: 1, tcp/55073: 1, tcp/55072: 1, tcp/55071: 1, tcp/55070: 1, tcp/55069: 1, tcp/55068: 1, tcp/55067: 1, tcp/55066: 1, tcp/55065: 1, tcp/55064: 1, tcp/55063: 1, tcp/55062: 1, tcp/55061: 1, tcp/55060: 1, tcp/55059: 1, tcp/55058: 1, tcp/55057: 1, tcp/55056: 1, tcp/55055: 1, tcp/55054: 1, tcp/55053: 1, tcp/55052: 1, tcp/55051: 1, tcp/55050: 1, tcp/55049: 1, tcp/55048: 1, tcp/55047: 1, tcp/55046: 1, tcp/55045: 1, tcp/55044: 1, tcp/55043: 1, tcp/55042: 1, tcp/55041: 1, tcp/55040: 1, tcp/55039: 1, tcp/55038: 1, tcp/55037: 1, tcp/55036: 1, tcp/55035: 1, tcp/55034: 1, tcp/55033: 1, tcp/55032: 1, tcp/55031: 1, tcp/55030: 1, tcp/55029: 1, tcp/55028: 1, tcp/55027: 1, tcp/55026: 1, tcp/55025: 1, tcp/55024: 1, tcp/55022: 1, tcp/55021: 1">93</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 93">1</span></td>
							</tr><tr>
							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.254</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.99.2</td>
							<td class="text-center">1</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16818: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
						</tr>
						<tr>
							<td>127.0.0.1</td>
							<td class="text-center">64</td>

							<td>udp</td>
							<td class="text-center" >64</td>
							<td class="text-center" ><span title="udp/24594: 2, udp/2645: 2, udp/58028: 2, udp/62353: 2, udp/28025: 2, udp/24661: 2, udp/11007: 2, udp/55304: 2, udp/37102: 2, udp/2294: 2, udp/53548: 2, udp/10154: 2, udp/57631: 2, udp/14778: 2, udp/60387: 2, udp/59162: 2, udp/58065: 2, udp/36990: 2, udp/47750: 2, udp/10050 (zabbix-agent): 2, udp/4265: 2, udp/35247: 2, udp/1373: 2, udp/4393: 2, udp/24868: 2, udp/3439: 2, udp/31114: 2, udp/39740: 2, udp/42259: 2, udp/26142: 2, udp/56289: 2, udp/18648: 2">32</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 64">1</span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">By Destination IP</h2>
		</div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-hover table-condensed table-striped">
					<thead>
						<tr>
							<th></th><th></th><th></th>
							<th colspan="3" class="text-center colspanth">Protocol counts</th>
						</tr>

						<tr>
							<th >IP</th>
							<th class="text-center"># States</th>
							<th >Protocol</th>
							<th class="text-center"># States</th>
							<th class="text-center">Source Ports</th>
							<th class="text-center">Dest. Ports</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>2001:502:8cc::30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/60061: 1, udp/31656: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2620:4f:8000::42</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/52408: 1, udp/46855: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>fe80::1</td>
							<td class="text-center">2</td>

							<td>ipv6-icmp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="ipv6-icmp/16621: 1">1</span></td>
							<td class="text-center" ><span title="ipv6-icmp/135: 1, ipv6-icmp/128: 1">2</span></td>
						</tr>
						<tr>
							<td>::1</td>
							<td class="text-center">8</td>

							<td>udp</td>
							<td class="text-center" >8</td>
							<td class="text-center" ><span title="udp/55700: 2, udp/12973: 2, udp/31505: 2, udp/56264: 2">4</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 8">1</span></td>
						</tr>
						<tr>
							<td>2001:500:2d::d</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/38613: 1, udp/11837: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2620:4f:8000::6</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/61279: 1, udp/21484: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2001:500:d937::30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/55107: 1, udp/38338: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2001:500:2::c</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/21763: 1, udp/64964: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2001:503:c27::2:30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/47439: 1, udp/37733: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2610:160:11:11::69</td>
							<td class="text-center">1</td>

							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/34119: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
						</tr>
						<tr>
							<td>2001:7fd::1</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/14236: 1, udp/39593: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47</td>
							<td class="text-center">93</td>

							<td>tcp</td>
							<td class="text-center" >93</td>
							<td class="text-center" ><span title="tcp/49986: 1, tcp/49983: 1, tcp/55116: 1, tcp/55111: 1, tcp/55110: 1, tcp/55109: 1, tcp/55108: 1, tcp/55107: 1, tcp/55106: 1, tcp/55105: 1, tcp/55104: 1, tcp/55103: 1, tcp/55102: 1, tcp/55101: 1, tcp/55100: 1, tcp/55099: 1, tcp/55098: 1, tcp/55097: 1, tcp/55096: 1, tcp/55095: 1, tcp/55094: 1, tcp/55093: 1, tcp/55092: 1, tcp/55091: 1, tcp/55090: 1, tcp/55089: 1, tcp/55088: 1, tcp/55087: 1, tcp/55086: 1, tcp/55085: 1, tcp/55084: 1, tcp/55083: 1, tcp/55082: 1, tcp/55081: 1, tcp/55080: 1, tcp/55079: 1, tcp/55078: 1, tcp/55077: 1, tcp/55076: 1, tcp/55075: 1, tcp/55074: 1, tcp/55073: 1, tcp/55072: 1, tcp/55071: 1, tcp/55070: 1, tcp/55069: 1, tcp/55068: 1, tcp/55067: 1, tcp/55066: 1, tcp/55065: 1, tcp/55064: 1, tcp/55063: 1, tcp/55062: 1, tcp/55061: 1, tcp/55060: 1, tcp/55059: 1, tcp/55058: 1, tcp/55057: 1, tcp/55056: 1, tcp/55055: 1, tcp/55054: 1, tcp/55053: 1, tcp/55052: 1, tcp/55051: 1, tcp/55050: 1, tcp/55049: 1, tcp/55048: 1, tcp/55047: 1, tcp/55046: 1, tcp/55045: 1, tcp/55044: 1, tcp/55043: 1, tcp/55042: 1, tcp/55041: 1, tcp/55040: 1, tcp/55039: 1, tcp/55038: 1, tcp/55037: 1, tcp/55036: 1, tcp/55035: 1, tcp/55034: 1, tcp/55033: 1, tcp/55032: 1, tcp/55031: 1, tcp/55030: 1, tcp/55029: 1, tcp/55028: 1, tcp/55027: 1, tcp/55026: 1, tcp/55025: 1, tcp/55024: 1, tcp/55022: 1, tcp/55021: 1">93</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 93">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.254</td>
							<td class="text-center">1</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16117: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.255</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.99.1</td>
							<td class="text-center">1</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16818: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
						</tr>
						<tr>
							<td>34.197.184.5</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/47121: 1, udp/20595: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>127.0.0.1</td>
							<td class="text-center">64</td>

							<td>udp</td>
							<td class="text-center" >64</td>
							<td class="text-center" ><span title="udp/24594: 2, udp/2645: 2, udp/58028: 2, udp/62353: 2, udp/28025: 2, udp/24661: 2, udp/11007: 2, udp/55304: 2, udp/37102: 2, udp/2294: 2, udp/53548: 2, udp/10154: 2, udp/57631: 2, udp/14778: 2, udp/60387: 2, udp/59162: 2, udp/58065: 2, udp/36990: 2, udp/47750: 2, udp/10050 (zabbix-agent): 2, udp/4265: 2, udp/35247: 2, udp/1373: 2, udp/4393: 2, udp/24868: 2, udp/3439: 2, udp/31114: 2, udp/39740: 2, udp/42259: 2, udp/26142: 2, udp/56289: 2, udp/18648: 2">32</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 64">1</span></td>
						</tr>
						<tr>
							<td>192.31.80.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/61302: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.33.14.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/46645: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.36.148.17</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/11811: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.43.172.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/54675: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.54.112.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/24077: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.58.128.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/48326: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.175.48.6</td>
							<td class="text-center">7</td>

							<td>udp</td>
							<td class="text-center" >7</td>
							<td class="text-center" ><span title="udp/56883: 1, udp/45261: 1, udp/25885: 1, udp/47475: 1, udp/11825: 1, udp/29080: 1, udp/27041: 1">7</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 7">1</span></td>
						</tr>
						<tr>
							<td>192.175.48.42</td>
							<td class="text-center">7</td>

							<td>udp</td>
							<td class="text-center" >7</td>
							<td class="text-center" ><span title="udp/11514: 1, udp/42348: 1, udp/26330: 1, udp/33676: 1, udp/35284: 1, udp/27496: 1, udp/58266: 1">7</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 7">1</span></td>
						</tr>
						<tr>
							<td>199.7.83.42</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/15618: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>199.7.91.13</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/59238: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>208.123.73.69</td>
							<td class="text-center">1</td>

							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/26656: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
						</tr>
						<tr>
							<td>208.123.73.80</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/27496: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>255.255.255.255</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Total per IP</h2>
		</div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-hover table-condensed table-striped">
					<thead>
						<tr>
							<th></th><th></th><th></th>
							<th colspan="3" class="text-center colspanth">Protocol counts</th>
						</tr>

						<tr>
							<th >IP</th>
							<th class="text-center"># States</th>
							<th >Protocol</th>
							<th class="text-center"># States</th>
							<th class="text-center">Source Ports</th>
							<th class="text-center">Dest. Ports</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>2620:4f:8000::42</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/52408: 1, udp/46855: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2610:160:11:11::69</td>
							<td class="text-center">1</td>

							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/34119: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
						</tr>
						<tr>
							<td>2001:503:c27::2:30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/47439: 1, udp/37733: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2001:500:d937::30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/55107: 1, udp/38338: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2001:500:2d::d</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/38613: 1, udp/11837: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>::1</td>
							<td class="text-center">16</td>

							<td>udp</td>
							<td class="text-center" >16</td>
							<td class="text-center" ><span title="udp/55700: 4, udp/12973: 4, udp/31505: 4, udp/56264: 4">4</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 16">1</span></td>
						</tr>
						<tr>
							<td>2001:7fd::1</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/14236: 1, udp/39593: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2620:4f:8000::6</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/61279: 1, udp/21484: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>fe80::1</td>
							<td class="text-center">2</td>

							<td>ipv6-icmp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="ipv6-icmp/16621: 1">1</span></td>
							<td class="text-center" ><span title="ipv6-icmp/135: 1, ipv6-icmp/128: 1">2</span></td>
						</tr>
						<tr>
							<td rowspan="3">fe80::a00:27ff:fe27:309e</td>
							<td rowspan="3" class="text-center">19</td>

							<td>ipv6-icmp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="ipv6-icmp/16621: 1">1</span></td>
							<td class="text-center" ><span title="ipv6-icmp/135: 1, ipv6-icmp/128: 1">2</span></td>
							</tr><tr>
							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/34119: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
							</tr><tr>
							<td>udp</td>
							<td class="text-center" >16</td>
							<td class="text-center" ><span title="udp/14236: 1, udp/39593: 1, udp/47439: 1, udp/37733: 1, udp/38613: 1, udp/11837: 1, udp/61279: 1, udp/21484: 1, udp/52408: 1, udp/46855: 1, udp/21763: 1, udp/64964: 1, udp/55107: 1, udp/38338: 1, udp/60061: 1, udp/31656: 1">16</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 16">1</span></td>
						</tr>
						<tr>
							<td>2001:502:8cc::30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/60061: 1, udp/31656: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>2001:500:2::c</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/21763: 1, udp/64964: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td rowspan="3">10.10.66.47</td>
							<td rowspan="3" class="text-center">120</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16117: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
							</tr><tr>
							<td>tcp</td>
							<td class="text-center" >94</td>
							<td class="text-center" ><span title="tcp/49986: 1, tcp/49983: 1, tcp/26656: 1, tcp/55116: 1, tcp/55111: 1, tcp/55110: 1, tcp/55109: 1, tcp/55108: 1, tcp/55107: 1, tcp/55106: 1, tcp/55105: 1, tcp/55104: 1, tcp/55103: 1, tcp/55102: 1, tcp/55101: 1, tcp/55100: 1, tcp/55099: 1, tcp/55098: 1, tcp/55097: 1, tcp/55096: 1, tcp/55095: 1, tcp/55094: 1, tcp/55093: 1, tcp/55092: 1, tcp/55091: 1, tcp/55090: 1, tcp/55089: 1, tcp/55088: 1, tcp/55087: 1, tcp/55086: 1, tcp/55085: 1, tcp/55084: 1, tcp/55083: 1, tcp/55082: 1, tcp/55081: 1, tcp/55080: 1, tcp/55079: 1, tcp/55078: 1, tcp/55077: 1, tcp/55076: 1, tcp/55075: 1, tcp/55074: 1, tcp/55073: 1, tcp/55072: 1, tcp/55071: 1, tcp/55070: 1, tcp/55069: 1, tcp/55068: 1, tcp/55067: 1, tcp/55066: 1, tcp/55065: 1, tcp/55064: 1, tcp/55063: 1, tcp/55062: 1, tcp/55061: 1, tcp/55060: 1, tcp/55059: 1, tcp/55058: 1, tcp/55057: 1, tcp/55056: 1, tcp/55055: 1, tcp/55054: 1, tcp/55053: 1, tcp/55052: 1, tcp/55051: 1, tcp/55050: 1, tcp/55049: 1, tcp/55048: 1, tcp/55047: 1, tcp/55046: 1, tcp/55045: 1, tcp/55044: 1, tcp/55043: 1, tcp/55042: 1, tcp/55041: 1, tcp/55040: 1, tcp/55039: 1, tcp/55038: 1, tcp/55037: 1, tcp/55036: 1, tcp/55035: 1, tcp/55034: 1, tcp/55033: 1, tcp/55032: 1, tcp/55031: 1, tcp/55030: 1, tcp/55029: 1, tcp/55028: 1, tcp/55027: 1, tcp/55026: 1, tcp/55025: 1, tcp/55024: 1, tcp/55022: 1, tcp/55021: 1">94</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 94">1</span></td>
							</tr><tr>
							<td>udp</td>
							<td class="text-center" >25</td>
							<td class="text-center" ><span title="udp/27496: 2, udp/11514: 1, udp/42348: 1, udp/11811: 1, udp/26330: 1, udp/33676: 1, udp/56883: 1, udp/48326: 1, udp/35284: 1, udp/45261: 1, udp/25885: 1, udp/47475: 1, udp/59238: 1, udp/11825: 1, udp/58266: 1, udp/29080: 1, udp/15618: 1, udp/27041: 1, udp/46645: 1, udp/24077: 1, udp/47121: 1, udp/54675: 1, udp/61302: 1, udp/20595: 1">24</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 25">1</span></td>
						</tr>
						<tr>
							<td rowspan="2">10.10.66.51</td>
							<td rowspan="2" class="text-center">94</td>

							<td>tcp</td>
							<td class="text-center" >93</td>
							<td class="text-center" ><span title="tcp/49986: 1, tcp/49983: 1, tcp/55116: 1, tcp/55111: 1, tcp/55110: 1, tcp/55109: 1, tcp/55108: 1, tcp/55107: 1, tcp/55106: 1, tcp/55105: 1, tcp/55104: 1, tcp/55103: 1, tcp/55102: 1, tcp/55101: 1, tcp/55100: 1, tcp/55099: 1, tcp/55098: 1, tcp/55097: 1, tcp/55096: 1, tcp/55095: 1, tcp/55094: 1, tcp/55093: 1, tcp/55092: 1, tcp/55091: 1, tcp/55090: 1, tcp/55089: 1, tcp/55088: 1, tcp/55087: 1, tcp/55086: 1, tcp/55085: 1, tcp/55084: 1, tcp/55083: 1, tcp/55082: 1, tcp/55081: 1, tcp/55080: 1, tcp/55079: 1, tcp/55078: 1, tcp/55077: 1, tcp/55076: 1, tcp/55075: 1, tcp/55074: 1, tcp/55073: 1, tcp/55072: 1, tcp/55071: 1, tcp/55070: 1, tcp/55069: 1, tcp/55068: 1, tcp/55067: 1, tcp/55066: 1, tcp/55065: 1, tcp/55064: 1, tcp/55063: 1, tcp/55062: 1, tcp/55061: 1, tcp/55060: 1, tcp/55059: 1, tcp/55058: 1, tcp/55057: 1, tcp/55056: 1, tcp/55055: 1, tcp/55054: 1, tcp/55053: 1, tcp/55052: 1, tcp/55051: 1, tcp/55050: 1, tcp/55049: 1, tcp/55048: 1, tcp/55047: 1, tcp/55046: 1, tcp/55045: 1, tcp/55044: 1, tcp/55043: 1, tcp/55042: 1, tcp/55041: 1, tcp/55040: 1, tcp/55039: 1, tcp/55038: 1, tcp/55037: 1, tcp/55036: 1, tcp/55035: 1, tcp/55034: 1, tcp/55033: 1, tcp/55032: 1, tcp/55031: 1, tcp/55030: 1, tcp/55029: 1, tcp/55028: 1, tcp/55027: 1, tcp/55026: 1, tcp/55025: 1, tcp/55024: 1, tcp/55022: 1, tcp/55021: 1">93</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 93">1</span></td>
							</tr><tr>
							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
						</tr>
						<tr>
							<td rowspan="2">10.10.66.254</td>
							<td rowspan="2" class="text-center">2</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16117: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
							</tr><tr>
							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.255</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.99.1</td>
							<td class="text-center">1</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16818: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.99.2</td>
							<td class="text-center">1</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16818: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
						</tr>
						<tr>
							<td>34.197.184.5</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/47121: 1, udp/20595: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>127.0.0.1</td>
							<td class="text-center">128</td>

							<td>udp</td>
							<td class="text-center" >128</td>
							<td class="text-center" ><span title="udp/24594: 4, udp/2645: 4, udp/58028: 4, udp/62353: 4, udp/28025: 4, udp/24661: 4, udp/11007: 4, udp/55304: 4, udp/37102: 4, udp/2294: 4, udp/53548: 4, udp/10154: 4, udp/57631: 4, udp/14778: 4, udp/60387: 4, udp/59162: 4, udp/58065: 4, udp/36990: 4, udp/47750: 4, udp/10050 (zabbix-agent): 4, udp/4265: 4, udp/35247: 4, udp/1373: 4, udp/4393: 4, udp/24868: 4, udp/3439: 4, udp/31114: 4, udp/39740: 4, udp/42259: 4, udp/26142: 4, udp/56289: 4, udp/18648: 4">32</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 128">1</span></td>
						</tr>
						<tr>
							<td>192.31.80.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/61302: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.33.14.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/46645: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.36.148.17</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/11811: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.43.172.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/54675: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.54.112.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/24077: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.58.128.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/48326: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>192.175.48.6</td>
							<td class="text-center">7</td>

							<td>udp</td>
							<td class="text-center" >7</td>
							<td class="text-center" ><span title="udp/56883: 1, udp/45261: 1, udp/25885: 1, udp/47475: 1, udp/11825: 1, udp/29080: 1, udp/27041: 1">7</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 7">1</span></td>
						</tr>
						<tr>
							<td>192.175.48.42</td>
							<td class="text-center">7</td>

							<td>udp</td>
							<td class="text-center" >7</td>
							<td class="text-center" ><span title="udp/11514: 1, udp/42348: 1, udp/26330: 1, udp/33676: 1, udp/35284: 1, udp/27496: 1, udp/58266: 1">7</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 7">1</span></td>
						</tr>
						<tr>
							<td>199.7.83.42</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/15618: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>199.7.91.13</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/59238: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>208.123.73.69</td>
							<td class="text-center">1</td>

							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/26656: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
						</tr>
						<tr>
							<td>208.123.73.80</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/27496: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>255.255.255.255</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">By IP Pair</h2>
		</div>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-hover table-condensed table-striped">
					<thead>
						<tr>
							<th></th><th></th><th></th>
							<th colspan="3" class="text-center colspanth">Protocol counts</th>
						</tr>

						<tr>
							<th >IP</th>
							<th class="text-center"># States</th>
							<th >Protocol</th>
							<th class="text-center"># States</th>
							<th class="text-center">Source Ports</th>
							<th class="text-center">Dest. Ports</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>10.10.66.47 -> 10.10.66.254</td>
							<td class="text-center">1</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16117: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> fe80::1</td>
							<td class="text-center">2</td>

							<td>ipv6-icmp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="ipv6-icmp/16621: 1">1</span></td>
							<td class="text-center" ><span title="ipv6-icmp/135: 1, ipv6-icmp/128: 1">2</span></td>
						</tr>
						<tr>
							<td>10.10.99.2 -> 10.10.99.1</td>
							<td class="text-center">1</td>

							<td>icmp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="icmp/16818: 1">1</span></td>
							<td class="text-center" ><span title="icmp/8: 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.254 -> 255.255.255.255</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
							<td class="text-center" ><span title="udp/5678: 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.51 -> 10.10.66.47</td>
							<td class="text-center">93</td>

							<td>tcp</td>
							<td class="text-center" >93</td>
							<td class="text-center" ><span title="tcp/49986: 1, tcp/49983: 1, tcp/55116: 1, tcp/55111: 1, tcp/55110: 1, tcp/55109: 1, tcp/55108: 1, tcp/55107: 1, tcp/55106: 1, tcp/55105: 1, tcp/55104: 1, tcp/55103: 1, tcp/55102: 1, tcp/55101: 1, tcp/55100: 1, tcp/55099: 1, tcp/55098: 1, tcp/55097: 1, tcp/55096: 1, tcp/55095: 1, tcp/55094: 1, tcp/55093: 1, tcp/55092: 1, tcp/55091: 1, tcp/55090: 1, tcp/55089: 1, tcp/55088: 1, tcp/55087: 1, tcp/55086: 1, tcp/55085: 1, tcp/55084: 1, tcp/55083: 1, tcp/55082: 1, tcp/55081: 1, tcp/55080: 1, tcp/55079: 1, tcp/55078: 1, tcp/55077: 1, tcp/55076: 1, tcp/55075: 1, tcp/55074: 1, tcp/55073: 1, tcp/55072: 1, tcp/55071: 1, tcp/55070: 1, tcp/55069: 1, tcp/55068: 1, tcp/55067: 1, tcp/55066: 1, tcp/55065: 1, tcp/55064: 1, tcp/55063: 1, tcp/55062: 1, tcp/55061: 1, tcp/55060: 1, tcp/55059: 1, tcp/55058: 1, tcp/55057: 1, tcp/55056: 1, tcp/55055: 1, tcp/55054: 1, tcp/55053: 1, tcp/55052: 1, tcp/55051: 1, tcp/55050: 1, tcp/55049: 1, tcp/55048: 1, tcp/55047: 1, tcp/55046: 1, tcp/55045: 1, tcp/55044: 1, tcp/55043: 1, tcp/55042: 1, tcp/55041: 1, tcp/55040: 1, tcp/55039: 1, tcp/55038: 1, tcp/55037: 1, tcp/55036: 1, tcp/55035: 1, tcp/55034: 1, tcp/55033: 1, tcp/55032: 1, tcp/55031: 1, tcp/55030: 1, tcp/55029: 1, tcp/55028: 1, tcp/55027: 1, tcp/55026: 1, tcp/55025: 1, tcp/55024: 1, tcp/55022: 1, tcp/55021: 1">93</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 93">1</span></td>
						</tr>
						<tr>
							<td>127.0.0.1 -> 127.0.0.1</td>
							<td class="text-center">64</td>

							<td>udp</td>
							<td class="text-center" >64</td>
							<td class="text-center" ><span title="udp/24594: 2, udp/2645: 2, udp/58028: 2, udp/62353: 2, udp/28025: 2, udp/24661: 2, udp/11007: 2, udp/55304: 2, udp/37102: 2, udp/2294: 2, udp/53548: 2, udp/10154: 2, udp/57631: 2, udp/14778: 2, udp/60387: 2, udp/59162: 2, udp/58065: 2, udp/36990: 2, udp/47750: 2, udp/10050 (zabbix-agent): 2, udp/4265: 2, udp/35247: 2, udp/1373: 2, udp/4393: 2, udp/24868: 2, udp/3439: 2, udp/31114: 2, udp/39740: 2, udp/42259: 2, udp/26142: 2, udp/56289: 2, udp/18648: 2">32</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 64">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2610:160:11:11::69</td>
							<td class="text-center">1</td>

							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/34119: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 208.123.73.69</td>
							<td class="text-center">1</td>

							<td>tcp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="tcp/26656: 1">1</span></td>
							<td class="text-center" ><span title="tcp/443 (https): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 34.197.184.5</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/47121: 1, udp/20595: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2001:502:8cc::30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/60061: 1, udp/31656: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 192.31.80.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/61302: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 192.43.172.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/54675: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2001:500:d937::30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/55107: 1, udp/38338: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 208.123.73.80</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/27496: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 192.54.112.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/24077: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 192.33.14.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/46645: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>::1 -> ::1</td>
							<td class="text-center">8</td>

							<td>udp</td>
							<td class="text-center" >8</td>
							<td class="text-center" ><span title="udp/55700: 2, udp/12973: 2, udp/31505: 2, udp/56264: 2">4</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 8">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 192.175.48.6</td>
							<td class="text-center">7</td>

							<td>udp</td>
							<td class="text-center" >7</td>
							<td class="text-center" ><span title="udp/56883: 1, udp/45261: 1, udp/25885: 1, udp/47475: 1, udp/11825: 1, udp/29080: 1, udp/27041: 1">7</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 7">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2001:500:2::c</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/21763: 1, udp/64964: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 199.7.83.42</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/15618: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2620:4f:8000::42</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/52408: 1, udp/46855: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 192.175.48.42</td>
							<td class="text-center">7</td>

							<td>udp</td>
							<td class="text-center" >7</td>
							<td class="text-center" ><span title="udp/11514: 1, udp/42348: 1, udp/26330: 1, udp/33676: 1, udp/35284: 1, udp/27496: 1, udp/58266: 1">7</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 7">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 199.7.91.13</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/59238: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2620:4f:8000::6</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/61279: 1, udp/21484: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 192.58.128.30</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/48326: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.51 -> 10.10.66.255</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
							<td class="text-center" ><span title="udp/137 (netbios-ns): 1">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2001:500:2d::d</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/38613: 1, udp/11837: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2001:503:c27::2:30</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/47439: 1, udp/37733: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>fe80::a00:27ff:fe27:309e -> 2001:7fd::1</td>
							<td class="text-center">2</td>

							<td>udp</td>
							<td class="text-center" >2</td>
							<td class="text-center" ><span title="udp/14236: 1, udp/39593: 1">2</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 2">1</span></td>
						</tr>
						<tr>
							<td>10.10.66.47 -> 192.36.148.17</td>
							<td class="text-center">1</td>

							<td>udp</td>
							<td class="text-center" >1</td>
							<td class="text-center" ><span title="udp/11811: 1">1</span></td>
							<td class="text-center" ><span title="udp/53 (domain): 1">1</span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
