// ==============================================================================
// MitraNet Network OS - Diagnostics Package JS
// Ping, Traceroute, Packet Sniffer, Conntrack, DHCP Leases, ARP & Backup/Restore
// ==============================================================================

async function runDiagnosticPing() {
  const target = document.getElementById('diag-ping-target')?.value.trim() || '1.1.1.1';
  const outBox = document.getElementById('diag-ping-result');
  if (outBox) outBox.textContent = `Pinging ${target}...`;

  const res = await apiFetch(`/api/diagnostics/ping?target=${encodeURIComponent(target)}`);
  if (outBox && res) {
    outBox.textContent = res.output || res.message || 'Ping completed.';
  }
}

async function runDiagnosticTraceroute() {
  const target = document.getElementById('diag-trace-target')?.value.trim() || '1.1.1.1';
  const outBox = document.getElementById('diag-trace-result');
  if (outBox) outBox.textContent = `Tracing route to ${target}...`;

  const res = await apiFetch(`/api/diagnostics/traceroute?target=${encodeURIComponent(target)}`);
  if (outBox && res) {
    outBox.textContent = res.output || res.message || 'Traceroute completed.';
  }
}

async function runDiagnosticTcpdump() {
  const iface = document.getElementById('diag-tcpdump-iface')?.value.trim() || 'eth0';
  const count = document.getElementById('diag-tcpdump-count')?.value.trim() || '10';
  const outBox = document.getElementById('diag-tcpdump-result');
  if (outBox) outBox.textContent = `Sniffing ${count} packets on ${iface}...`;

  const res = await apiFetch(`/api/diagnostics/tcpdump?iface=${encodeURIComponent(iface)}&count=${encodeURIComponent(count)}`);
  if (outBox && res) {
    outBox.textContent = res.output || res.message || 'Packet capture completed.';
  }
}

async function loadLiveConntrack() {
  const outBox = document.getElementById('diag-conntrack-result');
  if (outBox) outBox.textContent = 'Loading live connection tracking states...';

  const res = await apiFetch('/api/diagnostics/conntrack');
  if (outBox && res) {
    outBox.textContent = res.flows || res.output || 'Conntrack table empty or zero active stateful sessions.';
  }
}

async function loadDiagnostics() {
  if (document.getElementById('diag-conntrack-result')) await loadLiveConntrack();
  if (document.getElementById('diag-dhcp-tbody')) await loadDhcpLeases();
  if (document.getElementById('diag-arp-tbody')) await loadArpTable();
}

// -----------------------------------------------------------------------------
// DHCP Server Leases (MitraNet Enterprise DHCP Subsystem)
// -----------------------------------------------------------------------------
async function loadDhcpLeases() {
  const data = await apiFetch('/api/dhcp/leases');
  const tbody = document.getElementById('diag-dhcp-tbody');
  const countEl = document.getElementById('dhcp-lease-count');
  if (!tbody || !data || !data.leases) return;

  if (countEl) countEl.textContent = `${data.leases.length} Leases`;

  if (data.leases.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No active DHCP leases recorded in dnsmasq/kea database.</td></tr>';
    return;
  }

  tbody.innerHTML = data.leases.map(l => `
    <tr>
      <td class="mono font-bold text-primary">${escapeHtml(l.ip)}</td>
      <td class="mono">${escapeHtml(l.mac)}</td>
      <td>${escapeHtml(l.hostname || 'Unknown')}</td>
      <td class="mono">${escapeHtml(l.lease_end || l.expires || 'Active')}</td>
      <td><span class="badge ${l.status === 'online' || l.status === 'active' ? 'badge-success' : 'badge-indigo'}">${escapeHtml((l.status || 'Active').toUpperCase())}</span></td>
    </tr>
  `).join('');
}

// -----------------------------------------------------------------------------
// ARP & Neighbor Table (MitraNet Kernel Neighbor Cache)
// -----------------------------------------------------------------------------
let currentArpEntries = [];

async function loadArpTable() {
  const data = await apiFetch('/api/diagnostics/arp');
  const tbody = document.getElementById('diag-arp-tbody');
  if (!tbody || !data || !data.arp_table) return;

  currentArpEntries = data.arp_table;
  renderArpTable();
}

function filterArpTable() {
  renderArpTable();
}

function renderArpTable() {
  const tbody = document.getElementById('diag-arp-tbody');
  if (!tbody) return;

  const query = (document.getElementById('diag-arp-search')?.value || '').toLowerCase().trim();
  let filtered = currentArpEntries;
  if (query) {
    filtered = currentArpEntries.filter(e => 
      (e.ip && e.ip.toLowerCase().includes(query)) ||
      (e.mac && e.mac.toLowerCase().includes(query)) ||
      (e.interface && e.interface.toLowerCase().includes(query))
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No matching ARP / Neighbor entries found.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(e => {
    const isReachable = e.state === 'REACHABLE' || e.flags === '0x2' || e.state === 'PERMANENT';
    return `
      <tr>
        <td class="mono font-bold text-cyan">${escapeHtml(e.ip)}</td>
        <td class="mono">${escapeHtml(e.mac || '<incomplete>')}</td>
        <td class="mono">${escapeHtml(e.interface || '')}</td>
        <td><span class="badge ${isReachable ? 'badge-success' : 'badge-secondary'}">${escapeHtml(e.state || e.flags || 'STALE')}</span></td>
        <td>${escapeHtml(e.hostname || '')}</td>
      </tr>
    `;
  }).join('');
}

// -----------------------------------------------------------------------------
// Configuration Backup & Restore (MitraNet Configuration Engine)
// -----------------------------------------------------------------------------
async function downloadConfigBackup() {
  showAlert('Generating full system configuration backup...', 'info');
  try {
    const res = await fetch(`${API_BASE}/api/config/backup`);
    if (!res.ok) throw new Error('Backup request failed');
    const blob = await res.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    const now = new Date().toISOString().replace(/[:.]/g, '-');
    a.download = `mitranet-config-backup-${now}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    showAlert('Configuration backup downloaded successfully!', 'success');
  } catch (err) {
    console.error('Download error:', err);
    showAlert('Error downloading configuration backup: ' + err.message, 'error');
  }
}

async function restoreConfigFromFile() {
  const fileInput = document.getElementById('diag-restore-file');
  const statusDiv = document.getElementById('diag-restore-status');
  if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
    showAlert('Please select a JSON configuration backup file.', 'error');
    return;
  }

  const file = fileInput.files[0];
  if (!file.name.endsWith('.json')) {
    showAlert('Selected file must be a JSON file.', 'error');
    return;
  }

  if (!confirm(`Are you sure you want to restore configuration from "${file.name}"? This will overwrite existing rules and interfaces.`)) {
    return;
  }

  const reader = new FileReader();
  reader.onload = async (e) => {
    try {
      const parsed = JSON.parse(e.target.result);
      if (statusDiv) statusDiv.innerHTML = '<span class="text-primary">Uploading configuration...</span>';

      const res = await apiFetch('/api/config/restore', {
        method: 'POST',
        body: JSON.stringify({ config: parsed })
      });

      if (res && res.status === 'success') {
        if (statusDiv) statusDiv.innerHTML = '<span class="text-success font-bold" style="color:#10b981;font-weight:700;">Configuration restored successfully!</span>';
        showAlert('Configuration restored! Reloading firewall and settings...', 'success');
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        if (statusDiv) statusDiv.innerHTML = `<span class="text-danger font-bold" style="color:#f43f5e;font-weight:700;">Restore failed: ${escapeHtml(res?.message || 'Error')}</span>`;
        showAlert('Configuration restore failed: ' + (res?.message || 'Unknown error'), 'error');
      }
    } catch (err) {
      if (statusDiv) statusDiv.innerHTML = '<span class="text-danger font-bold" style="color:#f43f5e;font-weight:700;">Invalid JSON backup file.</span>';
      showAlert('Invalid JSON file format: ' + err.message, 'error');
    }
  };
  reader.readAsText(file);
}
