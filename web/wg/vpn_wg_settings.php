<?php
/*
 * vpn_wg_settings.php - MitraNet WireGuard Global Settings
 * Adapted from pfSense /wg/vpn_wg_settings.php
 * Licensed under the Apache License, Version 2.0.
 */

$badge_title = "WireGuard";
$pgtitle = array("VPN", "WireGuard", "Settings");
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");
$selected_menu = "wireguard";
require_once(__DIR__ . '/../includes/head.inc');

$savemsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sched_enable = isset($_POST['sched_enabled']);
    $sched_cron = trim($_POST['sched_cron'] ?? '0 4 * * *');
    $sched_tun = trim($_POST['sched_tun'] ?? 'wg0');
    $sched_desc = trim($_POST['sched_desc'] ?? 'Auto Restart WireGuard');

    $res = MitraNetApi::saveWireGuardSchedule($sched_enable, $sched_cron, $sched_tun, $sched_desc);
    if (!empty($res['data']['success'])) {
        $savemsg = "Pengaturan jadwal restart otomatis WireGuard berhasil disimpan.";
    } else {
        $savemsg = "Gagal menyimpan jadwal: " . ($res['data']['error'] ?? 'Terjadi kesalahan');
    }
}

$wg = MitraNetApi::getWireGuard();
$sched = MitraNetApi::getWireGuardSchedule();
$sched_enabled = !empty($sched['enabled']);
$sched_cron_val = $sched['cron'] ?? '0 4 * * *';
$sched_tun_val = $sched['tunnel'] ?? 'wg0';
$sched_desc_val = $sched['description'] ?? 'Daily at 04:00 AM';

$tab_array = array(
    array("Interfaces", false, "/wg/vpn_wg_tunnels.php"),
    array("Secrets", false, "/wg/vpn_wg_peers.php"),
    array("Settings", true, "/wg/vpn_wg_settings.php"),
    array("Status", false, "/wg/status_wireguard.php")
);
?>

<div class="container-fluid mitranet-page-container">
	<div class="mitranet-window">
<?php display_top_tabs($tab_array, false, 'tabs'); ?>
		<div class="mitranet-window-body">

<?php if (!empty($savemsg)): ?>
<div class="alert alert-info alert-dismissible" role="alert">
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<i class="fa-solid fa-circle-info"></i> <?=htmlspecialchars($savemsg)?>
</div>
<?php endif; ?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><i class="fa-solid fa-server"></i> WireGuard Subsystem Information</h2></div>
	<div class="panel-body">
		<div class="form-horizontal">
			<div class="form-group">
				<label class="col-sm-3 control-label">Linux Kernel Driver</label>
				<div class="col-sm-9">
					<p class="form-control-static text-success">
						<i class="fa-solid fa-check-circle"></i> In-Tree Linux 6.12 WireGuard Kernel Module (wireguard.ko)
					</p>
					<span class="help-block">MitraNet utilizes native Linux kernel WireGuard for line-rate throughput and hardware crypto acceleration.</span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label">Service Daemon Status</label>
				<div class="col-sm-9">
					<p class="form-control-static">
						<?php if (!empty($wg['running'])): ?>
							<span class="label label-success"><i class="fa-solid fa-check"></i> Active (systemd: wg-quick@wg0)</span>
						<?php else: ?>
							<span class="label label-danger"><i class="fa-solid fa-ban"></i> Inactive</span>
						<?php endif; ?>
					</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label">Configuration Path</label>
				<div class="col-sm-9">
					<p class="form-control-static"><code>/etc/wireguard/wg0.conf</code></p>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- AUTO-RESTART SCHEDULER (CRON SYSTEMD INTEGRATION) -->
<div class="panel panel-default">
	<div class="panel-heading">
		<h2 class="panel-title"><i class="fa-solid fa-clock-rotate-left"></i> Jadwal Restart Otomatis (Auto-Restart Scheduler)</h2>
	</div>
	<div class="panel-body">
		<form method="post" class="form-horizontal" id="wgScheduleForm">
			<div class="form-group">
				<label class="col-sm-3 control-label">Aktifkan Scheduler</label>
				<div class="col-sm-9">
					<div class="checkbox">
						<label>
							<input type="checkbox" name="sched_enabled" id="sched_enabled" value="1" <?=$sched_enabled ? 'checked' : ''?>>
							<strong>Auto-restart service WireGuard secara berkala</strong>
						</label>
					</div>
					<span class="help-block">Mereset counter byte statistik di kernel dan memastikan tunnel tetap segar serta otomatis terhubung kembali (auto-reconnect).</span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label">Target Interface</label>
				<div class="col-sm-4">
					<select name="sched_tun" class="form-control">
						<?php foreach ($wg['tunnels'] ?? [['name' => 'wg0']] as $t): ?>
							<option value="<?=htmlspecialchars($t['name'])?>" <?=$sched_tun_val === $t['name'] ? 'selected' : ''?>>
								<?=htmlspecialchars($t['name'])?> (<?=htmlspecialchars($t['description'] ?? 'WireGuard')?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label">Pilihan Jadwal Cepat</label>
				<div class="col-sm-5">
					<select class="form-control" id="sched_preset" onchange="applyCronPreset(this.value)">
						<option value="0 4 * * *">Setiap Hari Pukul 04:00 Pagi (Rekomendasi)</option>
						<option value="0 0 * * *">Setiap Hari Pukul 00:00 Tengah Malam</option>
						<option value="0 */6 * * *">Setiap 6 Jam Sekali</option>
						<option value="0 */12 * * *">Setiap 12 Jam Sekali</option>
						<option value="0 3 * * 0">Setiap Minggu Pukul 03:00 Pagi</option>
						<option value="custom">Kustom (Format Cron Bebas)...</option>
					</select>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label">Ekspresi Cron Linux</label>
				<div class="col-sm-4">
					<input type="text" class="form-control" name="sched_cron" id="sched_cron" value="<?=htmlspecialchars($sched_cron_val)?>" placeholder="0 4 * * *" required>
					<span class="help-block" style="font-size:11px;">Format standar crontab 5 field: <code>menit jam tanggal bulan hari</code> (cth: <code>0 4 * * *</code> = 04:00 subuh).</span>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-3 control-label">Deskripsi / Catatan</label>
				<div class="col-sm-6">
					<input type="text" class="form-control" name="sched_desc" value="<?=htmlspecialchars($sched_desc_val)?>" placeholder="Auto-restart wg0 harian">
				</div>
			</div>

			<div class="form-group">
				<div class="col-sm-offset-3 col-sm-9">
					<button type="submit" class="btn btn-primary">
						<i class="fa-solid fa-floppy-disk"></i> Simpan Jadwal Scheduler
					</button>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
function applyCronPreset(val) {
    if (val !== 'custom') {
        $('#sched_cron').val(val);
    }
}
</script>
		</div><!-- /.mitranet-window-body -->
	</div><!-- /.mitranet-window -->
</div><!-- /.mitranet-page-container -->

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
