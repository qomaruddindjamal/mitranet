<?php
/*
 * advanced_sysctl.php - MitraNet System: Advanced: System Tunables & Kernel Performance (TCP BBR)
 * MitraNet Rinjani 1.0.2 - Debian 13 (Trixie) Appliance
 */

require_once(__DIR__ . '/../includes/api.inc');

$savemsg = "";
$err_msg = "";

// Handle tuning form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cc = trim($_POST['tcp_congestion_control'] ?? 'bbr');
    $qdisc = trim($_POST['default_qdisc'] ?? 'fq_codel');
    $tcp_fastopen = trim($_POST['tcp_fastopen'] ?? '3');
    $rmem_max = trim($_POST['rmem_max'] ?? '16777216');
    $wmem_max = trim($_POST['wmem_max'] ?? '16777216');

    // Validate congestion control
    if (in_array($cc, ['bbr', 'cubic', 'reno', 'dctcp'])) {
        exec("modprobe tcp_{$cc} 2>/dev/null");
        exec("sysctl -w net.ipv4.tcp_congestion_control=" . escapeshellarg($cc));
    }
    if (in_array($qdisc, ['fq_codel', 'fq', 'cake', 'pfifo_fast'])) {
        exec("sysctl -w net.core.default_qdisc=" . escapeshellarg($qdisc));
    }
    if (is_numeric($tcp_fastopen)) {
        exec("sysctl -w net.ipv4.tcp_fastopen=" . escapeshellarg($tcp_fastopen));
    }
    if (is_numeric($rmem_max)) {
        exec("sysctl -w net.core.rmem_max=" . escapeshellarg($rmem_max));
    }
    if (is_numeric($wmem_max)) {
        exec("sysctl -w net.core.wmem_max=" . escapeshellarg($wmem_max));
    }

    // Persist to /etc/sysctl.d/99-mitranet-tuning.conf
    $conf_lines = [
        "# MitraNet Performance Tunables",
        "net.ipv4.tcp_congestion_control = " . $cc,
        "net.core.default_qdisc = " . $qdisc,
        "net.ipv4.tcp_fastopen = " . $tcp_fastopen,
        "net.core.rmem_max = " . $rmem_max,
        "net.core.wmem_max = " . $wmem_max,
        "net.ipv4.tcp_rmem = 4096 87380 16777216",
        "net.ipv4.tcp_wmem = 4096 65536 16777216",
        "net.core.netdev_max_backlog = 10000"
    ];
    @file_put_contents('/etc/sysctl.d/99-mitranet-tuning.conf', implode("\n", $conf_lines) . "\n");

    $savemsg = "System Tunables and TCP Congestion Control (BBR) configuration successfully applied.";
}

// Read live values
$cur_cc = trim(shell_exec("sysctl -n net.ipv4.tcp_congestion_control 2>/dev/null") ?? 'bbr');
$cur_qdisc = trim(shell_exec("sysctl -n net.core.default_qdisc 2>/dev/null") ?? 'fq_codel');
$cur_avail_cc = trim(shell_exec("sysctl -n net.ipv4.tcp_available_congestion_control 2>/dev/null") ?? 'reno cubic bbr');
$cur_fastopen = trim(shell_exec("sysctl -n net.ipv4.tcp_fastopen 2>/dev/null") ?? '3');
$cur_rmem = trim(shell_exec("sysctl -n net.core.rmem_max 2>/dev/null") ?? '16777216');
$cur_wmem = trim(shell_exec("sysctl -n net.core.wmem_max 2>/dev/null") ?? '16777216');

$pgtitle = array("System", "Advanced", "System Tunables");
$selected_menu = "system";
require_once(__DIR__ . '/../includes/head.inc');
?>

<ul class="nav nav-pills" style="margin-bottom: 20px;">
    <li role="presentation"><a href="advanced_admin.php">Admin Access</a></li>
    <li role="presentation" class="active"><a href="advanced_sysctl.php">System Tunables &amp; BBR</a></li>
</ul>

<?php if (!empty($savemsg)): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($savemsg) ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fa-solid fa-gauge-high text-primary"></i> <strong>Linux Kernel TCP Performance &amp; Congestion Control</strong>
                </h3>
            </div>
            <div class="panel-body">
                <form action="advanced_sysctl.php" method="post" class="form-horizontal">
                    
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Current Kernel Status</label>
                        <div class="col-sm-9">
                            <p class="form-control-static">
                                <span class="label <?=($cur_cc === 'bbr') ? 'label-success' : 'label-warning'?>" style="font-size: 13px;">
                                    <i class="fa-solid fa-bolt"></i> Active Algoritma: <?=strtoupper($cur_cc)?>
                                </span>
                                &nbsp;
                                <span class="label label-info" style="font-size: 13px;">
                                    <i class="fa-solid fa-layer-group"></i> Default Qdisc: <?=$cur_qdisc?>
                                </span>
                            </p>
                            <span class="help-block">Tersedia di kernel: <code><?=htmlspecialchars($cur_avail_cc)?></code></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="tcp_congestion_control">TCP Congestion Control</label>
                        <div class="col-sm-6">
                            <select name="tcp_congestion_control" id="tcp_congestion_control" class="form-control">
                                <option value="bbr" <?=($cur_cc === 'bbr') ? 'selected' : ''?>>BBR (Bottleneck Bandwidth and RTT) - Rekomendasi / Throughput Maksimal</option>
                                <option value="cubic" <?=($cur_cc === 'cubic') ? 'selected' : ''?>>CUBIC (Linux Default Standar)</option>
                                <option value="reno" <?=($cur_cc === 'reno') ? 'selected' : ''?>>Reno (Klasik TCP)</option>
                            </select>
                            <span class="help-block">
                                <strong>BBR</strong> dirancang oleh Google untuk memaksimalkan kapasitas pipa bandwidth jaringan dan mencegah throttling drop paket akibat antrian buffer (bufferbloat) pada router upstream.
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="default_qdisc">Packet Scheduling (Qdisc)</label>
                        <div class="col-sm-6">
                            <select name="default_qdisc" id="default_qdisc" class="form-control">
                                <option value="fq_codel" <?=($cur_qdisc === 'fq_codel') ? 'selected' : ''?>>fq_codel (Fair Queuing Controlled Delay - Standar Modern)</option>
                                <option value="fq" <?=($cur_qdisc === 'fq') ? 'selected' : ''?>>fq (Fair Queuing Pacing - Sangat optimal untuk BBR)</option>
                                <option value="cake" <?=($cur_qdisc === 'cake') ? 'selected' : ''?>>cake (Common Applications Kept Enhanced)</option>
                                <option value="pfifo_fast" <?=($cur_qdisc === 'pfifo_fast') ? 'selected' : ''?>>pfifo_fast (FIFO Legacy)</option>
                            </select>
                            <span class="help-block">Algoritma antrian paket keluar Linux kernel.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="tcp_fastopen">TCP Fast Open (TFO)</label>
                        <div class="col-sm-4">
                            <select name="tcp_fastopen" id="tcp_fastopen" class="form-control">
                                <option value="3" <?=($cur_fastopen == '3') ? 'selected' : ''?>>3 (Enable Client &amp; Server TFO)</option>
                                <option value="1" <?=($cur_fastopen == '1') ? 'selected' : ''?>>1 (Enable Client Only)</option>
                                <option value="0" <?=($cur_fastopen == '0') ? 'selected' : ''?>>0 (Disabled)</option>
                            </select>
                            <span class="help-block">Mempercepat handshake TCP tanpa menunggu round-trip tambahan.</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="rmem_max">Max Receive Buffer (rmem_max)</label>
                        <div class="col-sm-4">
                            <input type="text" name="rmem_max" id="rmem_max" class="form-control" value="<?=htmlspecialchars($cur_rmem)?>">
                            <span class="help-block">Ukuran window buffer penerimaan socket TCP (Bytes).</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label" for="wmem_max">Max Transmit Buffer (wmem_max)</label>
                        <div class="col-sm-4">
                            <input type="text" name="wmem_max" id="wmem_max" class="form-control" value="<?=htmlspecialchars($cur_wmem)?>">
                            <span class="help-block">Ukuran window buffer pengiriman socket TCP (Bytes).</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk icon-embed-btn"></i> Simpan &amp; Terapkan Tuning
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/foot.inc'); ?>
