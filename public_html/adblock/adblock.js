// ==============================================================================
// MitraNet Network OS - DNS Guard & Adblock Package JS
// Network-Wide DNS Sinkhole, Anti-Telemetry & Malware Domain Filtering
// ==============================================================================

async function loadAdblockStatus() {
  const data = await apiFetch('/api/enterprise/adblock');
  const box = document.getElementById('adblock-telemetry-box');
  if (!box || !data) return;

  const isEnabled = Boolean(data.enabled);
  const statusBadge = document.getElementById('adblock-status-badge');
  if (statusBadge) {
    statusBadge.textContent = isEnabled ? 'ACTIVE' : 'BYPASSED';
    statusBadge.className = isEnabled ? 'badge badge-success' : 'badge badge-rose';
  }

  const stateVal = document.getElementById('adblock-state-val');
  if (stateVal) {
    stateVal.innerHTML = isEnabled ? 'Protected <span class="unit">Sinkhole</span>' : 'Disabled <span class="unit">Bypassed</span>';
  }

  const toggleBtn = document.getElementById('btn-toggle-adblock');
  if (toggleBtn) {
    toggleBtn.textContent = isEnabled ? 'Disable Protection' : 'Enable Protection';
    toggleBtn.className = isEnabled ? 'btn btn-sm btn-outline' : 'btn btn-sm btn-primary';
  }

  box.innerHTML = `DNS Guard Sinkhole Status: ${isEnabled ? '<span style="color:#10b981;font-weight:700;">ENABLED</span>' : '<span style="color:#f43f5e;font-weight:700;">DISABLED</span>'}\n` +
    `Resolver Engine: ${escapeHtml(data.engine || 'dnsmasq / unbound')}\n` +
    `Total Filtered Domains: 142,500+ rules loaded\n` +
    `Upstream Resolvers: 1.1.1.1 (Cloudflare DoT), 9.9.9.9 (Quad9 Filtered)\n` +
    `Sinkhole IP Target: 127.0.0.1 (NXDOMAIN / Null Route)`;
}

async function toggleAdblock() {
  const statusBadge = document.getElementById('adblock-status-badge');
  const currentlyActive = statusBadge && statusBadge.textContent === 'ACTIVE';
  const res = await apiFetch('/api/enterprise/adblock/toggle', {
    method: 'POST',
    body: JSON.stringify({ enabled: !currentlyActive })
  });
  if (res && res.status === 'success') {
    showAlert(`DNS Guard ${!currentlyActive ? 'Activated' : 'Bypassed'}`, 'success');
    loadAdblockStatus();
  } else {
    showAlert('Failed to toggle DNS Guard: ' + (res?.message || 'Error'), 'error');
  }
}
