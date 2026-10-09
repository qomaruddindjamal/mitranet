<?php
/*
 * vEther_tunel.php - MitraNet Virtual Ethernet Tunnel Management
 * Configures virtual ethernet tunnels and host-guest tap/veth tunnels.
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Interfaces"), gettext("vEther Tunnel"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/../includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_tunnel') {
        $name = trim($_POST['name'] ?? '');
        $peer = trim($_POST['peer'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!empty($name)) {
            // Create veth pair for tunnel
            $peer_name = !empty($peer) ? $peer : $name . "-peer";
            $cmd = "ip link add " . escapeshellarg($name) . " type veth peer name " . escapeshellarg($peer_name);
            exec($cmd, $out, $ret);
            if ($ret === 0) {
                exec("ip link set " . escapeshellarg($name) . " up");
                exec("ip link set " . escapeshellarg($peer_name) . " up");
                $msg = "vEther Tunnel '{$name}' <-> '{$peer_name}' berhasil dibuat.";
            } else {
                $err = "Gagal membuat vEther tunnel.";
            }
        } else {
            $err = "Nama tunnel wajib diisi.";
        }
    } elseif ($action === 'delete_tunnel') {
        $name = trim($_POST['name'] ?? '');
        if (!empty($name)) {
            exec("ip link delete " . escapeshellarg($name), $out, $ret);
            if ($ret === 0) {
                $msg = "vEther Tunnel '{$name}' berhasil dihapus.";
            } else {
                $err = "Gagal menghapus vEther tunnel.";
            }
        }
    }
}

// Fetch all interfaces to list active veth tunnels
$all_ifaces = MitraNetApi::getInterfaces();
$tunnels = [];
foreach ($all_ifaces as $if) {
    if (strpos($if['name'], 'veth') === 0 || strpos($if['name'], 'vtun') === 0) {
        $tunnels[] = $if;
    }
}

$tab_array = array();
$tab_array[] = array(gettext("Interface"), false, "interfaces.php");
$tab_array[] = array(gettext("VLANs"), false, "vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "lagg.php");
$tab_array[] = array(gettext("VRFs"), false, "vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"), false, "vethernet.php");
$tab_array[] = array(gettext("vEther Tunnel"), true, "vEther_tunel.php");
display_top_tabs($tab_array);

if (!empty($msg)) print_info_box($msg, "success");
if (!empty($err)) print_info_box($err, "danger");
?>

<div class="panel panel-default panel-mitranet">
	<div class="panel-heading">
		<h2 class="panel-title"><?=gettext("Configured vEther Tunnels")?></h2>
	</div>
	<div class="panel-body">
		<table class="table table-striped table-hover table-condensed">
			<thead>
				<tr>
					<th><?=gettext("Interface")?></th>
					<th><?=gettext("Status")?></th>
					<th><?=gettext("MAC Address")?></th>
					<th><?=gettext("MTU")?></th>
					<th><?=gettext("IPv4 Address")?></th>
					<th class="text-right"><?=gettext("Actions")?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($tunnels)): ?>
					<tr>
						<td colspan="6" class="text-center text-muted">
							<?=gettext("No vEther tunnels configured.")?>
						</td>
					</tr>
				<?php else: ?>
					<?php foreach ($tunnels as $t): ?>
					<tr>
						<td><strong><?=htmlspecialchars($t['name'])?></strong></td>
						<td>
							<?php if (!empty($t['is_up'])): ?>
								<span class="label label-success">UP</span>
							<?php else: ?>
								<span class="label label-danger">DOWN</span>
							<?php endif; ?>
						</td>
						<td><code><?=htmlspecialchars($t['mac_address'] ?? 'N/A')?></code></td>
						<td><?=htmlspecialchars($t['mtu'] ?? 1500)?></td>
						<td><?=htmlspecialchars(implode(', ', $t['ipv4_addresses'] ?? [])) ?: '-'?></td>
						<td class="text-right">
							<form method="post" action="vEther_tunel.php" style="display:inline;" onsubmit="return confirm('Hapus tunnel <?=htmlspecialchars($t['name'])?>?');">
								<input type="hidden" name="action" value="delete_tunnel">
								<input type="hidden" name="name" value="<?=htmlspecialchars($t['name'])?>">
								<button type="submit" class="btn btn-xs btn-danger" title="<?=gettext('Delete')?>">
									<i class="fa-solid fa-trash-can"></i>
								</button>
							</form>
						</td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<nav class="action-buttons">
			<button type="button" class="btn btn-sm btn-success" data-toggle="collapse" data-target="#addTunnelForm">
				<i class="fa-solid fa-plus icon-embed"></i> <?=gettext("Add New Tunnel")?>
			</button>
		</nav>

		<div id="addTunnelForm" class="collapse" style="margin-top: 15px;">
			<div class="well">
				<h4><?=gettext("Create vEther Tunnel (veth pair)")?></h4>
				<form method="post" action="vEther_tunel.php" class="form-horizontal">
					<input type="hidden" name="action" value="create_tunnel">

					<div class="form-group">
						<label class="col-sm-3 control-label"><?=gettext("Tunnel Name")?></label>
						<div class="col-sm-6">
							<input type="text" name="name" class="form-control" placeholder="e.g. vtun0" required>
						</div>
					</div>

					<div class="form-group">
						<label class="col-sm-3 control-label"><?=gettext("Peer Name (Optional)")?></label>
						<div class="col-sm-6">
							<input type="text" name="peer" class="form-control" placeholder="e.g. vtun0-peer">
						</div>
					</div>

					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-6">
							<button type="submit" class="btn btn-primary"><?=gettext("Save & Apply")?></button>
							<button type="button" class="btn btn-default" data-toggle="collapse" data-target="#addTunnelForm"><?=gettext("Cancel")?></button>
						</div>
					</div>
				</form>
			</div>
		</div>

	</div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
