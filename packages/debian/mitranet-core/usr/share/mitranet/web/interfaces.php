<?php
/*
 * interfaces.php - MitraNet Interface Details
 * Adapted from pfSense interfaces.php
 */

$pgtitle = "Interfaces: General Details";
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ifname = $_POST['interface'] ?? '';

    if ($action === 'set_state' && !empty($ifname)) {
        $state = $_POST['state'] ?? 'up';
        $res = MitraNetApi::request('/interfaces/set-state', 'POST', [
            'name' => $ifname,
            'state' => $state
        ]);
        if ($res['status'] === 200) {
            $msg = "Interface '$ifname' state set to " . strtoupper($state);
        } else {
            $err = $res['data']['error'] ?? 'Failed to update interface state';
        }
    } elseif ($action === 'set_mtu' && !empty($ifname)) {
        $mtu = (int)($_POST['mtu'] ?? 1500);
        $res = MitraNetApi::request('/interfaces/set-mtu', 'POST', [
            'name' => $ifname,
            'mtu' => $mtu
        ]);
        if ($res['status'] === 200) {
            $msg = "Interface '$ifname' MTU set to $mtu";
        } else {
            $err = $res['data']['error'] ?? 'Failed to update MTU';
        }
    }
}

$ifaces = MitraNetApi::getInterfaces();
?>

<h2>Interfaces</h2>

<?php if (!empty($msg)): ?>
	<div class="alert alert-success"><?=htmlspecialchars($msg)?></div>
<?php endif; ?>
<?php if (!empty($err)): ?>
	<div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">Detected Network Interfaces</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Interface</th>
					<th>Link State</th>
					<th>Type</th>
					<th>MAC Address</th>
					<th>MTU</th>
					<th>IP Addresses</th>
					<th>RX (Bytes/Pkts)</th>
					<th>TX (Bytes/Pkts)</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($ifaces)): ?>
					<tr><td colspan="9" class="text-center text-muted">No interfaces found</td></tr>
				<?php else: foreach ($ifaces as $i): ?>
					<tr>
						<td><strong><?=htmlspecialchars($i['name'])?></strong></td>
						<td>
							<?php if ($i['is_up']): ?>
								<span class="label label-success">UP</span>
							<?php else: ?>
								<span class="label label-danger">DOWN</span>
							<?php endif; ?>
						</td>
						<td><span class="label label-default"><?=htmlspecialchars($i['type'] ?? 'ethernet')?></span></td>
						<td><code><?=htmlspecialchars($i['mac_address'] ?? '--')?></code></td>
						<td><?=htmlspecialchars($i['mtu'] ?? 1500)?></td>
						<td>
							<?php
							$ips = array_merge($i['ipv4_addresses'] ?? [], $i['ipv6_addresses'] ?? []);
							echo empty($ips) ? '<span class="text-muted">None</span>' : htmlspecialchars(implode(', ', $ips));
							?>
						</td>
						<td>
							<?=number_format($i['traffic']['rx_bytes'] ?? 0)?> B<br>
							<small class="text-muted"><?=number_format($i['traffic']['rx_packets'] ?? 0)?> pkts</small>
						</td>
						<td>
							<?=number_format($i['traffic']['tx_bytes'] ?? 0)?> B<br>
							<small class="text-muted"><?=number_format($i['traffic']['tx_packets'] ?? 0)?> pkts</small>
						</td>
						<td>
							<form method="post" style="display:inline;">
								<input type="hidden" name="action" value="set_state">
								<input type="hidden" name="interface" value="<?=htmlspecialchars($i['name'])?>">
								<?php if ($i['is_up']): ?>
									<input type="hidden" name="state" value="down">
									<button type="submit" class="btn btn-xs btn-danger" <?=($i['name']==='lo' || $i['name']==='enp0s3')?'disabled title="Protected management interface"':''?>>Down</button>
								<?php else: ?>
									<input type="hidden" name="state" value="up">
									<button type="submit" class="btn btn-xs btn-success">Up</button>
								<?php endif; ?>
							</form>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
