// ==============================================================================
// MitraNet Network OS - Dashboard Package JS
// Telemetry, System Health, Gateway Monitor, and Services Controller
// ==============================================================================

async function loadDashboard() {
  const data = await apiFetch('/api/status');
  if (!data) return;

  const uptimeEl = document.getElementById('dash-uptime');
  if (uptimeEl) uptimeEl.textContent = data.uptime || 'Active';

  const hostEl = document.getElementById('dash-hostname');
  if (hostEl) hostEl.textContent = data.hostname || 'mitranet-router';

  const loadEl = document.getElementById('dash-loadavg');
  if (loadEl && data.load_average) loadEl.textContent = `Load: ${data.load_average}`;

  // Live CPU Telemetry
  if (data.cpu) {
    const cpuModelEl = document.getElementById('dash-cpu-model');
    if (cpuModelEl && data.cpu.model) cpuModelEl.textContent = data.cpu.model;
    const cpuCoresEl = document.getElementById('dash-cpu-cores');
    if (cpuCoresEl && data.cpu.cores) cpuCoresEl.textContent = `${data.cpu.cores} Core${data.cpu.cores > 1 ? 's' : ''}`;
  }

  // Live Memory (RAM) Telemetry
  if (data.memory) {
    const ramUsageEl = document.getElementById('dash-ram-usage');
    if (ramUsageEl) ramUsageEl.textContent = `${data.memory.used_mb} MB / ${data.memory.total_mb} MB`;
    const ramPercentEl = document.getElementById('dash-ram-percent');
    if (ramPercentEl) ramPercentEl.textContent = `${data.memory.usage_percent}%`;
    const ramBarEl = document.getElementById('dash-ram-bar');
    if (ramBarEl) ramBarEl.style.width = `${Math.min(100, Math.max(5, data.memory.usage_percent))}%`;
  }

  // Active Interface count
  if (data.interfaces) {
    const ifaceCountEl = document.getElementById('dash-iface-count');
    if (ifaceCountEl) {
      const activeCount = Array.isArray(data.interfaces) ? data.interfaces.length : 4;
      ifaceCountEl.textContent = `${activeCount} Active Interfaces`;
    }
  }

  // Audit info (tuning profile)
  try {
    const auditData = await apiFetch('/api/system/audit');
    if (auditData) {
      const profileEl = document.getElementById('dash-tuning-profile');
      if (profileEl && auditData.tuning_profile) profileEl.textContent = auditData.tuning_profile;
    }
  } catch (e) {}
}

async function loadGateways() {
  const data = await apiFetch('/api/gateways/status');
  const tbody = document.getElementById('dash-gateways-tbody');
  if (!tbody || !data || !data.gateways) return;

  if (data.gateways.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding:1rem;">No gateways configured.</td></tr>';
    return;
  }

  tbody.innerHTML = data.gateways.map(gw => {
    let statusBadge = 'badge-success';
    if (gw.status === 'Offline') statusBadge = 'badge-danger';
    else if (gw.status === 'High Latency' || gw.status === 'Packet Loss') statusBadge = 'badge-warning';

    return `
      <tr>
        <td class="font-bold">${escapeHtml(gw.name)}</td>
        <td><span class="badge badge-zone-wan">${escapeHtml(gw.interface.toUpperCase())}</span></td>
        <td class="mono">${escapeHtml(gw.gateway_ip)}</td>
        <td class="mono font-bold">${escapeHtml(gw.rtt)}</td>
        <td class="mono">${escapeHtml(gw.loss)}</td>
        <td><span class="badge ${statusBadge}">${escapeHtml(gw.status)}</span></td>
      </tr>
    `;
  }).join('');
}

async function loadServicesStatus() {
  const data = await apiFetch('/api/services/status');
  const tbody = document.getElementById('dash-services-tbody');
  if (!tbody || !data || !data.services) return;

  tbody.innerHTML = data.services.map(s => {
    const isRunning = s.status === 'running' || s.status === 'active';
    const statusBadge = isRunning ? 'badge-success' : 'badge-danger';

    return `
      <tr>
        <td class="font-bold text-primary">${escapeHtml(s.name)}</td>
        <td>${escapeHtml(s.description || '')}</td>
        <td><span class="badge ${statusBadge}">${escapeHtml(s.status.toUpperCase())}</span></td>
        <td style="text-align: right; white-space: nowrap;">
          ${isRunning ? `
            <button class="btn btn-sm btn-secondary" style="padding:2px 8px; font-size:0.75rem;" onclick="controlService('${s.name}', 'restart')" title="Restart Service">Restart</button>
            <button class="btn btn-sm btn-danger" style="padding:2px 8px; font-size:0.75rem;" onclick="controlService('${s.name}', 'stop')" title="Stop Service">Stop</button>
          ` : `
            <button class="btn btn-sm btn-success" style="padding:2px 8px; font-size:0.75rem;" onclick="controlService('${s.name}', 'start')" title="Start Service">Start</button>
          `}
        </td>
      </tr>
    `;
  }).join('');
}

async function controlService(serviceName, action) {
  showAlert(`Executing ${action} on ${serviceName}...`, 'info');
  const res = await apiFetch('/api/services/control', {
    method: 'POST',
    body: JSON.stringify({ service: serviceName, action })
  });

  if (res && res.status === 'success') {
    showAlert(res.message || `Service ${serviceName} ${action}ed successfully.`, 'success');
    loadServicesStatus();
  } else {
    showAlert(`Failed to ${action} ${serviceName}: ` + (res?.message || 'Error'), 'error');
  }
}
