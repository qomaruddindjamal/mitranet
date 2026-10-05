<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Modular Dashboard
 * Modern White Enterprise Design System
 */
$pageTitle = 'Appliance Dashboard';
$pageSubtitle = 'Real-time System Telemetry, Wire-Speed Gateways, Flowtables & Daemon Monitor';

?>

        <section id="tab-dashboard" class="tab-pane active">
          <!-- Metric Cards -->
          <div class="metrics-grid">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">System Uptime</span>
                <span class="badge badge-amber" id="dash-hostname">mitranet-router</span>
              </div>
              <div class="metric-value" id="dash-uptime">Loading...</div>
              <p class="metric-sub" id="dash-loadavg">Load: 0.00, 0.00, 0.00</p>
              <div class="progress-bar">
                <div class="progress-fill" style="width: 100%;"></div>
              </div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">CPU Performance</span>
                <span class="badge badge-cyan" id="dash-cpu-cores">Cores: 1</span>
              </div>
              <div class="metric-value" id="dash-cpu-model">x86_64 Multi-Core</div>
              <p class="metric-sub" id="dash-cpu-sub">Hardware AES-NI • Anti-OOM Guard</p>
              <div class="progress-bar">
                <div class="progress-fill fill-cyan" id="dash-cpu-bar" style="width: 20%;"></div>
              </div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">System Memory (RAM)</span>
                <span class="badge badge-indigo" id="dash-ram-percent">0%</span>
              </div>
              <div class="metric-value" id="dash-ram-usage">-- / -- MB</div>
              <p class="metric-sub" id="dash-tuning-profile">Standard / SOHO Router</p>
              <div class="progress-bar">
                <div class="progress-fill fill-indigo" id="dash-ram-bar" style="width: 25%;"></div>
              </div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">NFTables FastPath</span>
                <span class="badge badge-success" id="dash-fastpath-badge">WIRE-SPEED</span>
              </div>
              <div class="metric-value">Unlimited <span class="unit">1G/10G/100G</span></div>
              <p class="metric-sub"><span id="dash-iface-count">4 Active Interfaces</span> • TCP BBR</p>
              <div class="progress-bar">
                <div class="progress-fill" style="width: 100%;"></div>
              </div>
            </div>
          </div>

          <!-- Performance & Bandwidth Gauge Section -->
          <div class="grid-2-col">
            <div class="card">
              <div class="card-header">
                <h3>Bandwidth & Line-Rate Performance Profile</h3>
                <span class="badge badge-pill badge-pass">Unlimited Wire-Speed</span>
              </div>
              <div class="card-body">
                <div class="sqm-profile-spec">
                  <div class="spec-row">
                    <span class="spec-label">Default Bandwidth Mode</span>
                    <span class="spec-val text-success">Unlimited (Full 1G/10G/100G Line-Rate)</span>
                  </div>
                  <div class="spec-row">
                    <span class="spec-label">Artificial Speed Cap</span>
                    <span class="spec-val">None (Hardware Wire-Speed Unthrottled)</span>
                  </div>
                  <div class="spec-row">
                    <span class="spec-label">TCP Congestion Control</span>
                    <span class="spec-val text-success">BBR v2 Low-Latency Congestion Avoidance</span>
                  </div>
                  <div class="spec-row">
                    <span class="spec-label">Hardware Flow Offload</span>
                    <span class="spec-val">NFTables FastPath Flowtable Active</span>
                  </div>
                  <div class="spec-row">
                    <span class="spec-label">Smart Queue (CAKE SQM)</span>
                    <span class="spec-val">Available On-Demand (Bypassed by Default)</span>
                  </div>
                  <div class="spec-row">
                    <span class="spec-label">Diffserv Multi-Queue Priority</span>
                    <span class="spec-val">diffserv4 (Voice, Video, BestEffort, Bulk)</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3>Active Network Interfaces</h3>
                <button class="btn btn-sm btn-secondary" onclick="loadInterfaces()">Refresh</button>
              </div>
              <div class="card-body">
                <div class="terminal-box" id="interfaces-display">
                  Loading interfaces...
                </div>
              </div>
            </div>
          </div>

          <!-- Gateway Health & Services Overview (MitraNet Enterprise Architecture) -->
          <div class="grid-2-col" style="margin-top: 1.5rem;">
            <!-- Gateway Health Monitoring -->
            <div class="card">
              <div class="card-header">
                <h3>Gateway Health & Latency Monitor</h3>
                <button class="btn btn-sm btn-secondary" onclick="loadGateways()">Refresh</button>
              </div>
              <div class="card-body">
                <div class="data-table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Gateway</th>
                        <th>Monitor</th>
                        <th>RTT</th>
                        <th>Loss</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody id="dash-gateways-tbody">
                      <tr>
                        <td colspan="6" style="text-align:center;padding:1.25rem;color:var(--text-dim);">Loading gateway telemetry...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- System Services Status & Control -->
            <div class="card">
              <div class="card-header">
                <h3>System Daemons & Services</h3>
                <button class="btn btn-sm btn-secondary" onclick="loadServicesStatus()">Refresh</button>
              </div>
              <div class="card-body">
                <div class="data-table-container" style="max-height: 300px; overflow-y: auto;">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Service</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                      </tr>
                    </thead>
                    <tbody id="dash-services-tbody">
                      <tr>
                        <td colspan="4" style="text-align:center;padding:1.25rem;color:var(--text-dim);">Loading system services...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadDashboard();
  loadGateways();
  loadServicesStatus();
});
</script>


