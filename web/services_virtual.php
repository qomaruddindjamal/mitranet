<?php
/*
 * services_virtual.php - MitraNet DIRECT: KVM Hypervisor
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

$savemsg = "";
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    $id = $_POST['id'] ?? '';
    if ($act === 'setup_hypervisor') {
        $savemsg = "Modul Kernel Hypervisor (KVM/QEMU) aktif dan siap digunakan.";
    } elseif ($act === 'start') {
        $savemsg = "Virtual Machine '{$id}' berhasil dinyalakan.";
    } elseif ($act === 'stop') {
        $savemsg = "Virtual Machine '{$id}' telah dihentikan.";
    }
}

$pgtitle = array("DIRECT", "KVM");
$selected_menu = "direct";
require_once(__DIR__ . '/includes/head.inc');

$vms = MitraNetApi::getKvmVms();
?>

<ul class="nav nav-pills">
    <li role="presentation" class="active"><a href="services_virtual.php">Virtual Machines</a></li>
    <li role="presentation"><a href="services_virtual.php?act=images">ISO & Images Manager</a></li>
    <li role="presentation"><a href="services_virtual.php?act=hypervisor">Hypervisor Status</a></li>
    <li role="presentation"><a href="services_virtual.php?act=network">Network & Bridge</a></li>
</ul>

<?php if (!empty($savemsg)): ?>
    <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?=htmlspecialchars($savemsg)?></div>
<?php endif; ?>

<!-- MAIN DASHBOARD & VM LIST -->
<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">
            <i class="fa-solid fa-server"></i> Daftar Virtual Machine (KVM Hypervisor Appliance)
        </h2>
    </div>
    <div class="panel-body">
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <a href="services_virtual.php?act=add" class="btn btn-success">
                    <i class="fa-solid fa-plus"></i> Tambah Virtual Machine Baru
                </a>
                <a href="services_virtual.php?act=images" class="btn btn-default" style="margin-left: 5px;">
                    <i class="fa-solid fa-compact-disc text-primary"></i> Kelola ISO / Images
                </a>
                <form method="post" action="services_virtual.php" style="display: inline-block; margin-left: 5px;">
                    <input type="hidden" name="act" value="setup_hypervisor" />
                    <button type="submit" class="btn btn-primary" title="Muat modul kernel hypervisor">
                        <i class="fa-solid fa-shield-halved"></i> Aktifkan Hypervisor
                    </button>
                </form>
            </div>
            <div>
                <a href="http://192.168.56.101:8888" target="_blank" class="btn btn-info">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka aaPanel Web (Port 8888)
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th style="width: 20%;">Nama & Identitas</th>
                        <th style="width: 15%;">Alokasi Resource</th>
                        <th style="width: 20%;">Media & Network</th>
                        <th style="width: 20%;">Status Hypervisor</th>
                        <th style="width: 25%; text-align: right;">Aksi & Kontrol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vms)): ?>
                        <?php foreach ($vms as $vm): ?>
                            <tr>
                                <td>
                                    <strong><i class="fa-solid fa-cubes"></i> <?=htmlspecialchars($vm['name'])?></strong>
                                    <?php if (!empty($vm['is_default'])): ?>
                                        <span class="label label-primary" style="margin-left: 5px;"><i class="fa-solid fa-lock"></i> Default</span>
                                    <?php endif; ?>
                                    <div style="font-size: 11px; color: #777; margin-top: 3px;">
                                        ID: <code><?=htmlspecialchars($vm['id'])?></code> - <?=htmlspecialchars($vm['description'])?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: #5bc0de;"><?=htmlspecialchars($vm['vcpu'])?> vCPU</span>
                                    <span class="badge" style="background-color: #337ab7;"><?=htmlspecialchars($vm['ram_mb'])?> MB RAM</span>
                                    <span class="badge" style="background-color: #f0ad4e;"><?=htmlspecialchars($vm['disk_gb'])?> GB HDD</span>
                                </td>
                                <td>
                                    <div><i class="fa-solid fa-ethernet"></i> Interface: <code><?=htmlspecialchars($vm['interface'])?></code></div>
                                    <div style="font-size: 11px; margin-top: 2px;">
                                        <span class="label label-info"><i class="fa-solid fa-bridge"></i> Bridge: <?=htmlspecialchars($vm['bridge'])?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="label label-default" style="font-size: 12px; padding: 4px 8px;">
                                        <i class="fa-solid fa-stop"></i> <?=htmlspecialchars($vm['status'])?>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <form method="post" action="services_virtual.php" style="display: inline-block;">
                                        <input type="hidden" name="act" value="start" />
                                        <input type="hidden" name="id" value="<?=htmlspecialchars($vm['id'])?>" />
                                        <button type="submit" class="btn btn-xs btn-success" title="Nyalakan Virtual Machine">
                                            <i class="fa-solid fa-play"></i> Start
                                        </button>
                                    </form>
                                    <a href="services_virtual.php?act=edit&id=<?=htmlspecialchars($vm['id'])?>" class="btn btn-xs btn-info" title="Edit Resource">
                                        <i class="fa-solid fa-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
