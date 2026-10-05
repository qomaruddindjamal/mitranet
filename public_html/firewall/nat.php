<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Firewall Port Forwarding (DNAT)
 * Modern White Enterprise Design System
 */
$pageTitle = 'Firewall: NAT Port Forward';
$pageSubtitle = 'Ingress Port Mapping, Destination NAT (DNAT) & Exposed Internal Services';
$currentFw = 'forwards';

?>

        <section id="tab-firewall" class="tab-pane active">
          <?php include(__DIR__ . '/subnav.php'); ?>

          <!-- SUBTAB: PORT FORWARDING -->
          <div id="fw-subtab-forwards" class="fw-subpane active">
            <div class="card">
              <div class="card-header">
                <h3>Port Forwarding (DNAT Ingress Rules)</h3>
                <button class="btn btn-sm btn-primary" onclick="showAddFwdModal()">+ Add Port Forward</button>
              </div>
              <div class="card-body">
                <p class="description">Forward external incoming connections on WAN ports directly to internal LAN servers and edge appliances.</p>
                <div class="data-table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>WAN Port</th>
                        <th>Protocol</th>
                        <th>Target IP Address</th>
                        <th>Target Port</th>
                        <th>Description</th>
                        <th style="text-align: right;">Manage</th>
                      </tr>
                    </thead>
                    <tbody id="fw-forwards-tbody">
                      <tr>
                        <td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No port forward mappings configured.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Add Forward Inline Form -->
                <div id="add-fwd-panel" class="hidden"
                  style="margin-top: 1.5rem; padding: 1.25rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px;">
                  <h4 style="margin-bottom: 1rem; color: var(--accent-primary);">Create Port Forwarding Rule</h4>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="new-fwd-wanport">External WAN Port</label>
                      <input type="text" id="new-fwd-wanport" class="form-control" placeholder="e.g. 80, 443, 25565">
                    </div>
                    <div class="form-group">
                      <label for="new-fwd-proto">Protocol</label>
                      <select id="new-fwd-proto" class="form-control">
                        <option value="tcp">TCP</option>
                        <option value="udp">UDP</option>
                        <option value="both">Both (TCP+UDP)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="new-fwd-targetip">Internal Target IP</label>
                      <input type="text" id="new-fwd-targetip" class="form-control" placeholder="e.g. 192.168.1.100">
                    </div>
                    <div class="form-group">
                      <label for="new-fwd-targetport">Internal Target Port</label>
                      <input type="text" id="new-fwd-targetport" class="form-control" placeholder="e.g. 80">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                      <label for="new-fwd-descr">Description</label>
                      <input type="text" id="new-fwd-descr" class="form-control"
                        placeholder="e.g. Web Server Production Forward">
                    </div>
                  </div>
                  <div style="margin-top: 1rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button class="btn btn-secondary" onclick="hideAddFwdModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="submitAddForward()">Save Port Forward</button>
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


