<?php
/*
 * diag_sockets.php - MitraNet Diagnostics: Sockets
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Sockets");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">IPv4 System Socket Information</h2></div>
	<div class="panel-body">
		<div class="table table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
<tr>
<th class="">USER</th>
<th class="">COMMAND</th>
<th class="">PID</th>
<th class="">FD</th>
<th class="">PROTO</th>
<th class="">LOCAL</th>
<th class="">FOREIGN</th>
</tr>
</thead>
<tbody>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">4</td>
<td class="">tcp46</td>
<td class="">*:9443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">5</td>
<td class="">tcp4</td>
<td class="">127.0.0.1:10808</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">6</td>
<td class="">udp4</td>
<td class="">127.0.0.1:10808</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">7</td>
<td class="">tcp4</td>
<td class="">127.0.0.1:12345</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">8</td>
<td class="">udp4</td>
<td class="">127.0.0.1:12345</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">9</td>
<td class="">tcp46</td>
<td class="">*:8443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">11</td>
<td class="">tcp46</td>
<td class="">*:8080</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">syslogd</td>
<td class="">54338</td>
<td class="">10</td>
<td class="">udp4</td>
<td class="">*:514</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">dhcpd</td>
<td class="">dhcpd</td>
<td class="">16226</td>
<td class="">9</td>
<td class="">udp4</td>
<td class="">*:67</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">13037</td>
<td class="">5</td>
<td class="">tcp4</td>
<td class="">*:443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">13037</td>
<td class="">7</td>
<td class="">tcp4</td>
<td class="">*:80</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">12930</td>
<td class="">5</td>
<td class="">tcp4</td>
<td class="">*:443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">12930</td>
<td class="">7</td>
<td class="">tcp4</td>
<td class="">*:80</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">12821</td>
<td class="">5</td>
<td class="">tcp4</td>
<td class="">*:443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">12821</td>
<td class="">7</td>
<td class="">tcp4</td>
<td class="">*:80</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">21</td>
<td class="">udp4</td>
<td class="">*:123</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">23</td>
<td class="">udp4</td>
<td class="">10.10.66.47:123</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">24</td>
<td class="">udp4</td>
<td class="">10.20.30.254:123</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">26</td>
<td class="">udp4</td>
<td class="">192.168.1.1:123</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">29</td>
<td class="">udp4</td>
<td class="">127.0.0.1:123</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">unbound</td>
<td class="">unbound</td>
<td class="">8001</td>
<td class="">5</td>
<td class="">udp4</td>
<td class="">*:53</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">unbound</td>
<td class="">unbound</td>
<td class="">8001</td>
<td class="">6</td>
<td class="">tcp4</td>
<td class="">*:53</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">unbound</td>
<td class="">unbound</td>
<td class="">8001</td>
<td class="">7</td>
<td class="">tcp4</td>
<td class="">127.0.0.1:953</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">php-fpm</td>
<td class="">4491</td>
<td class="">4</td>
<td class="">udp4</td>
<td class="">*:*</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">sshd</td>
<td class="">71314</td>
<td class="">7</td>
<td class="">tcp4</td>
<td class="">*:22</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">php-fpm</td>
<td class="">452</td>
<td class="">4</td>
<td class="">udp4</td>
<td class="">*:*</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">php-fpm</td>
<td class="">451</td>
<td class="">4</td>
<td class="">udp4</td>
<td class="">*:*</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">php-fpm</td>
<td class="">450</td>
<td class="">4</td>
<td class="">udp4</td>
<td class="">*:*</td>
<td class="">*:*</td>
</tr>
				</tbody>
			</table>
		</div>
	</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">IPv6 System Socket Information</h2></div>
	<div class="panel-body">
		<div class="table table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
<tr>
<th class="">USER</th>
<th class="">COMMAND</th>
<th class="">PID</th>
<th class="">FD</th>
<th class="">PROTO</th>
<th class="">LOCAL</th>
<th class="">FOREIGN</th>
</tr>
</thead>
<tbody>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">4</td>
<td class="">tcp46</td>
<td class="">*:9443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">9</td>
<td class="">tcp46</td>
<td class="">*:8443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">xray</td>
<td class="">4413</td>
<td class="">11</td>
<td class="">tcp46</td>
<td class="">*:8080</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">syslogd</td>
<td class="">54338</td>
<td class="">9</td>
<td class="">udp6</td>
<td class="">*:514</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">13037</td>
<td class="">6</td>
<td class="">tcp6</td>
<td class="">*:443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">13037</td>
<td class="">9</td>
<td class="">tcp6</td>
<td class="">*:80</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">12930</td>
<td class="">6</td>
<td class="">tcp6</td>
<td class="">*:443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">12930</td>
<td class="">9</td>
<td class="">tcp6</td>
<td class="">*:80</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">12821</td>
<td class="">6</td>
<td class="">tcp6</td>
<td class="">*:443</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">nginx</td>
<td class="">12821</td>
<td class="">9</td>
<td class="">tcp6</td>
<td class="">*:80</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">20</td>
<td class="">udp6</td>
<td class="">*:123</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">22</td>
<td class="">udp6</td>
<td class="">[fe80::a00:27ff:fe27:</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">25</td>
<td class="">udp6</td>
<td class="">[fe80::a00:27ff:fe7a:</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">27</td>
<td class="">udp6</td>
<td class="">[::1]:123</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">ntpd</td>
<td class="">12451</td>
<td class="">28</td>
<td class="">udp6</td>
<td class="">[fe80::1%lo0]:123</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">unbound</td>
<td class="">unbound</td>
<td class="">8001</td>
<td class="">3</td>
<td class="">udp6</td>
<td class="">*:53</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">unbound</td>
<td class="">unbound</td>
<td class="">8001</td>
<td class="">4</td>
<td class="">tcp6</td>
<td class="">*:53</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">php-fpm</td>
<td class="">4491</td>
<td class="">5</td>
<td class="">udp6</td>
<td class="">*:*</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">sshd</td>
<td class="">71314</td>
<td class="">6</td>
<td class="">tcp6</td>
<td class="">*:22</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">dhcp6c</td>
<td class="">53767</td>
<td class="">4</td>
<td class="">udp6</td>
<td class="">*:546</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">php-fpm</td>
<td class="">452</td>
<td class="">5</td>
<td class="">udp6</td>
<td class="">*:*</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">php-fpm</td>
<td class="">451</td>
<td class="">5</td>
<td class="">udp6</td>
<td class="">*:*</td>
<td class="">*:*</td>
</tr>
<tr>
<td class="">root</td>
<td class="">php-fpm</td>
<td class="">450</td>
<td class="">5</td>
<td class="">udp6</td>
<td class="">*:*</td>
<td class="">*:*</td>
</tr>
				</tbody>
			</table>
		</div>
	</div>



<div class="infoblock">
<div class="alert alert-info clearfix" role="alert"><div class="pull-left">Socket Information<br /><br />This page shows all listening sockets by default, and shows both listening and outbound connection sockets when <strong>Show all socket connections</strong> is clicked.<br /><br />The information listed for each socket is:<br /><br /><dl class="dl-horizontal responsive"><dt>USER</dt>	<dd>The user who owns the socket.</dd><dt>COMMAND</dt>	<dd>The command which holds the socket.</dd><dt>PID</dt>	<dd>The process ID of the command which holds the socket.</dd><dt>FD</dt>	<dd>The file descriptor number of the socket.</dd><dt>PROTO</dt>	<dd>The transport protocol associated with the socket.</dd><dt>LOCAL ADDRESS</dt>	<dd>The address the local end of the socket is bound to.</dd><dt>FOREIGN ADDRESS</dt>	<dd>The address the foreign end of the socket is bound to.</dd></dl></div></div>

<?php include(__DIR__ . '/../includes/foot.inc'); ?>
