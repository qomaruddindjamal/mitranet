<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Thermal & Hardware Sensors
 * Modern White Enterprise Design System
 */
$pageTitle = 'Thermal & Hardware Health';
$pageSubtitle = 'CPU Core Temperatures, Chassis Fan RPMs, Power Supply Telemetry & Governors';

?>

        <section id="tab-sensors" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h3>Hardware Environment, Thermal & Fan Health</h3>
              <button class="btn btn-sm btn-secondary" onclick="loadSensors()">Refresh Sensors</button>
            </div>
            <div class="card-body">
              <p class="description">CPU core temperatures, chassis fan RPMs, power supply telemetry, and kernel CPU
                scaling governor.</p>
              <pre class="code-terminal" id="raw-sensors-box">Loading sensors...</pre>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadSensors();
});
</script>


