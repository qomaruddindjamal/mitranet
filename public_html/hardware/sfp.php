<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - SFP / SFP+ Optical Transceiver Diagnostics
 * Modern White Enterprise Design System
 */
$pageTitle = 'SFP Diagnostics (DOM / DDM)';
$pageSubtitle = 'Optical Transceiver Monitoring: Laser Temperature, TX/RX Power & Module EEPROM';

?>

        <section id="tab-sfp" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h3>SFP / SFP+ Optical Transceiver Diagnostics (DOM / DDM)</h3>
              <div class="card-actions">
                <input type="text" id="sfp-iface-input" class="form-control" style="width:130px;display:inline-block;"
                  value="eth0" placeholder="eth0, sfp0">
                <button class="btn btn-sm btn-primary" onclick="loadSFP()">Query DDM</button>
              </div>
            </div>
            <div class="card-body">
              <p class="description">Live optical transceiver monitoring: Laser temperature, TX/RX optical power (dBm),
                voltage, and vendor module EEPROM.</p>
              <pre class="code-terminal" id="raw-sfp-box">Click 'Query DDM' to read SFP optical diagnostics...</pre>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadSFP();
});
</script>


