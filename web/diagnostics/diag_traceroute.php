<?php
/*
 * diag_traceroute.php - MitraNet Diagnostics Traceroute Tool
 * Adapted from pfSense diag_traceroute.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Diagnostics"), gettext("Traceroute"));
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$host = '';
$output = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? '');
    if (!empty($host)) {
        $res = MitraNetApi::traceroute($host);
        if ($res['status'] === 200) {
            $output = $res['data']['output'] ?? '';
        } else {
            $err = $res['data']['error'] ?? 'Traceroute failed to execute';
        }
    } else {
        $err = "Host/IP address is required.";
    }
}

if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<form method="post" action="diag_traceroute.php" class="form-horizontal">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=gettext("Trace Network Route")?></h2></div>
		<div class="panel-body">
			<div class="form-group">
				<label class="col-sm-2 control-label" for="host"><span class="element-required">*</span><?=gettext("Hostname / IP Address")?></label>
				<div class="col-sm-6">
					<input type="text" class="form-control" id="host" name="host" value="<?=htmlspecialchars($host)?>" placeholder="e.g. 192.168.56.1 or 1.1.1.1" required>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary"><i class="fa-solid fa-play icon-embed-btn"></i><?=gettext("Traceroute")?></button>
				</div>
			</div>
		</div>
	</div>
</form>

<?php if (!empty($output)): ?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa fa-poll"></i> Traceroute Output</h2></div>
	<div class="panel-body">
		<pre class="pre-terminal"><?=htmlspecialchars($output)?></pre>
	</div>
</div>
<?php endif; ?>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
