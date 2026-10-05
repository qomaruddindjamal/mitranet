<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Firewall Packet Filter Rules
 * Modern White Enterprise Design System
 */
$pageTitle = 'Firewall: Rules';
$pageSubtitle = 'Stateful Packet Filtering, Directional Chains & Interface Access Lists';
$currentFw = 'rules';

?>

        <section id="tab-firewall" class="tab-pane active">
          <?php include(__DIR__ . '/subnav.php'); ?>

          <!-- SUBTAB: FILTER RULES -->
          <div id="fw-subtab-rules" class="fw-subpane active">
            <div class="card">
              <div class="card-header">
                <h3>Active Firewall Filter Rules</h3>
                <div class="card-actions">
                  <button class="btn btn-sm btn-secondary" onclick="loadFirewall()">Refresh</button>
                  <button class="btn btn-sm btn-primary" onclick="showAddRuleModal()">+ Add Filter Rule</button>
                </div>
              </div>
              <div class="card-body">
                <!-- Interface Filter Tabs (MitraNet Enterprise Firewall) -->
                <div class="interface-pills" style="display: flex; gap: 0.4rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; flex-wrap: wrap;">
                  <button class="interface-pill active" id="rule-iface-btn-all" onclick="filterRulesByIface('all')">
                    All Interfaces <span class="pill-count" id="rule-cnt-all">0</span>
                  </button>
                  <button class="interface-pill" id="rule-iface-btn-wan" onclick="filterRulesByIface('wan')">
                    WAN <span class="pill-count" id="rule-cnt-wan">0</span>
                  </button>
                  <button class="interface-pill" id="rule-iface-btn-lan" onclick="filterRulesByIface('lan')">
                    LAN <span class="pill-count" id="rule-cnt-lan">0</span>
                  </button>
                  <button class="interface-pill" id="rule-iface-btn-dmz" onclick="filterRulesByIface('dmz')">
                    DMZ <span class="pill-count" id="rule-cnt-dmz">0</span>
                  </button>
                  <button class="interface-pill" id="rule-iface-btn-vpn" onclick="filterRulesByIface('vpn')">
                    VPN <span class="pill-count" id="rule-cnt-vpn">0</span>
                  </button>
                </div>

                <div class="data-table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th style="width: 45px;">State</th>
                        <th style="width: 70px;">Action</th>
                        <th style="width: 65px;">Proto</th>
                        <th>Interface</th>
                        <th>Source</th>
                        <th>Port</th>
                        <th>Destination</th>
                        <th>Port</th>
                        <th>Gateway</th>
                        <th>Description</th>
                        <th style="text-align: right; width: 85px;">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="fw-rules-tbody">
                      <tr>
                        <td colspan="11" style="text-align:center;padding:1.5rem;color:var(--text-dim);">Loading filter rules...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Inline Modal: Add Rule -->
                <div id="modal-add-rule" class="hidden"
                  style="margin-top: 1.5rem; padding: 1.25rem; border: 1px solid var(--accent-primary); border-radius: 8px; background: rgba(37, 99, 235, 0.02);">
                  <h4 style="margin-bottom: 1rem; color: var(--accent-primary);">Add Firewall Filter Rule</h4>
                  <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                      <label for="new-rule-action">Action</label>
                      <select id="new-rule-action" class="form-control">
                        <option value="pass" selected>Pass (Accept)</option>
                        <option value="block">Block (Drop)</option>
                        <option value="reject">Reject (ICMP Unreachable)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="new-rule-iface">Interface</label>
                      <select id="new-rule-iface" class="form-control">
                        <option value="wan">WAN (eth0 / Internet)</option>
                        <option value="lan" selected>LAN (eth1 / Local)</option>
                        <option value="dmz">DMZ (eth2)</option>
                        <option value="vpn">VPN (wg0 / xray)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="new-rule-proto">Protocol</label>
                      <select id="new-rule-proto" class="form-control">
                        <option value="tcp">TCP</option>
                        <option value="udp">UDP</option>
                        <option value="tcp/udp" selected>TCP/UDP</option>
                        <option value="icmp">ICMP (Ping)</option>
                        <option value="any">Any Protocol</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="new-rule-direction">Direction</label>
                      <select id="new-rule-direction" class="form-control">
                        <option value="in" selected>Inbound (ingress)</option>
                        <option value="out">Outbound (egress)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="new-rule-src">Source IP / CIDR / Alias</label>
                      <input type="text" id="new-rule-src" class="form-control" value="any"
                        placeholder="any, 192.168.1.0/24, or alias">
                    </div>
                    <div class="form-group">
                      <label for="new-rule-src-port">Source Port</label>
                      <input type="text" id="new-rule-src-port" class="form-control" value="any" placeholder="any or port">
                    </div>
                    <div class="form-group">
                      <label for="new-rule-dst">Destination IP / CIDR / Alias</label>
                      <input type="text" id="new-rule-dst" class="form-control" value="any"
                        placeholder="any, 10.0.0.0/8, or alias">
                    </div>
                    <div class="form-group">
                      <label for="new-rule-dst-port">Destination Port / Range</label>
                      <input type="text" id="new-rule-dst-port" class="form-control" value="any"
                        placeholder="any, 443, 8000-8080">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                      <label for="new-rule-descr">Description</label>
                      <input type="text" id="new-rule-descr" class="form-control"
                        placeholder="e.g. Allow Media Server Streaming">
                    </div>
                  </div>
                  <div style="margin-top: 1rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button class="btn btn-secondary" onclick="hideAddRuleModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="submitAddRule()">Save & Apply Rule</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadFirewall();
});
</script>


