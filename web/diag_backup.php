<?php
/*
 * diag_backup.php - MitraNet Configuration Transactions & Backups
 * Adapted from pfSense diag_backup.php
 */

$pgtitle = "Diagnostics: Backup, Restore & Transactions";
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
?>

<h2>Configuration & Transactions</h2>

<?php if (!empty($msg)): ?>
	<div class="alert alert-success"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
	<div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-history"></i> Transaction Engine Status</h3></div>
	<div class="panel-body">
		<p><strong>Running Config Version:</strong> <span class="label label-success">v<?=htmlspecialchars($cfg['running_version'] ?? 1)?></span></p>
		<p><strong>Candidate Config Version:</strong> <span class="label label-info">v<?=htmlspecialchars($cfg['candidate_version'] ?? 1)?></span></p>
		<p><strong>Transaction Lock:</strong> <?=!empty($cfg['is_locked']) ? '<span class="label label-danger">LOCKED</span>' : '<span class="label label-default">IDLE / UNLOCKED</span>'?></p>

		<hr>
		<form method="post" style="display:inline;">
			<input type="hidden" name="action" value="apply">
			<button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Commit Candidate Changes</button>
		</form>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-undo"></i> Rollback Configuration</h3></div>
	<div class="panel-body">
		<form method="post" class="form-inline">
			<input type="hidden" name="action" value="rollback">
			<div class="form-group">
				<label>Snapshot ID</label>
				<input type="text" name="snapshot_id" class="form-control" placeholder="snap_20261007_..." required>
			</div>
			<button type="submit" class="btn btn-warning"><i class="fa fa-undo"></i> Rollback to Snapshot</button>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
