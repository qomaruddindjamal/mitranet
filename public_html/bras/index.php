<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Subscriber BRAS / AAA
 * Modern White Enterprise Design System
 */
$pageTitle = 'Subscriber BRAS & RADIUS AAA';
$pageSubtitle = 'Broadband Remote Access Server (PPPoE / IPoE), FreeRADIUS Accounting & IP Pools';

?>

        <section id="tab-bras" class="tab-pane active">
          <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">BRAS Engine</span>
                <span class="badge badge-success">ACTIVE</span>
              </div>
              <div class="metric-value">PPPoE <span class="unit">Server</span></div>
              <p class="metric-sub">Kernel-mode pppoe / accel-ppp termination</p>
              <div class="progress-bar"><div class="progress-fill" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">FreeRADIUS AAA</span>
                <span class="badge badge-cyan" id="bras-radius-badge">RADIUS READY</span>
              </div>
              <div class="metric-value">FreeRADIUS <span class="unit">v3</span></div>
              <p class="metric-sub">Centralized authentication, authorization &amp; accounting</p>
              <div class="progress-bar"><div class="progress-fill fill-cyan" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Subscriber IP Pool</span>
                <span class="badge badge-indigo">10.100.0.0/16</span>
              </div>
              <div class="metric-value">65,534 <span class="unit">IPs</span></div>
              <p class="metric-sub">Dynamic IP allocation &amp; lease management</p>
              <div class="progress-bar"><div class="progress-fill fill-indigo" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">NetFlow Accounting</span>
                <span class="badge badge-amber">PMACCT</span>
              </div>
              <div class="metric-value">IPFIX / <span class="unit">v9</span></div>
              <p class="metric-sub">Per-subscriber bandwidth metering &amp; billing</p>
              <div class="progress-bar"><div class="progress-fill" style="background: var(--accent-amber); width: 100%;"></div></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>Broadband Remote Access Server (BRAS) Status &amp; Subscriber Sessions</h3>
              <button class="btn btn-sm btn-primary" onclick="loadBRASStatus()">Refresh BRAS</button>
            </div>
            <div class="card-body">
              <p class="description">Broadband aggregation terminating subscriber PPPoE/IPoE tunnels, enforcing per-user bandwidth profiles via CAKE/HTB, and logging to FreeRADIUS.</p>
              <pre class="code-terminal" id="raw-bras-box">Loading subscriber sessions...</pre>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadBRASStatus();
});
</script>


