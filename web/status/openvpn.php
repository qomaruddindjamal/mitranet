<?php
/*
 * status_openvpn.php - MitraNet Status: OpenVPN
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Status", "OpenVPN");
$selected_menu = "status";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<form action="status_openvpn.php" method="get" name="iform">
<script type="text/javascript">
//<![CDATA[
	function killClient(mport, remipp, client_id) {
		if (client_id === '') {
			$('a[id="i:' + mport + ":" + remipp + '"]').first().children('i').removeClass().addClass('fa-solid fa-cog fa-spin text-danger');
		} else {
			$('a[id="i:' + mport + ":" + remipp + '"]').last().children('i').removeClass().addClass('fa-solid fa-cog fa-spin text-danger');
		}

		$.ajax(
			"/status_openvpn.php",
			{
				type: "post",
				data: {
					action:           "kill",
					port:		  mport,
					remipp:		  remipp,
					client_id:	  client_id
				},
				complete: killComplete
			}
		);
	}

	function killComplete(req) {
		var values = req.responseText.split("|");
		if (values[3] != "0") {
	//		alert('An error occurred.' + ' (' + values[3] + ')');
			return;
		}

		$('tr[id="r:' + values[1] + ":" + values[2] + '"]').each(
			function(index,row) { $(row).fadeOut(1000); }
		);
	}

	function showRuleContents(vpnid, username, port) {
			$('#rulesviewer_text').text("...Loading...");
			$('#rulesviewer').modal('show');

			$.ajax(
				"/status_openvpn.php",
				{
					type: 'post',
					data: {
						vpnid:           vpnid,
						username:     username,
						port:             port,
						action:      'showrule'
					},
					complete: ruleComplete
				}
			);
	}

	function ruleComplete(req) {
			$('#rulesviewer_text').text(atob(req.responseText));
			$('#rulesviewer_text').attr('readonly', true);
	}

//]]>
</script>

<br />

<br />
<div class="alert alert-warning clearfix" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><div class="pull-left">No OpenVPN instances defined.</div></div>	<form class="form-horizontal" method="post" action="#">
			<div id="rulesviewer" class="modal fade" role="dialog" aria-labelledby="rulesviewer" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h3 class="modal-title">RADIUS ACL Generated Ruleset</h3>
				</div>
<!--				<form class="form-horizontal" action="" method="post"> -->
					<div class="modal-body">
							<div class="form-group">
		<label class="col-sm-2 control-label">
			
		</label>
			<div class="col-sm-10">
		
			<textarea rows="10" class="row-fluid col-sm-11" name="rulesviewer_text" id="rulesviewer_text" wrap="soft">...Loading...</textarea>
		

		
	</div>
		
	</div>
					</div>
					<div class="modal-footer">
						<input class="btn btn-primary" type="submit" value="Close" name="save" id="save" data-dismiss="modal"/>
					</div>
<!--				</form>





<?php include(__DIR__ . '/../includes/foot.inc'); ?>
