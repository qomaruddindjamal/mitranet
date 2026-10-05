// ==============================================================================
// MitraNet Network OS - Firewall Package JS
// Rules, Aliases, NAT (Port Forward, 1:1, Outbound), Blacklist & Netfilter Engine
// ==============================================================================

let currentFwRules = [];
let activeIfaceFilter = 'all';
let currentAliases = [];
let activeAliasFilter = 'all';

async function loadFirewall() {
  await loadFirewallStatus();
  if (document.getElementById('fw-rules-tbody')) await loadFirewallRules();
  if (document.getElementById('fw-aliases-tbody')) await loadAliases();
  if (document.getElementById('fw-forwards-tbody')) await loadPortForwards();
  if (document.getElementById('fw-1to1-tbody')) await load1to1Nat();
  if (document.getElementById('fw-outbound-tbody')) await loadOutboundNat();
  if (document.getElementById('fw-blacklist-tbody')) await loadBlacklist();
  if (document.getElementById('raw-firewall-logs')) await loadFirewallLogs();
  if (document.getElementById('raw-firewall-box')) await loadFirewallRaw();
}

function checkFirewallDirty(isDirty) {
  const alertEl = document.getElementById('fw-apply-alert');
  if (alertEl) {
    if (isDirty) alertEl.classList.remove('hidden');
    else alertEl.classList.add('hidden');
  }
}

async function applyFirewallPendingChanges() {
  const btn = document.getElementById('btn-apply-fw-changes');
  if (btn) btn.disabled = true;
  showAlert('Compiling & activating nftables ruleset in kernel...', 'info');

  const res = await apiFetch('/api/firewall/apply', { method: 'POST' });
  if (btn) btn.disabled = false;

  if (res && res.status === 'success') {
    showAlert('Firewall changes successfully applied and activated in kernel nftables!', 'success');
    checkFirewallDirty(false);
    await loadFirewall();
  } else {
    showAlert('Failed to apply changes: ' + (res?.message || 'Error'), 'error');
  }
}

async function loadFirewallStatus() {
  const status = await apiFetch('/api/firewall/status');
  if (!status) return;

  const activeConnsEl = document.getElementById('fw-active-conns');
  if (activeConnsEl) activeConnsEl.innerHTML = `${status.active_connections} <span class="unit">sessions</span>`;

  const conntrackBar = document.getElementById('fw-conntrack-bar');
  if (conntrackBar) {
    const pct = Math.min(100, Math.max(5, Math.round((status.active_connections / (status.conntrack_max || 262144)) * 100)));
    conntrackBar.style.width = `${pct}%`;
  }

  const dropsEl = document.getElementById('fw-drops-count');
  if (dropsEl) dropsEl.innerHTML = `${status.dropped_packets} <span class="unit">packets</span>`;

  const dropsBar = document.getElementById('fw-drops-bar');
  if (dropsBar) {
    const dPct = Math.min(100, Math.max(5, Math.round((status.dropped_packets / 100) * 100)));
    dropsBar.style.width = `${dPct}%`;
  }

  checkFirewallDirty(Boolean(status.dirty));

  const flowtableChk = document.getElementById('sec-toggle-flowtable');
  if (flowtableChk) flowtableChk.checked = Boolean(status.fastpath_active);

  const synfloodChk = document.getElementById('sec-toggle-synflood');
  if (synfloodChk) synfloodChk.checked = Boolean(status.syn_flood_protection);

  const wanpingChk = document.getElementById('sec-toggle-wanping');
  if (wanpingChk) wanpingChk.checked = Boolean(status.wan_ping_allowed);
}

function filterRulesByIface(iface) {
  activeIfaceFilter = iface;
  document.querySelectorAll('.interface-pills .interface-pill').forEach(btn => btn.classList.remove('active'));
  const activeBtn = document.getElementById(`rule-iface-btn-${iface}`);
  if (activeBtn) activeBtn.classList.add('active');

  renderFirewallRules();
}

function updateIfacePillCounts() {
  const allCount = currentFwRules.length;
  const wanCount = currentFwRules.filter(r => (r.interface || '').toLowerCase() === 'wan').length;
  const lanCount = currentFwRules.filter(r => (r.interface || '').toLowerCase() === 'lan').length;
  const dmzCount = currentFwRules.filter(r => (r.interface || '').toLowerCase() === 'dmz').length;
  const vpnCount = currentFwRules.filter(r => (r.interface || '').toLowerCase() === 'vpn').length;
  const floatCount = currentFwRules.filter(r => !r.interface || r.interface === 'any' || r.interface === 'floating').length;

  const setEl = (id, count) => {
    const el = document.getElementById(id);
    if (el) el.textContent = count;
  };
  setEl('rule-cnt-all', allCount);
  setEl('rule-cnt-wan', wanCount);
  setEl('rule-cnt-lan', lanCount);
  setEl('rule-cnt-dmz', dmzCount);
  setEl('rule-cnt-vpn', vpnCount);
  setEl('rule-cnt-floating', floatCount);
}

function renderFirewallRules() {
  const tbody = document.getElementById('fw-rules-tbody');
  if (!tbody) return;

  updateIfacePillCounts();

  let filtered = currentFwRules;
  if (activeIfaceFilter !== 'all') {
    if (activeIfaceFilter === 'floating') {
      filtered = currentFwRules.filter(r => !r.interface || r.interface === 'any' || r.interface === 'floating');
    } else {
      filtered = currentFwRules.filter(r => (r.interface || '').toLowerCase() === activeIfaceFilter);
    }
  }

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No filter rules configured for ${escapeHtml(activeIfaceFilter.toUpperCase())}.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(r => {
    const isEnabled = r.enabled !== false;
    const actionClass = r.action === 'accept' ? 'badge-pass' : (r.action === 'drop' ? 'badge-block' : 'badge-reject');
    const actionLabel = r.action.toUpperCase();
    const ifaceLower = (r.interface || 'floating').toLowerCase();
    const zoneClass = ifaceLower === 'wan' ? 'badge-zone-wan' : (ifaceLower === 'lan' ? 'badge-zone-lan' : (ifaceLower === 'vpn' ? 'badge-zone-vpn' : (ifaceLower === 'dmz' ? 'badge-zone-dmz' : 'badge-zone-floating')));

    return `
      <tr class="${isEnabled ? '' : 'text-muted'}" style="${isEnabled ? '' : 'opacity: 0.6;'}">
        <td>
          <button class="btn btn-sm ${isEnabled ? 'btn-success' : 'btn-secondary'}" style="padding: 2px 8px; font-size: 0.75rem;" onclick="toggleRuleState('${r.id}', ${isEnabled})" title="Click to ${isEnabled ? 'Disable' : 'Enable'} rule">
            ${isEnabled ? 'Enabled' : 'Disabled'}
          </button>
        </td>
        <td><span class="${actionClass}">${actionLabel}</span></td>
        <td class="mono font-bold">${escapeHtml((r.chain || 'input').toUpperCase())}</td>
        <td><span class="${zoneClass}">${escapeHtml((r.interface || 'FLOATING').toUpperCase())}</span></td>
        <td class="mono">${escapeHtml((r.protocol || 'any').toUpperCase())}</td>
        <td class="mono">${escapeHtml(r.src || 'any')}</td>
        <td class="mono">${escapeHtml(r.port || 'any')}</td>
        <td>${escapeHtml(r.descr || '')}</td>
        <td style="text-align: right;">
          <button class="btn btn-sm btn-danger" onclick="deleteFirewallRule('${r.id}')" title="Delete Rule">
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

async function loadFirewallRules() {
  const data = await apiFetch('/api/firewall/rules');
  if (data && data.rules) {
    currentFwRules = data.rules;
    renderFirewallRules();
  }
}

async function toggleRuleState(ruleId, currentState) {
  const res = await apiFetch('/api/firewall/rules/toggle', {
    method: 'POST',
    body: JSON.stringify({ id: ruleId, enabled: !currentState })
  });
  if (res && res.status === 'success') {
    showAlert(`Rule ${!currentState ? 'enabled' : 'disabled'}. Click Apply Changes to activate.`, 'info');
    checkFirewallDirty(true);
    const target = currentFwRules.find(r => r.id === ruleId);
    if (target) target.enabled = !currentState;
    renderFirewallRules();
  }
}

function showAddRuleModal() {
  document.getElementById('add-rule-panel')?.classList.remove('hidden');
}

function hideAddRuleModal() {
  document.getElementById('add-rule-panel')?.classList.add('hidden');
}

async function submitAddRule() {
  const action = document.getElementById('new-rule-action')?.value || 'accept';
  const chain = document.getElementById('new-rule-chain')?.value || 'input';
  const iface = document.getElementById('new-rule-zone')?.value || 'lan';
  const proto = document.getElementById('new-rule-proto')?.value || 'tcp';
  const src = document.getElementById('new-rule-src')?.value || 'any';
  const port = document.getElementById('new-rule-port')?.value || 'any';
  const descr = document.getElementById('new-rule-descr')?.value || 'Custom User Rule';

  const res = await apiFetch('/api/firewall/rules/add', {
    method: 'POST',
    body: JSON.stringify({ action, chain, interface: iface, protocol: proto, src, port, descr })
  });

  if (res && res.status === 'success') {
    showAlert('Firewall rule staged! Click Apply Changes to activate in kernel.', 'info');
    checkFirewallDirty(true);
    hideAddRuleModal();
    loadFirewallRules();
  }
}

async function deleteFirewallRule(ruleId) {
  if (!confirm('Are you sure you want to remove this firewall rule?')) return;
  const res = await apiFetch('/api/firewall/rules/delete', {
    method: 'POST',
    body: JSON.stringify({ id: ruleId })
  });
  if (res && res.status === 'success') {
    showAlert('Rule removed from staged config. Click Apply Changes to activate.', 'info');
    checkFirewallDirty(true);
    loadFirewallRules();
  }
}

// Aliases
async function loadAliases() {
  const data = await apiFetch('/api/firewall/aliases');
  const tbody = document.getElementById('fw-aliases-tbody');
  if (!tbody || !data || !data.aliases) return;

  currentAliases = data.aliases;
  renderAliases();
}

function filterAliasesByType(type) {
  activeAliasFilter = type;
  document.querySelectorAll('.interface-pills .interface-pill').forEach(btn => {
    btn.classList.remove('active');
  });
  const activeBtn = document.getElementById(`alias-cat-btn-${type}`);
  if (activeBtn) activeBtn.classList.add('active');
  renderAliases();
}

function renderAliases() {
  const tbody = document.getElementById('fw-aliases-tbody');
  if (!tbody) return;

  let filtered = currentAliases;
  if (activeAliasFilter !== 'all') {
    filtered = currentAliases.filter(a => a.type === activeAliasFilter);
  }

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No aliases configured for ${escapeHtml(activeAliasFilter.toUpperCase())}.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(a => {
    const typeBadge = a.type === 'host' ? 'badge-primary' : (a.type === 'network' ? 'badge-indigo' : (a.type === 'port' ? 'badge-cyan' : 'badge-amber'));
    const vals = Array.isArray(a.values) ? a.values.join(', ') : a.values;
    return `
      <tr>
        <td class="mono font-bold text-primary">${escapeHtml(a.name)}</td>
        <td><span class="badge ${typeBadge}">${escapeHtml(a.type.toUpperCase())}</span></td>
        <td class="mono" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(vals)}">${escapeHtml(vals)}</td>
        <td>${escapeHtml(a.descr || '')}</td>
        <td style="text-align: right;">
          <button class="btn btn-sm btn-danger" onclick="deleteAlias('${a.id}')">Delete</button>
        </td>
      </tr>
    `;
  }).join('');
}

function showAddAliasModal() {
  document.getElementById('add-alias-panel')?.classList.remove('hidden');
}

function hideAddAliasModal() {
  document.getElementById('add-alias-panel')?.classList.add('hidden');
}

async function submitAddAlias() {
  const name = document.getElementById('new-alias-name')?.value?.trim();
  const type = document.getElementById('new-alias-type')?.value;
  const valuesStr = document.getElementById('new-alias-values')?.value?.trim();
  const descr = document.getElementById('new-alias-descr')?.value?.trim();

  if (!name || !valuesStr) {
    showAlert('Please specify Alias Name and Values.', 'error');
    return;
  }

  const values = valuesStr.split(/[\n,;]+/).map(s => s.trim()).filter(Boolean);

  const res = await apiFetch('/api/firewall/aliases/add', {
    method: 'POST',
    body: JSON.stringify({ name, type, values, descr })
  });

  if (res && res.status === 'success') {
    showAlert('Alias added! Click Apply Changes to update firewall ruleset.', 'info');
    checkFirewallDirty(true);
    hideAddAliasModal();
    loadAliases();
  }
}

async function deleteAlias(aliasId) {
  if (!confirm('Are you sure you want to delete this alias?')) return;
  const res = await apiFetch('/api/firewall/aliases/delete', {
    method: 'POST',
    body: JSON.stringify({ id: aliasId })
  });
  if (res && res.status === 'success') {
    showAlert('Alias deleted. Click Apply Changes to compile.', 'info');
    checkFirewallDirty(true);
    loadAliases();
  }
}

// 1:1 NAT
async function load1to1Nat() {
  const data = await apiFetch('/api/firewall/nat/1to1');
  const tbody = document.getElementById('fw-1to1-tbody');
  if (!tbody || !data || !data.nat_1to1) return;

  if (data.nat_1to1.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No 1:1 static NAT mappings configured.</td></tr>';
    return;
  }

  tbody.innerHTML = data.nat_1to1.map(entry => {
    const isEnabled = entry.enabled !== false;
    return `
      <tr class="${isEnabled ? '' : 'text-muted'}" style="${isEnabled ? '' : 'opacity: 0.6;'}">
        <td>
          <button class="btn btn-sm ${isEnabled ? 'btn-success' : 'btn-secondary'}" style="padding: 2px 8px; font-size: 0.75rem;" onclick="toggle1to1State('${entry.id}', ${isEnabled})">
            ${isEnabled ? 'Enabled' : 'Disabled'}
          </button>
        </td>
        <td><span class="badge badge-zone-wan">${escapeHtml((entry.interface || 'WAN').toUpperCase())}</span></td>
        <td class="mono font-bold text-cyan">${escapeHtml(entry.external_ip)}</td>
        <td class="mono font-bold text-primary">${escapeHtml(entry.internal_ip)}</td>
        <td>${escapeHtml(entry.descr || '')}</td>
        <td style="text-align: right;">
          <button class="btn btn-sm btn-danger" onclick="delete1to1Nat('${entry.id}')">Delete</button>
        </td>
      </tr>
    `;
  }).join('');
}

function showAdd1to1Modal() {
  document.getElementById('add-1to1-panel')?.classList.remove('hidden');
}

function hideAdd1to1Modal() {
  document.getElementById('add-1to1-panel')?.classList.add('hidden');
}

async function submitAdd1to1() {
  const iface = document.getElementById('new-1to1-iface')?.value || 'wan';
  const extIp = document.getElementById('new-1to1-extip')?.value?.trim();
  const intIp = document.getElementById('new-1to1-intip')?.value?.trim();
  const descr = document.getElementById('new-1to1-descr')?.value?.trim() || '';

  if (!extIp || !intIp) {
    showAlert('External IP and Internal IP are required.', 'error');
    return;
  }

  const res = await apiFetch('/api/firewall/nat/1to1/add', {
    method: 'POST',
    body: JSON.stringify({ interface: iface, external_ip: extIp, internal_ip: intIp, descr })
  });

  if (res && res.status === 'success') {
    showAlert('1:1 NAT mapping added! Click Apply Changes to activate.', 'info');
    checkFirewallDirty(true);
    hideAdd1to1Modal();
    load1to1Nat();
  }
}

async function delete1to1Nat(entryId) {
  if (!confirm('Delete this 1:1 NAT entry?')) return;
  const res = await apiFetch('/api/firewall/nat/1to1/delete', {
    method: 'POST',
    body: JSON.stringify({ id: entryId })
  });
  if (res && res.status === 'success') {
    showAlert('1:1 NAT mapping removed. Click Apply Changes to compile.', 'info');
    checkFirewallDirty(true);
    load1to1Nat();
  }
}

async function toggle1to1State(entryId, currentState) {
  const res = await apiFetch('/api/firewall/nat/1to1/toggle', {
    method: 'POST',
    body: JSON.stringify({ id: entryId, enabled: !currentState })
  });
  if (res && res.status === 'success') {
    showAlert(`1:1 NAT entry ${!currentState ? 'enabled' : 'disabled'}. Click Apply Changes to activate.`, 'info');
    checkFirewallDirty(true);
    load1to1Nat();
  }
}

// Outbound NAT
async function loadOutboundNat() {
  const data = await apiFetch('/api/firewall/nat/outbound');
  if (!data) return;

  const mode = data.mode || 'automatic';
  const radio = document.getElementById(`out-mode-${mode}`);
  if (radio) radio.checked = true;

  const tbody = document.getElementById('fw-outbound-tbody');
  if (!tbody) return;

  const rules = data.rules || [];
  if (rules.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No custom outbound NAT rules. Using standard interface masquerading.</td></tr>';
    return;
  }

  tbody.innerHTML = rules.map(r => `
    <tr>
      <td><span class="badge badge-zone-wan">${escapeHtml((r.interface || 'WAN').toUpperCase())}</span></td>
      <td class="mono">${escapeHtml(r.src || 'any')}</td>
      <td class="mono">${escapeHtml(r.dst || 'any')}</td>
      <td class="mono font-bold text-success">${escapeHtml(r.target_ip || 'interface')}</td>
      <td>${escapeHtml(r.descr || '')}</td>
      <td style="text-align: right;">
        <button class="btn btn-sm btn-danger" onclick="deleteOutboundRule('${r.id}')">Delete</button>
      </td>
    </tr>
  `).join('');
}

async function saveOutboundNatMode() {
  const selected = document.querySelector('input[name="outbound-mode-radio"]:checked')?.value || 'automatic';
  const res = await apiFetch('/api/firewall/nat/outbound/mode', {
    method: 'POST',
    body: JSON.stringify({ mode: selected })
  });

  if (res && res.status === 'success') {
    showAlert(`Outbound NAT mode updated to ${selected.toUpperCase()}! Click Apply Changes to compile.`, 'info');
    checkFirewallDirty(true);
    loadOutboundNat();
  }
}

function showAddOutboundModal() {
  document.getElementById('add-outbound-panel')?.classList.remove('hidden');
}

function hideAddOutboundModal() {
  document.getElementById('add-outbound-panel')?.classList.add('hidden');
}

async function submitAddOutbound() {
  const iface = document.getElementById('new-out-iface')?.value || 'wan';
  const src = document.getElementById('new-out-src')?.value?.trim() || '192.168.1.0/24';
  const dst = document.getElementById('new-out-dst')?.value?.trim() || 'any';
  const targetIp = document.getElementById('new-out-natip')?.value?.trim() || 'wan_interface';
  const descr = document.getElementById('new-out-descr')?.value?.trim() || '';

  const res = await apiFetch('/api/firewall/nat/outbound/add', {
    method: 'POST',
    body: JSON.stringify({ interface: iface, src, dst, target_ip: targetIp, descr })
  });

  if (res && res.status === 'success') {
    showAlert('Outbound NAT rule added! Click Apply Changes to activate.', 'info');
    checkFirewallDirty(true);
    hideAddOutboundModal();
    loadOutboundNat();
  }
}

async function deleteOutboundRule(ruleId) {
  if (!confirm('Delete this Outbound NAT rule?')) return;
  const res = await apiFetch('/api/firewall/nat/outbound/delete', {
    method: 'POST',
    body: JSON.stringify({ id: ruleId })
  });
  if (res && res.status === 'success') {
    showAlert('Outbound NAT rule deleted. Click Apply Changes to compile.', 'info');
    checkFirewallDirty(true);
    loadOutboundNat();
  }
}

// Port Forwarding
async function loadPortForwards() {
  const data = await apiFetch('/api/firewall/forwards');
  const tbody = document.getElementById('fw-forwards-tbody');
  if (!tbody || !data || !data.port_forwards) return;

  if (data.port_forwards.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No port forward mappings configured.</td></tr>';
    return;
  }

  tbody.innerHTML = data.port_forwards.map(pf => `
    <tr>
      <td class="mono font-bold text-cyan">${escapeHtml(pf.wan_port)}</td>
      <td class="mono">${escapeHtml(pf.protocol.toUpperCase())}</td>
      <td class="mono">${escapeHtml(pf.target_ip)}</td>
      <td class="mono">${escapeHtml(pf.target_port)}</td>
      <td>${escapeHtml(pf.descr || '')}</td>
      <td style="text-align: right;">
        <button class="btn btn-sm btn-danger" onclick="deletePortForward('${pf.id}')">Delete</button>
      </td>
    </tr>
  `).join('');
}

function showAddFwdModal() {
  document.getElementById('add-fwd-panel')?.classList.remove('hidden');
}

function hideAddFwdModal() {
  document.getElementById('add-fwd-panel')?.classList.add('hidden');
}

async function submitAddForward() {
  const wanPort = document.getElementById('new-fwd-wanport')?.value;
  const proto = document.getElementById('new-fwd-proto')?.value || 'tcp';
  const targetIp = document.getElementById('new-fwd-targetip')?.value;
  const targetPort = document.getElementById('new-fwd-targetport')?.value || wanPort;
  const descr = document.getElementById('new-fwd-descr')?.value || '';

  if (!wanPort || !targetIp) {
    showAlert('Please specify WAN port and Target IP.', 'error');
    return;
  }

  const res = await apiFetch('/api/firewall/forwards/add', {
    method: 'POST',
    body: JSON.stringify({ wan_port: wanPort, protocol: proto, target_ip: targetIp, target_port: targetPort, descr })
  });

  if (res && res.status === 'success') {
    showAlert('Port forward applied to NAT prerouting!', 'success');
    hideAddFwdModal();
    loadPortForwards();
  }
}

async function deletePortForward(fwdId) {
  if (!confirm('Delete this port forwarding rule?')) return;
  const res = await apiFetch('/api/firewall/forwards/delete', {
    method: 'POST',
    body: JSON.stringify({ id: fwdId })
  });
  if (res && res.status === 'success') {
    showAlert('Port forward deleted.', 'success');
    loadPortForwards();
  }
}

// Blacklist
async function loadBlacklist() {
  const data = await apiFetch('/api/firewall/blacklist');
  const tbody = document.getElementById('fw-blacklist-tbody');
  if (!tbody || !data || !data.blacklist) return;

  if (data.blacklist.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:1.5rem;color:var(--text-dim);">No blacklisted threat IPs.</td></tr>';
    return;
  }

  tbody.innerHTML = data.blacklist.map(b => `
    <tr>
      <td class="mono font-bold" style="color:var(--accent-rose);">${escapeHtml(b.ip)}</td>
      <td>${escapeHtml(b.reason || 'Manual block')}</td>
      <td class="mono text-dim">${escapeHtml(b.added_at || 'Recently')}</td>
      <td style="text-align: right;">
        <button class="btn btn-sm btn-secondary" onclick="unbanIP('${b.ip}')">Unban IP</button>
      </td>
    </tr>
  `).join('');
}

async function submitBlacklistIP() {
  const ipInput = document.getElementById('blacklist-ip-input');
  const reasonInput = document.getElementById('blacklist-reason-input');
  const ip = ipInput?.value?.trim();
  const reason = reasonInput?.value?.trim() || 'Manual CLI/UI block';

  if (!ip) {
    showAlert('Enter IP or CIDR subnet to block.', 'error');
    return;
  }

  const res = await apiFetch('/api/firewall/blacklist/add', {
    method: 'POST',
    body: JSON.stringify({ ip, reason })
  });

  if (res && res.status === 'success') {
    showAlert(`IP ${ip} blocked by MitraNet Firewall!`, 'success');
    if (ipInput) ipInput.value = '';
    loadBlacklist();
  }
}

async function unbanIP(ip) {
  const res = await apiFetch('/api/firewall/blacklist/delete', {
    method: 'POST',
    body: JSON.stringify({ ip })
  });
  if (res && res.status === 'success') {
    showAlert(`IP ${ip} unblocked.`, 'success');
    loadBlacklist();
  }
}

async function saveSecurityPolicies() {
  const fastpath = document.getElementById('sec-toggle-flowtable')?.checked;
  const synflood = document.getElementById('sec-toggle-synflood')?.checked;
  const portscan = document.getElementById('sec-toggle-portscan')?.checked;
  const wanping = document.getElementById('sec-toggle-wanping')?.checked;

  const res = await apiFetch('/api/firewall/security', {
    method: 'POST',
    body: JSON.stringify({
      fastpath_flowtable: fastpath,
      syn_flood_protection: synflood,
      drop_port_scans: portscan,
      allow_wan_ping: wanping
    })
  });

  if (res && res.status === 'success') {
    showAlert('Security policies applied to kernel NFTables!', 'success');
    loadFirewallStatus();
  }
}

async function loadFirewallLogs() {
  const data = await apiFetch('/api/firewall/logs');
  const box = document.getElementById('raw-firewall-logs');
  if (!box || !data) return;
  box.textContent = (data.logs && data.logs.length > 0) ? data.logs.join('\n') : 'No dropped threat logs recorded.';
}

async function loadFirewallRaw() {
  const data = await apiFetch('/api/firewall');
  const box = document.getElementById('raw-firewall-box');
  if (!box || !data) return;
  box.textContent = data.ruleset || 'No ruleset active.';
}
