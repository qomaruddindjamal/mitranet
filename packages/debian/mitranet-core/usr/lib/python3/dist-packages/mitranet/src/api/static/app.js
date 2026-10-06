/**
 * MitraNet Management UI Application Logic (app.js).
 * Handles authentication, CSRF tokens, dynamic views, API requests, and live telemetry.
 * Zero external CDN dependencies (100% offline-ready).
 */

const app = {
  csrfToken: "",
  activeTab: "dashboard",
  timer: null,

  async init() {
    this.startClock();
    await this.checkAuth();
  },

  startClock() {
    setInterval(() => {
      const el = document.getElementById("clock-display");
      if (el) el.textContent = new Date().toTimeString().split(" ")[0] + " UTC";
    }, 1000);
  },

  async api(endpoint, method = "GET", body = null) {
    const headers = {
      "Accept": "application/json",
    };
    if (this.csrfToken && method !== "GET") {
      headers["X-CSRF-Token"] = this.csrfToken;
    }
    if (body) {
      headers["Content-Type"] = "application/json";
    }

    const opts = { method, headers };
    if (body) opts.body = JSON.stringify(body);

    const res = await fetch(endpoint, opts);
    if (res.status === 401 && endpoint !== "/api/v1/auth/login") {
      this.showLogin(true);
      throw new Error("Unauthorized");
    }
    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.error || "API request failed");
    }
    return data;
  },

  async checkAuth() {
    try {
      const data = await this.api("/api/v1/auth/status");
      if (data.authenticated) {
        this.csrfToken = data.csrf_token;
        document.getElementById("nav-user").textContent = data.username;
        this.showLogin(false);
        this.navigate(this.activeTab);
      } else {
        this.showLogin(true);
      }
    } catch {
      this.showLogin(true);
    }
  },

  showLogin(show) {
    const el = document.getElementById("login-modal");
    if (el) el.style.display = show ? "flex" : "none";
  },

  async login() {
    const u = document.getElementById("login-user").value.trim();
    const p = document.getElementById("login-pass").value;
    const errEl = document.getElementById("login-err");
    errEl.style.display = "none";

    try {
      const data = await this.api("/api/v1/auth/login", "POST", { username: u, password: p });
      if (data.success) {
        this.csrfToken = data.csrf_token;
        document.getElementById("nav-user").textContent = data.username;
        this.showLogin(false);
        this.navigate("dashboard");
      }
    } catch (e) {
      errEl.textContent = e.message;
      errEl.style.display = "block";
    }
  },

  async logout() {
    try {
      await this.api("/api/v1/auth/logout", "POST");
    } catch {}
    this.csrfToken = "";
    this.showLogin(true);
  },

  navigate(tab) {
    this.activeTab = tab;
    document.querySelectorAll(".nav-item").forEach(el => el.classList.remove("active"));
    const activeNav = Array.from(document.querySelectorAll(".nav-item")).find(el => el.textContent.toLowerCase().includes(tab));
    if (activeNav) activeNav.classList.add("active");

    const titleEl = document.getElementById("page-title");
    if (titleEl) titleEl.textContent = tab.toUpperCase();

    if (this.timer) clearInterval(this.timer);

    switch (tab) {
      case "dashboard": this.renderDashboard(); break;
      case "interfaces": this.renderInterfaces(); break;
      case "routing": this.renderRouting(); break;
      case "vlans": this.renderVLANs(); break;
      case "bridges": this.renderBridges(); break;
      case "bonds": this.renderBonds(); break;
      case "vrfs": this.renderVRFs(); break;
      case "firewall": this.renderFirewall(); break;
      case "gateways": this.renderGateways(); break;
      case "config": this.renderConfig(); break;
      case "logs": this.renderLogs(); break;
    }
  },

  // =========================================================================
  // VIEW RENDERERS
  // =========================================================================

  async renderDashboard() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading dashboard metrics...</p>";

    try {
      const [sys, ifaces, fw, gws] = await Promise.all([
        this.api("/api/v1/system"),
        this.api("/api/v1/interfaces"),
        this.api("/api/v1/firewall"),
        this.api("/api/v1/gateways"),
      ]);

      const uptimeHours = (sys.uptime_seconds / 3600).toFixed(1);
      const activeIfaces = ifaces.filter(i => i.is_up).length;

      pane.innerHTML = `
        <div class="grid-cards">
          <div class="stat-card">
            <div class="label">System Hostname</div>
            <div class="value">${sys.hostname}</div>
            <div class="subtext">${sys.pretty_name} (${sys.kernel})</div>
          </div>
          <div class="stat-card">
            <div class="label">System Uptime</div>
            <div class="value">${uptimeHours} hrs</div>
            <div class="subtext">Load normal</div>
          </div>
          <div class="stat-card">
            <div class="label">Memory Usage</div>
            <div class="value">${sys.memory.used_percent}%</div>
            <div class="subtext">${Math.round(sys.memory.used_kb/1024)} MB / ${Math.round(sys.memory.total_kb/1024)} MB</div>
          </div>
          <div class="stat-card">
            <div class="label">Firewall State</div>
            <div class="value">${fw.status.active ? "ACTIVE" : "STANDBY"}</div>
            <div class="subtext">${fw.status.rule_count} rules compiled</div>
          </div>
        </div>

        <div class="section-card">
          <div class="section-header">
            <div class="section-title">Network Interface Summary</div>
            <button class="btn btn-outline" onclick="app.navigate('interfaces')">Manage Interfaces</button>
          </div>
          <table class="data-table">
            <thead>
              <tr><th>Interface</th><th>State</th><th>MAC</th><th>IPv4 Addresses</th><th>Type</th></tr>
            </thead>
            <tbody>
              ${ifaces.map(i => `
                <tr>
                  <td><b>${i.name}</b></td>
                  <td><span class="badge ${i.is_up ? 'badge-up' : 'badge-down'}">${i.is_up ? 'UP' : 'DOWN'}</span></td>
                  <td>${i.mac_address || 'None'}</td>
                  <td>${i.ipv4_addresses.join(', ') || 'Unassigned'}</td>
                  <td>${i.type}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<div class="section-card"><p style="color:var(--accent-red)">Failed loading dashboard: ${e.message}</p></div>`;
    }
  },

  async renderInterfaces() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading interface details...</p>";

    try {
      const ifaces = await this.api("/api/v1/interfaces");
      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">Kernel Network Interfaces</div>
            <button class="btn btn-outline" onclick="app.renderInterfaces()">Refresh</button>
          </div>
          <table class="data-table">
            <thead>
              <tr><th>Name</th><th>Status</th><th>MTU</th><th>IPv4</th><th>IPv6</th><th>RX / TX Bytes</th><th>Actions</th></tr>
            </thead>
            <tbody>
              ${ifaces.map(i => `
                <tr>
                  <td><b>${i.name}</b></td>
                  <td><span class="badge ${i.is_up ? 'badge-up' : 'badge-down'}">${i.is_up ? 'UP' : 'DOWN'}</span></td>
                  <td>${i.mtu}</td>
                  <td>${i.ipv4_addresses.join('<br>') || '—'}</td>
                  <td><small>${i.ipv6_addresses.slice(0, 2).join('<br>') || '—'}</small></td>
                  <td>${i.traffic ? `${i.traffic.rx_bytes} / ${i.traffic.tx_bytes}` : '0 / 0'}</td>
                  <td>
                    <button class="btn btn-outline" style="padding:4px 8px; font-size:11px;" onclick="app.toggleInterface('${i.name}', ${!i.is_up})">
                      ${i.is_up ? 'Set Down' : 'Set Up'}
                    </button>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <div class="section-title" style="font-size:14px; margin-bottom:12px;">Add IP Address</div>
            <div class="form-row">
              <input type="text" id="add-ip-iface" class="input-field" placeholder="Interface (e.g. enp0s8)">
              <input type="text" id="add-ip-cidr" class="input-field" placeholder="CIDR (e.g. 192.168.100.1/24)">
              <button class="btn btn-cyan" onclick="app.addAddress()">Assign Address</button>
            </div>
          </div>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async toggleInterface(name, up) {
    try {
      await this.api("/api/v1/interfaces/set-state", "POST", { name, state: up ? "up" : "down" });
      this.renderInterfaces();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async addAddress() {
    const name = document.getElementById("add-ip-iface").value.trim();
    const cidr = document.getElementById("add-ip-cidr").value.trim();
    if (!name || !cidr) return alert("Interface and CIDR required");
    try {
      await this.api("/api/v1/interfaces/address/add", "POST", { name, cidr });
      this.renderInterfaces();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async renderRouting() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading routing table...</p>";

    try {
      const data = await this.api("/api/v1/routes");
      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">IPv4 Routing Table</div>
          </div>
          <table class="data-table">
            <thead>
              <tr><th>Destination</th><th>Gateway</th><th>Interface</th><th>Metric</th><th>Protocol</th><th>Actions</th></tr>
            </thead>
            <tbody>
              ${data.ipv4.map(r => `
                <tr>
                  <td><b>${r.destination}</b></td>
                  <td>${r.gateway || 'Direct'}</td>
                  <td>${r.interface || '—'}</td>
                  <td>${r.metric ?? '—'}</td>
                  <td>${r.protocol || 'kernel'}</td>
                  <td>
                    ${r.gateway ? `<button class="btn btn-outline" style="padding:2px 6px; font-size:11px;" onclick="app.removeRoute('${r.destination}', '${r.gateway}')">Delete</button>` : '—'}
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <div class="section-title" style="font-size:14px; margin-bottom:12px;">Add Static Route</div>
            <div class="form-row">
              <input type="text" id="route-dest" class="input-field" placeholder="Destination (e.g. 10.50.0.0/16)">
              <input type="text" id="route-gw" class="input-field" placeholder="Gateway (e.g. 10.0.2.2)">
              <input type="text" id="route-dev" class="input-field" placeholder="Interface (optional)">
              <button class="btn btn-cyan" onclick="app.addRoute()">Add Route</button>
            </div>
          </div>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async addRoute() {
    const destination = document.getElementById("route-dest").value.trim();
    const gateway = document.getElementById("route-gw").value.trim();
    const iface = document.getElementById("route-dev").value.trim() || null;
    if (!destination || !gateway) return alert("Destination and Gateway required");
    try {
      await this.api("/api/v1/routes/add", "POST", { destination, gateway, interface: iface });
      this.renderRouting();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async removeRoute(destination, gateway) {
    if (!confirm(`Delete route ${destination}?`)) return;
    try {
      await this.api("/api/v1/routes/remove", "POST", { destination, gateway });
      this.renderRouting();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async renderVLANs() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading VLANs...</p>";
    try {
      const vlans = await this.api("/api/v1/vlans");
      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">802.1Q VLAN Interfaces</div>
          </div>
          <table class="data-table">
            <thead>
              <tr><th>VLAN Device</th><th>Parent</th><th>VLAN ID</th><th>Protocol</th><th>Actions</th></tr>
            </thead>
            <tbody>
              ${vlans.length === 0 ? '<tr><td colspan="5">No VLAN interfaces configured.</td></tr>' : ''}
              ${vlans.map(v => `
                <tr>
                  <td><b>${v.name}</b></td>
                  <td>${v.parent}</td>
                  <td><span class="badge badge-neutral">${v.vlan_id}</span></td>
                  <td>${v.protocol || '802.1q'}</td>
                  <td>
                    <button class="btn btn-outline" style="padding:2px 6px; font-size:11px;" onclick="app.deleteVLAN('${v.name}')">Delete</button>
                  </td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <div class="section-title" style="font-size:14px; margin-bottom:12px;">Create 802.1Q VLAN</div>
            <div class="form-row">
              <input type="text" id="vlan-name" class="input-field" placeholder="VLAN Name (e.g. vlan100)">
              <input type="text" id="vlan-parent" class="input-field" placeholder="Parent Device (e.g. enp0s8)">
              <input type="number" id="vlan-id" class="input-field" placeholder="Tag ID (1-4094)">
              <button class="btn btn-cyan" onclick="app.createVLAN()">Create VLAN</button>
            </div>
          </div>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async createVLAN() {
    const name = document.getElementById("vlan-name").value.trim();
    const parent = document.getElementById("vlan-parent").value.trim();
    const vlan_id = document.getElementById("vlan-id").value.trim();
    if (!name || !parent || !vlan_id) return alert("All fields required");
    try {
      await this.api("/api/v1/vlans/create", "POST", { name, parent, vlan_id });
      this.renderVLANs();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async deleteVLAN(name) {
    if (!confirm(`Delete VLAN ${name}?`)) return;
    try {
      await this.api("/api/v1/vlans/delete", "POST", { name });
      this.renderVLANs();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async renderBridges() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading Linux bridges...</p>";
    try {
      const bridges = await this.api("/api/v1/bridges");
      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">Linux Bridge Devices</div>
          </div>
          <table class="data-table">
            <thead><tr><th>Bridge Name</th><th>Ports</th><th>STP</th><th>Actions</th></tr></thead>
            <tbody>
              ${bridges.length === 0 ? '<tr><td colspan="4">No bridge interfaces configured.</td></tr>' : ''}
              ${bridges.map(b => `
                <tr>
                  <td><b>${b.name}</b></td>
                  <td>${b.ports && b.ports.length ? b.ports.map(p => p.name).join(', ') : 'None'}</td>
                  <td>${b.stp ? 'Enabled' : 'Disabled'}</td>
                  <td><button class="btn btn-outline" style="padding:2px 6px; font-size:11px;" onclick="app.deleteBridge('${b.name}')">Delete</button></td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <div class="section-title" style="font-size:14px; margin-bottom:12px;">Create Bridge</div>
            <div class="form-row">
              <input type="text" id="bridge-name" class="input-field" placeholder="Bridge Name (e.g. br0)">
              <button class="btn btn-cyan" onclick="app.createBridge()">Create Bridge</button>
            </div>
          </div>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async createBridge() {
    const name = document.getElementById("bridge-name").value.trim();
    if (!name) return alert("Bridge name required");
    try {
      await this.api("/api/v1/bridges/create", "POST", { name });
      this.renderBridges();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async deleteBridge(name) {
    if (!confirm(`Delete bridge ${name}?`)) return;
    try {
      await this.api("/api/v1/bridges/delete", "POST", { name });
      this.renderBridges();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async renderBonds() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading bond interfaces...</p>";
    try {
      const bonds = await this.api("/api/v1/bonds");
      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">Bond / LACP Interfaces</div>
          </div>
          <table class="data-table">
            <thead><tr><th>Bond Device</th><th>Mode</th><th>Slaves</th><th>MII Status</th></tr></thead>
            <tbody>
              ${bonds.length === 0 ? '<tr><td colspan="4">No bonding devices configured.</td></tr>' : ''}
              ${bonds.map(b => `
                <tr>
                  <td><b>${b.name}</b></td>
                  <td><span class="badge badge-neutral">${b.mode}</span></td>
                  <td>${b.slaves ? b.slaves.map(s => s.name).join(', ') : 'None'}</td>
                  <td>${b.mii_status || 'UP'}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async renderVRFs() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading VRF domains...</p>";
    try {
      const vrfs = await this.api("/api/v1/vrfs");
      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">VRF (Virtual Routing and Forwarding)</div>
          </div>
          <table class="data-table">
            <thead><tr><th>VRF Name</th><th>Table ID</th><th>Member Interfaces</th><th>Actions</th></tr></thead>
            <tbody>
              ${vrfs.length === 0 ? '<tr><td colspan="4">No VRF instances configured.</td></tr>' : ''}
              ${vrfs.map(v => `
                <tr>
                  <td><b>${v.name}</b></td>
                  <td><span class="badge badge-neutral">Table ${v.table}</span></td>
                  <td>${v.interfaces ? v.interfaces.join(', ') : 'None'}</td>
                  <td><button class="btn btn-outline" style="padding:2px 6px; font-size:11px;" onclick="app.deleteVRF('${v.name}')">Delete</button></td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <div class="section-title" style="font-size:14px; margin-bottom:12px;">Create VRF Instance</div>
            <div class="form-row">
              <input type="text" id="vrf-name" class="input-field" placeholder="VRF Name (e.g. vrf_red)">
              <input type="number" id="vrf-table" class="input-field" placeholder="Table ID (100-999)">
              <button class="btn btn-cyan" onclick="app.createVRF()">Create VRF</button>
            </div>
          </div>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async createVRF() {
    const name = document.getElementById("vrf-name").value.trim();
    const table_id = document.getElementById("vrf-table").value.trim();
    if (!name || !table_id) return alert("VRF name and table ID required");
    try {
      await this.api("/api/v1/vrfs/create", "POST", { name, table_id });
      this.renderVRFs();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async deleteVRF(name) {
    if (!confirm(`Delete VRF ${name}?`)) return;
    try {
      await this.api("/api/v1/vrfs/delete", "POST", { name });
      this.renderVRFs();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async renderFirewall() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading firewall rules...</p>";

    try {
      const fw = await this.api("/api/v1/firewall");
      const st = fw.status;
      const cfg = fw.config;

      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div>
              <div class="section-title">Firewall Core (nftables inet mitranet)</div>
              <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                Policy: INPUT=${cfg.policy.input_default.toUpperCase()} | FORWARD=${cfg.policy.forward_default.toUpperCase()} | OUTPUT=${cfg.policy.output_default.toUpperCase()}
                (Anti-Lockout SSH Protected)
              </p>
            </div>
            <div style="display:flex; gap:8px;">
              <button class="btn btn-cyan" onclick="app.applyFirewall()">Apply Changes</button>
              <button class="btn btn-outline" onclick="app.reloadFirewall()">Reload Kernel</button>
            </div>
          </div>

          <table class="data-table">
            <thead>
              <tr><th>Priority</th><th>ID</th><th>Direction</th><th>Protocol</th><th>Source</th><th>Destination</th><th>Action</th><th>Counters</th><th>Actions</th></tr>
            </thead>
            <tbody>
              ${cfg.rules.length === 0 ? '<tr><td colspan="9">No custom firewall rules defined (Baseline secure policies active).</td></tr>' : ''}
              ${cfg.rules.map(r => {
                const cnt = st.counters[r.id] || { packets: 0, bytes: 0 };
                return `
                  <tr>
                    <td>${r.priority}</td>
                    <td><b>${r.id}</b></td>
                    <td>${r.direction.toUpperCase()}</td>
                    <td>${r.protocol.toUpperCase()}</td>
                    <td>${r.source}</td>
                    <td>${r.destination}</td>
                    <td><span class="badge ${r.action === 'accept' ? 'badge-accept' : 'badge-drop'}">${r.action.toUpperCase()}</span></td>
                    <td><small>${cnt.packets} pkts (${cnt.bytes} B)</small></td>
                    <td>
                      <button class="btn btn-outline" style="padding:2px 6px; font-size:11px;" onclick="app.deleteFirewallRule('${r.id}')">Delete</button>
                    </td>
                  </tr>
                `;
              }).join('')}
            </tbody>
          </table>

          <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <div class="section-title" style="font-size:14px; margin-bottom:12px;">Add Firewall Rule</div>
            <div class="form-row">
              <input type="text" id="fw-id" class="input-field" placeholder="Rule ID (e.g. allow_http)">
              <select id="fw-action" class="input-field">
                <option value="accept">ACCEPT</option>
                <option value="drop">DROP</option>
                <option value="reject">REJECT</option>
              </select>
              <select id="fw-proto" class="input-field">
                <option value="any">ANY</option>
                <option value="tcp">TCP</option>
                <option value="udp">UDP</option>
                <option value="icmp">ICMP</option>
              </select>
              <input type="text" id="fw-src" class="input-field" placeholder="Source IP / CIDR" value="any">
              <input type="text" id="fw-dst" class="input-field" placeholder="Destination IP / CIDR" value="any">
              <input type="number" id="fw-prio" class="input-field" placeholder="Priority" value="100" style="width:80px;">
              <button class="btn btn-cyan" onclick="app.addFirewallRule()">Add to Candidate</button>
            </div>
          </div>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async addFirewallRule() {
    const id = document.getElementById("fw-id").value.trim();
    const action = document.getElementById("fw-action").value;
    const protocol = document.getElementById("fw-proto").value;
    const source = document.getElementById("fw-src").value.trim();
    const destination = document.getElementById("fw-dst").value.trim();
    const priority = document.getElementById("fw-prio").value.trim();

    if (!id) return alert("Rule ID required");
    try {
      await this.api("/api/v1/firewall/rule/add", "POST", { id, action, protocol, source, destination, priority });
      alert(`Rule '${id}' added to candidate. Click 'Apply Changes' to activate in kernel.`);
      this.renderFirewall();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async deleteFirewallRule(id) {
    if (!confirm(`Delete rule ${id} from candidate?`)) return;
    try {
      await this.api("/api/v1/firewall/rule/delete", "POST", { id });
      this.renderFirewall();
    } catch (e) {
      alert("Error: " + e.message);
    }
  },

  async applyFirewall() {
    try {
      const res = await this.api("/api/v1/firewall/apply", "POST");
      alert(`Firewall transaction ${res.transaction_id} COMMITTED successfully!`);
      this.renderFirewall();
    } catch (e) {
      alert("Apply Failed: " + e.message);
    }
  },

  async reloadFirewall() {
    try {
      await this.api("/api/v1/firewall/reload", "POST");
      alert("Firewall reloaded successfully.");
      this.renderFirewall();
    } catch (e) {
      alert("Reload Failed: " + e.message);
    }
  },

  async renderGateways() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading gateway monitor status...</p>";
    try {
      const gws = await this.api("/api/v1/gateways");
      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">Gateway Health & Monitoring</div>
          </div>
          <table class="data-table">
            <thead><tr><th>Gateway Name</th><th>IP Address</th><th>Interface</th><th>Metric</th><th>Status</th></tr></thead>
            <tbody>
              ${gws.length === 0 ? '<tr><td colspan="5">No default gateway detected.</td></tr>' : ''}
              ${gws.map(g => `
                <tr>
                  <td><b>${g.name}</b></td>
                  <td>${g.gateway}</td>
                  <td>${g.interface}</td>
                  <td>${g.metric}</td>
                  <td><span class="badge badge-up">${g.status.toUpperCase()}</span></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async renderConfig() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = "<p>Loading configuration transaction state...</p>";
    try {
      const cfg = await this.api("/api/v1/config/status");
      pane.innerHTML = `
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">Configuration Transaction Engine (Phase 1F)</div>
            <button class="btn btn-cyan" onclick="app.applyConfig()">Apply Candidate</button>
          </div>
          <div style="font-size:14px; line-height:2;">
            <div>Running Version: <b>v${cfg.running_version}</b></div>
            <div>Candidate Version: <b>v${cfg.candidate_version}</b></div>
            <div>Lock State: <b>${cfg.is_locked ? 'LOCKED' : 'IDLE (Unlocked)'}</b></div>
            <div>Active Transaction: <b>${cfg.current_transaction ? cfg.current_transaction.transaction_id : 'None'}</b></div>
          </div>
        </div>
      `;
    } catch (e) {
      pane.innerHTML = `<p style="color:var(--accent-red)">Error: ${e.message}</p>`;
    }
  },

  async applyConfig() {
    try {
      const res = await this.api("/api/v1/config/apply", "POST");
      alert(`Configuration applied successfully! Transaction: ${res.transaction_id}`);
      this.renderConfig();
    } catch (e) {
      alert("Transaction Error: " + e.message);
    }
  },

  async renderLogs() {
    const pane = document.getElementById("content-pane");
    pane.innerHTML = `
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">System & Subsystem Logs</div>
          <div class="form-row" style="margin-top:0;">
            <button class="btn btn-outline" onclick="app.fetchLogs('system')">System</button>
            <button class="btn btn-outline" onclick="app.fetchLogs('firewall')">Firewall</button>
            <button class="btn btn-outline" onclick="app.fetchLogs('gateway')">Gateway</button>
          </div>
        </div>
        <div id="log-output" class="log-terminal">Loading logs...</div>
      </div>
    `;
    this.fetchLogs("system");
  },

  async fetchLogs(cat) {
    const out = document.getElementById("log-output");
    if (!out) return;
    out.textContent = `Fetching ${cat} logs...`;
    try {
      const data = await this.api(`/api/v1/logs?category=${cat}`);
      if (data.lines && data.lines.length) {
        out.textContent = data.lines.join("\n");
      } else {
        out.textContent = `No active log records found in ${cat} log stream.`;
      }
    } catch (e) {
      out.textContent = "Log query error: " + e.message;
    }
  }
};

window.addEventListener("DOMContentLoaded", () => app.init());
