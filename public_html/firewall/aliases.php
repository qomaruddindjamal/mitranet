<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Firewall Aliases
 * Modern White Enterprise Design System
 */
$pageTitle = 'Firewall: Aliases';
$pageSubtitle = 'Reusable Objects: Host Lists, CIDR Networks, Port Groups & Threat URLs';
$currentFw = 'aliases';

?>

        <section id="tab-firewall" class="tab-pane active">
          <?php include(__DIR__ . '/subnav.php'); ?>

          <!-- SUBTAB: FIREWALL ALIASES -->
          <div id="fw-subtab-aliases" class="fw-subpane active">
            <div class="card">
              <div class="card-header">
                <h3>Firewall Aliases (Lists of IPs, Ports, URLs)</h3>
                <div class="card-actions">
                  <button class="btn btn-sm btn-secondary" onclick="loadAliases()">Refresh</button>
                  <button class="btn btn-sm btn-primary" onclick="showAddAliasModal()">+ Add Alias</button>
                </div>
              </div>
              <div class="card-body">
                <p class="description">Aliases act as placeholders for lists of IP addresses, CIDR subnets, port numbers, or threat intelligence URLs. They can be referenced across packet filter rules and NAT mappings.</p>
                
                <!-- Category Filter Pills (MitraNet: IP, Ports, URLs, All) -->
                <div class="interface-pills" style="display: flex; gap: 0.4rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; flex-wrap: wrap;">
                  <button class="interface-pill active" id="alias-cat-btn-all" onclick="filterAliasesByType('all')">
                    All Aliases <span class="pill-count" id="alias-cnt-all">0</span>
                  </button>
                  <button class="interface-pill" id="alias-cat-btn-host" onclick="filterAliasesByType('host')">
                    Hosts (IPs) <span class="pill-count" id="alias-cnt-host">0</span>
                  </button>
                  <button class="interface-pill" id="alias-cat-btn-network" onclick="filterAliasesByType('network')">
                    Networks (CIDR) <span class="pill-count" id="alias-cnt-network">0</span>
                  </button>
                  <button class="interface-pill" id="alias-cat-btn-port" onclick="filterAliasesByType('port')">
                    Ports <span class="pill-count" id="alias-cnt-port">0</span>
                  </button>
                  <button class="interface-pill" id="alias-cat-btn-url" onclick="filterAliasesByType('url')">
                    URLs (Threat Tables) <span class="pill-count" id="alias-cnt-url">0</span>
                  </button>
                </div>

                <div class="data-table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Alias Name</th>
                        <th>Type</th>
                        <th>Values / Endpoints</th>
                        <th>Description</th>
                        <th style="text-align: right;">Manage</th>
                      </tr>
                    </thead>
                    <tbody id="fw-aliases-tbody">
                      <tr>
                        <td colspan="5" style="text-align:center;padding:1.5rem;color:var(--text-dim);">Loading firewall aliases...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Add Alias Inline Form -->
                <div id="add-alias-panel" class="hidden" style="margin-top: 1.5rem; padding: 1.25rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px;">
                  <h4 style="margin-bottom: 1rem; color: var(--accent-primary);">Create New Firewall Alias</h4>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="new-alias-name">Alias Name</label>
                      <input type="text" id="new-alias-name" class="form-control" placeholder="e.g. WEB_SERVERS, ADMIN_IPS (A-Z, 0-9, _)">
                    </div>
                    <div class="form-group">
                      <label for="new-alias-type">Alias Type</label>
                      <select id="new-alias-type" class="form-control">
                        <option value="host">Host(s) - Single IP Addresses</option>
                        <option value="network">Network(s) - Subnets / CIDRs</option>
                        <option value="port">Port(s) - Service Ports</option>
                        <option value="url">URL Table - Dynamic Threat Feeds</option>
                      </select>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                      <label for="new-alias-values">Addresses / Ports / URLs (Comma-separated)</label>
                      <input type="text" id="new-alias-values" class="form-control" placeholder="e.g. 192.168.1.10, 192.168.1.20 or 80, 443, 8080">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                      <label for="new-alias-descr">Description</label>
                      <input type="text" id="new-alias-descr" class="form-control" placeholder="e.g. Authorized Management Stations">
                    </div>
                  </div>
                  <div style="margin-top: 1rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button class="btn btn-secondary" onclick="hideAddAliasModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="submitAddAlias()">Save Alias</button>
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


