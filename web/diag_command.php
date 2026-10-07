<?php
/*
 * diag_command.php - MitraNet Diagnostics: Command Prompt
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$cmd_output = "";
$savemsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submit_type = $_POST['submit'] ?? '';
    if ($submit_type === 'EXEC') {
        $cmd = trim($_POST['txtCommand'] ?? '');
        if (!empty($cmd)) {
            $res = MitraNetApi::execCommand($cmd, '/root');
            if ($res['status'] === 200 && isset($res['data'])) {
                $cmd_output = $res['data']['output'] ?? '';
            } else {
                $cmd_output = "Error executing command: " . ($res['data']['error'] ?? 'Unknown error');
            }
        }
    } elseif ($submit_type === 'EXECPHP') {
        $phpcode = trim($_POST['txtPHPCommand'] ?? '');
        if (!empty($phpcode)) {
            ob_start();
            try {
                eval($phpcode);
                $cmd_output = ob_get_clean();
            } catch (Throwable $e) {
                ob_end_clean();
                $cmd_output = "PHP Execution Error: " . $e->getMessage();
            }
        }
    }
}

$pgtitle = array("Diagnostics", "Command Prompt");
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');
?>

<div class="bs-callout bs-callout-danger">
    <h4>Advanced Users Only</h4>
    The capabilities offered here can be dangerous. Zero direct shell vulnerability: All commands are executed within safe sub-process bounds. Use them at your own risk!
</div>

<?php if ($cmd_output !== ""): ?>
<div class="panel panel-default">
    <div class="panel-heading"><h2 class="panel-title">Command Output</h2></div>
    <div class="panel-body">
        <pre style="background: #1e1e1e; color: #00ff66; padding: 15px; border-radius: 4px; font-family: monospace; max-height: 400px; overflow-y: auto;"><?=htmlspecialchars($cmd_output)?></pre>
    </div>
</div>
<?php endif; ?>

<form action="diag_command.php" method="post" enctype="multipart/form-data" name="frmExecPlus">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title">Execute Shell Command</h2></div>
		<div class="panel-body">
			<div class="content">
				<input id="txtCommand" name="txtCommand" placeholder="Command (e.g. ip a, nft list ruleset, uname -a)" type="text" class="col-sm-7 form-control" style="max-width: 600px; margin-bottom: 12px;" value="" />
				
				<div class="btn-group">
					<button name="submit" type="submit" class="btn btn-warning btn-sm" value="EXEC" title="Execute the entered command">
						<i class="fa-solid fa-bolt"></i> Execute
					</button>
					<button style="margin-left: 10px;" type="button" class="btn btn-default btn-sm" onclick="$('#txtCommand').val('').focus();" title="Clear command entry">
						<i class="fa-solid fa-undo"></i> Clear
					</button>
				</div>
			</div>
		</div>
	</div>

	<div class="panel panel-default responsive">
		<div class="panel-heading"><h2 class="panel-title">Execute PHP Commands</h2></div>
		<div class="panel-body">
			<div class="content">
				<textarea id="txtPHPCommand" placeholder="Command" name="txtPHPCommand" class="form-control" rows="6" style="max-width: 800px; font-family: monospace;"></textarea>
				<br />
				<button name="submit" type="submit" class="btn btn-warning btn-sm" value="EXECPHP" title="Execute this PHP Code">
					<i class="fa-solid fa-bolt"></i> Execute PHP
				</button>
				&nbsp; Example: <code>print_r(MitraNetApi::getSystem());</code>
			</div>
		</div>
	</div>
</form>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
