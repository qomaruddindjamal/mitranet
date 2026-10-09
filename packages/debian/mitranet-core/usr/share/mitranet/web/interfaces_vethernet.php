<?php
/*
 * interfaces_vethernet.php - MitraNet Virtual Ethernet (vEthernet / Host-Guest Subnets)
 * Dedicated virtual interfaces for Single NIC VPS / KVM guest routing & port forwarding
 * Licensed under the Apache License, Version 2.0.
 */

$pgtitle = array(gettext("Interfaces"), gettext("vEthernet"));
$selected_menu = "interfaces";
require_once(__DIR__ . '/includes/head.inc');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $ip_cidr = trim($_POST['ip_cidr'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!empty($name) && !empty($ip_cidr)) {
            $res = MitraNetApi::createVethernet($name, $ip_cidr, $desc);
            if ($res['status'] === 200 && ($res['data']['success'] ?? false)) {
                $msg = $res['data']['message'] ?? "vEthernet '$name' berhasil dibuat.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal membuat vEthernet interface.';
            }
        } else {
            $err = "Nama interface dan IP/CIDR wajib diisi.";
        }
    } elseif ($action === 'delete') {
        $name = trim($_POST['name'] ?? '');
        if (!empty($name)) {
            $res = MitraNetApi::deleteVethernet($name);
            if ($res['status'] === 200 && ($res['data']['success'] ?? false)) {
                $msg = $res['data']['message'] ?? "vEthernet '$name' berhasil dihapus.";
            } else {
                $err = $res['data']['error'] ?? 'Gagal menghapus vEthernet interface.';
            }
        }
    }
}

$vethernets = MitraNetApi::getVethernets();

$tab_array = array();
$tab_array[] = array(gettext("Interface Assignments"), false, "interfaces_assign.php");
$tab_array[] = array(gettext("VLANs"), false, "interfaces_vlan.php");
$tab_array[] = array(gettext("Bridges"), false, "interfaces_bridge.php");
$tab_array[] = array(gettext("LAGGs"), false, "interfaces_lagg.php");
$tab_array[] = array(gettext("VRFs"), false, "interfaces_vrf.php");
$tab_array[] = array(gettext("vEthernet (KVM)"), true, "interfaces_vethernet.php");
display_top_tabs($tab_array);

if (!empty($msg)) {
    print_info_box($msg, "success");
}
if (!empty($err)) {
    print_info_box($err, "danger");
}
?>

<div class="panel panel-default panel-mitranet">
	<div class="panel-heading">
		<h2 class="panel-title"><?=gettext("Virtual Ethernet (vEthernet) Subnets")?></h2>
	</div>
	<div class="panel-body">
		<p class="text-muted">
			vEthernet menyediakan interface virtual internal (isolated bridge) pada sistem MitraNet. Solusi ini sangat ideal untuk skenario VPS yang hanya memiliki 1 port Ethernet fisik, di mana mesin virtual (KVM / aaPanel) dapat terhubung langsung ke subnet internal ini (misal <code>192.168.101.0/24</code>), dan layanan di dalamnya dapat diakses dari luar melalui NAT / Port Forwarding dari port WAN.
		</p>
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr>
						<th><?=gettext("Interface")?></th>
						<th><?=gettext("IPv4 / CIDR")?></th>
						<th><?=gettext("Status")?></th>
						<th><?=gettext("Attached Members (TAP/VM)")?></th>
						<th><?=gettext("Deskripsi")?></th>
						<th><?=gettext("Actions")?></th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($vethernets)): ?>
						<tr><td colspan="6" class="text-center text-muted"><?=gettext("Belum ada interface vEthernet yang dibuat. Tambahkan vEthernet baru di bawah.")?></td></tr>
					<?php else: foreach ($vethernets as $v): ?>
						<tr>
							<td><strong><?=htmlspecialchars($v['name'])?></strong></td>
							<td><span class="label label-info"><?=htmlspecialchars($v['ip_cidr'] ?: 'Belum diatur')?></span></td>
							<td>
								<?php if (($v['status'] ?? '') === 'UP'): ?>
									<span class="label label-success"><i class="fa-solid fa-arrow-up"></i> UP</span>
								<?php else: ?>
									<span class="label label-danger"><i class="fa-solid fa-arrow-down"></i> DOWN</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if (!empty($v['members'])): ?>
									<?php foreach ($v['members'] as $m): ?>
										<span class="label label-default mr-1"><i class="fa-solid fa-network-wired"></i> <?=htmlspecialchars($m)?></span>
									<?php endforeach; ?>
								<?php else: ?>
									<span class="text-muted">Tidak ada member aktif</span>
								<?php endif; ?>
							</td>
							<td><?=htmlspecialchars($v['description'] ?? '-')?></td>
							<td>
								<form method="post" class="form-inline-action" onsubmit="return confirm('Hapus interface vEthernet ini?');">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="name" value="<?=htmlspecialchars($v['name'])?>">
									<button type="submit" class="btn btn-xs btn-danger" title="<?=gettext('Delete vEthernet')?>"><i class="fa-solid fa-trash-can"></i> Hapus</button>
								</form>
							</td>
						</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext("Tambah vEthernet Interface Baru")?></h2></div>
	<div class="panel-body">
		<form method="post" class="form-horizontal">
			<input type="hidden" name="action" value="create">
			<div class="form-group">
				<label class="col-sm-3 control-label"><span class="element-required">*</span><?=gettext("Nama Interface")?></label>
				<div class="col-sm-9">
					<input type="text" name="name" class="form-control" placeholder="veth0" value="veth0" required>
					<span class="help-block"><?=gettext("Nama virtual device pada kernel Linux (contoh: veth0, veth1, vnet0).")?></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-3 control-label"><span class="element-required">*</span><?=gettext("IPv4 Gateway / CIDR")?></label>
				<div class="col-sm-9">
					<input type="text" name="ip_cidr" class="form-control" placeholder="192.168.101.254/24" value="192.168.101.254/24" required>
					<span class="help-block"><?=gettext("Alamat IP host yang menjadi gateway untuk VM guest (contoh: 192.168.101.254/24).")?></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-sm-3 control-label"><?=gettext("Deskripsi")?></label>
				<div class="col-sm-9">
					<input type="text" name="description" class="form-control" placeholder="Subnet Virtual KVM / aaPanel">
					<span class="help-block"><?=gettext("Keterangan peruntukan vEthernet ini.")?></span>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-3 col-sm-9">
					<button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus icon-embed-btn"></i><?=gettext("Buat vEthernet")?></button>
				</div>
			</div>
		</form>
	</div>
</div>

<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
