<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - High Availability (VRRP)
 * Modern White Enterprise Design System
 */
$pageTitle = 'High Availability (VRRP)';
$pageSubtitle = 'Active/Passive Dual-Router Redundancy (RFC 5798) & Conntrackd State Synchronization';

?>

        <section id="tab-ha" class="tab-pane active">
          <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Cluster State</span>
                <span class="badge badge-success" id="ha-state-badge">MASTER</span>
              </div>
              <div class="metric-value" id="ha-state-title">VRRP Active <span class="unit">Node</span></div>
              <p class="metric-sub">Virtual Router Redundancy Protocol (RFC 5798)</p>
              <div class="progress-bar"><div class="progress-fill" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Virtual Router ID</span>
                <span class="badge badge-cyan">VRID 51</span>
              </div>
              <div class="metric-value">VIP <span class="unit">192.168.1.1</span></div>
              <p class="metric-sub">Shared default gateway address</p>
              <div class="progress-bar"><div class="progress-fill fill-cyan" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Node Priority</span>
                <span class="badge badge-indigo">PRIORITY 100</span>
              </div>
              <div class="metric-value">Master <span class="unit">(Preempt)</span></div>
              <p class="metric-sub">Sub-second heartbeat health check</p>
              <div class="progress-bar"><div class="progress-fill fill-indigo" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Conntrack Sync</span>
                <span class="badge badge-amber">CONNTRACKD</span>
              </div>
              <div class="metric-value">Stateful <span class="unit">Failover</span></div>
              <p class="metric-sub">Zero TCP session drops on cluster failover</p>
              <div class="progress-bar"><div class="progress-fill" style="background: var(--accent-amber); width: 100%;"></div></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>VRRP Cluster Redundancy &amp; Conntrack Synchronization</h3>
              <div class="card-actions">
                <button class="btn btn-sm btn-secondary" onclick="loadHAStatus()">Refresh Cluster</button>
                <button class="btn btn-sm btn-primary" id="btn-toggle-ha" onclick="toggleHA()">Toggle HA Service</button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">Enterprise active/passive dual-router clustering with Keepalived VRRP and Conntrackd connection table synchronization.</p>
              <pre class="code-terminal" id="raw-ha-box">Loading High Availability cluster status...</pre>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadHAStatus();
});
</script>


