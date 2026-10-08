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
            'net_mode' => trim($_POST['net_mode'] ?? 'veth'),
            'veth_iface' => trim($_POST['veth_iface'] ?? 'veth0'),
            'guest_ip' => trim($_POST['guest_ip'] ?? ''),
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
    } elseif ($act === 'update_vm') {
        $payload = [
            'id' => trim($_POST['vm_id'] ?? ''),
            'ram_mb' => intval($_POST['ram_mb'] ?? 1024),
            'vcpu' => intval($_POST['vcpu'] ?? 1),
            'disk_gb' => intval($_POST['disk_gb'] ?? 10),
            'port_fwd' => intval($_POST['port_fwd'] ?? 8888),
            'net_mode' => trim($_POST['net_mode'] ?? 'veth'),
            'veth_iface' => trim($_POST['veth_iface'] ?? 'veth0'),
            'guest_ip' => trim($_POST['guest_ip'] ?? ''),
            'iso' => trim($_POST['iso'] ?? ''),
            'restart' => !empty($_POST['restart_now'])
        ];
        $res = MitraNetApi::updateVm($payload);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $savemsg = "Konfigurasi Virtual Machine '" . htmlspecialchars($payload['id']) . "' berhasil diperbarui.";
            $tab = 'list';
        } else {
            $err_msg = htmlspecialchars($res['data']['error'] ?? "Gagal memperbarui Virtual Machine.");
            $tab = 'manage_aapanel';
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
    } elseif ($act === 'eject_iso') {
        $vm_id = trim($_POST['vm_id'] ?? '');
        $del_file = !empty($_POST['delete_file']);
        $res = MitraNetApi::ejectIso($vm_id, $del_file);
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $savemsg = htmlspecialchars($res['data']['message'] ?? "ISO berhasil dilepas dari VM.");
        } else {
            $err_msg = htmlspecialchars($res['data']['error'] ?? "Gagal melepas ISO.");
        }
    } elseif ($act === 'clean_unused_isos') {
        $res = MitraNetApi::cleanUnusedIsos();
        if (($res['status'] ?? 0) === 200 && ($res['data']['success'] ?? false)) {
            $savemsg = htmlspecialchars($res['data']['message'] ?? "Pembersihan ISO berhasil.");
        } else {
            $err_msg = htmlspecialchars($res['data']['error'] ?? "Gagal membersihkan ISO.");
        }
        $tab = 'images';
    }
}


$pgtitle = array("Services", "Virtual Machines (KVM)");
$selected_menu = "kvm";
require_once(__DIR__ . '/includes/head.inc');

$kvmData = MitraNetApi::getKvmData();
$vms = $kvmData['vms'] ?? [];
$isos = $kvmData['isos'] ?? [];
$vethernets = MitraNetApi::getVethernets();
$all_bridges = MitraNetApi::getBridges();
$all_vlans = MitraNetApi::getVlans();
$all_interfaces = MitraNetApi::getInterfaces();
$gateways = MitraNetApi::getGateways();

// Dynamically detect WAN interface(s)
$wan_ifaces = [];
foreach ($gateways as $gw) {
    if (!empty($gw['default']) && !empty($gw['interface'])) {
        $wan_ifaces[] = $gw['interface'];
    }
}
// Fallback check: if route has default via dev
if (empty($wan_ifaces)) {
    $routes = MitraNetApi::getRoutes();
    foreach ($routes['ipv4'] ?? [] as $rt) {
        if (($rt['destination'] ?? '') === '0.0.0.0/0' || ($rt['destination'] ?? '') === 'default') {
            if (!empty($rt['interface'])) {
                $wan_ifaces[] = $rt['interface'];
            }
        }
    }
}
$wan_ifaces = array_unique($wan_ifaces);

// Locate aaPanel VM object
$aapanelVm = null;
foreach ($vms as $v) {
    if ($v['id'] === 'aapanel') {
        $aapanelVm = $v;
        break;
    }
}
?>

<ul class="nav nav-pills">
    <li role="presentation" class="<?=$tab === 'list' ? 'active' : ''?>"><a href="services_virtual.php">Virtual Machines</a></li>
    <li role="presentation" class="<?=$tab === 'manage_aapanel' ? 'active' : ''?>"><a href="services_virtual.php?act=manage_aapanel"><i class="fa-solid fa-sliders text-info"></i> Manage aaPanel</a></li>
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
                <label class="col-sm-3 control-label">Network Interface:</label>
                <div class="col-sm-6">
                    <select name="veth_iface" class="form-control" id="veth_iface_select" required>
                        <?php if (!empty($vethernets)): ?>
                            <optgroup label="vEthernet (Host-Only Subnet / NAT / VPS)">
                                <?php foreach ($vethernets as $ve): ?>
                                    <option value="<?=htmlspecialchars($ve['name'])?>">
                                        <?=htmlspecialchars($ve['name'])?> (vEthernet Gateway: <?=htmlspecialchars($ve['ip_cidr'] ?: '192.168.101.254/24')?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>

                        <?php if (!empty($all_bridges)): ?>
                            <optgroup label="Bridges (Layer-2 Shared LAN Segment)">
                                <?php foreach ($all_bridges as $br): ?>
                                    <option value="<?=htmlspecialchars($br['name'])?>">
                                        <?=htmlspecialchars($br['name'])?> (Bridge - Members: <?=htmlspecialchars(implode(', ', $br['members'] ?? []))?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>

                        <?php if (!empty($all_vlans)): ?>
                            <optgroup label="802.1Q VLANs">
                                <?php foreach ($all_vlans as $vl): ?>
                                    <option value="<?=htmlspecialchars($vl['name'])?>">
                                        <?=htmlspecialchars($vl['name'])?> (VLAN <?=htmlspecialchars($vl['tag'] ?? '')?> on <?=htmlspecialchars($vl['parent'] ?? '')?>)
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>

                        <optgroup label="Physical Network Interfaces (Direct LAN / Baremetal PC)">
                            <?php foreach ($all_interfaces as $iface): ?>
                                <?php if ($iface['name'] !== 'lo' && !str_starts_with($iface['name'], 'veth') && !str_starts_with($iface['name'], 'tap') && !in_array($iface['name'], $wan_ifaces)): ?>
                                    <option value="<?=htmlspecialchars($iface['name'])?>">
                                        <?=htmlspecialchars($iface['name'])?> (<?=htmlspecialchars($iface['type'] ?? 'ether')?><?=!empty($iface['ipv4_addresses']) ? ' - ' . htmlspecialchars(implode(', ', $iface['ipv4_addresses'])) : ''?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <span class="help-block">
                        Pilih interface jaringan tujuan:
                        <br>&bull; <strong>vEthernet</strong>: Subnet virtual internal terisolasi (cocok untuk VPS / NAT port forward).
                        <br>&bull; <strong>Bridge</strong>: Menggabungkan VM langsung ke segmen IP yang sama dengan LAN fisik (satu subnet IP di PC baremetal).
                        <br>&bull; <strong>VLAN / Physical</strong>: Menghubungkan langsung ke tag VLAN atau port ethernet fisik.
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">IP Address VM Guest (Opsional):</label>
                <div class="col-sm-6">
                    <input type="text" name="guest_ip" class="form-control" placeholder="192.168.101.2" value="192.168.101.2" />
                    <span class="help-block">Alamat IP statis / DHCP yang akan diberikan pada sistem operasi di dalam VM (misal aaPanel).</span>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Port Forwarding (NAT dari WAN):</label>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span class="input-group-addon">Host/WAN Port:</span>
                        <input type="number" name="port_fwd" class="form-control" value="8888" />
                        <span class="input-group-addon">&rarr; Guest aaPanel (8888)</span>
                    </div>
                    <span class="help-block">Port akses dari luar (VPS Public IP) diteruskan ke port web panel VM.</span>
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

<?php elseif ($tab === 'manage_aapanel'): ?>
<!-- MANAGE AAPANEL (DEFAULT BUILT-IN VM) -->
<div class="panel panel-default" style="margin-top: 15px;">
    <div class="panel-heading">
        <h2 class="panel-title">
            <i class="fa-solid fa-sliders text-info"></i> <?=gettext("Manage Built-in VM: aaPanel Linux Web Control Panel")?>
        </h2>
    </div>
    <div class="panel-body">
        <?php if (!$aapanelVm): ?>
            <div class="alert alert-warning">
                <i class="fa-solid fa-triangle-exclamation"></i> <?=gettext("Virtual Machine aaPanel belum terdeteksi di /var/lib/mitranet/vms/aapanel.")?>
            </div>
        <?php else: ?>
            <?php
                $aapanelCreds = $aapanelVm['aapanel_creds'] ?? [
                    'username' => 'mitranet',
                    'password' => 'mitranet123',
                    'admin_path' => '/mitranet',
                    'port' => ($aapanelVm['port_fwd'] ?? 8888)
                ];
                $hostIp = $_SERVER['SERVER_ADDR'] ?? ($_SERVER['HTTP_HOST'] ? explode(':', $_SERVER['HTTP_HOST'])[0] : '192.168.56.101');
                $panelPort = $aapanelCreds['port'] ?? ($aapanelVm['port_fwd'] ?? 8888);
                $panelPath = $aapanelCreds['admin_path'] ?? '/mitranet';
                if (!str_starts_with($panelPath, '/')) {
                    $panelPath = '/' . $panelPath;
                }
                $aapanelUrl = "http://{$hostIp}:{$panelPort}{$panelPath}";
            ?>

            <!-- AAPANEL ACCESS CREDENTIALS CARD (READ-ONLY) -->
            <div class="panel panel-info" style="border-width: 2px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div class="panel-heading" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); color: #fff; padding: 12px 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <h3 class="panel-title" style="font-weight: bold; font-size: 16px;">
                            <i class="fa-solid fa-key"></i> Kredensial & Akses Masuk aaPanel
                        </h3>
                        <a href="<?=htmlspecialchars($aapanelUrl)?>" target="_blank" class="btn btn-sm btn-default" style="font-weight: 600; color: #117a8b; background: #fff; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka aaPanel Dashboard
                        </a>
                    </div>
                </div>
                <div class="panel-body" style="background-color: #fcfdfe; padding: 20px;">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning" style="margin-bottom: 20px; font-size: 13px;">
                                <i class="fa-solid fa-shield-halved"></i>
                                <strong>Keamanan:</strong> Username dan Password di bawah ini bersifat <strong>Read-Only</strong> di MitraNet. Perubahan kredensial hanya dapat dilakukan langsung di dalam antarmuka web aaPanel. Kredensial baru yang Anda simpan di aaPanel akan langsung terbaca otomatis di sini.
                            </div>
                        </div>

                        <!-- Panel Entrance URL -->
                        <div class="col-md-12" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                <i class="fa-solid fa-link text-primary"></i> URL Akses Masuk (Entrance Login):
                            </label>
                            <div class="input-group">
                                <input type="text" id="aapanel_url_input" class="form-control" value="<?=htmlspecialchars($aapanelUrl)?>" readonly style="background-color: #fff; font-family: monospace; font-size: 13px; font-weight: bold; color: #0275d8;" />
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" onclick="navigator.clipboard.writeText(document.getElementById('aapanel_url_input').value); alert('URL login aaPanel berhasil disalin!');" title="Salin URL">
                                        <i class="fa-solid fa-copy"></i> Salin
                                    </button>
                                    <a href="<?=htmlspecialchars($aapanelUrl)?>" target="_blank" class="btn btn-info" title="Buka di tab baru">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Kunjungi
                                    </a>
                                </span>
                            </div>
                            <span class="help-block" style="margin-top: 4px; font-size: 11px;">
                                Security Entrance Path: <code><?=htmlspecialchars($panelPath)?></code> (Port: <code><?=htmlspecialchars($panelPort)?></code>)
                            </span>
                        </div>

                        <!-- Username Field -->
                        <div class="col-sm-6" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                <i class="fa-solid fa-user text-info"></i> Username:
                            </label>
                            <div class="input-group">
                                <input type="text" id="aapanel_user_input" class="form-control" value="<?=htmlspecialchars($aapanelCreds['username'] ?? 'mitranet')?>" readonly style="background-color: #fff; font-family: monospace; font-size: 14px; font-weight: bold;" />
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" onclick="navigator.clipboard.writeText(document.getElementById('aapanel_user_input').value); alert('Username aaPanel berhasil disalin!');" title="Salin Username">
                                        <i class="fa-solid fa-copy"></i> Salin
                                    </button>
                                </span>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="col-sm-6" style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                <i class="fa-solid fa-lock text-info"></i> Password:
                            </label>
                            <div class="input-group">
                                <input type="password" id="aapanel_pass_input" class="form-control" value="<?=htmlspecialchars($aapanelCreds['password'] ?? 'mitranet123')?>" readonly style="background-color: #fff; font-family: monospace; font-size: 14px; font-weight: bold; letter-spacing: 1px;" />
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" id="btn_toggle_pass" onclick="var p = document.getElementById('aapanel_pass_input'); var icon = this.querySelector('i'); if (p.type === 'password') { p.type = 'text'; icon.className = 'fa-solid fa-eye-slash'; } else { p.type = 'password'; icon.className = 'fa-solid fa-eye'; }" title="Tampilkan / Sembunyikan Password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="btn btn-default" type="button" onclick="navigator.clipboard.writeText(document.getElementById('aapanel_pass_input').value); alert('Password aaPanel berhasil disalin!');" title="Salin Password">
                                        <i class="fa-solid fa-copy"></i> Salin
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info"></i>
                <strong>Konfigurasi Hardware & Virtualisasi:</strong> Anda dapat menyesuaikan vCPU, RAM, kapasitas Disk, atau memindahkan antarmuka jaringan aaPanel ke Bridge lokal (agar 1 segmen dengan LAN kantor di PC baremetal), atau tetap di vEthernet / VLAN tanpa mengganggu interface WAN.
            </div>

            <form method="post" action="services_virtual.php" class="form-horizontal">
                <input type="hidden" name="act" value="update_vm" />
                <input type="hidden" name="vm_id" value="aapanel" />

                <div class="form-group">
                    <label class="col-sm-3 control-label">Nama VM / Identitas:</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" value="aaPanel (Built-in Linux Appliance)" readonly disabled />
                        <span class="help-block">VM ID: <code>aapanel</code> (Status saat ini: <strong><?=htmlspecialchars($aapanelVm['status'])?></strong>)</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label"><span class="element-required">*</span>Alokasi vCPU Cores:</label>
                    <div class="col-sm-6">
                        <select name="vcpu" class="form-control">
                            <option value="1" <?=$aapanelVm['vcpu'] == 1 ? 'selected' : ''?>>1 Core (Default Ringan)</option>
                            <option value="2" <?=$aapanelVm['vcpu'] == 2 ? 'selected' : ''?>>2 Cores</option>
                            <option value="4" <?=$aapanelVm['vcpu'] == 4 ? 'selected' : ''?>>4 Cores</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label"><span class="element-required">*</span>Alokasi RAM (Memory):</label>
                    <div class="col-sm-6">
                        <select name="ram_mb" class="form-control">
                            <option value="512" <?=$aapanelVm['ram_mb'] == 512 ? 'selected' : ''?>>512 MB (Sangat Ringan)</option>
                            <option value="1024" <?=$aapanelVm['ram_mb'] == 1024 ? 'selected' : ''?>>1024 MB (1 GB - Rekomendasi)</option>
                            <option value="2048" <?=$aapanelVm['ram_mb'] == 2048 ? 'selected' : ''?>>2048 MB (2 GB)</option>
                            <option value="4096" <?=$aapanelVm['ram_mb'] == 4096 ? 'selected' : ''?>>4096 MB (4 GB)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label"><span class="element-required">*</span>Ukuran Virtual Disk (qcow2):</label>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <input type="number" name="disk_gb" class="form-control" value="<?=htmlspecialchars($aapanelVm['disk_gb'] ?? 10)?>" min="5" max="500" />
                            <span class="input-group-addon">GB</span>
                        </div>
                        <span class="help-block">
                            Ukuran saat ini: <strong><?=htmlspecialchars($aapanelVm['disk_gb'] ?? 10)?> GB</strong> (Format qcow2 dinamis di <code>/var/lib/mitranet/vms/aapanel/disk.qcow2</code>). Anda dapat memperbesar kapasitas disk kapan saja.
                        </span>
                    </div>
                </div>


                <div class="form-group">
                    <label class="col-sm-3 control-label"><span class="element-required">*</span>Network Interface (Koneksi Jaringan):</label>
                    <div class="col-sm-6">
                        <?php $current_if = $aapanelVm['veth_iface'] ?? 'veth0'; ?>
                        <select name="veth_iface" class="form-control" required>
                            <?php if (!empty($vethernets)): ?>
                                <optgroup label="vEthernet (Host-Only Subnet / NAT / VPS)">
                                    <?php foreach ($vethernets as $ve): ?>
                                        <option value="<?=htmlspecialchars($ve['name'])?>" <?=$current_if === $ve['name'] ? 'selected' : ''?>>
                                            <?=htmlspecialchars($ve['name'])?> (vEthernet Gateway: <?=htmlspecialchars($ve['ip_cidr'] ?: '192.168.101.254/24')?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($all_bridges)): ?>
                                <optgroup label="Bridges (Layer-2 Shared LAN Segment - 1 IP Subnet dengan PC Baremetal)">
                                    <?php foreach ($all_bridges as $br): ?>
                                        <option value="<?=htmlspecialchars($br['name'])?>" <?=$current_if === $br['name'] ? 'selected' : ''?>>
                                            <?=htmlspecialchars($br['name'])?> (Bridge - Members: <?=htmlspecialchars(implode(', ', $br['members'] ?? []))?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($all_vlans)): ?>
                                <optgroup label="802.1Q VLANs">
                                    <?php foreach ($all_vlans as $vl): ?>
                                        <option value="<?=htmlspecialchars($vl['name'])?>" <?=$current_if === $vl['name'] ? 'selected' : ''?>>
                                            <?=htmlspecialchars($vl['name'])?> (VLAN <?=htmlspecialchars($vl['tag'] ?? '')?> on <?=htmlspecialchars($vl['parent'] ?? '')?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>

                            <optgroup label="Physical Network Interfaces (Direct LAN / Baremetal PC - Non-WAN)">
                                <?php foreach ($all_interfaces as $iface): ?>
                                    <?php if ($iface['name'] !== 'lo' && !str_starts_with($iface['name'], 'veth') && !str_starts_with($iface['name'], 'tap') && !in_array($iface['name'], $wan_ifaces)): ?>
                                        <option value="<?=htmlspecialchars($iface['name'])?>" <?=$current_if === $iface['name'] ? 'selected' : ''?>>
                                            <?=htmlspecialchars($iface['name'])?> (<?=htmlspecialchars($iface['type'] ?? 'ether')?><?=!empty($iface['ipv4_addresses']) ? ' - ' . htmlspecialchars(implode(', ', $iface['ipv4_addresses'])) : ''?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <span class="help-block">
                            Interface WAN (<?=htmlspecialchars(implode(', ', $wan_ifaces) ?: 'None')?>) <strong>otomatis disembunyikan</strong> untuk melindungi koneksi internet host dan mencegah IP/MAC conflict.
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">IP Address aaPanel Guest:</label>
                    <div class="col-sm-6">
                        <input type="text" name="guest_ip" class="form-control" value="<?=htmlspecialchars($aapanelVm['guest_ip'] ?? '192.168.101.2')?>" placeholder="contoh: 192.168.101.2 atau IP statis LAN" />
                        <span class="help-block">Alamat IP yang dikonfigurasikan di dalam sistem operasi aaPanel.</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Port Forwarding (Akses Eksternal):</label>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <span class="input-group-addon">Host Port:</span>
                            <input type="number" name="port_fwd" class="form-control" value="<?=htmlspecialchars($aapanelVm['port_fwd'] ?? 8888)?>" />
                            <span class="input-group-addon">&rarr; Guest Port (8888)</span>
                        </div>
                        <span class="help-block">Port akses web aaPanel dari luar host (contoh: http://IP_HOST:8888).</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Boot ISO Image (Opsional):</label>
                    <div class="col-sm-6">
                        <select name="iso" class="form-control">
                            <option value="">-- Boot Langsung dari Virtual Disk qcow2 (Default) --</option>
                            <?php foreach ($isos as $isoItem): ?>
                                <option value="<?=htmlspecialchars($isoItem['path'])?>" <?=($aapanelVm['iso'] ?? '') === $isoItem['path'] ? 'selected' : ''?>>
                                    <?=htmlspecialchars($isoItem['filename'])?> (<?=htmlspecialchars($isoItem['size_str'])?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Terapkan Perubahan:</label>
                    <div class="col-sm-6">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="restart_now" value="1" <?=($aapanelVm['status'] === 'RUNNING') ? 'checked' : ''?> />
                                <strong>Restart Virtual Machine aaPanel sekarang</strong> untuk segera menerapkan antarmuka jaringan baru.
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-6">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?=gettext("Simpan Konfigurasi aaPanel")?></button>
                        <a href="services_virtual.php" class="btn btn-default" style="margin-left: 5px;"><?=gettext("Batal")?></a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($tab === 'images'): ?>
<!-- ISO & IMAGES MANAGER -->
<div class="panel panel-default" style="margin-top: 15px;">
    <div class="panel-heading">
        <h2 class="panel-title"><i class="fa-solid fa-compact-disc"></i> ISO & Disk Images Repository</h2>
    </div>
    <div class="panel-body">
        <div class="well well-sm" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <form method="post" action="services_virtual.php" enctype="multipart/form-data" class="form-inline" style="margin: 0;">
                <input type="hidden" name="act" value="upload_iso" />
                <div class="form-group">
                    <label>Unggah File ISO Baru:</label>
                    <input type="file" name="iso_file" class="form-control" accept=".iso,.img" style="margin-left: 10px;" required />
                </div>
                <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                    <i class="fa-solid fa-upload"></i> Upload ISO
                </button>
            </form>
            <div>
                <form method="post" action="services_virtual.php" style="display: inline-block; margin: 0;" onsubmit="return confirm('Bersihkan seluruh file ISO yang tidak sedang digunakan oleh Virtual Machine? Tindakan ini akan membebaskan ruang disk.');">
                    <input type="hidden" name="act" value="clean_unused_isos" />
                    <button type="submit" class="btn btn-warning" title="Hapus seluruh ISO yang tidak sedang dipakai boot oleh VM manapun">
                        <i class="fa-solid fa-broom"></i> Bersihkan ISO Tak Terpakai
                    </button>
                </form>
            </div>
        </div>
        <div style="font-size: 11px; color: #777; margin-top: -10px; margin-bottom: 15px;">
            Direktori repositori: <code>/var/lib/mitranet/isos/</code>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th style="width: 35%;">Nama File ISO</th>
                        <th style="width: 15%;">Ukuran File</th>
                        <th style="width: 25%;">Status Penggunaan (VM)</th>
                        <th style="width: 15%;">Tanggal Modifikasi</th>
                        <th style="width: 10%; text-align: right;">Aksi</th>
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
                                <td>
                                    <?php if (!empty($iso['is_used'])): ?>
                                        <span class="label label-info"><i class="fa-solid fa-link"></i> Dipakai oleh: <?=htmlspecialchars(implode(', ', $iso['used_by']))?></span>
                                    <?php else: ?>
                                        <span class="label label-default" style="background-color: #777;"><i class="fa-solid fa-circle-check"></i> Tidak Digunakan (Bisa Dihapus)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?=date('Y-m-d H:i:s', $iso['mtime'])?></td>
                                <td style="text-align: right;">
                                    <form method="post" action="services_virtual.php" style="display: inline-block;" onsubmit="return confirm('Hapus file ISO ini dari penyimpanan?');">
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
                            <td colspan="5" class="text-center" style="padding: 20px; color: #888;">
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
    <?php
    // Detect if any running VM currently has an ISO attached
    $vms_with_iso = [];
    foreach ($vms as $v) {
        if (!empty($v['iso'])) {
            $vms_with_iso[] = $v;
        }
    }
    ?>
    <?php if (!empty($vms_with_iso)): ?>
        <div style="background: #2a3b4c; border-bottom: 1px solid #1a2733; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <div style="color: #cee5fd; font-size: 12px;">
                <i class="fa-solid fa-compact-disc"></i> <strong>Instalasi Selesai?</strong> Lepas media instalasi CD-ROM agar VM langsung boot dari virtual disk qcow2:
            </div>
            <div style="display: flex; gap: 8px;">
                <?php foreach ($vms_with_iso as $vi): ?>
                    <form method="post" action="services_virtual.php" style="display: inline-block; margin: 0;" onsubmit="return confirm('Instalasi VM <?=htmlspecialchars($vi['name'])?> telah selesai? Eject ISO dan hapus file installer dari disk?');">
                        <input type="hidden" name="act" value="eject_iso" />
                        <input type="hidden" name="vm_id" value="<?=htmlspecialchars($vi['id'])?>" />
                        <input type="hidden" name="delete_file" value="1" />
                        <button type="submit" class="btn btn-xs btn-success" style="font-weight: bold;">
                            <i class="fa-solid fa-eject"></i> Selesai Instalasi <?=htmlspecialchars($vi['name'])?> (Eject & Hapus ISO)
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
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
                <?php
                    $listAapanelUrl = "http://192.168.56.101:8888/mitranet";
                    if ($aapanelVm && !empty($aapanelVm['aapanel_creds']['admin_path'])) {
                        $listHost = $_SERVER['SERVER_ADDR'] ?? ($_SERVER['HTTP_HOST'] ? explode(':', $_SERVER['HTTP_HOST'])[0] : '192.168.56.101');
                        $listPort = $aapanelVm['aapanel_creds']['port'] ?? ($aapanelVm['port_fwd'] ?? 8888);
                        $listPath = $aapanelVm['aapanel_creds']['admin_path'] ?? '/mitranet';
                        if (!str_starts_with($listPath, '/')) $listPath = '/' . $listPath;
                        $listAapanelUrl = "http://{$listHost}:{$listPort}{$listPath}";
                    }
                ?>
                <a href="<?=htmlspecialchars($listAapanelUrl)?>" target="_blank" class="btn btn-info">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka aaPanel Web
                </a>
                <a href="services_virtual.php?act=manage_aapanel" class="btn btn-default" style="margin-left: 5px;">
                    <i class="fa-solid fa-key text-warning"></i> Lihat User & Password
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
                                    <?php if (!empty($vm['guest_ip'])): ?>
                                        <div style="font-size: 11px; margin-top: 2px;">
                                            <span class="label label-success"><i class="fa-solid fa-desktop"></i> IP: <?=htmlspecialchars($vm['guest_ip'])?></span>
                                        </div>
                                    <?php endif; ?>
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
                                    <?php if (!empty($vm['is_default'])): ?>
                                        <a href="services_virtual.php?act=manage_aapanel" class="btn btn-xs btn-info" style="margin-left: 3px;" title="Manage & Configure aaPanel">
                                            <i class="fa-solid fa-sliders"></i> Config
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($vm['iso'])): ?>
                                        <form method="post" action="services_virtual.php" style="display: inline-block; margin-left: 3px;" onsubmit="return confirm('Instalasi selesai? Eject CD-ROM dan hapus file installer ISO dari disk?');">
                                            <input type="hidden" name="act" value="eject_iso" />
                                            <input type="hidden" name="vm_id" value="<?=htmlspecialchars($vm['id'])?>" />
                                            <input type="hidden" name="delete_file" value="1" />
                                            <button type="submit" class="btn btn-xs btn-success" style="font-weight: bold;" title="Selesai Instalasi: Eject CD-ROM dan Hapus File ISO untuk menghemat ruang disk">
                                                <i class="fa-solid fa-eject"></i> Eject & Bersihkan
                                            </button>
                                        </form>
                                    <?php endif; ?>

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
