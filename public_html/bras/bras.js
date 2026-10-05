// ==============================================================================
// MitraNet Network OS - BRAS Package JS
// Subscriber BRAS / AAA (PPPoE / IPoE, FreeRADIUS Accounting)
// ==============================================================================

async function loadBRASStatus() {
  const data = await apiFetch('/api/enterprise/bras');
  const box = document.getElementById('raw-bras-box');
  if (!box || !data) return;

  box.innerHTML = `BRAS Subsystem Status:\n` +
    `PPPoE Daemon: ${escapeHtml(data.pppoe_daemon || 'accel-ppp / pppoe kernel module')}\n` +
    `FreeRADIUS AAA: ${escapeHtml(data.radius_status || 'Ready / Active')}\n` +
    `Active Sessions: ${escapeHtml(String(data.active_sessions || 0))} connected users\n` +
    `IP Allocation Pool: ${escapeHtml(data.ip_pool || '10.100.0.0/16')}\n` +
    `Accounting Interface: RADIUS CoA / NetFlow v9 enabled`;
}
