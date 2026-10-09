<?php
/*
 * system_reboot.php - System Reboot Confirmation Page
 * MitraNet Rinjani 1.0.2
 */
require_once(__DIR__ . '/includes/api.inc');

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reboot'])) {
    $res = MitraNetApi::systemReboot();
    if ($res['status'] === 200 && !empty($res['data']['success'])) {
        $msg = 'Perintah restart diterima. Perangkat akan restart dalam beberapa detik...';
        $msg_type = 'success';
    } else {
        $err = $res['data']['error'] ?? 'Gagal mengirim perintah restart.';
        $msg = htmlspecialchars($err);
        $msg_type = 'danger';
    }
}

$pgtitle = array('System', 'Restart');
require_once(__DIR__ . '/includes/head.inc');
$selected_menu = 'system';
?>
<div class="content-header">
    <h1><i class="fa-solid fa-rotate-right"></i> Restart Sistem</h1>
    <p class="text-muted">Restart perangkat MitraNet. Semua koneksi aktif akan terputus sementara.</p>
</div>

<div class="card" style="max-width:520px; margin: 2rem auto;">
    <div class="card-header" style="background: linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border-radius:8px 8px 0 0; padding:1rem 1.25rem;">
        <h2 style="margin:0;font-size:1.1rem;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:.5rem;"></i> Konfirmasi Restart</h2>
    </div>
    <div class="card-body" style="padding:1.5rem;">
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>" style="padding:.75rem 1rem; border-radius:6px; margin-bottom:1rem;
            background: <?= $msg_type === 'success' ? '#d1fae5' : '#fee2e2' ?>;
            color: <?= $msg_type === 'success' ? '#065f46' : '#991b1b' ?>;
            border: 1px solid <?= $msg_type === 'success' ? '#6ee7b7' : '#fca5a5' ?>;">
            <i class="fa-solid <?= $msg_type === 'success' ? 'fa-check-circle' : 'fa-circle-xmark' ?>"></i>
            <?= $msg ?>
        </div>
        <?php if ($msg_type === 'success'): ?>
        <div id="countdown-box" style="text-align:center; padding:1rem;">
            <i class="fa-solid fa-spinner fa-spin fa-2x" style="color:#d97706;"></i>
            <p style="margin-top:.75rem; color:#92400e;">Menunggu perangkat kembali online... <span id="countdown">90</span> detik</p>
        </div>
        <script>
        var c = 90;
        var t = setInterval(function(){
            c--;
            document.getElementById('countdown').textContent = c;
            if(c <= 0){ clearInterval(t); window.location.href = '/index.php'; }
        }, 1000);
        </script>
        <?php endif; ?>
        <?php else: ?>
        <p>Apakah Anda yakin ingin <strong>me-restart</strong> perangkat MitraNet?</p>
        <ul style="color:#6b7280; font-size:.9rem; margin-bottom:1.5rem;">
            <li>Semua koneksi aktif akan terputus sementara.</li>
            <li>Proses restart membutuhkan waktu sekitar 1–2 menit.</li>
            <li>Pastikan tidak ada konfigurasi yang sedang dalam proses.</li>
        </ul>
        <form method="POST" action="/system_reboot.php">
            <div class="d-flex" style="gap:.75rem;">
                <button type="submit" name="confirm_reboot" value="1" class="btn btn-warning" style="flex:1;">
                    <i class="fa-solid fa-rotate-right" style="margin-right:.4rem;"></i> Ya, Restart Sekarang
                </button>
                <a href="/index.php" class="btn btn-secondary" style="flex:1; text-align:center; display:inline-block; padding:.5rem 1rem;">
                    <i class="fa-solid fa-xmark" style="margin-right:.4rem;"></i> Batal
                </a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php require_once(__DIR__ . '/includes/foot.inc'); ?>
