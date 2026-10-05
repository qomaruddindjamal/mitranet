// ==============================================================================
// MitraNet Network OS - Interfaces Package JS
// Physical Interfaces (1 to 54 Ports Auto-Detection), Vether, VLAN, EoIP & VXLAN
// ==============================================================================

let currentDetectedInterfaces = [];

function switchIfaceSubTab(subName) {
  document.querySelectorAll('#tab-interfaces .subnav-tab').forEach(b => b.classList.remove('active'));
  const btn = document.getElementById(`iface-tab-btn-${subName}`);
  if (btn) btn.classList.add('active');

  document.querySelectorAll('#tab-interfaces .iface-subpane').forEach(p => p.classList.add('hidden'));
  const pane = document.getElementById(`iface-subtab-${subName}`);
  if (pane) pane.classList.remove('hidden');

  if (subName === 'physical') loadInterfaces();
}

/**
 * Format bytes into human readable binary units
 */
function formatBytes(bytes) {
  if (!bytes || isNaN(bytes) || bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

/**
 * Loads and auto-detects physical Ethernet interfaces (1 to 54 ports)
 */
async function loadInterfaces() {
  const data = await apiFetch('/api/interfaces');
  const box = document.getElementById('raw-interfaces-box');
  const dashBox = document.getElementById('interfaces-display');
  if (!data) return;

  let ifaceList = [];
  if (data.physical_interfaces && Array.isArray(data.physical_interfaces)) {
    ifaceList = data.physical_interfaces;
  } else if (Array.isArray(data)) {
    ifaceList = data.filter(i => i.ifname !== 'lo').map((i, idx) => ({
      port_number: idx + 1,
      name: i.ifname,
      display_name: `Port ${idx + 1} (${i.ifname})`,
      role: idx === 0 ? 'WAN (Primary Gateway)' : 'LAN (Trust Network)',
      state: i.operstate || 'UNKNOWN',
      carrier: i.operstate === 'UP',
      mac: i.address || '-',
      speed: 'Auto',
      duplex: 'Full',
      mtu: i.mtu || 1500,
      driver: 'ethernet',
      ip_addresses: (i.addr_info || []).map(a => `${a.local}/${a.prefixlen}`),
      rx_bytes: 0,
      tx_bytes: 0,
      rx_packets: 0,
      tx_packets: 0
    }));
  }

  currentDetectedInterfaces = ifaceList;

  // 1. Update KPI Summary Stats
  const totalCount = ifaceList.length;
  const upCount = ifaceList.filter(i => i.state === 'UP' || i.carrier).length;
  const downCount = totalCount - upCount;

  const countVal = document.getElementById('iface-count-val');
  if (countVal) countVal.textContent = `${totalCount} Port${totalCount > 1 ? 's' : ''}`;

  const modeBadge = document.getElementById('iface-mode-badge');
  if (modeBadge) {
    if (totalCount === 1) {
      modeBadge.textContent = 'Single-NIC Mode (Router-on-a-Stick)';
      modeBadge.className = 'iface-stat-badge badge-single-nic';
    } else if (totalCount <= 4) {
      modeBadge.textContent = 'Multi-Port SOHO Router';
      modeBadge.className = 'iface-stat-badge badge-multi-nic';
    } else {
      modeBadge.textContent = `Enterprise High-Density Switch (${totalCount} Ports)`;
      modeBadge.className = 'iface-stat-badge badge-enterprise-nic';
    }
  }

  const upVal = document.getElementById('iface-up-val');
  if (upVal) upVal.textContent = `${upCount} Link Active`;

  const downVal = document.getElementById('iface-down-val');
  if (downVal) downVal.textContent = `${downCount} Link Down`;

  const caption = document.getElementById('iface-table-caption');
  if (caption) {
    caption.textContent = totalCount === 1
      ? '1 physical Ethernet port detected (Single-NIC Mode)'
      : `${totalCount} physical Ethernet ports detected (Ports 1 to ${totalCount})`;
  }

  // 2. Render Interactive Chassis Port Bay (Front Panel View)
  renderChassisBay(ifaceList);

  // 3. Render Detailed Table
  renderInterfacesTable(ifaceList);

  // 4. Populate VLAN Parent Dropdown
  const vlanParentSelect = document.getElementById('vlan-parent-input');
  if (vlanParentSelect && vlanParentSelect.tagName && vlanParentSelect.tagName.toLowerCase() === 'select') {
    const curVal = vlanParentSelect.value;
    vlanParentSelect.innerHTML = '';
    ifaceList.forEach(iface => {
      const opt = document.createElement('option');
      opt.value = iface.name;
      opt.textContent = `${iface.name} - Port ${iface.port_number} (${iface.role})`;
      vlanParentSelect.appendChild(opt);
    });
    if (curVal && ifaceList.some(i => i.name === curVal)) {
      vlanParentSelect.value = curVal;
    }
  }

  // 5. Update Raw Terminal Box
  let rawContent = '';
  if (data.interfaces && Array.isArray(data.interfaces)) {
    rawContent = data.interfaces.join('\n');
  } else {
    rawContent = JSON.stringify(data, null, 2);
  }
  if (box) box.textContent = rawContent;

  // 6. Update Dashboard display if present
  if (dashBox) {
    dashBox.innerHTML = renderDashboardInterfacesSummary(ifaceList);
  }
}

/**
 * Renders the Visual Chassis Front-Panel Port Matrix (Supports 1 to 54 Ports)
 */
function renderChassisBay(ifaceList) {
  const container = document.getElementById('chassis-port-grid');
  if (!container) return;

  if (!ifaceList || ifaceList.length === 0) {
    container.innerHTML = '<div style="padding:1.5rem; text-align:center; color:var(--text-muted);">No physical Ethernet interfaces detected on this hardware.</div>';
    return;
  }

  // SINGLE-NIC MODE: 1 port present
  if (ifaceList.length === 1) {
    const p = ifaceList[0];
    const isUp = (p.state === 'UP' || p.carrier);
    container.innerHTML = `
      <div class="chassis-single-nic-card ${isUp ? 'is-up' : 'is-down'}" onclick="highlightTableRow('${p.name}')">
        <div class="single-nic-jack">
          <div class="port-jack-led-row">
            <span class="led-dot ${isUp ? 'led-up' : 'led-down'}"></span>
            <span class="port-jack-num">PORT 1</span>
          </div>
          <div class="port-jack-body">
            <div class="port-jack-name">${escapeHtml(p.name)}</div>
            <div class="port-jack-role role-wan">WAN / LAN Shared</div>
          </div>
          <div class="port-jack-speed">${escapeHtml(p.speed || '1 Gbps')}</div>
        </div>
        <div class="single-nic-meta">
          <div class="meta-row"><strong>Hardware:</strong> Single-NIC Appliance / Router-on-a-Stick Mode</div>
          <div class="meta-row"><strong>MAC Address:</strong> <code>${escapeHtml(p.mac || '-')}</code></div>
          <div class="meta-row"><strong>IP Configuration:</strong> ${p.ip_addresses && p.ip_addresses.length ? p.ip_addresses.map(ip => `<span class="badge badge-info">${escapeHtml(ip)}</span>`).join(' ') : '<span class="text-muted">No IP assigned</span>'}</div>
          <div class="meta-row"><strong>Carrier Link:</strong> <span class="badge ${isUp ? 'badge-success' : 'badge-secondary'}">${isUp ? 'CARRIER ACTIVE' : 'NO CARRIER'}</span></div>
        </div>
      </div>
    `;
    return;
  }

  // MULTI-NIC / HIGH-DENSITY CHASSIS MODE (2 up to 54 Ports)
  // For enterprise switch layout: Odd ports on top, Even ports on bottom
  const isHighDensity = ifaceList.length >= 16;
  let html = `<div class="chassis-ports-wrapper ${isHighDensity ? 'high-density-matrix' : 'standard-matrix'}">`;

  if (isHighDensity) {
    // Top row: Odd ports (1, 3, 5...)
    html += '<div class="matrix-row matrix-odd-row">';
    for (let i = 0; i < ifaceList.length; i += 2) {
      html += renderPortJack(ifaceList[i]);
    }
    html += '</div>';

    // Bottom row: Even ports (2, 4, 6...)
    html += '<div class="matrix-row matrix-even-row">';
    for (let i = 1; i < ifaceList.length; i += 2) {
      html += renderPortJack(ifaceList[i]);
    }
    html += '</div>';
  } else {
    // Standard linear / flex grid for 2-15 ports
    html += '<div class="matrix-grid-flex">';
    ifaceList.forEach(p => {
      html += renderPortJack(p);
    });
    html += '</div>';
  }

  html += '</div>';
  container.innerHTML = html;
}

/**
 * Helper to render an individual RJ45/SFP Port Jack in the Chassis
 */
function renderPortJack(p) {
  const isUp = (p.state === 'UP' || p.carrier);
  const isUplink = p.port_number >= 49;
  let roleClass = 'role-switch';
  let roleText = 'SWP';

  if (p.port_number === 1) {
    roleClass = 'role-wan';
    roleText = 'WAN';
  } else if (p.port_number === 2) {
    roleClass = 'role-lan';
    roleText = 'LAN';
  } else if (p.port_number === 3) {
    roleClass = 'role-dmz';
    roleText = 'DMZ';
  } else if (isUplink) {
    roleClass = 'role-uplink';
    roleText = 'SFP+';
  }

  return `
    <div class="port-jack ${isUp ? 'is-up' : 'is-down'} ${isUplink ? 'is-uplink' : ''}" 
         id="chassis-port-${escapeHtml(p.name)}"
         title="${escapeHtml(p.display_name)} - ${escapeHtml(p.speed || 'Auto')} | ${isUp ? 'Link UP' : 'Link DOWN'}"
         onclick="highlightTableRow('${p.name}')">
      <div class="port-jack-led-row">
        <span class="led-dot ${isUp ? 'led-up' : 'led-down'}"></span>
        <span class="port-jack-num">${p.port_number}</span>
      </div>
      <div class="port-jack-body">
        <div class="port-jack-name">${escapeHtml(p.name)}</div>
        <div class="port-jack-role ${roleClass}">${roleText}</div>
      </div>
      <div class="port-jack-speed">${escapeHtml(p.speed ? p.speed.split(' ')[0] : '1G')}</div>
    </div>
  `;
}

/**
 * Renders the Structured Physical Interfaces Table
 */
function renderInterfacesTable(ifaceList) {
  const tbody = document.getElementById('physical-iface-tbody');
  if (!tbody) return;

  if (!ifaceList || ifaceList.length === 0) {
    tbody.innerHTML = '<tr><td colspan="11" style="text-align:center; padding:2rem; color:var(--text-muted);">No interfaces detected.</td></tr>';
    return;
  }

  let html = '';
  ifaceList.forEach(p => {
    const isUp = (p.state === 'UP' || p.carrier);
    let roleBadge = '<span class="badge-role badge-switch">Switch</span>';
    if (p.role.includes('WAN')) {
      roleBadge = '<span class="badge-role badge-wan">WAN</span>';
    } else if (p.role.includes('LAN') && !p.role.includes('Single-NIC')) {
      roleBadge = '<span class="badge-role badge-lan">LAN</span>';
    } else if (p.role.includes('Single-NIC')) {
      roleBadge = '<span class="badge-role badge-wan">WAN/LAN</span>';
    } else if (p.role.includes('DMZ')) {
      roleBadge = '<span class="badge-role badge-dmz">DMZ</span>';
    }

    const ipBadges = (p.ip_addresses && p.ip_addresses.length)
      ? p.ip_addresses.map(ip => `<span class="badge badge-info" style="font-family:var(--font-mono);font-size:0.75rem;">${escapeHtml(ip)}</span>`).join(' ')
      : '<span class="text-muted" style="font-size:0.8rem;">Unassigned</span>';

    const throughput = (p.rx_bytes || p.tx_bytes)
      ? `<div style="font-size:0.75rem; font-family:var(--font-mono); line-height:1.2;">
           <span style="color:#059669;">↓ ${formatBytes(p.rx_bytes)}</span><br>
           <span style="color:#2563eb;">↑ ${formatBytes(p.tx_bytes)}</span>
         </div>`
      : '<span class="text-muted" style="font-size:0.8rem;">0 B / 0 B</span>';

    html += `
      <tr id="iface-row-${escapeHtml(p.name)}" class="iface-table-row">
        <td>
          <span style="font-weight:700; color:var(--text-main);">Port ${p.port_number}</span>
        </td>
        <td>
          <strong style="font-family:var(--font-mono);">${escapeHtml(p.name)}</strong>
        </td>
        <td>${roleBadge}</td>
        <td>
          <span class="badge ${isUp ? 'badge-success' : 'badge-secondary'}" style="display:inline-flex; align-items:center; gap:4px;">
            <span class="led-dot ${isUp ? 'led-up' : 'led-down'}" style="width:7px; height:7px;"></span>
            ${isUp ? 'UP' : 'DOWN'}
          </span>
        </td>
        <td><code style="font-size:0.8rem;">${escapeHtml(p.mac || '-')}</code></td>
        <td>${ipBadges}</td>
        <td>
          <span style="font-size:0.85rem;">${escapeHtml(p.speed || 'Auto')}</span>
          <span class="text-muted" style="font-size:0.75rem; display:block;">${escapeHtml(p.duplex || 'Full')}</span>
        </td>
        <td style="font-family:var(--font-mono); font-size:0.85rem;">${p.mtu || 1500}</td>
        <td><span class="text-muted" style="font-size:0.8rem;">${escapeHtml(p.driver || 'ethernet')}</span></td>
        <td>${throughput}</td>
        <td style="text-align: right; white-space: nowrap;">
          <button class="btn btn-sm btn-secondary" onclick="openIfaceModal('${escapeHtml(p.name)}')" title="Configure IP & MTU">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg> Config
          </button>
          <button class="btn btn-sm ${isUp ? 'btn-danger' : 'btn-primary'}" onclick="toggleInterfaceState('${escapeHtml(p.name)}', ${isUp})" title="${isUp ? 'Disable Interface' : 'Enable Interface'}">
            ${isUp ? 'Disable' : 'Enable'}
          </button>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;
}

/**
 * Highlights a table row when clicked on the front-panel chassis port
 */
function highlightTableRow(ifaceName) {
  document.querySelectorAll('.iface-table-row').forEach(r => r.classList.remove('row-highlighted'));
  const target = document.getElementById(`iface-row-${ifaceName}`);
  if (target) {
    target.classList.add('row-highlighted');
    target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

/**
 * Renders compact summary for main dashboard card
 */
function renderDashboardInterfacesSummary(ifaceList) {
  if (!ifaceList || ifaceList.length === 0) {
    return '<div class="text-muted">No physical Ethernet interfaces detected.</div>';
  }

  let html = `<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
    <strong style="font-size:0.9rem;">${ifaceList.length} Physical Interface${ifaceList.length > 1 ? 's' : ''} Detected</strong>
    <span class="badge ${ifaceList.length === 1 ? 'badge-info' : 'badge-success'}">${ifaceList.length === 1 ? 'Single-NIC Mode' : 'Active'}</span>
  </div><div class="dash-port-mini-grid">`;

  ifaceList.forEach(p => {
    const isUp = (p.state === 'UP' || p.carrier);
    html += `
      <div class="dash-port-chip ${isUp ? 'chip-up' : 'chip-down'}" title="${escapeHtml(p.name)}: ${isUp ? 'Connected' : 'Disconnected'}">
        <span class="led-dot ${isUp ? 'led-up' : 'led-down'}" style="width:6px; height:6px;"></span>
        <strong>${escapeHtml(p.name)}</strong>
        <span style="font-size:0.7rem; color:var(--text-muted);">${escapeHtml(p.speed ? p.speed.split(' ')[0] : '1G')}</span>
      </div>
    `;
  });

  html += '</div>';
  return html;
}

/**
 * Toggles Interface Administrative State (UP / DOWN)
 */
async function toggleInterfaceState(ifaceName, currentState) {
  const action = currentState ? 'down' : 'up';
  const confirmMsg = currentState
    ? `Are you sure you want to disable interface ${ifaceName}? Network traffic will stop on this port.`
    : `Bring interface ${ifaceName} UP?`;

  if (!confirm(confirmMsg)) return;

  const res = await apiFetch('/api/interfaces/set', {
    method: 'POST',
    body: JSON.stringify({ interface: ifaceName, action })
  });

  if (res && res.status === 'success') {
    showAlert(res.message || `Interface ${ifaceName} updated.`, 'success');
    await loadInterfaces();
  } else {
    showAlert(res ? res.message : 'Failed to toggle interface state.', 'error');
  }
}

/**
 * Opens IP Configuration Modal for an Interface
 */
function openIfaceModal(ifaceName) {
  const p = currentDetectedInterfaces.find(i => i.name === ifaceName);
  const modal = document.getElementById('iface-config-modal');
  if (!modal) return;

  document.getElementById('iface-modal-title').textContent = `Configure Interface: ${ifaceName} (Port ${p ? p.port_number : ''})`;
  document.getElementById('modal-iface-name').value = ifaceName;

  const ipField = document.getElementById('modal-iface-ip');
  if (ipField) {
    ipField.value = (p && p.ip_addresses && p.ip_addresses.length) ? p.ip_addresses[0] : '';
  }

  const mtuField = document.getElementById('modal-iface-mtu');
  if (mtuField) {
    mtuField.value = (p && p.mtu) ? p.mtu : 1500;
  }

  const resBox = document.getElementById('modal-result-box');
  if (resBox) resBox.style.display = 'none';

  modal.style.display = 'flex';
}

function closeIfaceModal() {
  const modal = document.getElementById('iface-config-modal');
  if (modal) modal.style.display = 'none';
}

function toggleIfaceModeFields() {
  const mode = document.getElementById('modal-iface-mode')?.value;
  const staticFields = document.getElementById('modal-static-fields');
  if (staticFields) {
    staticFields.style.display = (mode === 'dhcp') ? 'none' : 'block';
  }
}

/**
 * Submits Interface IP / MTU configuration to REST API
 */
async function submitIfaceConfig() {
  const ifaceName = document.getElementById('modal-iface-name')?.value;
  const mode = document.getElementById('modal-iface-mode')?.value || 'static';
  const ip = document.getElementById('modal-iface-ip')?.value.trim();
  const gateway = document.getElementById('modal-iface-gw')?.value.trim();
  const mtu = parseInt(document.getElementById('modal-iface-mtu')?.value.trim() || '1500');

  if (mode === 'static' && !ip) {
    showAlert('Please specify an IPv4 address with subnet (e.g. 192.168.1.1/24).', 'error');
    return;
  }

  const payload = {
    interface: ifaceName,
    action: 'configure',
    mode,
    ip,
    gateway,
    mtu
  };

  const res = await apiFetch('/api/interfaces/set', {
    method: 'POST',
    body: JSON.stringify(payload)
  });

  const resBox = document.getElementById('modal-result-box');
  if (res && res.status === 'success') {
    showAlert(res.message || `Interface ${ifaceName} configured successfully!`, 'success');
    closeIfaceModal();
    await loadInterfaces();
  } else {
    if (resBox) {
      resBox.style.display = 'block';
      resBox.innerHTML = `<span style="color:#ef4444;font-weight:700;">✗ Error:</span> ${escapeHtml(res ? res.message : 'Operation failed')}`;
    }
    showAlert(res ? res.message : 'Failed to configure interface', 'error');
  }
}

/**
 * Toggles collapsible raw kernel interface output
 */
function toggleRawOutput() {
  const card = document.getElementById('raw-output-card');
  const btn = document.getElementById('raw-toggle-btn');
  if (!card) return;
  const isHidden = (card.style.display === 'none');
  card.style.display = isHidden ? 'block' : 'none';
  if (btn) btn.textContent = isHidden ? 'Hide Raw' : 'Show Raw';
}

// -----------------------------------------------------------------------------
// Virtual Interfaces Operations (Vether, VLAN, EoIP, VXLAN)
// -----------------------------------------------------------------------------
async function submitCreateVether() {
  const name = document.getElementById('veth-name-input')?.value.trim() || 'veth0';
  const peer = document.getElementById('veth-peer-input')?.value.trim() || 'veth0_peer';
  const res = await apiFetch('/api/interfaces/vether', {
    method: 'POST',
    body: JSON.stringify({ action: 'add', name, peer })
  });
  displayInterfaceOpResult('vether-result-box', res, `Vether ${name} <-> ${peer} created successfully!`);
}

async function submitDeleteVether() {
  const name = document.getElementById('veth-name-input')?.value.trim() || 'veth0';
  const res = await apiFetch('/api/interfaces/vether', {
    method: 'POST',
    body: JSON.stringify({ action: 'delete', name })
  });
  displayInterfaceOpResult('vether-result-box', res, `Vether ${name} removed.`);
}

async function submitCreateVlan() {
  const parent = document.getElementById('vlan-parent-input')?.value.trim() || 'eth0';
  const vid = parseInt(document.getElementById('vlan-id-input')?.value.trim() || '10');
  const res = await apiFetch('/api/interfaces/vlan', {
    method: 'POST',
    body: JSON.stringify({ action: 'add', parent, vlan_id: vid })
  });
  displayInterfaceOpResult('vlan-result-box', res, `VLAN ${parent}.${vid} created successfully!`);
}

async function submitDeleteVlan() {
  const parent = document.getElementById('vlan-parent-input')?.value.trim() || 'eth0';
  const vid = parseInt(document.getElementById('vlan-id-input')?.value.trim() || '10');
  const name = `${parent}.${vid}`;
  const res = await apiFetch('/api/interfaces/vlan', {
    method: 'POST',
    body: JSON.stringify({ action: 'delete', name })
  });
  displayInterfaceOpResult('vlan-result-box', res, `VLAN ${name} removed.`);
}

async function submitCreateEoIP() {
  const name = document.getElementById('eoip-name-input')?.value.trim() || 'eoip0';
  const remote = document.getElementById('eoip-remote-input')?.value.trim();
  const local = document.getElementById('eoip-local-input')?.value.trim();
  const tid = document.getElementById('eoip-tid-input')?.value.trim() || '1';

  if (!remote || !local) {
    showAlert('Remote and Local IP are required for EoIP Tunnel.', 'error');
    return;
  }

  const res = await apiFetch('/api/tunnel/eoip', {
    method: 'POST',
    body: JSON.stringify({ action: 'add', name, remote, local, tid })
  });
  displayInterfaceOpResult('eoip-result-box', res, `EoIP Tunnel ${name} (ID: ${tid}) established!`);
}

async function submitDeleteEoIP() {
  const name = document.getElementById('eoip-name-input')?.value.trim() || 'eoip0';
  const res = await apiFetch('/api/tunnel/eoip', {
    method: 'POST',
    body: JSON.stringify({ action: 'delete', name })
  });
  displayInterfaceOpResult('eoip-result-box', res, `EoIP Tunnel ${name} removed.`);
}

async function submitCreateVxLAN() {
  const name = document.getElementById('vxlan-name-input')?.value.trim() || 'vxlan0';
  const vni = document.getElementById('vxlan-vni-input')?.value.trim() || '100';
  const remote = document.getElementById('vxlan-remote-input')?.value.trim();
  const local = document.getElementById('vxlan-local-input')?.value.trim();

  if (!remote || !local) {
    showAlert('Remote and Local VTEP IP are required for VXLAN.', 'error');
    return;
  }

  const res = await apiFetch('/api/tunnel/vxlan', {
    method: 'POST',
    body: JSON.stringify({ action: 'add', name, vni, remote, local })
  });
  displayInterfaceOpResult('vxlan-result-box', res, `VXLAN Overlay ${name} (VNI: ${vni}) deployed!`);
}

async function submitDeleteVxLAN() {
  const name = document.getElementById('vxlan-name-input')?.value.trim() || 'vxlan0';
  const res = await apiFetch('/api/tunnel/vxlan', {
    method: 'POST',
    body: JSON.stringify({ action: 'delete', name })
  });
  displayInterfaceOpResult('vxlan-result-box', res, `VXLAN Overlay ${name} removed.`);
}

function displayInterfaceOpResult(boxId, res, successMsg) {
  const box = document.getElementById(boxId);
  if (!box) return;
  box.style.display = 'block';
  if (res && res.status === 'success') {
    box.innerHTML = `<span class="ansi-emerald" style="color:#10b981;font-weight:700;">✓ ${escapeHtml(successMsg)}</span>\n${escapeHtml(res.output || '')}`;
    showAlert(successMsg, 'success');
  } else {
    box.innerHTML = `<span class="ansi-rose" style="color:#f43f5e;font-weight:700;">✗ Error:</span>\n${escapeHtml(res ? (res.message || res.output || 'Operation failed') : 'Connection error')}`;
    showAlert('Interface operation failed', 'error');
  }
}
