// ==============================================================================
// MitraNet Network OS - Security Zones Package JS
// Zero-Trust Zone-Based Policy Architecture & Directional Isolation Matrix
// ==============================================================================

async function loadSecurityZones() {
  const data = await apiFetch('/api/enterprise/zones');
  const tbody = document.getElementById('zones-table-tbody');
  if (!tbody || !data || !data.zone_policies) return;

  const countBadge = document.getElementById('zones-count-badge');
  if (countBadge && data.zones) countBadge.textContent = `${Object.keys(data.zones).length} ZONES`;

  const countVal = document.getElementById('zones-count-val');
  if (countVal && data.zones) countVal.innerHTML = `${Object.keys(data.zones).length} <span class="unit">configured</span>`;

  const polCount = document.getElementById('zone-policies-count');
  if (polCount && data.zone_policies) polCount.innerHTML = `${data.zone_policies.length} <span class="unit">rules</span>`;

  if (data.zone_policies.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No inter-zone security policies configured.</td></tr>';
    return;
  }

  tbody.innerHTML = data.zone_policies.map(p => {
    const actClass = p.action === 'permit' ? 'badge-pass' : (p.action === 'drop' ? 'badge-block' : 'badge-reject');
    return `
      <tr>
        <td><span class="badge-zone-lan">${escapeHtml(p.from_zone.toUpperCase())}</span></td>
        <td><span class="badge-zone-wan">${escapeHtml(p.to_zone.toUpperCase())}</span></td>
        <td><span class="${actClass}">${escapeHtml(p.action.toUpperCase())}</span></td>
        <td class="mono text-cyan">Stateful Conntrack (Established)</td>
        <td>${escapeHtml(p.description || '')}</td>
        <td style="text-align: right;">
          <span class="badge badge-success">ACTIVE</span>
        </td>
      </tr>
    `;
  }).join('');
}

function showAddZonePolicyModal() {
  document.getElementById('add-zone-policy-panel')?.classList.remove('hidden');
}

function hideAddZonePolicyModal() {
  document.getElementById('add-zone-policy-panel')?.classList.add('hidden');
}

async function submitAddZonePolicy() {
  const from_zone = document.getElementById('new-policy-from')?.value || 'trust';
  const to_zone = document.getElementById('new-policy-to')?.value || 'untrust';
  const action = document.getElementById('new-policy-action')?.value || 'permit';
  const description = document.getElementById('new-policy-desc')?.value.trim() || '';

  const res = await apiFetch('/api/enterprise/zones/policy', {
    method: 'POST',
    body: JSON.stringify({ from_zone, to_zone, action, description })
  });

  if (res && res.status === 'success') {
    showAlert('Security Zone Policy applied!', 'success');
    hideAddZonePolicyModal();
    loadSecurityZones();
  } else {
    showAlert('Failed to apply zone policy: ' + (res?.message || 'Error'), 'error');
  }
}
