<?php
/*
 * diag_smart.php - MitraNet Diagnostics: S.M.A.R.T. Status
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("Diagnostics", "S.M.A.R.T. Status");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>



<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Information</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Select a drive and type:</span>
		</label>
			<div class="col-sm-3">
		
			<select class="form-control" name="device" id="device">
		<option value="ada0">ada0</option>
	</select>
		

		<span class="help-block">Device: /dev/</span>
	</div>	<div class="col-sm-3">
		
			<select class="form-control" name="type" id="type">
		<option value="x">All SMART and Non-SMART Information</option><option value="a">All SMART Information</option><option value="i">Device Information</option><option value="H">Device Health</option><option value="c">SMART Capabilities</option><option value="A">SMART Attributes</option>
	</select>
		

		<span class="help-block">Information Type</span>
	</div>	<div class="col-sm-3">
		
		<button class="btn btn-primary" type="submit" value="View" name="submit"><i class="fa-regular fa-file-lines icon-embed-btn"> </i>View</button>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">View Logs</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Select a device and log</span>
		</label>
			<div class="col-sm-3">
		
			<select class="form-control" name="device" id="device">
		<option value="ada0">ada0</option>
	</select>
		

		<span class="help-block">Device: /dev/</span>
	</div>	<div class="col-sm-3">
		
			<select class="form-control" name="type" id="type">
		<option value="error">Summary Error Log</option><option value="xerror">Extended Error Log</option><option value="selftest">SMART Self-Test Log</option><option value="xselftest">Extended Self-Test Log</option><option value="selective">Selective Self-Test Log</option><option value="directory">Log Directory</option><option value="scttemp">Device Temperature Log (ATA Only)</option><option value="devstat">Device Statistics (ATA Only)</option><option value="sataphy">SATA PHY Events (SATA Only)</option><option value="sasphy">SAS PHY Events (SAS Only)</option><option value="nvmelog">NVMe Log (NVMe Only)</option><option value="ssd">SSD Device Statistics (ATA/SCSI)</option>
	</select>
		

		<span class="help-block">Log</span>
	</div>	<div class="col-sm-3">
		
		<button class="btn btn-primary" type="submit" value="View" name="submit"><i class="fa-regular fa-file-lines icon-embed-btn"> </i>View</button>
		

		
	</div>
		
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Perform self-tests</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Select a drive and test</span>
		</label>
			<div class="col-sm-3">
		
			<select class="form-control" name="device" id="device">
		<option value="ada0">ada0</option>
	</select>
		

		<span class="help-block">Device: /dev/</span>
	</div>	<div class="col-sm-3">
		
			<select class="form-control" name="type" id="type">
		<option value="offline">Offline Test</option><option value="short">Short Test</option><option value="long">Long Test</option><option value="conveyance">Conveyance Test</option>
	</select>
		

		<span class="help-block">Self-Test Type</span>
	</div>	<div class="col-sm-3">
		
		<button class="btn btn-primary" type="submit" value="Test" name="submit"><i class="fa-solid fa-wrench icon-embed-btn"> </i>Test</button>
		

		
	</div>
			<div class="col-sm-10 col-sm-offset-2">
		<span class="help-block">
			Select "Conveyance" for ATA disks only.
		</span>
	</div>
	</div>

<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">Abort Tests</h2>
		</div>
		<div class="panel-body">
				<div class="form-group">
		<label class="col-sm-2 control-label">
			<span>Device: /dev/</span>
		</label>
			<div class="col-sm-10">
		
			<select class="form-control" name="device" id="device">
		<option value="ada0">ada0</option>
	</select>
		

		<span class="help-block">Aborts all self-tests running on the selected device.</span>
	</div>
		
	</div>





<?php include(__DIR__ . '/includes/foot.inc'); ?>
