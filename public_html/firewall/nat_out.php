<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Firewall Outbound NAT
 * Modern White Enterprise Design System
 */
$pageTitle = 'Firewall: Outbound NAT';
$pageSubtitle = 'SNAT Masquerading, Multi-WAN Source Rules & Network Address Translation';
$currentFw = 'outbound';

?>

        <section id="tab-firewall" class="tab-pane active">
          <?php include(__DIR__ . '/subnav.php'); ?>

          <!-- SUBTAB: OUTBOUND NAT -->
          <div id="fw-subtab-outbound" class="fw-subpane active">
            <div class="card" style="margin-bottom: 1.5rem;">
              <div class="card-header">
                <h3>Outbound NAT Mode</h3>
                <button class="btn btn-sm btn-primary" onclick="saveOutboundNatMode()">Save Mode</button>
              </div>
              <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                  <label style="display: flex; align-items: flex-start; gap: 0.6rem; cursor: pointer;">
                    <input type="radio" name="outbound-mode-radio" value="automatic" id="out-mode-auto" checked style="margin-top: 0.25rem;">
                    <div>
                      <strong style="color: var(--text-main);">Automatic Outbound NAT Rule Generation</strong>
                      <div style="font-size: 0.82rem; color: var(--text-muted);">IP masquerade is automatically applied to all outbound traffic leaving the WAN interface.</div>
                    </div>
                  </label>
                  <label style="display: flex; align-items: flex-start; gap: 0.6rem; cursor: pointer;">
                    <input type="radio" name="outbound-mode-radio" value="hybrid" id="out-mode-hybrid" style="margin-top: 0.25rem;">
                    <div>
                      <strong style="color: var(--text-main);">Hybrid Outbound NAT Rule Generation</strong>
                      <div style="font-size: 0.82rem; color: var(--text-muted);">Automatic rules are created, plus additional custom mapping rules defined below.</div>
                    </div>
                  </label>
                  <label style="display: flex; align-items: flex-start; gap: 0.6rem; cursor: pointer;">
                    <input type="radio" name="outbound-mode-radio" value="manual" id="out-mode-manual" style="margin-top: 0.25rem;">
                    <div>
                      <strong style="color: var(--text-main);">Manual Outbound NAT Rule Generation (Advanced)</strong>
                      <div style="font-size: 0.82rem; color: var(--text-muted);">Automatic rules are disabled. Only mappings explicitly configured below will be applied.</div>
                    </div>
                  </label>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3>Outbound NAT Mappings</h3>
                <button class="btn btn-sm btn-primary" onclick="showAddOutboundModal()">+ Add Outbound Rule</button>
              </div>
              <div class="card-body">
                <div class="data-table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Interface</th>
                        <th>Source Subnet</th>
                        <th>Destination</th>
                        <th>NAT Translation Address</th>
                        <th>Description</th>
                        <th style="text-align: right;">Manage</th>
                      </tr>
                    </thead>
                    <tbody id="fw-outbound-tbody">
                      <tr>
                        <td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No custom outbound NAT mappings configured.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Add Outbound Rule Inline Form -->
                <div id="add-outbound-panel" class="hidden" style="margin-top: 1.5rem; padding: 1.25rem; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 10px;">
                  <h4 style="margin-bottom: 1rem; color: var(--accent-primary);">Create Outbound NAT Mapping</h4>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="new-out-iface">Interface</label>
                      <select id="new-out-iface" class="form-control">
                        <option value="wan">WAN (Internet)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="new-out-src">Source Subnet / CIDR</label>
                      <input type="text" id="new-out-src" class="form-control" value="192.168.1.0/24" placeholder="e.g. 192.168.1.0/24 or any">
                    </div>
                    <div class="form-group">
                      <label for="new-out-dst">Destination</label>
                      <input type="text" id="new-out-dst" class="form-control" value="any" placeholder="any or specific network">
                    </div>
                    <div class="form-group">
                      <label for="new-out-natip">NAT Translation Address</label>
                      <input type="text" id="new-out-natip" class="form-control" value="wan_interface" placeholder="wan_interface or specific IP">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                      <label for="new-out-descr">Description</label>
                      <input type="text" id="new-out-descr" class="form-control" placeholder="e.g. Standard LAN SNAT Masquerade">
                    </div>
                  </div>
                  <div style="margin-top: 1rem; display: flex; gap: 0.75rem; justify-content: flex-end;">
                    <button class="btn btn-secondary" onclick="hideAddOutboundModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="submitAddOutbound()">Save Outbound Rule</button>
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


