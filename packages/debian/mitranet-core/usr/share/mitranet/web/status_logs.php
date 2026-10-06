<?php
/*
 * status_logs.php - MitraNet System Logs Viewer
 * Adapted from pfSense status_logs.php
 */

$pgtitle = "Status: System Logs";
$selected_menu = "status";
require_once(__DIR__ . '/includes/head.inc');

$cat = $_GET['cat'] ?? 'system';
$logs = MitraNetApi::getLogs($cat);
?>

<h2>System Logs</h2>

<ul class="nav nav-tabs" style="margin-bottom: 20px;">
	<li class="<?=$cat==='system'?'active':''?>"><a href="/status_logs.php?cat=system">System</a></li>
	<li class="<?=$cat==='firewall'?'active':''?>"><a href="/status_logs.php?cat=firewall">Firewall</a></li>
	<li class="<?=$cat==='gateway'?'active':''?>"><a href="/status_logs.php?cat=gateway">Gateways</a></li>
	<li class="<?=$cat==='transaction'?'active':''?>"><a href="/status_logs.php?cat=transaction">Transactions</a></li>
</ul>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-terminal"></i> Log Output (Category: <?=htmlspecialchars(ucfirst($cat))?>)</h3></div>
	<div class="panel-body" style="background-color: #1a1a1a; color: #00ff00; font-family: monospace; max-height: 500px; overflow-y: scroll; padding: 15px;">
		<?php if (empty($logs)): ?>
			<div class="text-muted" style="color: #888;">No recent log events found for category '<?=htmlspecialchars($cat)?>'.</div>
		<?php else: foreach ($logs as $line): ?>
			<div><?=htmlspecialchars($line)?></div>
		<?php endforeach; endif; ?>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
