<?php
if (!defined('MITRANET_ROUTED')) {
    require_once __DIR__ . '/../includes/router.php';
    exit;
}

/**
 * MitraNet Network OS - VPN & Tunnels
 * Modern White Enterprise Design System
 */
$pageTitle = 'VPN & Encrypted Tunnels';
$pageSubtitle = 'Xray-Core (VLESS / Reality), WireGuard, OpenVPN, StrongSwan IPsec & L2 Overlays';

$activeVpnTab = $_GET['tab'] ?? 'xray';

?>

        <section id="tab-vpn" class="tab-pane active">
          <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">Xray Core Gateway</span>
                <span class="badge badge-success" id="xray-card-badge">VLESS / REALITY</span>
              </div>
              <div class="metric-value">Xray <span class="unit">Core</span></div>
              <p class="metric-sub">Port 443 • Anti-Censorship &amp; Steganography VPN</p>
              <div class="progress-bar"><div class="progress-fill fill-cyan" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">WireGuard VPN</span>
                <span class="badge badge-success">KERNEL ACCEL</span>
              </div>
              <div class="metric-value">WireGuard <span class="unit">wg0</span></div>
              <p class="metric-sub">ChaCha20-Poly1305 wire-speed encrypted tunnel</p>
              <div class="progress-bar"><div class="progress-fill" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">OpenVPN Server</span>
                <span class="badge badge-cyan">TLS 1.3</span>
              </div>
              <div class="metric-value">OpenVPN <span class="unit">tun0</span></div>
              <p class="metric-sub">Port 1194 UDP • Multi-client SSL gateway</p>
              <div class="progress-bar"><div class="progress-fill fill-cyan" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">StrongSwan IPsec</span>
                <span class="badge badge-indigo">IKEv2 / IPSEC</span>
              </div>
              <div class="metric-value">StrongSwan <span class="unit">Engine</span></div>
              <p class="metric-sub">Hardware AES-GCM offload for site-to-site</p>
              <div class="progress-bar"><div class="progress-fill fill-indigo" style="width: 100%;"></div></div>
            </div>
            <div class="card metric-card">
              <div class="metric-header">
                <span class="metric-title">L2/L3 Overlay Tunnels</span>
                <span class="badge badge-amber">OVERLAYS</span>
              </div>
              <div class="metric-value">EoIP &amp; <span class="unit">VXLAN</span></div>
              <p class="metric-sub">Ethernet over IP &amp; RFC 7348 VXLAN</p>
              <div class="progress-bar"><div class="progress-fill" style="background: var(--accent-amber); width: 100%;"></div></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>VPN Daemons &amp; Tunnel Status</h3>
              <button class="btn btn-sm btn-primary" onclick="loadVPNStatus()">Refresh VPN</button>
            </div>
            <div class="card-body">
              <div class="subnav-tabs">
                <button class="subnav-tab <?= $activeVpnTab === 'xray' ? 'active' : '' ?>" id="vpn-tab-btn-xray" onclick="switchVPNSubTab('xray')">Xray Core (VLESS / Reality)</button>
                <button class="subnav-tab <?= $activeVpnTab === 'wireguard' ? 'active' : '' ?>" id="vpn-tab-btn-wireguard" onclick="switchVPNSubTab('wireguard')">WireGuard</button>
                <button class="subnav-tab <?= $activeVpnTab === 'openvpn' ? 'active' : '' ?>" id="vpn-tab-btn-openvpn" onclick="switchVPNSubTab('openvpn')">OpenVPN</button>
                <button class="subnav-tab <?= $activeVpnTab === 'ipsec' ? 'active' : '' ?>" id="vpn-tab-btn-ipsec" onclick="switchVPNSubTab('ipsec')">StrongSwan IPsec</button>
                <button class="subnav-tab <?= $activeVpnTab === 'overlay' ? 'active' : '' ?>" id="vpn-tab-btn-overlay" onclick="switchVPNSubTab('overlay')">L2 Overlays (EoIP &amp; VXLAN)</button>
              </div>

              <!-- SUBPANE: XRAY CORE -->
              <div class="vpn-subpane <?= $activeVpnTab === 'xray' ? '' : 'hidden' ?>" id="vpn-subtab-xray">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                  <div>
                    <h4 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-main);">Xray-Core Service &amp; Inbound Server</h4>
                    <p class="description" style="margin: 0;">Next-generation encrypted VPN &amp; proxy protocol with TLS Reality masquerading.</p>
                  </div>
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success" onclick="toggleXray('start')">Start Xray</button>
                    <button class="btn btn-sm btn-secondary" onclick="toggleXray('restart')">Restart</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="toggleXray('stop')">Stop</button>
                  </div>
                </div>

                <div class="card p-3 mb-3" style="background: #ffffff; border: 1px solid var(--border-color);">
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="xray-protocol-select">Inbound Protocol</label>
                      <select id="xray-protocol-select" class="form-control" onchange="updateXrayShareLink()">
                        <option value="VLESS" selected>VLESS (High-Throughput Zero-Overhead)</option>
                        <option value="VMess">VMess (AEAD Authenticated)</option>
                        <option value="Trojan">Trojan (HTTPS Mimicry)</option>
                        <option value="Shadowsocks">Shadowsocks (2022-blake3)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="xray-port-input">Inbound Port</label>
                      <input type="number" id="xray-port-input" class="form-control" value="443" min="1" max="65535" onchange="updateXrayShareLink()">
                    </div>
                    <div class="form-group">
                      <label for="xray-security-select">Security Masquerade</label>
                      <select id="xray-security-select" class="form-control" onchange="updateXrayShareLink()">
                        <option value="reality" selected>Reality (Zero-Cert Steganography)</option>
                        <option value="tls">Standard TLS 1.3</option>
                        <option value="none">None (Direct In-Tunnel)</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="xray-dest-input">Masquerade SNI / Fallback Dest</label>
                      <input type="text" id="xray-dest-input" class="form-control" value="dl.google.com" onchange="updateXrayShareLink()">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                      <label for="xray-uuid-input">Client UUID / Encryption Key</label>
                      <div class="input-group">
                        <input type="text" id="xray-uuid-input" class="form-control font-monospace" value="d3b07384-d113-494a-a03a-33758b688d6a" onchange="updateXrayShareLink()">
                        <button class="btn btn-outline-secondary" type="button" onclick="generateXrayUUID()">Generate New UUID</button>
                      </div>
                    </div>
                  </div>
                  <div class="mt-3 d-flex justify-content-end gap-2">
                    <button class="btn btn-primary" onclick="saveXrayConfig()">Save &amp; Apply Xray Config</button>
                  </div>
                </div>

                <div class="share-link-container mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong style="font-size: 0.85rem; color: var(--text-main);">Client Connection URL (Share Link)</strong>
                    <div class="d-flex gap-2">
                      <button class="btn btn-sm btn-outline-primary" onclick="copyXrayShareLink()">Copy URL</button>
                      <button class="btn btn-sm btn-outline-secondary" onclick="copyXrayJson()">Copy JSON Config</button>
                    </div>
                  </div>
                  <input type="text" id="xray-share-link" class="form-control share-link-input" readonly value="vless://d3b07384-d113-494a-a03a-33758b688d6a@192.168.1.1:443?security=reality&amp;encryption=none&amp;pbk=MitraNetKeyRealityVLESS2026&amp;headerType=none&amp;fp=chrome&amp;spx=%2F&amp;type=tcp&amp;flow=xtls-rprx-vision&amp;sni=dl.google.com#MitraNet-Rinjani-Xray">
                  <small class="text-muted mt-1 d-block">Compatible with v2rayN, Nekoray, v2rayNG, Sing-box, Shadowrocket, and remote MitraNet edge appliances.</small>
                </div>

                <p class="description mb-1">Live Xray Daemon Process &amp; Connection Log:</p>
                <pre class="code-terminal" id="raw-vpn-xray">Loading Xray Core daemon output...</pre>
              </div>

              <!-- SUBPANE: WIREGUARD -->
              <div class="vpn-subpane <?= $activeVpnTab === 'wireguard' ? '' : 'hidden' ?>" id="vpn-subtab-wireguard">
                <p class="description">Live WireGuard interfaces, public keys, and peer handshake counters.</p>
                <pre class="code-terminal" id="raw-vpn-wireguard">Loading WireGuard status...</pre>
              </div>

              <!-- SUBPANE: OPENVPN -->
              <div class="vpn-subpane <?= $activeVpnTab === 'openvpn' ? '' : 'hidden' ?>" id="vpn-subtab-openvpn">
                <p class="description">OpenVPN service status, connected clients, and cipher suites.</p>
                <pre class="code-terminal" id="raw-vpn-openvpn">Loading OpenVPN status...</pre>
              </div>

              <!-- SUBPANE: IPSEC -->
              <div class="vpn-subpane <?= $activeVpnTab === 'ipsec' ? '' : 'hidden' ?>" id="vpn-subtab-ipsec">
                <p class="description">StrongSwan IPsec Security Associations (SA), proposals, and tunnels.</p>
                <pre class="code-terminal" id="raw-vpn-ipsec">Loading IPsec status...</pre>
              </div>

              <!-- SUBPANE: L2 OVERLAYS -->
              <div class="vpn-subpane <?= $activeVpnTab === 'overlay' ? '' : 'hidden' ?>" id="vpn-subtab-overlay">
                <p class="description">Live Layer-2 Ethernet-over-IP (EoIP) and RFC 7348 VXLAN overlay tunnels status.</p>
                <pre class="code-terminal" id="raw-vpn-overlay">Loading Layer-2 overlay status...</pre>
              </div>
            </div>
          </div>
        </section>

<script>
$(document).ready(function() {
  loadVPNStatus();
  <?php if ($activeVpnTab !== 'xray'): ?>
  switchVPNSubTab('<?= html_esc($activeVpnTab) ?>');
  <?php endif; ?>
});
</script>


