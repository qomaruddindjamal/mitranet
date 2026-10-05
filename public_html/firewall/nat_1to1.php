<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Firewall 1:1 Static NAT
 * Modern White Enterprise Design System
 */
$pageTitle = 'Firewall: 1:1 Static NAT';
$pageSubtitle = 'Bi-Directional Host Mapping & Public IPv4 Static Allocation';
$currentFw = '1to1';

?>

        <section id="tab-firewall" class="tab-pane active">
          <?php include(__DIR__ . '/subnav.php'); ?>

          <!-- SUBTAB: 1:1 NAT -->
          <div id="fw-subtab-1to1" class="fw-subpane active">
            <div class="card">
              <div class="card-header">
                <h3>1:1 Static Bi-Directional NAT</h3>
                <button class="btn btn-sm btn-primary" onclick="showAdd1to1Modal()">+ Add 1:1 NAT Rule</button>
              </div>
              <div class="card-body">
                <p class="description">1:1 NAT maps an external public IP address to an internal private host address bi-directionally (inbound connections are DNAT'd and outbound traffic from that host is SNAT'd).</p>
                <div class="data-table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th style="width: 50px;">State</th>
                        <th>Interface</th>
                        <th>External IP Address</th>
                        <th>Internal IP Address</th>
                        <th>Description</th>
                        <th style="text-align: right;">Manage</th>
                      </tr>
                    </thead>
                    <tbody id="fw-1to1-tbody">
                      <tr>
                        <td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No 1:1 NAT mappings configured.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Add 1:1 NAT Inline Form -->
                <div id="add-1to1-panel" class="hidden" style="margin-top: 1.5rem; padding: 1.25rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px;">
                  <h4 style="margin-bottom: 1rem; color: var(--accent-primary);">Create 1:1 Static NAT Mapping</h4>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="new-1to1-iface">Interface</label>
                      <select id="new-1to1-iface" class="form-control">
                        <option value="wan">WAN (Internet)</option>
                        <option value="lan">LAN (Internal)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="new-1to1-extip">External Public IP Address</label>
                      <input type="text" id="new-1to1-extip" class="form-control" placeholder="e.g. 203.0.113.10">
                    </div>
                    <div class="form-group">
                      <label for="new-1to1-intip">Internal Private Host IP</label>
                      <input type="text" id="new-1to1-intip" class="form-control" placeholder="e.g. 192.168.1.100">
                    </div>
                    <div class="form-group">
                      <label for="new-1to1-descr">Description</label>
                      <input type="text" id="new-1to1-descr" class="form-control" placeholder="e.g. Production Web Server Dedicated NAT">
                    </div>
                  </div>
                  <div style="margin-top: 1rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button class="btn btn-secondary" onclick="hideAdd1to1Modal()">Cancel</button>
                    <button class="btn btn-primary" onclick="submitAdd1to1()">Save 1:1 NAT</button>
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


