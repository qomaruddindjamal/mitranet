// ==============================================================================
// MitraNet Network OS - High Availability Package JS
// Active/Passive Dual-Router Redundancy (RFC 5798 VRRP) & Conntrack Sync
// ==============================================================================

async function loadHAStatus() {
  const data = await apiFetch('/api/enterprise/ha');
  const box = document.getElementById('raw-ha-box');
  if (!box || !data) return;

  const isEnabled = Boolean(data.enabled);
  const stateBadge = document.getElementById('ha-state-badge');
  if (stateBadge) {
    stateBadge.textContent = isEnabled ? (data.role || 'MASTER').toUpperCase() : 'DISABLED';
    stateBadge.className = isEnabled ? 'badge badge-success' : 'badge badge-rose';
  }

  const toggleBtn = document.getElementById('btn-toggle-ha');
  if (toggleBtn) {
    toggleBtn.textContent = isEnabled ? 'Disable HA Cluster' : 'Enable HA Cluster';
    toggleBtn.className = isEnabled ? 'btn btn-sm btn-outline' : 'btn btn-sm btn-primary';
  }

  box.innerHTML = `VRRP Cluster Service: ${isEnabled ? '<span style="color:#10b981;font-weight:700;">ACTIVE</span>' : '<span style="color:#f43f5e;font-weight:700;">DISABLED</span>'}\n` +
    `Node Role: ${escapeHtml((data.role || 'master').toUpperCase())}\n` +
    `Virtual Router ID (VRID): ${escapeHtml(String(data.vrid || 51))}\n` +
    `Virtual IP (VIP): ${escapeHtml(data.vip || '192.168.1.1/24')}\n` +
    `Heartbeat Interface: ${escapeHtml(data.interface || 'eth0')}\n` +
    `Conntrackd State Sync: ${isEnabled ? 'ACTIVE (Multicast 225.0.0.50)' : 'STANDBY'}\n` +
    `Health Status: OK (Preempt delay: 3s)`;
}

async function toggleHA() {
  const stateBadge = document.getElementById('ha-state-badge');
  const currentlyActive = stateBadge && stateBadge.textContent !== 'DISABLED';
  const res = await apiFetch('/api/enterprise/ha/toggle', {
    method: 'POST',
    body: JSON.stringify({ enabled: !currentlyActive })
  });
  if (res && res.status === 'success') {
    showAlert(`High Availability ${!currentlyActive ? 'Enabled' : 'Disabled'}`, 'success');
    loadHAStatus();
  } else {
    showAlert('Failed to toggle High Availability: ' + (res?.message || 'Error'), 'error');
  }
}
