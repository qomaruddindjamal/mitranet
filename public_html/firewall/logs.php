<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Firewall Threat Logs & Raw Ruleset
 * Modern White Enterprise Design System
 */
$pageTitle = 'Firewall: Logs & Ruleset';
$pageSubtitle = 'Real-time Netfilter Threat Telemetry & Kernel Compiled NFT Bytecode';
$currentFw = 'logs';

?>

        <section id="tab-firewall" class="tab-pane active">
          <?php include(__DIR__ . '/subnav.php'); ?>

          <!-- SUBTAB: LOGS & RAW RULES -->
          <div id="fw-subtab-logs" class="fw-subpane active">
            <div class="grid-2-col">
              <div class="card">
                <div class="card-header">
                  <h3>Real-time Dropped Attack Logs</h3>
                  <button class="btn btn-sm btn-secondary" onclick="loadFirewallLogs()">Refresh Logs</button>
                </div>
                <div class="card-body">
                  <pre class="code-terminal" id="raw-firewall-logs"
                    style="height:320px;">Loading kernel drop logs...</pre>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h3>Raw Compiled NFTables Ruleset</h3>
                  <button class="btn btn-sm btn-secondary" onclick="loadFirewallRaw()">Refresh Ruleset</button>
                </div>
                <div class="card-body">
                  <pre class="code-terminal" id="raw-firewall-box"
                    style="height:320px;">Loading raw nft ruleset...</pre>
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


