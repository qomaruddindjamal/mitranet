// ==============================================================================
// MitraNet Network OS - Routing Package JS
// Kernel Routing Table, Static Routes, BGP & OSPF Dynamic Routing
// ==============================================================================

async function loadRoutes() {
  const data = await apiFetch('/api/routing');
  const box = document.getElementById('raw-routing-box');
  if (!box || !data) return;

  let text = '=== Dynamic Routing (FRRouting) ===\n';
  text += `FRR Status: ${data.frr_active ? 'Active' : 'Idle'}\n\n`;
  text += '--- Routing Table ---\n' + (data.routing_table ? data.routing_table.join('\n') : '') + '\n\n';
  text += '--- BGP Summary ---\n' + (data.bgp_summary ? data.bgp_summary.join('\n') : '') + '\n\n';
  text += '--- OSPF Neighbors ---\n' + (data.ospf_neighbors ? data.ospf_neighbors.join('\n') : '');
  box.textContent = text;
}
