<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Firewall Threat Blacklist
 * Modern White Enterprise Design System
 */
$pageTitle = 'Firewall: Threat Blacklist';
$pageSubtitle = 'Ingress Banned IPs, Zero-Tolerance Droplists & Auto-Ban Thresholds';
$currentFw = 'blacklist';

?>

        <section id="tab-firewall" class="tab-pane active">
          <?php include(__DIR__ . '/subnav.php'); ?>

          <!-- SUBTAB: THREAT BLACKLIST -->
          <div id="fw-subtab-blacklist" class="fw-subpane active">
            <div class="card">
              <div class="card-header">
                <h3>Dynamic Threat Blacklist & IP Bans</h3>
                <div class="card-actions">
                  <input type="text" id="blacklist-ip-input" class="form-control"
                    style="width:180px;display:inline-block;" placeholder="192.0.2.1 or CIDR">
                  <input type="text" id="blacklist-reason-input" class="form-control"
                    style="width:160px;display:inline-block;" placeholder="Reason (e.g. Port Scan)">
                  <button class="btn btn-sm btn-danger" onclick="submitBlacklistIP()">Ban Threat IP</button>
                </div>
              </div>
              <div class="card-body">
                <p class="description">Malicious IP addresses and CIDR subnets blocked at the earliest nftables hook
                  with zero CPU overhead.</p>
                <div class="data-table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Blocked IP / Subnet</th>
                        <th>Threat Reason</th>
                        <th>Banned Timestamp</th>
                        <th style="text-align: right;">Action</th>
                      </tr>
                    </thead>
                    <tbody id="fw-blacklist-tbody">
                      <tr>
                        <td colspan="4" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No blacklisted IPs.</td>
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
  loadFirewall();
});
</script>


