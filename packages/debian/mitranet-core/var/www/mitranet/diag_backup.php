<?php
/*
 * diag_backup.php - MitraNet Configuration Transactions & Backups
 * Adapted from pfSense diag_backup.php
 */

$pgtitle = array(gettext("Diagnostics"), gettext("Backup & Restore"));
$selected_menu = "diagnostics";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'apply') {
        $res = MitraNetApi::request('/config/apply', 'POST', []);
        if ($res['status'] === 200) {
            $msg = "Configuration candidate applied and committed atomically (TX: " . ($res['data']['transaction_id'] ?? '') . ")";
        } else {
            $err = $res['data']['error'] ?? 'Transaction apply failed';
        }
    } elseif ($action === 'rollback') {
        $snap = trim($_POST['snapshot_id'] ?? '');
        if ($snap) {
            $res = MitraNetApi::request('/config/rollback', 'POST', ['snapshot_id' => $snap]);
            if ($res['status'] === 200) {
                $msg = "Configuration successfully rolled back to snapshot '$snap'";
            } else {
                $err = $res['data']['error'] ?? 'Rollback failed';
            }
        }
    }
}

$cfg = MitraNetApi::getConfigStatus();

$tab_array = array();
$tab_array[] = array(gettext("Backup & Restore"), true, "diag_backup.php");
$tab_array[] = array(gettext("Config History"), false, "diag_backup.php#history");
display_top_tabs($tab_array);

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Transaction Engine Status")?></h2></div>
	<div class="panel-body">
		<div class="content">
			<p><strong><?=gettext("Running Config Version:")?></strong> <span class="label label-success">v<?=htmlspecialchars($cfg['running_version'] ?? 1)?></span></p>
			<p><strong><?=gettext("Candidate Config Version:")?></strong> <span class="label label-info">v<?=htmlspecialchars($cfg['candidate_version'] ?? 1)?></span></p>
			<p><strong><?=gettext("Transaction Lock:")?></strong> <?=!empty($cfg['is_locked']) ? '<span class="label label-danger">' . gettext("LOCKED") . '</span>' : '<span class="label label-default">' . gettext("IDLE / UNLOCKED") . '</span>'?></p>
		</div>
		<div class="panel-footer">
			<form method="post" class="form-inline-action">
				<input type="hidden" name="action" value="apply">
				<button type="submit" class="btn btn-success"><i class="fa-solid fa-check icon-embed-btn"></i><?=gettext("Commit Candidate Changes")?></button>
			</form>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Rollback Configuration")?></h2></div>
	<div class="panel-body">
		<form method="post" class="form-horizontal">
			<input type="hidden" name="action" value="rollback">
			<div class="form-group">
				<label class="col-sm-2 control-label"><span class="element-required">*</span><?=gettext("Snapshot ID")?></label>
				<div class="col-sm-6">
					<input type="text" name="snapshot_id" class="form-control" placeholder="snap_20261007_..." required>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-warning"><i class="fa-solid fa-rotate-left icon-embed-btn"></i><?=gettext("Rollback to Snapshot")?></button>
				</div>
			</div>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
