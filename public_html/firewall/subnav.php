<?php
/**
 * MitraNet Network OS - Shared Firewall Topbar & Subnav Include
 * Modern White Enterprise Design System
 */
$currentFw = $currentFw ?? 'rules';
?>
          <!-- Apply Changes Alert Banner (MitraNet Enterprise Architecture) -->
          <div id="fw-apply-alert" class="hidden" style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: 8px; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.08);">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
              <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#d97706" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
              <div>
                <strong style="color: #92400e; font-size: 0.95rem;">The firewall configuration has been changed.</strong>
                <div style="font-size: 0.82rem; color: #b45309;">The changes must be applied for them to take effect in the active nftables engine.</div>
              </div>
            </div>
            <button class="btn btn-sm btn-primary" id="btn-apply-fw-changes" onclick="applyFirewallPendingChanges()" style="background: #d97706; border-color: #d97706; font-weight: 700;">Apply Changes</button>
          </div>

          <!-- Firewall Metric Overview -->
          <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Firewall Engine</span>
                <span class="badge badge-success" id="fw-engine-badge">NFTABLES ACTIVE</span>
              </div>
              <div class="metric-value" id="fw-engine-title">FastPath <span class="unit">Offload</span></div>
              <p class="metric-sub">Kernel flowtable wire-speed 1G/10G/100G bypass</p>
              <div class="progress-bar">
                <div class="progress-fill" style="width: 100%;"></div>
              </div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Active Connections</span>
                <span class="badge badge-cyan" id="fw-conntrack-badge">CONNTRACK</span>
              </div>
              <div class="metric-value" id="fw-active-conns">-- <span class="unit">sessions</span></div>
              <p class="metric-sub">Max capacity: <strong>262,144</strong> states</p>
              <div class="progress-bar">
                <div class="progress-fill fill-cyan" id="fw-conntrack-bar" style="width: 10%;"></div>
              </div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Anti-DDoS Shield</span>
                <span class="badge badge-indigo" id="fw-ddos-badge">PROTECTED</span>
              </div>
              <div class="metric-value">SYN/ICMP <span class="unit">Guard</span></div>
              <p class="metric-sub">Rate limit: 100 syn/s burst 200</p>
              <div class="progress-bar">
                <div class="progress-fill fill-indigo" style="width: 100%;"></div>
              </div>
            </div>

            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Threat Interceptions</span>
                <span class="badge badge-rose" id="fw-drops-badge">DROPPED</span>
              </div>
              <div class="metric-value" id="fw-drops-count">-- <span class="unit">packets</span></div>
              <p class="metric-sub">Port scans, bogons & banned IPs</p>
              <div class="progress-bar">
                <div class="progress-fill" id="fw-drops-bar" style="background: var(--accent-rose); width: 10%;"></div>
              </div>
            </div>
          </div>

          <!-- Security Hardening Toggles -->
          <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
              <h3>Security Hardening & Acceleration Policies</h3>
              <button class="btn btn-sm btn-primary" onclick="saveSecurityPolicies()">Apply Security Policies</button>
            </div>
            <div class="card-body">
              <div class="security-toggles-grid">
                <div class="security-toggle-card">
                  <div class="sec-toggle-info">
                    <span class="sec-toggle-title">FastPath Flowtable</span>
                    <span class="sec-toggle-desc">Bypass Linux TCP stack for wire-speed (1G/10G) line-rate routing</span>
                  </div>
                  <label class="switch">
                    <input type="checkbox" id="sec-toggle-flowtable" checked>
                    <span class="slider"></span>
                  </label>
                </div>

                <div class="security-toggle-card">
                  <div class="sec-toggle-info">
                    <span class="sec-toggle-title">Anti-DDoS SYN Flood Guard</span>
                    <span class="sec-toggle-desc">Dynamic rate-limiting on suspicious TCP handshakes</span>
                  </div>
                  <label class="switch">
                    <input type="checkbox" id="sec-toggle-synflood" checked>
                    <span class="slider"></span>
                  </label>
                </div>

                <div class="security-toggle-card">
                  <div class="sec-toggle-info">
                    <span class="sec-toggle-title">Stealth Port Scan Protection</span>
                    <span class="sec-toggle-desc">Drop Xmas, Null, and invalid TCP flag probes</span>
                  </div>
                  <label class="switch">
                    <input type="checkbox" id="sec-toggle-portscan" checked>
                    <span class="slider"></span>
                  </label>
                </div>

                <div class="security-toggle-card">
                  <div class="sec-toggle-info">
                    <span class="sec-toggle-title">WAN Ping Response (ICMP Echo)</span>
                    <span class="sec-toggle-desc">Respond to ping requests arriving from public WAN</span>
                  </div>
                  <label class="switch">
                    <input type="checkbox" id="sec-toggle-wanping">
                    <span class="slider"></span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Subnav for Firewall Modules (MitraNet Multi-Page Navigation) -->
          <div class="subnav-tabs">
            <a class="subnav-tab <?= $currentFw === 'rules' ? 'active' : '' ?>" id="fw-tab-btn-rules" href="rules.php">Filter Rules</a>
            <a class="subnav-tab <?= $currentFw === 'aliases' ? 'active' : '' ?>" id="fw-tab-btn-aliases" href="aliases.php">Aliases</a>
            <a class="subnav-tab <?= $currentFw === 'forwards' ? 'active' : '' ?>" id="fw-tab-btn-forwards" href="nat.php">Port Forward (NAT)</a>
            <a class="subnav-tab <?= $currentFw === '1to1' ? 'active' : '' ?>" id="fw-tab-btn-1to1" href="nat_1to1.php">1:1 NAT</a>
            <a class="subnav-tab <?= $currentFw === 'outbound' ? 'active' : '' ?>" id="fw-tab-btn-outbound" href="nat_out.php">Outbound NAT</a>
            <a class="subnav-tab <?= $currentFw === 'blacklist' ? 'active' : '' ?>" id="fw-tab-btn-blacklist" href="blacklist.php">Threat Blacklist (Bans)</a>
            <a class="subnav-tab <?= $currentFw === 'logs' ? 'active' : '' ?>" id="fw-tab-btn-logs" href="logs.php">Live Threat Logs & Ruleset</a>
          </div>
