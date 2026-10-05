<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - Routing Suite
 * Modern White Enterprise Design System
 */
$pageTitle = 'Routing (FRR / BIRD2)';
$pageSubtitle = 'Kernel Routing Table, Static Routes, BGP (Border Gateway Protocol) & OSPF Dynamic Routing';

?>

        <section id="tab-routing" class="tab-pane active">
          <div class="card">
            <div class="card-header">
              <h3>Enterprise Dynamic Routing (FRRouting - BGP / OSPF)</h3>
              <button class="btn btn-sm btn-secondary" onclick="loadRoutes()">Refresh Routes</button>
            </div>
            <div class="card-body">
              <pre class="code-terminal" id="raw-routing-box">Loading routing table...</pre>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadRoutes();
});
</script>


