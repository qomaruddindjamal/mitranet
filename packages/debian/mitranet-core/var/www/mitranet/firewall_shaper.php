<?php
/*
 * firewall_shaper.php - MitraNet Firewall: Traffic Shaper
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Firewall", "Traffic Shaper");
$selected_menu = "firewall";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/firewall_shaper.php" >By Interface</a></li><li role="presentation"><a href="/firewall_shaper_queues.php" >By Queue</a></li><li role="presentation"><a href="/firewall_shaper_vinterface.php" >Limiters</a></li><li role="presentation"><a href="/firewall_shaper_wizards.php" >Wizards</a></li></ul>

<div id="notices" class="modal fade" role="dialog">

	<div class="modal-dialog">

		<div class="modal-content">

			<div class="modal-header">

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">

					<span aria-hidden="true">&times;</span>

				</button>



				<h3 class="modal-title" id="myModalLabel">Notices</h3>

			</div>



			<div class="modal-body">

				<h4>Upgrade</h4>

				<ul>

					<li>

						<b>

						</b>

						check_upgrade: &quot;&quot; returned error code 1						<i>@ 2026-10-07 09:09:03</i>

					</li>

					<li>

						<b>

						</b>

						check_upgrade: &quot;&quot; returned error code 1						<i>@ 2026-10-07 09:43:12</i>

					</li>

				</ul>

			</div>



			<div class="modal-footer">

				<button type="button" class="btn btn-info" data-dismiss="modal"><i class="fa-solid fa-times icon-embed-btn"></i>Close</button>

				<button type="button" id="clearallnotices" class="btn btn-primary"><i class="fa-regular fa-trash-can icon-embed-btn"></i>Mark All as Read</button>

			</div>

		</div>

	</div>

</div>



<script type="text/javascript">

//<![CDATA[

	events.push(function() {

	    $('#clearallnotices').click(function() {

			ajaxRequest = $.ajax({

				url: "/index.php",

				type: "post",

				data: { closenotice: "all"},

				success: function() {

					window.location = window.location.href;

				},

				failure: function() {

					alert("Error clearing notices!");

				}

			});

		});

	});

//]]>

</script>



<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/firewall_shaper.php" >By Interface</a></li><li role="presentation"><a href="/firewall_shaper_queues.php" >By Queue</a></li><li role="presentation"><a href="/firewall_shaper_vinterface.php" >Limiters</a></li><li role="presentation"><a href="/firewall_shaper_wizards.php" >Wizards</a></li></ul><script type="text/javascript" src="./vendor/tree/tree.js"></script>

<div class="table-responsive">
	<table class="table">
		<tbody>
			<tr class="tabcont">
				<td class="col-md-1">
<ul class="tree" > <li><a href="firewall_shaper.php?interface=wan&amp;action=add">WAN</a></li> <li><a href="firewall_shaper.php?interface=lan&amp;action=add">LAN</a></li></ul>				</td>
				<td>
				</td>
			</tr>
		</tbody>
	</table>
</div>


<div>
	<div class="infoblock">
		<div class="alert alert-info clearfix" role="alert"><div class="pull-left">Welcome to the pfSense Traffic Shaper.<br />The tree on the left navigates through the queues.<br />Buttons at the bottom represent queue actions and are activated accordingly.</div></div>	</div>
</div>
	</div>



<div class="infoblock">
		<div class="alert alert-info clearfix" role="alert"><div class="pull-left">Welcome to the pfSense Traffic Shaper.<br />The tree on the left navigates through the queues.<br />Buttons at the bottom represent queue actions and are activated accordingly.</div></div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
