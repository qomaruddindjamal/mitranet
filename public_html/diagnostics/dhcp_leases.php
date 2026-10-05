<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - DHCP Server Leases
 * Modern White Enterprise Design System
 */
$pageTitle = 'Diagnostics: DHCP Server Leases';
$pageSubtitle = 'Real-time IPv4 Address Leases, MAC Bindings & Client Hostnames';
$currentDiag = 'dhcp';

?>

        <section id="tab-diagnostics" class="tab-pane active">
          <!-- Diagnostics Subnav Tabs -->
          <div class="subnav-tabs" style="margin-bottom: 1.5rem;">
            <a class="subnav-tab <?= $currentDiag === 'tools' ? 'active' : '' ?>" href="index.php">Ping, Traceroute & Sniffer</a>
            <a class="subnav-tab <?= $currentDiag === 'dhcp' ? 'active' : '' ?>" href="dhcp_leases.php">DHCP Leases</a>
            <a class="subnav-tab <?= $currentDiag === 'arp' ? 'active' : '' ?>" href="arp.php">ARP Table</a>
            <a class="subnav-tab <?= $currentDiag === 'backup' ? 'active' : '' ?>" href="backup.php">Backup & Restore</a>
          </div>

          <!-- DHCP Leases -->
          <div class="card" id="diag-dhcp-card">
            <div class="card-header">
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                  <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                  <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                  <line x1="6" y1="6" x2="6.01" y2="6"></line>
                  <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
                <h3>DHCP Server Active Leases</h3>
              </div>
              <div class="card-actions">
                <span class="badge badge-indigo" id="dhcp-lease-count">0 Leases</span>
                <button class="btn btn-sm btn-secondary" onclick="loadDhcpLeases()">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                  Refresh
                </button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">Active dynamic and static IP address leases issued to client devices by the local Kea/Dnsmasq DHCP daemon.</p>
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th>IP Address</th>
                      <th>MAC Address</th>
                      <th>Hostname</th>
                      <th>Lease Expiration</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="diag-dhcp-tbody">
                    <tr><td colspan="5" class="text-center text-muted" style="padding: 1.5rem;">Loading active DHCP client leases...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadDhcpLeases();
});
</script>


