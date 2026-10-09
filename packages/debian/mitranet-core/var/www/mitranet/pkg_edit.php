<?php
/*
 * pkg_edit.php - MitraNet Services: UPnP IGD &amp; PCP
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Services", "UPnP IGD &amp; PCP");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



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

				<h4>PHP errors</h4>

				<ul>

					<li>

						<b>

						</b>

						PHP ERROR: Type: 1, File: /usr/local/www/services_dhcp.php, Line: 1842, Message: Uncaught TypeError: count(): Argument #1 ($value) must be of type Countable|array, null given in /usr/local/www/services_dhcp.php:1842<br/>Stack trace:<br/>#0 {main}<br/>  thrown						<i>@ 2026-10-07 10:31:46</i>

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



<div class="alert alert-danger clearfix" role="alert"><div class="pull-left">No valid package defined.</div></div>	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
