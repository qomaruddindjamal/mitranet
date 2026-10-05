<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - ONLP Whitebox Platform
 * Modern White Enterprise Design System
 */
$pageTitle = 'ONLP Switch Platform';
$pageSubtitle = 'Open Network Linux Platform (ONLP) Whitebox Switch Abstraction & System Sync';

?>

        <section id="tab-platform" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h3>MitraNet Platform & Open Network Linux (ONLP) Switch Engine</h3>
              <div class="card-actions">
                <button class="btn btn-sm btn-secondary" onclick="loadPlatformInfo()">Query Platform</button>
                <button class="btn btn-sm btn-primary" onclick="syncMitranetConfig()">Sync MitraNet Config</button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">Hardware Abstraction Layer integrating Open Network Linux Platform (ONLP) telemetry
                for whitebox data-center switches, optical transceiver telemetry, chassis smart fan PWM, and native
                MitraNet configuration.</p>
              <pre class="code-terminal"
                id="raw-platform-box">Click 'Query Platform' to read hardware platform abstraction details...</pre>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadPlatformInfo();
});
</script>


