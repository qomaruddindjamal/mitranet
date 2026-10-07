<?php
/*
 * services_virtual.php - MitraNet DIRECT: KVM Hypervisor
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$savemsg = "";
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    $id = $_POST['id'] ?? '';
    if ($act === 'setup_hypervisor') {
        $savemsg = "Modul Kernel Hypervisor (KVM/QEMU) aktif dan siap digunakan.";
    } elseif (in_array($act, ['start', 'stop', 'restart'])) {
        $res = MitraNetApi::vmAction($id, $act);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $savemsg = htmlspecialchars($res['data']['message'] ?? "Perintah {$act} berhasil dijalankan.");
        } else {
            $err_msg = htmlspecialchars($res['data']['error'] ?? "Gagal menjalankan {$act} pada VM.");
        }
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
    <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?=$savemsg?></div>
<?php endif; ?>

<?php if (!empty($err_msg)): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?=$err_msg?></div>
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
                        <th style="width: 22%;">Nama & Identitas</th>
                        <th style="width: 20%;">Alokasi Resource</th>
                        <th style="width: 20%;">Media & Network</th>
                        <th style="width: 18%;">Status Hypervisor</th>
                        <th style="width: 20%; text-align: right;">Aksi & Kontrol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vms)): ?>
                        <?php foreach ($vms as $vm): ?>
                            <?php $isRunning = ($vm['status'] === 'RUNNING'); ?>
                            <tr>
                                <td>
                                    <strong><i class="fa-solid fa-cubes"></i> <?=htmlspecialchars($vm['name'])?></strong>
                                    <?php if (!empty($vm['is_default'])): ?>
                                        <span class="label label-primary" style="margin-left: 5px;"><i class="fa-solid fa-lock"></i> Built-in</span>
                                    <?php endif; ?>
                                    <div style="font-size: 11px; color: #777; margin-top: 3px;">
                                        ID: <code><?=htmlspecialchars($vm['id'])?></code> - <?=htmlspecialchars($vm['description'])?>
                                    </div>
                                    <?php if ($isRunning && !empty($vm['pid'])): ?>
                                        <div style="font-size: 11px; color: #28a745; margin-top: 2px;">
                                            <i class="fa-solid fa-microchip"></i> PID: <code><?=htmlspecialchars($vm['pid'])?></code>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: #5bc0de;"><?=htmlspecialchars($vm['vcpu'])?> vCPU</span>
                                    <span class="badge" style="background-color: #337ab7;"><?=htmlspecialchars($vm['ram_mb'])?> MB RAM</span>
                                    <span class="badge" style="background-color: #f0ad4e;"><?=htmlspecialchars($vm['disk_gb'])?> GB HDD</span>
                                    <?php if ($isRunning && !empty($vm['ram_rss_mb'])): ?>
                                        <div style="font-size: 11px; color: #666; margin-top: 4px;">
                                            RSS: <strong><?=htmlspecialchars($vm['ram_rss_mb'])?> MB</strong> (Live QEMU)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><i class="fa-solid fa-ethernet"></i> Net: <code><?=htmlspecialchars($vm['interface'])?></code></div>
                                    <div style="font-size: 11px; margin-top: 2px;">
                                        <span class="label label-info"><i class="fa-solid fa-network-wired"></i> FWD: Port <?=htmlspecialchars($vm['port_fwd'] ?? 8888)?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isRunning): ?>
                                        <span class="label label-success" style="font-size: 12px; padding: 4px 8px;">
                                            <i class="fa-solid fa-play"></i> RUNNING
                                        </span>
                                    <?php else: ?>
                                        <span class="label label-default" style="font-size: 12px; padding: 4px 8px;">
                                            <i class="fa-solid fa-stop"></i> STOPPED
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <?php if ($isRunning): ?>
                                        <form method="post" action="services_virtual.php" style="display: inline-block;">
                                            <input type="hidden" name="act" value="restart" />
                                            <input type="hidden" name="id" value="<?=htmlspecialchars($vm['id'])?>" />
                                            <button type="submit" class="btn btn-xs btn-warning" title="Restart Virtual Machine">
                                                <i class="fa-solid fa-rotate-right"></i> Restart
                                            </button>
                                        </form>
                                        <form method="post" action="services_virtual.php" style="display: inline-block; margin-left: 3px;">
                                            <input type="hidden" name="act" value="stop" />
                                            <input type="hidden" name="id" value="<?=htmlspecialchars($vm['id'])?>" />
                                            <button type="submit" class="btn btn-xs btn-danger" title="Hentikan Virtual Machine">
                                                <i class="fa-solid fa-stop"></i> Stop
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="services_virtual.php" style="display: inline-block;">
                                            <input type="hidden" name="act" value="start" />
                                            <input type="hidden" name="id" value="<?=htmlspecialchars($vm['id'])?>" />
                                            <button type="submit" class="btn btn-xs btn-success" title="Nyalakan Virtual Machine">
                                                <i class="fa-solid fa-play"></i> Start
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="services_virtual.php?act=edit&id=<?=htmlspecialchars($vm['id'])?>" class="btn btn-xs btn-info" style="margin-left: 3px;" title="Edit Resource">
                                        <i class="fa-solid fa-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 20px; color: #888;">
                                <em>Tidak ada Virtual Machine yang terkonfigurasi di <code>/var/lib/mitranet/vms</code>.</em>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
