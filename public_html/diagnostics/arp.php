<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - ARP Table
 * Modern White Enterprise Design System
 */
$pageTitle = 'Diagnostics: ARP & Neighbor Table';
$pageSubtitle = 'Kernel IPv4 ARP and IPv6 Neighbor Discovery (NDP) Resolution Cache';
$currentDiag = 'arp';

?>

        <section id="tab-diagnostics" class="tab-pane active">
          <!-- Diagnostics Subnav Tabs -->
          <div class="subnav-tabs" style="margin-bottom: 1.5rem;">
            <a class="subnav-tab <?= $currentDiag === 'tools' ? 'active' : '' ?>" href="index.php">Ping, Traceroute & Sniffer</a>
            <a class="subnav-tab <?= $currentDiag === 'dhcp' ? 'active' : '' ?>" href="dhcp_leases.php">DHCP Leases</a>
            <a class="subnav-tab <?= $currentDiag === 'arp' ? 'active' : '' ?>" href="arp.php">ARP Table</a>
            <a class="subnav-tab <?= $currentDiag === 'backup' ? 'active' : '' ?>" href="backup.php">Backup & Restore</a>
          </div>

          <!-- ARP Table -->
          <div class="card" id="diag-arp-card">
            <div class="card-header">
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-cyan">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                  <line x1="22" y1="10" x2="2" y2="10"></line>
                </svg>
                <h3>ARP &amp; Neighbor Table</h3>
              </div>
              <div class="card-actions">
                <input type="text" id="diag-arp-search" class="form-control" style="width: 160px; display: inline-block;" placeholder="Filter IP/MAC..." onkeyup="filterArpTable()">
                <button class="btn btn-sm btn-secondary" onclick="loadArpTable()">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                  Refresh
                </button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">Current IP-to-MAC resolution entries resolved by the Linux kernel IPv4 ARP and IPv6 Neighbor Discovery caches.</p>
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th>IP Address</th>
                      <th>MAC Address</th>
                      <th>Interface</th>
                      <th>State / Flags</th>
                      <th>Hostname</th>
                    </tr>
                  </thead>
                  <tbody id="diag-arp-tbody">
                    <tr><td colspan="5" class="text-center text-muted" style="padding: 1.5rem;">Loading kernel ARP cache...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadArpTable();
});
</script>


