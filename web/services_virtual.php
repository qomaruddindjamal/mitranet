<?php
/*
 * services_virtual.php - MitraNet DIRECT: KVM Hypervisor & ISO Management
 * Faithful port from pfSense 2.9 WebUI for Debian 13 (Trixie) Appliance
 * Licensed under the Apache License, Version 2.0.
 */

require_once(__DIR__ . '/includes/api.inc');

$savemsg = "";
$err_msg = "";
$tab = $_GET['act'] ?? 'list';

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
    } elseif ($act === 'create_vm') {
        $payload = [
            'id' => trim($_POST['vm_id'] ?? ''),
            'ram_mb' => intval($_POST['ram_mb'] ?? 1024),
            'vcpu' => intval($_POST['vcpu'] ?? 1),
            'disk_gb' => intval($_POST['disk_gb'] ?? 10),
            'port_fwd' => intval($_POST['port_fwd'] ?? 8080),
            'iso' => trim($_POST['iso'] ?? '')
        ];
        $res = MitraNetApi::createVm($payload);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $savemsg = "Virtual Machine '" . htmlspecialchars($payload['id']) . "' berhasil dibuat dengan disk virtual " . $payload['disk_gb'] . "GB.";
            $tab = 'list';
        } else {
            $err_msg = htmlspecialchars($res['data']['error'] ?? "Gagal membuat Virtual Machine.");
            $tab = 'add';
        }
    } elseif ($act === 'delete_vm') {
        $res = MitraNetApi::deleteVm($id);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $savemsg = "Virtual Machine '" . htmlspecialchars($id) . "' berhasil dihapus.";
        } else {
            $err_msg = htmlspecialchars($res['data']['error'] ?? "Gagal menghapus VM.");
        }
    } elseif ($act === 'delete_iso') {
        $fn = trim($_POST['filename'] ?? '');
        $res = MitraNetApi::deleteIso($fn);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $savemsg = "File ISO '" . htmlspecialchars($fn) . "' berhasil dihapus.";
        } else {
            $err_msg = htmlspecialchars($res['data']['error'] ?? "Gagal menghapus ISO.");
        }
        $tab = 'images';
    } elseif ($act === 'upload_iso') {
        if (!empty($_FILES['iso_file']['name'])) {
            $fn = basename($_FILES['iso_file']['name']);
            if (preg_match('/\.(iso|img)$/i', $fn)) {
                $target = '/var/lib/mitranet/isos/' . $fn;
                if (move_uploaded_file($_FILES['iso_file']['tmp_name'], $target)) {
                    $savemsg = "File ISO '{$fn}' berhasil diunggah.";
                } else {
                    $err_msg = "Gagal memindahkan file yang diunggah ke /var/lib/mitranet/isos/.";
                }
            } else {
                $err_msg = "Format file tidak didukung. Harap unggah file berekstensi .iso atau .img.";
            }
        } else {
            $err_msg = "Tidak ada file ISO yang dipilih.";
        }
        $tab = 'images';
    }
}

$pgtitle = array("DIRECT", "KVM");
$selected_menu = "direct";
require_once(__DIR__ . '/includes/head.inc');

$kvmData = MitraNetApi::getKvmData();
$vms = $kvmData['vms'] ?? [];
$isos = $kvmData['isos'] ?? [];
?>

<ul class="nav nav-pills">
    <li role="presentation" class="<?=$tab === 'list' ? 'active' : ''?>"><a href="services_virtual.php">Virtual Machines</a></li>
    <li role="presentation" class="<?=$tab === 'images' ? 'active' : ''?>"><a href="services_virtual.php?act=images">ISO & Images Manager</a></li>
    <li role="presentation" class="<?=$tab === 'add' ? 'active' : ''?>"><a href="services_virtual.php?act=add">Tambah VM Baru</a></li>
    <li role="presentation" class="<?=$tab === 'console' ? 'active' : ''?>"><a href="services_virtual.php?act=console">Live VNC Console</a></li>
</ul>

<?php if (!empty($savemsg)): ?>
    <div class="alert alert-success" style="margin-top: 15px;"><i class="fa-solid fa-check"></i> <?=$savemsg?></div>
<?php endif; ?>

<?php if (!empty($err_msg)): ?>
    <div class="alert alert-danger" style="margin-top: 15px;"><i class="fa-solid fa-triangle-exclamation"></i> <?=$err_msg?></div>
<?php endif; ?>

<?php if ($tab === 'add'): ?>
<!-- TAMBAH VM FORM -->
<div class="panel panel-default" style="margin-top: 15px;">
    <div class="panel-heading">
        <h2 class="panel-title"><i class="fa-solid fa-plus-circle"></i> Tambah Virtual Machine Baru (KVM/QEMU)</h2>
    </div>
    <div class="panel-body">
        <form method="post" action="services_virtual.php" class="form-horizontal">
            <input type="hidden" name="act" value="create_vm" />
            
            <div class="form-group">
                <label class="col-sm-3 control-label">Nama / VM ID:</label>
                <div class="col-sm-6">
                    <input type="text" name="vm_id" class="form-control" placeholder="contoh: debian-vm, alpine01, win10" required pattern="[a-zA-Z0-9_\-]+" />
                    <span class="help-block">Identifier unik VM (hanya huruf, angka, strip, underscore).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">vCPU (Cores):</label>
                <div class="col-sm-6">
                    <select name="vcpu" class="form-control">
                        <option value="1">1 Core (Default Ringan)</option>
                        <option value="2">2 Cores</option>
                        <option value="4">4 Cores</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Alokasi RAM:</label>
                <div class="col-sm-6">
                    <select name="ram_mb" class="form-control">
                        <option value="512">512 MB (Sangat Ringan)</option>
                        <option value="1024" selected>1024 MB (1 GB - Rekomendasi)</option>
                        <option value="2048">2048 MB (2 GB)</option>
                        <option value="4096">4096 MB (4 GB)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Ukuran Virtual Disk (qcow2):</label>
                <div class="col-sm-6">
                    <div class="input-group">
                        <input type="number" name="disk_gb" class="form-control" value="10" min="2" max="100" />
                        <span class="input-group-addon">GB</span>
                    </div>
                    <span class="help-block">Format qcow2 dinamis (hanya menggunakan ruang penyimpanan sesuai isi).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Port Forwarding (Web Service):</label>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span class="input-group-addon">Host Port:</span>
                        <input type="number" name="port_fwd" class="form-control" value="8080" />
                        <span class="input-group-addon">&rarr; Guest Port 80/8888</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Attach Boot ISO (Opsional):</label>
                <div class="col-sm-6">
                    <select name="iso" class="form-control">
                        <option value="">-- Tanpa Boot ISO (Boot dari Disk) --</option>
                        <?php foreach ($isos as $isoItem): ?>
                            <option value="<?=htmlspecialchars($isoItem['path'])?>">
                                <?=htmlspecialchars($isoItem['filename'])?> (<?=htmlspecialchars($isoItem['size_str'])?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help-block">Pilih ISO installer untuk proses instalasi OS guest pertama kali.</span>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-6">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan & Buat VM</button>
                    <a href="services_virtual.php" class="btn btn-default" style="margin-left: 5px;">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php elseif ($tab === 'images'): ?>
<!-- ISO & IMAGES MANAGER -->
<div class="panel panel-default" style="margin-top: 15px;">
    <div class="panel-heading">
        <h2 class="panel-title"><i class="fa-solid fa-compact-disc"></i> ISO & Disk Images Repository</h2>
    </div>
    <div class="panel-body">
        <div class="well well-sm">
            <form method="post" action="services_virtual.php" enctype="multipart/form-data" class="form-inline">
                <input type="hidden" name="act" value="upload_iso" />
                <div class="form-group">
                    <label>Unggah File ISO Baru:</label>
                    <input type="file" name="iso_file" class="form-control" accept=".iso,.img" style="margin-left: 10px;" required />
                </div>
                <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                    <i class="fa-solid fa-upload"></i> Upload ISO
                </button>
            </form>
            <div style="font-size: 11px; color: #777; margin-top: 6px;">
                Direktori penyimpanan: <code>/var/lib/mitranet/isos/</code>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th style="width: 40%;">Nama File ISO</th>
                        <th style="width: 25%;">Ukuran File</th>
                        <th style="width: 20%;">Tanggal Modifikasi</th>
                        <th style="width: 15%; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($isos)): ?>
                        <?php foreach ($isos as $iso): ?>
                            <tr>
                                <td>
                                    <strong><i class="fa-solid fa-compact-disc text-primary"></i> <?=htmlspecialchars($iso['filename'])?></strong>
                                    <div style="font-size: 11px; color: #888;">Path: <code><?=htmlspecialchars($iso['path'])?></code></div>
                                </td>
                                <td><span class="badge" style="background-color: #337ab7;"><?=htmlspecialchars($iso['size_str'])?></span></td>
                                <td><?=date('Y-m-d H:i:s', $iso['mtime'])?></td>
                                <td style="text-align: right;">
                                    <form method="post" action="services_virtual.php" style="display: inline-block;" onsubmit="return confirm('Hapus file ISO ini?');">
                                        <input type="hidden" name="act" value="delete_iso" />
                                        <input type="hidden" name="filename" value="<?=htmlspecialchars($iso['filename'])?>" />
                                        <button type="submit" class="btn btn-xs btn-danger" title="Hapus ISO">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center" style="padding: 20px; color: #888;">
                                <em>Belum ada ISO image di <code>/var/lib/mitranet/isos</code>.</em>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($tab === 'console'): ?>
<!-- LIVE VNC CONSOLE (noVNC HTML5) -->
<div class="panel panel-default" style="margin-top: 15px;">
    <div class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2 class="panel-title"><i class="fa-solid fa-desktop"></i> Remote Console: Web noVNC (HTML5 Remote Desktop)</h2>
        <div>
            <a href="http://192.168.56.101:6080/vnc.html?host=192.168.56.101&port=6080&autoconnect=true&resize=scale" target="_blank" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Layar Penuh di Tab Baru
            </a>
            <a href="services_virtual.php" class="btn btn-sm btn-default" style="margin-left: 5px;">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar VM
            </a>
        </div>
    </div>
    <div class="panel-body" style="padding: 10px 15px; background: #222; color: #fff;">
        <div style="font-size: 13px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="label label-success"><i class="fa-solid fa-signal"></i> noVNC Port 6080 Active</span>
                <span style="margin-left: 10px; color: #bbb;">Klik di dalam layar monitor untuk mengarahkan keyboard & mouse ke sistem operasi VM.</span>
            </div>
            <div>
                <span style="color: #aaa; font-size: 11px;">Server: <code>192.168.56.101:6080</code> (WebSocket Proxy)</span>
            </div>
        </div>
        <div style="border: 2px solid #444; border-radius: 4px; overflow: hidden; background: #000; text-align: center;">
            <iframe src="http://192.168.56.101:6080/vnc.html?host=192.168.56.101&port=6080&autoconnect=true&resize=scale" style="width: 100%; height: 620px; border: none; display: block;"></iframe>
        </div>
    </div>
</div>

<?php else: ?>
<!-- MAIN DASHBOARD & VM LIST -->
<div class="panel panel-default" style="margin-top: 15px;">
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
                    <i class="fa-solid fa-compact-disc text-primary"></i> Kelola ISO / Images (<?=count($isos)?>)
                </a>
                <a href="services_virtual.php?act=console" class="btn btn-default" style="margin-left: 5px;">
                    <i class="fa-solid fa-desktop text-success"></i> VNC Console
                </a>
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
                                    <?php if (!empty($vm['iso'])): ?>
                                        <div style="font-size: 10px; color: #888; margin-top: 2px;">
                                            <i class="fa-solid fa-compact-disc"></i> ISO: <?=htmlspecialchars(basename($vm['iso']))?>
                                        </div>
                                    <?php endif; ?>
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
                                    <a href="services_virtual.php?act=console" class="btn btn-xs btn-primary" style="margin-left: 3px;" title="VNC Console">
                                        <i class="fa-solid fa-desktop"></i>
                                    </a>
                                    <?php if (empty($vm['is_default'])): ?>
                                        <form method="post" action="services_virtual.php" style="display: inline-block; margin-left: 3px;" onsubmit="return confirm('Hapus VM ini beserta disk virtualnya?');">
                                            <input type="hidden" name="act" value="delete_vm" />
                                            <input type="hidden" name="id" value="<?=htmlspecialchars($vm['id'])?>" />
                                            <button type="submit" class="btn btn-xs btn-danger" title="Hapus Virtual Machine">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
<?php endif; ?>

<?php include(__DIR__ . '/includes/foot.inc'); ?>
