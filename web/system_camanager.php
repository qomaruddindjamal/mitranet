<?php
/*
 * system_camanager.php - MitraNet System: Certificates
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array("System", "Certificates");
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$savemsg = "";
$sys = MitraNetApi::getSystem();
?>

<ul class="nav nav-pills"><li role="presentation" class="active"><a href="/system_camanager.php" >Authorities</a></li><li role="presentation"><a href="/system_certmanager.php" >Certificates</a></li><li role="presentation"><a href="/system_crlmanager.php" >Revocation</a></li></ul>

<div class="panel panel-default" id="search-panel">
	<div class="panel-heading">
		<h2 class="panel-title">
			Search			<span class="widget-heading-icon pull-right">
				<a data-toggle="collapse" href="#search-panel_panel-body">
					<i class="fa-solid fa-plus-circle"></i>
				</a>
			</span>
		</h2>
	</div>
	<div id="search-panel_panel-body" class="panel-body collapse in">
		<div class="form-group">
			<label class="col-sm-2 control-label">
				Search term			</label>
			<div class="col-sm-5"><input class="form-control" name="searchstr" id="searchstr" type="text"/></div>
			<div class="col-sm-2">
				<select id="where" class="form-control">
					<option value="0">Name</option>
					<option value="1">Distinguished Name</option>
					<option value="2" selected>Both</option>
				</select>
			</div>
			<div class="col-sm-3">
				<a id="btnsearch" title="Search" class="btn btn-primary btn-sm"><i class="fa-solid fa-search icon-embed-btn"></i>Search</a>
				<a id="btnclear" title="Clear" class="btn btn-info btn-sm"><i class="fa-solid fa-undo icon-embed-btn"></i>Clear</a>
			</div>
			<div class="col-sm-10 col-sm-offset-2">
				<span class="help-block">Enter a search string or *nix regular expression to search certificate names and distinguished names.</span>
			</div>
		</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title">Certificate Authorities</h2></div>
	<div class="panel-body">
		<div class="table-responsive">
		<table id="catable" class="table table-striped table-hover table-rowdblclickedit sortable-theme-bootstrap" data-sortable>
			<thead>
				<tr>
					<th>Name</th>
					<th>Internal</th>
					<th>Issuer</th>
					<th>Certificates</th>
					<th>Identity</th>
					<th>In Use</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
		</div>
	</div>

<nav class="action-buttons">
	<a href="/?act=new" class="btn btn-success btn-sm">
		<i class="fa-solid fa-plus icon-embed-btn"></i>
		Add	</a>
</nav>



<?php include(__DIR__ . '/includes/foot.inc'); ?>
