<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Security Zones (ZBF)
 * Modern White Enterprise Design System
 */
$pageTitle = 'Security Zones (ZBF)';
$pageSubtitle = 'Zero-Trust Zone-Based Policy Architecture & Directional Isolation Matrix';

?>

        <section id="tab-zones" class="tab-pane active">
          <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">ZBF Engine</span>
                <span class="badge badge-success">ZERO-TRUST</span>
              </div>
              <div class="metric-value">Zone-Based <span class="unit">Firewall</span></div>
              <p class="metric-sub">Stateful inter-zone security policies</p>
              <div class="progress-bar"><div class="progress-fill" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Security Zones</span>
                <span class="badge badge-cyan" id="zones-count-badge">4 ZONES</span>
              </div>
              <div class="metric-value" id="zones-count-val">4 <span class="unit">configured</span></div>
              <p class="metric-sub">trust, untrust, dmz, vpn</p>
              <div class="progress-bar"><div class="progress-fill fill-cyan" style="width: 80%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Zone Policies</span>
                <span class="badge badge-indigo" id="zone-policies-badge">ACTIVE</span>
              </div>
              <div class="metric-value" id="zone-policies-count">5 <span class="unit">rules</span></div>
              <p class="metric-sub">Strict inter-zone directional isolation</p>
              <div class="progress-bar"><div class="progress-fill fill-indigo" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">IDS/IPS Inspection</span>
                <span class="badge badge-rose">SURICATA</span>
              </div>
              <div class="metric-value">Deep Packet <span class="unit">Scan</span></div>
              <p class="metric-sub">Flow analysis &amp; threat suppression</p>
              <div class="progress-bar"><div class="progress-fill" style="background: var(--accent-rose); width: 100%;"></div></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>Security Zones &amp; Policy Table</h3>
              <div class="card-actions">
                <button class="btn btn-sm btn-secondary" onclick="loadSecurityZones()">Refresh Zones</button>
                <button class="btn btn-sm btn-primary" onclick="showAddZonePolicyModal()">+ Add Zone Policy</button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">Directional Security Policies enforcing Zero-Trust traffic control between ingress and egress security zones.</p>
              <div class="data-table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Source Zone</th>
                      <th>Destination Zone</th>
                      <th>Policy Action</th>
                      <th>Stateful Inspection</th>
                      <th>Description</th>
                      <th style="text-align: right;">Manage</th>
                    </tr>
                  </thead>
                  <tbody id="zones-table-tbody">
                    <tr><td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-dim);">Loading security zones...</td></tr>
                  </tbody>
                </table>
              </div>

              <!-- Add Zone Policy Form -->
              <div id="add-zone-policy-panel" class="hidden" style="margin-top: 1.5rem; padding: 1.25rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px;">
                <h4 style="margin-bottom: 1rem; color: var(--accent-primary);">Create Inter-Zone Security Policy</h4>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="new-policy-from">Source (From Zone)</label>
                    <select id="new-policy-from" class="form-control">
                      <option value="trust">trust (LAN internal)</option>
                      <option value="untrust">untrust (WAN Internet)</option>
                      <option value="dmz">dmz (Public Services)</option>
                      <option value="vpn">vpn (Remote Access)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="new-policy-to">Destination (To Zone)</label>
                    <select id="new-policy-to" class="form-control">
                      <option value="untrust">untrust (WAN Internet)</option>
                      <option value="trust">trust (LAN internal)</option>
                      <option value="dmz">dmz (Public Services)</option>
                      <option value="vpn">vpn (Remote Access)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="new-policy-action">Action</label>
                    <select id="new-policy-action" class="form-control">
                      <option value="permit">PERMIT (Allow Stateful Flow)</option>
                      <option value="reject">REJECT (Reset TCP / ICMP Port Unreachable)</option>
                      <option value="drop">DROP (Drop Silently)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="new-policy-desc">Policy Description</label>
                    <input type="text" id="new-policy-desc" class="form-control" placeholder="e.g. Allow LAN Users to WAN Internet">
                  </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                  <button class="btn btn-secondary" onclick="hideAddZonePolicyModal()">Cancel</button>
                  <button class="btn btn-primary" onclick="submitAddZonePolicy()">Apply Zone Policy</button>
                </div>
              </div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadSecurityZones();
});
</script>


