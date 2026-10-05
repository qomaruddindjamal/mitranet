<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Configuration Backup & Restore
 * Modern White Enterprise Design System
 */
$pageTitle = 'Diagnostics: Backup & Restore';
$pageSubtitle = 'Export and Restore Complete Router Appliance Configurations (JSON Format)';
$currentDiag = 'backup';

?>

        <section id="tab-diagnostics" class="tab-pane active">
          <!-- Diagnostics Subnav Tabs -->
          <div class="subnav-tabs" style="margin-bottom: 1.5rem;">
            <a class="subnav-tab <?= $currentDiag === 'tools' ? 'active' : '' ?>" href="index.php">Ping, Traceroute & Sniffer</a>
            <a class="subnav-tab <?= $currentDiag === 'dhcp' ? 'active' : '' ?>" href="dhcp_leases.php">DHCP Leases</a>
            <a class="subnav-tab <?= $currentDiag === 'arp' ? 'active' : '' ?>" href="arp.php">ARP Table</a>
            <a class="subnav-tab <?= $currentDiag === 'backup' ? 'active' : '' ?>" href="backup.php">Backup & Restore</a>
          </div>

          <!-- Configuration Backup & Restore -->
          <div class="card" id="diag-backup-card">
            <div class="card-header">
              <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-success">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                  <polyline points="17 21 17 13 7 13 7 21"></polyline>
                  <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <h3>Configuration Backup &amp; Restore</h3>
              </div>
            </div>
            <div class="card-body">
              <div class="grid-2-col">
                <!-- Backup Box -->
                <div style="border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 1.25rem;">
                  <h4 style="margin-top: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download Configuration Backup
                  </h4>
                  <p class="description">Export full system configuration as JSON format, including network interfaces, firewall rules, aliases, NAT 1:1, Outbound NAT, routing, and kernel sysctl parameters.</p>
                  <button class="btn btn-primary" onclick="downloadConfigBackup()" style="margin-top: 0.75rem;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download Backup File
                  </button>
                </div>

                <!-- Restore Box -->
                <div style="border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 1.25rem;">
                  <h4 style="margin-top: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Restore Configuration
                  </h4>
                  <p class="description">Upload a previously exported JSON configuration file to restore firewall rules, aliases, and appliance parameters.</p>
                  <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 0.75rem;">
                    <input type="file" id="diag-restore-file" accept=".json" class="form-control" style="font-size: 0.85rem;">
                    <button class="btn btn-warning" onclick="restoreConfigFromFile()">
                      Restore Config
                    </button>
                  </div>
                  <div id="diag-restore-status" style="margin-top: 0.5rem; font-size: 0.85rem;"></div>
                </div>
              </div>
            </div>
          </div>
        </section>


