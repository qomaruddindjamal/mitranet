<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Enterprise Packages & System Audit
 * Modern White Enterprise Design System
 */
$pageTitle = 'Packages & System Audit';
$pageSubtitle = 'Cryptographically Verified Enterprise Networking Packages & Hardware Capability Profile';

?>

        <section id="tab-packages" class="tab-pane active">
          <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Device Identity</span>
                <span class="badge badge-success" id="audit-device-badge">ONLINE</span>
              </div>
              <div class="metric-value" id="audit-device-name" style="font-size: 1.15rem; word-break: break-word;">Detecting...</div>
              <p class="metric-sub" id="audit-hostname">Hostname: Detecting...</p>
              <div class="progress-bar"><div class="progress-fill" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Hardware Platform</span>
                <span class="badge badge-cyan" id="audit-profile-badge">DETECTION</span>
              </div>
              <div class="metric-value" id="audit-cpu-summary">Detecting...</div>
              <p class="metric-sub" id="audit-mem-summary">Memory: Detecting...</p>
              <div class="progress-bar"><div class="progress-fill fill-cyan" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">OS Release &amp; Kernel</span>
                <span class="badge badge-indigo">RINJANI</span>
              </div>
              <div class="metric-value">MitraNet <span class="unit">1.0.0-LTS</span></div>
              <p class="metric-sub" id="audit-kernel-summary">Kernel: Detecting...</p>
              <div class="progress-bar"><div class="progress-fill fill-indigo" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Security &amp; Cryptography</span>
                <span class="badge badge-emerald" id="audit-mode-badge">HARDENED</span>
              </div>
              <div class="metric-value" id="audit-aes-val">Detecting...</div>
              <p class="metric-sub" id="audit-crypto-sub">Hardware Acceleration</p>
              <div class="progress-bar"><div class="progress-fill" style="background: var(--accent-emerald); width: 100%;"></div></div>
            </div>
          </div>

          <div class="grid-2-col" style="margin-bottom: 1.5rem;">
            <div class="card">
              <div class="card-header">
                <h3>Hardware Capability &amp; Stability Audit</h3>
                <button class="btn btn-sm btn-secondary" onclick="loadAuditInfo()">Run Audit</button>
              </div>
              <div class="card-body">
                <pre class="code-terminal" id="raw-audit-box" style="height: 260px;">Reading hardware telemetry...</pre>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3>System Maintenance &amp; Update Manager</h3>
              </div>
              <div class="card-body">
                <p class="description">Audit system repository updates and switch between Hardened Appliance mode and Developer mode.</p>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem;">
                  <button class="btn btn-primary" onclick="triggerSystemUpdate()">Check Repository Updates</button>
                  <button class="btn btn-outline" id="btn-toggle-devmode" onclick="toggleDeveloperMode()">Toggle Dev Mode</button>
                </div>
                <pre class="code-terminal" id="raw-update-box" style="height: 180px;">System ready.</pre>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin-bottom: 0.25rem;">Installed Enterprise Packages Inventory</h3>
                <span class="badge badge-cyan" id="package-count-badge">0 Packages</span>
              </div>
              <button class="btn btn-sm btn-secondary" onclick="loadPackagesList()">Query Installed Packages</button>
            </div>
            <div class="card-body">
              <p class="description">Real cryptographically verified Debian packages installed on this router appliance.</p>
              <div class="data-table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Package Name</th>
                      <th>Package Version</th>
                      <th>Operational Status</th>
                    </tr>
                  </thead>
                  <tbody id="packages-table-tbody">
                    <tr><td colspan="3" style="text-align:center;padding:1.5rem;color:var(--text-dim);">Querying installed package database via dpkg-query...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadPackagesAndAudit();
});
</script>
