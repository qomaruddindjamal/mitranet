<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Modular Interfaces & Links
 * Modern White Enterprise Design System
 */
$pageTitle = 'Interfaces & Virtual Links';
$pageSubtitle = 'Physical Ethernet, 802.1Q VLANs, Vether Pairs, EoIP Tunnels & VXLAN Overlays';

$activeSub = $_GET['sub'] ?? 'physical';

?>

        <section id="tab-interfaces" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h3><svg class="svg-inline text-primary" viewBox="0 0 24 24" width="20" height="20" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="2" width="20" height="8" rx="2"></rect>
                  <rect x="2" y="14" width="20" height="8" rx="2"></rect>
                  <line x1="6" y1="6" x2="6.01" y2="6"></line>
                  <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg> Interface & Virtual Links Management</h3>
              <button class="btn btn-sm btn-primary" onclick="loadInterfaces()"><svg viewBox="0 0 24 24" width="14"
                  height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                  stroke-linejoin="round">
                  <polyline points="23 4 23 10 17 10"></polyline>
                  <polyline points="1 20 1 14 7 14"></polyline>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg> Reload</button>
            </div>
            <div class="card-body">
              <div class="subnav-tabs">
                <button class="subnav-tab <?= $activeSub === 'physical' ? 'active' : '' ?>" id="iface-tab-btn-physical" onclick="switchIfaceSubTab('physical')">
                  <svg class="svg-inline" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="14" rx="2"></rect>
                    <line x1="6" y1="16" x2="6" y2="20"></line>
                    <line x1="10" y1="16" x2="10" y2="22"></line>
                    <line x1="14" y1="16" x2="14" y2="22"></line>
                    <line x1="18" y1="16" x2="18" y2="20"></line>
                  </svg> Physical Interfaces
                </button>
                <button class="subnav-tab <?= $activeSub === 'vether' ? 'active' : '' ?>" id="iface-tab-btn-vether" onclick="switchIfaceSubTab('vether')">
                  <svg class="svg-inline text-success" viewBox="0 0 24 24" width="15" height="15" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 16l-4-4 4-4"></path>
                    <path d="M3 12h18"></path>
                    <path d="M17 8l4 4-4 4"></path>
                  </svg> Vether (Virtual Ethernet)
                </button>
                <button class="subnav-tab <?= $activeSub === 'vlan' ? 'active' : '' ?>" id="iface-tab-btn-vlan" onclick="switchIfaceSubTab('vlan')">
                  <svg class="svg-inline text-info" viewBox="0 0 24 24" width="15" height="15" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                  </svg> VLAN (802.1Q)
                </button>
                <button class="subnav-tab <?= $activeSub === 'eoip' ? 'active' : '' ?>" id="iface-tab-btn-eoip" onclick="switchIfaceSubTab('eoip')">
                  <svg class="svg-inline text-warning" viewBox="0 0 24 24" width="15" height="15" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                  </svg> EoIP Tunnel
                </button>
                <button class="subnav-tab <?= $activeSub === 'vxlan' ? 'active' : '' ?>" id="iface-tab-btn-vxlan" onclick="switchIfaceSubTab('vxlan')">
                  <svg class="svg-inline text-primary" viewBox="0 0 24 24" width="15" height="15" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path>
                    <polyline points="12 13 12 8"></polyline>
                    <polyline points="9 11 12 8 15 11"></polyline>
                  </svg> VXLAN Overlay
                </button>
              </div>

              <!-- Physical Links Pane (Auto-Detects 1 to 54+ Ethernet Interfaces) -->
              <div class="iface-subpane <?= $activeSub === 'physical' ? '' : 'hidden' ?>" id="iface-subtab-physical">
                
                <!-- Interface Detection Status Banner -->
                <div class="iface-summary-bar">
                  <div class="iface-stat-card">
                    <span class="iface-stat-title">Detected Physical Ports</span>
                    <span class="iface-stat-value" id="iface-count-val">Detecting...</span>
                    <span class="iface-stat-badge" id="iface-mode-badge">Auto-Calibrating</span>
                  </div>
                  <div class="iface-stat-card">
                    <span class="iface-stat-title">Link State & Connectivity</span>
                    <span class="iface-stat-value text-success" id="iface-up-val">-</span>
                    <span class="iface-stat-sub" id="iface-down-val">- Link Down</span>
                  </div>
                  <div class="iface-stat-card">
                    <span class="iface-stat-title">FastPath Line-Rate Acceleration</span>
                    <span class="iface-stat-value text-success">NFTables Flowtable</span>
                    <span class="iface-stat-sub" id="iface-flow-info">Hardware/Software Bypass Active</span>
                  </div>
                  <div class="iface-stat-card">
                    <span class="iface-stat-title">Hardware Detection</span>
                    <span class="iface-stat-value text-primary" id="iface-hw-title">Sysfs Kernel Scanner</span>
                    <span class="iface-stat-sub">1 to 54 Port Auto-Sense</span>
                  </div>
                </div>

                <!-- Hardware Chassis Port Matrix Bay (Visual Port Jacks 1 to 54) -->
                <div class="chassis-panel-container">
                  <div class="chassis-header">
                    <h4>
                      <svg class="svg-inline text-primary" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="8" rx="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                      </svg>
                      Hardware Port Bay (Front Panel View)
                    </h4>
                    <span class="chassis-legend">
                      <span class="legend-item"><span class="led-dot led-up"></span> Link UP (Carrier)</span>
                      <span class="legend-item"><span class="led-dot led-down"></span> Link DOWN</span>
                      <span class="legend-item"><span class="badge-role badge-wan">WAN</span> Internet</span>
                      <span class="legend-item"><span class="badge-role badge-lan">LAN</span> Trust Subnet</span>
                    </span>
                  </div>
                  <div class="chassis-bay" id="chassis-port-grid">
                    <div style="padding:1.5rem; text-align:center; color:var(--text-muted);">
                      Detecting hardware ethernet interfaces...
                    </div>
                  </div>
                </div>

                <!-- Detailed Physical Interfaces Table -->
                <div class="card" style="margin-top: 1.5rem; border: 1px solid var(--border-color);">
                  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h4 style="margin:0;">Physical Ethernet Interfaces Detail</h4>
                    <span class="text-muted" style="font-size: 0.85rem;" id="iface-table-caption">Auto-detected interfaces</span>
                  </div>
                  <div class="card-body" style="padding: 0;">
                    <div class="data-table-container">
                      <table class="data-table" id="physical-iface-table">
                        <thead>
                          <tr>
                            <th style="width: 70px;">Port</th>
                            <th>Interface</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>MAC Address</th>
                            <th>IP Address</th>
                            <th>Speed / Duplex</th>
                            <th>MTU</th>
                            <th>Driver</th>
                            <th>RX / TX Throughput</th>
                            <th style="text-align: right;">Action</th>
                          </tr>
                        </thead>
                        <tbody id="physical-iface-tbody">
                          <tr><td colspan="11" style="text-align:center; padding:2rem; color:var(--text-muted);">Scanning physical network interfaces...</td></tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Collapsible Raw Kernel Output -->
                <div class="card" style="margin-top: 1.5rem; border: 1px solid var(--border-color);">
                  <div class="card-header" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleRawOutput()">
                    <h5 style="margin: 0; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="4 17 10 11 4 5"></polyline>
                        <line x1="12" y1="19" x2="20" y2="19"></line>
                      </svg>
                      Raw Kernel Interface Telemetry (ip link / ip addr)
                    </h5>
                    <button class="btn btn-sm btn-secondary" id="raw-toggle-btn" type="button">Show Raw</button>
                  </div>
                  <div class="card-body" id="raw-output-card" style="display: none;">
                    <pre class="code-terminal" id="raw-interfaces-box">Loading...</pre>
                  </div>
                </div>

              </div>

              <!-- Vether Creation Pane -->
              <div class="iface-subpane <?= $activeSub === 'vether' ? '' : 'hidden' ?>" id="iface-subtab-vether">
                <p class="description">Virtual Ethernet (veth) creates paired virtual interfaces for network namespaces,
                  container routing, and loopback testing.</p>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="veth-name-input">Interface Name</label>
                    <input type="text" id="veth-name-input" class="form-control" placeholder="veth0" value="veth0">
                  </div>
                  <div class="form-group">
                    <label for="veth-peer-input">Peer Name</label>
                    <input type="text" id="veth-peer-input" class="form-control" placeholder="veth0_peer"
                      value="veth0_peer">
                  </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                  <button class="btn btn-primary" onclick="submitCreateVether()"><svg viewBox="0 0 24 24" width="14"
                      height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="16"></line>
                      <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg> Create Vether Pair</button>
                  <button class="btn btn-danger" onclick="submitDeleteVether()"><svg viewBox="0 0 24 24" width="14"
                      height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg> Delete Vether</button>
                </div>
                <div id="vether-result-box" class="terminal-box" style="margin-top: 1rem; display: none;"></div>
              </div>

              <!-- VLAN Creation Pane -->
              <div class="iface-subpane <?= $activeSub === 'vlan' ? '' : 'hidden' ?>" id="iface-subtab-vlan">
                <p class="description">802.1Q tagged sub-interfaces for multi-tenant VLAN segmentation on trunk ports.
                </p>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="vlan-parent-input">Parent Physical Interface</label>
                    <select id="vlan-parent-input" class="form-control">
                      <option value="eth0">eth0 (Auto-detected)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="vlan-id-input">VLAN ID (1-4094)</label>
                    <input type="number" id="vlan-id-input" class="form-control" min="1" max="4094" value="10">
                  </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                  <button class="btn btn-primary" onclick="submitCreateVlan()"><svg viewBox="0 0 24 24" width="14"
                      height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="16"></line>
                      <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg> Create VLAN Sub-interface</button>
                  <button class="btn btn-danger" onclick="submitDeleteVlan()"><svg viewBox="0 0 24 24" width="14"
                      height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg> Delete VLAN</button>
                </div>
                <div id="vlan-result-box" class="terminal-box" style="margin-top: 1rem; display: none;"></div>
              </div>

              <!-- EoIP Creation Pane -->
              <div class="iface-subpane <?= $activeSub === 'eoip' ? '' : 'hidden' ?>" id="iface-subtab-eoip">
                <p class="description">Ethernet over IP (EoIP) Layer 2 Tunneling over standard GRE encapsulation (RFC Protocol 0x6400).
                </p>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="eoip-name-input">Tunnel Interface Name</label>
                    <input type="text" id="eoip-name-input" class="form-control" placeholder="eoip0" value="eoip0">
                  </div>
                  <div class="form-group">
                    <label for="eoip-remote-input">Remote Router IP</label>
                    <input type="text" id="eoip-remote-input" class="form-control" placeholder="192.168.100.2">
                  </div>
                  <div class="form-group">
                    <label for="eoip-local-input">Local Endpoint IP</label>
                    <input type="text" id="eoip-local-input" class="form-control" placeholder="192.168.100.1">
                  </div>
                  <div class="form-group">
                    <label for="eoip-tid-input">Tunnel ID (Key)</label>
                    <input type="number" id="eoip-tid-input" class="form-control" value="1" min="1" max="65535">
                  </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                  <button class="btn btn-primary" onclick="submitCreateEoIP()"><svg viewBox="0 0 24 24" width="14"
                      height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="16"></line>
                      <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg> Establish EoIP Tunnel</button>
                  <button class="btn btn-danger" onclick="submitDeleteEoIP()"><svg viewBox="0 0 24 24" width="14"
                      height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg> Remove Tunnel</button>
                </div>
                <div id="eoip-result-box" class="terminal-box" style="margin-top: 1rem; display: none;"></div>
              </div>

              <!-- VXLAN Creation Pane -->
              <div class="iface-subpane <?= $activeSub === 'vxlan' ? '' : 'hidden' ?>" id="iface-subtab-vxlan">
                <p class="description">Virtual Extensible LAN (VXLAN) RFC 7348 Layer 2 overlay across Layer 3 UDP
                  networks.</p>
                <div class="form-grid">
                  <div class="form-group">
                    <label for="vxlan-name-input">Overlay Interface Name</label>
                    <input type="text" id="vxlan-name-input" class="form-control" placeholder="vxlan0" value="vxlan0">
                  </div>
                  <div class="form-group">
                    <label for="vxlan-vni-input">VNI ID (1-16777215)</label>
                    <input type="number" id="vxlan-vni-input" class="form-control" value="100" min="1">
                  </div>
                  <div class="form-group">
                    <label for="vxlan-remote-input">Remote VTEP IP</label>
                    <input type="text" id="vxlan-remote-input" class="form-control" placeholder="10.0.0.2">
                  </div>
                  <div class="form-group">
                    <label for="vxlan-local-input">Local VTEP IP</label>
                    <input type="text" id="vxlan-local-input" class="form-control" placeholder="10.0.0.1">
                  </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                  <button class="btn btn-primary" onclick="submitCreateVxLAN()"><svg viewBox="0 0 24 24" width="14"
                      height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="12" y1="8" x2="12" y2="16"></line>
                      <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg> Deploy VXLAN Overlay</button>
                  <button class="btn btn-danger" onclick="submitDeleteVxLAN()"><svg viewBox="0 0 24 24" width="14"
                      height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg> Remove VXLAN</button>
                </div>
                <div id="vxlan-result-box" class="terminal-box" style="margin-top: 1rem; display: none;"></div>
              </div>
            </div>
          </div>
        </section>

<!-- Interface IP / Link Configuration Modal -->
<div id="iface-config-modal" class="modal-backdrop" style="display:none;">
  <div class="modal-dialog">
    <div class="modal-header">
      <h4 id="iface-modal-title" style="margin:0;">Configure Interface</h4>
      <button class="modal-close-btn" onclick="closeIfaceModal()" type="button">&times;</button>
    </div>
    <div class="modal-body" style="padding: 1.25rem 0;">
      <input type="hidden" id="modal-iface-name" value="">
      <div class="form-group">
        <label for="modal-iface-mode">IP Assignment Mode</label>
        <select id="modal-iface-mode" class="form-control" onchange="toggleIfaceModeFields()">
          <option value="static">Static IPv4 Address</option>
          <option value="dhcp">DHCP Client (Dynamic Auto-Configuration)</option>
        </select>
      </div>
      <div id="modal-static-fields">
        <div class="form-group" style="margin-top: 1rem;">
          <label for="modal-iface-ip">IPv4 Address & CIDR Subnet</label>
          <input type="text" id="modal-iface-ip" class="form-control" placeholder="192.168.1.1/24">
          <small class="text-muted">Example: 192.168.1.1/24 or 10.0.0.1/16</small>
        </div>
        <div class="form-group" style="margin-top: 1rem;">
          <label for="modal-iface-gw">Default Gateway (Optional)</label>
          <input type="text" id="modal-iface-gw" class="form-control" placeholder="192.168.1.254">
        </div>
      </div>
      <div class="form-group" style="margin-top: 1rem;">
        <label for="modal-iface-mtu">MTU (Maximum Transmission Unit)</label>
        <input type="number" id="modal-iface-mtu" class="form-control" value="1500" min="576" max="9216">
        <small class="text-muted">Standard: 1500 | Jumbo Frames: 9000</small>
      </div>
      <div id="modal-result-box" class="terminal-box" style="margin-top:1rem; display:none;"></div>
    </div>
    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1rem; border-top:1px solid var(--border-color); padding-top:1rem;">
      <button class="btn btn-secondary" onclick="closeIfaceModal()" type="button">Cancel</button>
      <button class="btn btn-primary" onclick="submitIfaceConfig()" type="button">Save & Apply</button>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  loadInterfaces();
  <?php if ($activeSub !== 'physical'): ?>
  switchIfaceSubTab('<?= html_esc($activeSub) ?>');
  <?php endif; ?>
});
</script>


