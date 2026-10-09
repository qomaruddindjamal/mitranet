<?php
/*
 * diag_ping.php - MitraNet Diagnostics Ping Tool
 * Adapted from pfSense diag_ping.php
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Diagnostics"), gettext("Ping"));
$selected_menu = "diagnostics";
require_once(__DIR__ . '/../includes/head.inc');

$host = '';
$count = 3;
$output = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? '');
    $count = intval($_POST['count'] ?? 3);
    if (!empty($host)) {
        $res = MitraNetApi::ping($host, $count);
        if ($res['status'] === 200) {
            $output = $res['data']['output'] ?? '';
        } else {
            $err = $res['data']['error'] ?? 'Ping failed to execute';
        }
    } else {
        $err = "Host/IP address is required.";
    }
}

if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<form method="post" action="diag_ping.php" class="form-horizontal">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=gettext("Ping Target")?></h2></div>
		<div class="panel-body">
			<div class="form-group">
				<label class="col-sm-2 control-label" for="host"><span class="element-required">*</span><?=gettext("Hostname / IP Address")?></label>
				<div class="col-sm-6">
					<input type="text" class="form-control" id="host" name="host" value="<?=htmlspecialchars($host)?>" placeholder="e.g. 192.168.56.1 or 8.8.8.8" required>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-2 control-label" for="count"><?=gettext("Count")?></label>
				<div class="col-sm-2">
					<select class="form-control" id="count" name="count">
						<option value="1" <?=$count===1?'selected':''?>>1</option>
						<option value="2" <?=$count===2?'selected':''?>>2</option>
						<option value="3" <?=$count===3?'selected':''?>>3</option>
						<option value="4" <?=$count===4?'selected':''?>>4</option>
						<option value="5" <?=$count===5?'selected':''?>>5</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary"><i class="fa-solid fa-play icon-embed-btn"></i><?=gettext("Ping")?></button>
				</div>
			</div>
		</div>
	</div>
</form>

<?php if (!empty($output)): ?>
<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa fa-poll"></i> Ping Output</h2></div>
	<div class="panel-body">
		<pre class="pre-terminal"><?=htmlspecialchars($output)?></pre>
	</div>
</div>
<?php endif; ?>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
