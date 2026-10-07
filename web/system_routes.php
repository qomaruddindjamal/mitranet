<?php
/*
 * system_routes.php - MitraNet Routing Configuration
 * Adapted from pfSense system_routes.php
 */

$pgtitle = array(gettext("System"), gettext("Routing"), gettext("Static Routes"));
$selected_menu = "system";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $dst = trim($_POST['destination'] ?? '');
        $gw = trim($_POST['gateway'] ?? '');
        $iface = trim($_POST['interface'] ?? '');

        if (!empty($dst)) {
            $res = MitraNetApi::request('/routes/add', 'POST', [
                'destination' => $dst,
                'gateway' => $gw ?: null,
                'interface' => $iface ?: null,
            ]);
            if ($res['status'] === 200) {
                $msg = "Route to '$dst' added successfully";
            } else {
                $err = $res['data']['error'] ?? 'Failed to add route';
            }
        }
    } elseif ($action === 'delete') {
        $dst = trim($_POST['destination'] ?? '');
        if (!empty($dst)) {
            $res = MitraNetApi::request('/routes/remove', 'POST', ['destination' => $dst]);
            if ($res['status'] === 200) {
                $msg = "Route to '$dst' removed successfully";
            } else {
                $err = $res['data']['error'] ?? 'Failed to remove route';
            }
        }
    }
}

$routes_data = MitraNetApi::getRoutes();
$ipv4_routes = $routes_data['ipv4'] ?? [];
$ipv6_routes = $routes_data['ipv6'] ?? [];
$ifaces = MitraNetApi::getInterfaces();
$tab_array = array();
$tab_array[] = array(gettext("Gateways"), false, "system_gateways.php");
$tab_array[] = array(gettext("Static Routes"), true, "system_routes.php");
display_top_tabs($tab_array);

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">Active IPv4 Routes</h3></div>
	<div class="panel-body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>Network / Destination</th>
					<th>Gateway</th>
					<th>Interface</th>
					<th>Metric</th>
					<th>Protocol</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($ipv4_routes)): ?>
					<tr><td colspan="5" class="text-center text-muted">No IPv4 routes found</td></tr>
				<?php else: foreach ($ipv4_routes as $r): ?>
					<tr>
						<td><strong><?=htmlspecialchars($r['destination'])?></strong></td>
						<td><?=htmlspecialchars($r['gateway'] ?? 'Direct / On-link')?></td>
						<td><code><?=htmlspecialchars($r['interface'] ?? '--')?></code></td>
						<td><?=htmlspecialchars($r['metric'] ?? 0)?></td>
						<td><span class="label label-default"><?=htmlspecialchars($r['protocol'] ?? 'kernel')?></span></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">Add Static Route</h3></div>
	<div class="panel-body">
		<form method="post" class="form-inline">
			<input type="hidden" name="action" value="add">
			<div class="form-group">
				<label>Destination (CIDR)</label>
				<input type="text" name="destination" class="form-control" placeholder="192.168.100.0/24" required>
			</div>
			<div class="form-group">
				<label>Gateway IP</label>
				<input type="text" name="gateway" class="form-control" placeholder="10.0.2.2">
			</div>
			<div class="form-group">
				<label>Interface</label>
				<select name="interface" class="form-control">
					<option value="">Auto</option>
					<?php foreach ($ifaces as $i): ?>
						<option value="<?=htmlspecialchars($i['name'])?>"><?=htmlspecialchars($i['name'])?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Route</button>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
