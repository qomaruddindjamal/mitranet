<?php
/*
 * system_halt.php - System Halt (Shutdown) Confirmation Page
 * MitraNet Rinjani 1.0.2
 */
require_once(__DIR__ . '/includes/api.inc');

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_halt'])) {
    $res = MitraNetApi::systemHalt();
    if ($res['status'] === 200 && !empty($res['data']['success'])) {
        $msg = 'Perintah shutdown diterima. Perangkat akan mati dalam beberapa detik. Nyalakan kembali secara manual menggunakan tombol power.';
        $msg_type = 'success';
    } else {
        $err = $res['data']['error'] ?? 'Gagal mengirim perintah shutdown.';
        $msg = htmlspecialchars($err);
        $msg_type = 'danger';
    }
}

$pgtitle = array('System', 'Shutdown');
require_once(__DIR__ . '/includes/head.inc');
$selected_menu = 'system';
?>
<div class="content-header">
    <h1><i class="fa-solid fa-power-off"></i> Shutdown Sistem</h1>
    <p class="text-muted">Matikan perangkat MitraNet sepenuhnya. Perangkat harus dinyalakan secara manual setelahnya.</p>
</div>

<div class="card" style="max-width:520px; margin: 2rem auto;">
    <div class="card-header" style="background: linear-gradient(135deg,#ef4444,#b91c1c); color:#fff; border-radius:8px 8px 0 0; padding:1rem 1.25rem;">
        <h2 style="margin:0;font-size:1.1rem;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:.5rem;"></i> Konfirmasi Shutdown</h2>
    </div>
    <div class="card-body" style="padding:1.5rem;">
        <?php if ($msg): ?>
        <div class="alert" style="padding:.75rem 1rem; border-radius:6px; margin-bottom:1rem;
            background: <?= $msg_type === 'success' ? '#d1fae5' : '#fee2e2' ?>;
            color: <?= $msg_type === 'success' ? '#065f46' : '#991b1b' ?>;
            border: 1px solid <?= $msg_type === 'success' ? '#6ee7b7' : '#fca5a5' ?>;">
            <i class="fa-solid <?= $msg_type === 'success' ? 'fa-power-off' : 'fa-circle-xmark' ?>"></i>
            <?= $msg ?>
        </div>
        <?php if ($msg_type === 'success'): ?>
        <div style="text-align:center; padding:1rem; color:#7f1d1d;">
            <i class="fa-solid fa-power-off fa-2x"></i>
            <p style="margin-top:.75rem;">Perangkat sedang mati. Koneksi ke WebUI akan terputus.</p>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <p>Apakah Anda yakin ingin <strong>mematikan</strong> perangkat MitraNet?</p>
        <ul style="color:#6b7280; font-size:.9rem; margin-bottom:1.5rem;">
            <li>Semua layanan jaringan akan berhenti.</li>
            <li>Perangkat harus dinyalakan secara manual (tombol power).</li>
            <li>Pastikan tidak ada pengguna aktif yang sedang terhubung.</li>
        </ul>
        <form method="POST" action="/system_halt.php">
            <div class="d-flex" style="gap:.75rem;">
                <button type="submit" name="confirm_halt" value="1" class="btn btn-danger" style="flex:1;">
                    <i class="fa-solid fa-power-off" style="margin-right:.4rem;"></i> Ya, Shutdown Sekarang
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
