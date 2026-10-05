<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Network Diagnostics
 * Modern White Enterprise Design System
 */
$pageTitle = 'Network Diagnostics';
$pageSubtitle = 'ICMP Ping, Traceroute Path Analysis, Live Packet Sniffer (tcpdump) & Conntrack Flows';
$currentDiag = 'tools';

?>

        <section id="tab-diagnostics" class="tab-pane active">
          <!-- Diagnostics Subnav Tabs -->
          <div class="subnav-tabs" style="margin-bottom: 1.5rem;">
            <a class="subnav-tab <?= $currentDiag === 'tools' ? 'active' : '' ?>" href="index.php">Ping, Traceroute & Sniffer</a>
            <a class="subnav-tab <?= $currentDiag === 'dhcp' ? 'active' : '' ?>" href="dhcp_leases.php">DHCP Leases</a>
            <a class="subnav-tab <?= $currentDiag === 'arp' ? 'active' : '' ?>" href="arp.php">ARP Table</a>
            <a class="subnav-tab <?= $currentDiag === 'backup' ? 'active' : '' ?>" href="backup.php">Backup & Restore</a>
          </div>

          <div class="grid-2-col" style="margin-bottom: 1.5rem;">
            <div class="card">
              <div class="card-header">
                <h3>ICMP Ping Diagnostic (fping / ping)</h3>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group">
                    <label for="diag-ping-target">Target IP / Hostname</label>
                    <input type="text" id="diag-ping-target" class="form-control" value="1.1.1.1" placeholder="e.g. 1.1.1.1, google.com">
                  </div>
                </div>
                <div style="margin-top: 1rem;">
                  <button class="btn btn-primary" onclick="runDiagnosticPing()">Run Ping Test</button>
                </div>
                <pre class="code-terminal" id="diag-ping-result" style="margin-top: 1rem; height: 160px;">Ping output will appear here...</pre>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3>Traceroute Path Diagnostic (mtr / traceroute)</h3>
              </div>
              <div class="card-body">
                <div class="form-grid">
                  <div class="form-group">
                    <label for="diag-trace-target">Target IP / Hostname</label>
                    <input type="text" id="diag-trace-target" class="form-control" value="1.1.1.1" placeholder="e.g. 8.8.8.8">
                  </div>
                </div>
                <div style="margin-top: 1rem;">
                  <button class="btn btn-primary" onclick="runDiagnosticTraceroute()">Trace Route</button>
                </div>
                <pre class="code-terminal" id="diag-trace-result" style="margin-top: 1rem; height: 160px;">Traceroute output will appear here...</pre>
              </div>
            </div>
          </div>

          <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
              <h3>Live Packet Sniffer (tcpdump / Deep Packet Inspection)</h3>
              <div class="card-actions">
                <input type="text" id="diag-tcpdump-iface" class="form-control" style="width:120px;display:inline-block;" value="eth0" placeholder="eth0">
                <input type="number" id="diag-tcpdump-count" class="form-control" style="width:80px;display:inline-block;" value="10" min="1" max="50">
                <button class="btn btn-sm btn-primary" onclick="runDiagnosticTcpdump()">Capture Packets</button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">Live packet capture on router interfaces using <code>tcpdump</code>. Captures frame headers, protocol flags, and source/dest IPs.</p>
              <pre class="code-terminal" id="diag-tcpdump-result" style="height: 240px;">Packet capture output will appear here...</pre>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>Live Kernel Conntrack Flow State Table</h3>
              <button class="btn btn-sm btn-secondary" onclick="loadLiveConntrack()">Refresh Flows</button>
            </div>
            <div class="card-body">
              <p class="description">Real-time stateful NAT and connection tracking flows active in kernel netfilter.</p>
              <pre class="code-terminal" id="diag-conntrack-result" style="height: 240px;">Loading conntrack flows...</pre>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadLiveConntrack();
});
</script>


