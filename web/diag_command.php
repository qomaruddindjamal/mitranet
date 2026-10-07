<?php
/*
 * diag_command.php - MitraNet Diagnostics: Command Prompt
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Command Prompt");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title">Execute Shell Command</h2></div>
		<div class="panel-body">
			<div class="content">
				<input id="txtCommand" name="txtCommand" placeholder="Command" type="text" class="col-sm-7"	 value="" />
				<br /><br />
				<input type="hidden" name="txtRecallBuffer" value="" />

				<div class="btn-group">
					<button type="button" class="btn btn-success btn-sm" name="btnRecallPrev" onclick="btnRecall_onClick( this.form, -1 );" title="Recall Previous Command">
						<i class="fa-solid fa-angle-double-left"></i>
					</button>
					<button name="submit" type="submit" class="btn btn-warning btn-sm" value="EXEC" title="Execute the entered command">
						<i class="fa-solid fa-bolt"></i>
						Execute					</button>
					<button type="button" class="btn btn-success btn-sm" name="btnRecallNext" onclick="btnRecall_onClick( this.form,  1 );" title="Recall Next Command">
						<i class="fa-solid fa-angle-double-right"></i>
					</button>
					<button style="margin-left: 10px;" type="button" class="btn btn-default btn-sm" onclick="return Reset_onClick( this.form );" title="Clear command entry">
						<i class="fa-solid fa-undo"></i>
						Clear					</button>
				</div>
			</div>

<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title">Download File</h2></div>
		<div class="panel-body">
			<div class="content">
				<input name="dlPath" type="text" id="dlPath" placeholder="File to download" class="col-sm-4" value=""/>
				<br /><br />
				<button name="submit" type="submit" class="btn btn-primary btn-sm" id="download" value="DOWNLOAD">
					<i class="fa-solid fa-download icon-embed-btn"></i>
					Download				</button>
			</div>
		</div>

<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title">Upload File</h2></div>
		<div class="panel-body">
			<div class="content">
				<input name="ulfile" type="file" class="btn btn-default btn-sm btn-file" id="ulfile" />
				<br />
				<button name="submit" type="submit" class="btn btn-primary btn-sm" id="upload" value="UPLOAD">
					<i class="fa-solid fa-upload icon-embed-btn"></i>
					Upload				</button>
			</div>
		</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
