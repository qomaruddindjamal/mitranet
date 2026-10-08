<?php
/*
 * services_kvm.php - MitraNet Services: KVM Subsystem Master Switch
 * Dedicated control page with pfSense-style Enable / Disable toggle
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$savemsg = "";
$err_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'toggle_kvm') {
        $target_state = !empty($_POST['target_state']) && $_POST['target_state'] === 'enable';
        $res = MitraNetApi::toggleKvmService($target_state);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $savemsg = htmlspecialchars($res['data']['message'] ?? "Status layanan KVM berhasil diperbarui.");
        } else {
            $err_msg = htmlspecialchars($res['data']['error'] ?? "Gagal mengubah status layanan KVM.");
        }
    }
}

// Fetch current KVM service status
$is_enabled = MitraNetApi::isKvmEnabled();

$pgtitle = array("Services", "KVM Services");
$selected_menu = "services";
require_once(__DIR__ . '/includes/head.inc');

if (!empty($savemsg)) {
    print_info_box($savemsg, "success");
}
if (!empty($err_msg)) {
    print_info_box($err_msg, "danger");
}
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h2 class="panel-title">
            <i class="fa-solid fa-server" style="margin-right: 6px;"></i>
            KVM Services Configuration
        </h2>
    </div>
    <div class="panel-body">
        <form action="services_kvm.php" method="post" class="form-horizontal">
            <input type="hidden" name="act" value="toggle_kvm">
            <input type="hidden" name="target_state" value="<?=$is_enabled ? 'disable' : 'enable'?>">

            <div class="form-group">
                <label class="col-sm-3 control-label">
                    <strong>Status Layanan KVM</strong>
                </label>
                <div class="col-sm-9" style="padding-top: 7px;">
                    <?php if ($is_enabled): ?>
                        <span class="label label-success" style="font-size: 13px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-circle-check"></i> AKTIF (Enabled)
                        </span>
                    <?php else: ?>
                        <span class="label label-default" style="font-size: 13px; padding: 6px 12px; background-color: #777; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-circle-xmark"></i> NONAKTIF (Disabled)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">
                    <strong>Informasi Layanan</strong>
                </label>
                <div class="col-sm-9" style="padding-top: 7px;">
                    <p class="text-muted" style="margin-bottom: 8px;">
                        Secara default, <strong>Layanan KVM dinonaktifkan (Disabled)</strong> untuk menghemat alokasi RAM dan penggunaan CPU pada mesin atau router dengan spesifikasi rendah atau sedang.
                    </p>
                    <ul class="text-muted" style="padding-left: 18px; margin-bottom: 0;">
                        <li><strong>Saat Diaktifkan:</strong> Sub-sistem virtualisasi berjalan dan menu <strong>KVM</strong> akan tampil di sidebar untuk mengelola VM, aaPanel, dan file ISO.</li>
                        <li><strong>Saat Dinonaktifkan:</strong> Menu KVM disembunyikan dari sidebar navigasi dan seluruh VM dihentikan secara otomatis.</li>
                    </ul>
                </div>
            </div>

            <div class="form-group" style="margin-top: 25px; margin-bottom: 10px;">
                <div class="col-sm-offset-3 col-sm-9">
                    <?php if ($is_enabled): ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-power-off"></i> Nonaktifkan Layanan KVM
                        </button>
                        <a href="services_virtual.php" class="btn btn-primary" style="margin-left: 10px;">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Virtual Machines Manager
                        </a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-play"></i> Aktifkan Layanan KVM
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
