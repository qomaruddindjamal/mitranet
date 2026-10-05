<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - QoS & Bandwidth Shaper
 * Modern White Enterprise Design System
 */
$pageTitle = 'QoS & Bandwidth (CAKE SQM)';
$pageSubtitle = 'DiffServ-Aware Smart Queue Management, BBR Latency Optimization & Anti-Bufferbloat';

?>

        <section id="tab-qos" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h3>Bandwidth & Smart Queue Management (CAKE) Control</h3>
              <div class="card-actions">
                <button class="btn btn-secondary" id="btn-unlimited-qos" onclick="applyQoS('stop')">Set Unlimited (Wire-Speed)</button>
                <button class="btn btn-primary" id="btn-enable-qos" onclick="applyQoS('start')">Apply Custom Shaper</button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">
                Mode bawaan (default) MitraNet adalah <strong>Unlimited / Wire-Speed</strong> (berjalan penuh sesuai
                kecepatan fisik port 1G/10G tanpa batasan).
                Jika Anda ingin membatasi kecepatan bandwidth untuk jalur ISP tertentu atau mencegah
                <em>bufferbloat</em>, tentukan nilai batas di bawah ini dan klik <em>Apply Custom Shaper</em>.
              </p>
              <div class="form-grid">
                <div class="form-group">
                  <label for="wan-if-select">WAN Interface</label>
                  <input type="text" id="wan-if-select" class="form-control" value="auto"
                    placeholder="auto (e.g. eth0, enp0s3)">
                </div>
                <div class="form-group">
                  <label for="qos-down-rate">Download Ceiling (e.g. unlimited, 100000kbit, 1000mbit)</label>
                  <input type="text" id="qos-down-rate" class="form-control" value="unlimited" placeholder="unlimited">
                </div>
                <div class="form-group">
                  <label for="qos-up-rate">Upload Ceiling (e.g. unlimited, 100000kbit, 1000mbit)</label>
                  <input type="text" id="qos-up-rate" class="form-control" value="unlimited" placeholder="unlimited">
                </div>
              </div>
            </div>
          </div>
        </section>


