<?php
/*
 * diag_edit.php - MitraNet Diagnostics: Edit File
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "Edit File");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Save / Load a File from the Filesystem</h2></div>
	<div class="panel-body">
		<div class="content">
			<form>
				<p><input type="text" class="form-control" id="fbTarget" placeholder="Path to file to be edited"/></p>
				<div class="btn-group">
					<p>
						<button type="button" class="btn btn-default btn-sm" onclick="loadFile();"	value="Load">
							<i class="fa-regular fa-file-lines"></i>
							Load						</button>
						<button type="button" class="btn btn-default btn-sm" id="fbOpen"		value="Browse">
							<i class="fa-solid fa-list"></i>
							Browse						</button>
						<button type="button" class="btn btn-default btn-sm" onclick="saveFile();"	value="Save">
							<i class="fa-solid fa-save"></i>
							Save						</button>
					</p>
				</div>
				<p class="pull-right">
					<button id="btngoto" class="btn btn-default btn-sm"><i class="fa-solid fa-forward"></i>GoTo Line #</button> <input type="number" id="gotoline" size="6" class="input-gotoline"/>
				</p>
			</form>

			<div id="fbBrowser" class="file-browser-panel"></div>

			<script type="text/javascript">
			//<![CDATA[
			window.onload=function() {
				document.getElementById("fileContent").wrap='off';
			}
			//]]>
			</script>
			<textarea id="fileContent" name="fileContent" class="form-control" rows="30" cols="20"  class="editor-textarea"></textarea>
		</div>
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
