<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - DNS Guard & Adblock
 * Modern White Enterprise Design System
 */
$pageTitle = 'DNS Guard & Adblock';
$pageSubtitle = 'Network-Wide DNS Sinkhole, Anti-Telemetry & Malware Domain Filtering';

?>

        <section id="tab-adblock" class="tab-pane active">
          <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">DNS Guard State</span>
                <span class="badge badge-success" id="adblock-status-badge">ACTIVE</span>
              </div>
              <div class="metric-value" id="adblock-state-val">Protected <span class="unit">Sinkhole</span></div>
              <p class="metric-sub">Zero-latency DNS sinkhole on 127.0.0.1</p>
              <div class="progress-bar"><div class="progress-fill" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Blocked Domains</span>
                <span class="badge badge-rose" id="adblock-rules-badge">TELEMETRY / ADS</span>
              </div>
              <div class="metric-value" id="adblock-blocked-count">142,500+ <span class="unit">domains</span></div>
              <p class="metric-sub">StevenBlack unified hosts feed</p>
              <div class="progress-bar"><div class="progress-fill" style="background: var(--accent-rose); width: 85%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">DNS Upstream Resolvers</span>
                <span class="badge badge-cyan">ENCRYPTED</span>
              </div>
              <div class="metric-value">1.1.1.1 <span class="unit">/ 8.8.8.8</span></div>
              <p class="metric-sub">Quad9 / Cloudflare Low-Latency DNS</p>
              <div class="progress-bar"><div class="progress-fill fill-cyan" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Memory Footprint</span>
                <span class="badge badge-indigo">ANTI-OOM</span>
              </div>
              <div class="metric-value">&lt; 12 <span class="unit">MB RAM</span></div>
              <p class="metric-sub">Ultra-efficient in-memory hash set</p>
              <div class="progress-bar"><div class="progress-fill fill-indigo" style="width: 25%;"></div></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>DNS Sinkhole &amp; Content Guard Controls</h3>
              <div class="card-actions">
                <button class="btn btn-sm btn-secondary" onclick="loadAdblockStatus()">Refresh Status</button>
                <button class="btn btn-sm btn-primary" id="btn-toggle-adblock" onclick="toggleAdblock()">Toggle Protection</button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">Network-wide ad blocking, malware domain sinkholing, and telemetry tracking prevention for all connected LAN and Wi-Fi clients without requiring client software.</p>
              <div class="terminal-box" id="adblock-telemetry-box">Loading DNS Guard status...</div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadAdblockStatus();
});
</script>


