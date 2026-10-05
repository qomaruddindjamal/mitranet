<?php
/**
 * MitraNet Network OS - Modular Sidebar Navigation Include
 * Modern White Enterprise Design System
 */
$webRoot = $webRoot ?? '';
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

$isFwGroup = ($currentDir === 'firewall');
$isDiagGroup = ($currentDir === 'diagnostics');
$isHardwareGroup = ($currentDir === 'hardware');
$isIfaceGroup = ($currentDir === 'interfaces');
$isQosGroup = ($currentDir === 'qos');
$isZonesGroup = ($currentDir === 'zones');
$isAdblockGroup = ($currentDir === 'adblock');
$isRoutingGroup = ($currentDir === 'routing');
$isHaGroup = ($currentDir === 'ha');
$isVpnGroup = ($currentDir === 'vpn');
$isBrasGroup = ($currentDir === 'bras');
$isTerminalGroup = ($currentDir === 'terminal');
$isPackagesGroup = ($currentDir === 'packages');
$isSystemGroup = ($currentDir === 'system');
$userRole = $_SESSION['mitranet_role'] ?? 'administrator';
$isDashboard = ($currentDir === 'public_html' || $currentPage === 'index.php' && $currentDir === '');
?>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <a href="<?= $webRoot ?>index.php" style="text-decoration:none; display:flex; align-items:center; gap:0.75rem; color:inherit;">
          <div class="logo-icon">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2"
              stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
              <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
              <line x1="6" y1="6" x2="6.01" y2="6"></line>
              <line x1="6" y1="18" x2="6.01" y2="18"></line>
            </svg>
          </div>
          <div class="brand-text">
            <span class="brand-title">MitraNet</span>
            <span class="brand-badge">Rinjani</span>
          </div>
        </a>
      </div>

      <nav class="nav-menu">
        <div class="sidebar-heading">Core Systems</div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isDashboard ? 'active' : '' ?>" id="nav-dashboard" href="<?= $webRoot ?>index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="18" rx="1"></rect>
                <rect x="14" y="3" width="7" height="9" rx="1"></rect>
                <rect x="14" y="16" width="7" height="5" rx="1"></rect>
              </svg>
            </span>
            <span class="nav-text">Dashboard</span>
          </a>
        </div>

        <div class="nav-item-wrapper dropend" id="group-interfaces">
          <a class="nav-item <?= $isIfaceGroup ? 'active' : '' ?>" id="nav-interfaces" href="<?= $webRoot ?>interfaces/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="8" rx="2"></rect>
                <rect x="2" y="14" width="20" height="8" rx="2"></rect>
                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                <line x1="6" y1="18" x2="6.01" y2="18"></line>
              </svg>
            </span>
            <span class="nav-text">Interfaces & Links</span>
            <span class="nav-chevron">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </span>
          </a>
          <div class="dropend-menu dropdown-menu shadow-lg">
            <div class="dropend-header">Interfaces & Virtual Links</div>
            <a class="dropdown-item" href="<?= $webRoot ?>interfaces/index.php?sub=physical">
              <svg class="svg-item text-primary" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="14" rx="2"></rect>
                <line x1="6" y1="16" x2="6" y2="20"></line>
                <line x1="10" y1="16" x2="10" y2="22"></line>
                <line x1="14" y1="16" x2="14" y2="22"></line>
                <line x1="18" y1="16" x2="18" y2="20"></line>
              </svg>
              All Physical Interfaces
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>interfaces/index.php?sub=vether">
              <svg class="svg-item text-success" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 16l-4-4 4-4"></path>
                <path d="M3 12h18"></path>
                <path d="M17 8l4 4-4 4"></path>
              </svg>
              Vether (Virtual Ethernet)
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>interfaces/index.php?sub=vlan">
              <svg class="svg-item text-info" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                <polyline points="2 17 12 22 22 17"></polyline>
                <polyline points="2 12 12 17 22 12"></polyline>
              </svg>
              VLAN (802.1Q Sub-ifaces)
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>interfaces/index.php?sub=eoip">
              <svg class="svg-item text-warning" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="18" cy="5" r="3"></circle>
                <circle cx="6" cy="12" r="3"></circle>
                <circle cx="18" cy="19" r="3"></circle>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
              </svg>
              EoIP Tunnel
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>interfaces/index.php?sub=vxlan">
              <svg class="svg-item text-primary" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"></path>
                <polyline points="12 13 12 8"></polyline>
                <polyline points="9 11 12 8 15 11"></polyline>
              </svg>
              VXLAN Overlay (L2/L3)
            </a>
          </div>
        </div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isQosGroup ? 'active' : '' ?>" id="nav-qos" href="<?= $webRoot ?>qos/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v2"></path>
                <path d="M4.93 4.93l1.41 1.41"></path>
                <path d="M20 12h-2"></path>
                <path d="M19.07 4.93l-1.41 1.41"></path>
                <path d="M2 12h2"></path>
                <path d="M20.24 16.24a10 10 0 1 0-16.48 0"></path>
                <line x1="12" y1="12" x2="16" y2="9"></line>
              </svg>
            </span>
            <span class="nav-text">QoS & Bandwidth</span>
          </a>
        </div>

        <div class="sidebar-heading">Security & Protection</div>

        <div class="nav-item-wrapper dropend" id="group-firewall">
          <a class="nav-item <?= $isFwGroup ? 'active' : '' ?>" id="nav-firewall" href="<?= $webRoot ?>firewall/rules.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </span>
            <span class="nav-text">Firewall (NFTables)</span>
            <span class="nav-chevron">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </span>
          </a>
          <div class="dropend-menu dropdown-menu shadow-lg">
            <div class="dropend-header">Packet Filter Suite</div>
            <a class="dropdown-item <?= ($isFwGroup && $currentPage === 'rules.php') ? 'active' : '' ?>" href="<?= $webRoot ?>firewall/rules.php">
              <svg class="svg-item text-primary" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" y1="6" x2="20" y2="6"></line>
                <line x1="4" y1="12" x2="20" y2="12"></line>
                <line x1="4" y1="18" x2="20" y2="18"></line>
              </svg>
              Filter Rules
            </a>
            <a class="dropdown-item <?= ($isFwGroup && $currentPage === 'aliases.php') ? 'active' : '' ?>" href="<?= $webRoot ?>firewall/aliases.php">
              <svg class="svg-item text-indigo" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              Aliases (Groups)
            </a>
            <a class="dropdown-item <?= ($isFwGroup && $currentPage === 'nat.php') ? 'active' : '' ?>" href="<?= $webRoot ?>firewall/nat.php">
              <svg class="svg-item text-success" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                <polyline points="15 9 15 15 9 15"></polyline>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              </svg>
              Port Forwarding (NAT)
            </a>
            <a class="dropdown-item <?= ($isFwGroup && $currentPage === 'nat_1to1.php') ? 'active' : '' ?>" href="<?= $webRoot ?>firewall/nat_1to1.php">
              <svg class="svg-item text-cyan" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="7" y1="12" x2="17" y2="12"></line>
                <polyline points="13 8 17 12 13 16"></polyline>
                <polyline points="11 16 7 12 11 8"></polyline>
              </svg>
              1:1 Static NAT
            </a>
            <a class="dropdown-item <?= ($isFwGroup && $currentPage === 'nat_out.php') ? 'active' : '' ?>" href="<?= $webRoot ?>firewall/nat_out.php">
              <svg class="svg-item text-warning" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="16 16 12 12 8 16"></polyline>
                <line x1="12" y1="12" x2="12" y2="21"></line>
                <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
              </svg>
              Outbound NAT
            </a>
            <a class="dropdown-item <?= ($isFwGroup && $currentPage === 'blacklist.php') ? 'active' : '' ?>" href="<?= $webRoot ?>firewall/blacklist.php">
              <svg class="svg-item text-danger" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
              </svg>
              IP Blacklist & Threat Ban
            </a>
            <a class="dropdown-item <?= ($isFwGroup && $currentPage === 'logs.php') ? 'active' : '' ?>" href="<?= $webRoot ?>firewall/logs.php">
              <svg class="svg-item text-info" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                <line x1="9" y1="7" x2="16" y2="7"></line>
                <line x1="9" y1="11" x2="16" y2="11"></line>
              </svg>
              Ruleset & Threat Logs
            </a>
          </div>
        </div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isZonesGroup ? 'active' : '' ?>" id="nav-zones" href="<?= $webRoot ?>zones/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                <polyline points="2 17 12 22 22 17"></polyline>
                <polyline points="2 12 12 17 22 12"></polyline>
              </svg>
            </span>
            <span class="nav-text">Security Zones (ZBF)</span>
          </a>
        </div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isAdblockGroup ? 'active' : '' ?>" id="nav-adblock" href="<?= $webRoot ?>adblock/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </span>
            <span class="nav-text">DNS Guard & Adblock</span>
          </a>
        </div>

        <div class="sidebar-heading">Routing & High Availability</div>

        <div class="nav-item-wrapper dropend" id="group-routing">
          <a class="nav-item <?= $isRoutingGroup ? 'active' : '' ?>" id="nav-routing" href="<?= $webRoot ?>routing/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="4" cy="4" r="2"></circle>
                <circle cx="20" cy="4" r="2"></circle>
                <circle cx="12" cy="20" r="2"></circle>
                <path d="M6 4h12"></path>
                <path d="M5.5 5.5l5 13"></path>
                <path d="M18.5 5.5l-5 13"></path>
              </svg>
            </span>
            <span class="nav-text">Routing (FRR/BIRD2)</span>
            <span class="nav-chevron">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </span>
          </a>
          <div class="dropend-menu dropdown-menu shadow-lg">
            <div class="dropend-header">Routing Suite</div>
            <a class="dropdown-item" href="<?= $webRoot ?>routing/index.php">
              <svg class="svg-item text-primary" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                <line x1="3" y1="9" x2="21" y2="9"></line>
                <line x1="3" y1="15" x2="21" y2="15"></line>
                <line x1="9" y1="9" x2="9" y2="21"></line>
              </svg>
              Kernel Routing Table
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>routing/index.php">
              <svg class="svg-item text-success" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="13" width="20" height="8" rx="2"></rect>
                <line x1="6" y1="13" x2="6" y2="7"></line>
                <line x1="18" y1="13" x2="18" y2="7"></line>
                <circle cx="6" cy="6" r="1"></circle>
                <circle cx="18" cy="6" r="1"></circle>
              </svg>
              Dynamic Routing (OSPF/BGP)
            </a>
          </div>
        </div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isHaGroup ? 'active' : '' ?>" id="nav-ha" href="<?= $webRoot ?>ha/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
              </svg>
            </span>
            <span class="nav-text">High Availability (VRRP)</span>
          </a>
        </div>

        <div class="sidebar-heading">Access & VPN Gateway</div>

        <div class="nav-item-wrapper dropend" id="group-vpn">
          <a class="nav-item <?= $isVpnGroup ? 'active' : '' ?>" id="nav-vpn" href="<?= $webRoot ?>vpn/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </span>
            <span class="nav-text">VPN & Tunnels</span>
            <span class="nav-chevron">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </span>
          </a>
          <div class="dropend-menu dropdown-menu shadow-lg">
            <div class="dropend-header">VPN & Overlays</div>
            <a class="dropdown-item" href="<?= $webRoot ?>vpn/index.php?tab=xray">
              <svg class="svg-item text-primary" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
              </svg>
              Xray Core (VLESS / Reality)
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>vpn/index.php?tab=wireguard">
              <svg class="svg-item text-success" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
              WireGuard (Kernel Accel)
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>vpn/index.php?tab=openvpn">
              <svg class="svg-item text-info" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 8v4l3 3"></path>
              </svg>
              OpenVPN SSL Server
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>vpn/index.php?tab=ipsec">
              <svg class="svg-item text-secondary" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
              StrongSwan IPsec
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>vpn/index.php?tab=overlay">
              <svg class="svg-item text-warning" viewBox="0 0 24 24" width="15" height="15" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              </svg>
              L2 Overlays (EoIP / VXLAN)
            </a>
          </div>
        </div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isBrasGroup ? 'active' : '' ?>" id="nav-bras" href="<?= $webRoot ?>bras/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </span>
            <span class="nav-text">Subscriber BRAS / AAA</span>
          </a>
        </div>

        <div class="sidebar-heading">Diagnostics & Tools</div>

        <div class="nav-item-wrapper dropend" id="group-diagnostics">
          <a class="nav-item <?= $isDiagGroup ? 'active' : '' ?>" id="nav-diagnostics" href="<?= $webRoot ?>diagnostics/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </span>
            <span class="nav-text">Network Diagnostics</span>
            <span class="nav-chevron">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </span>
          </a>
          <div class="dropend-menu dropdown-menu shadow-lg">
            <div class="dropend-header">Diagnostics & Tools</div>
            <a class="dropdown-item" href="<?= $webRoot ?>diagnostics/index.php?tab=ping">
              <svg class="svg-item text-primary" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 14 14"></polyline>
              </svg>
              Ping & Traceroute
            </a>
            <a class="dropdown-item" href="<?= $webRoot ?>diagnostics/index.php?tab=sniffer">
              <svg class="svg-item text-info" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
              </svg>
              Packet Sniffer & Flows
            </a>
            <a class="dropdown-item <?= ($isDiagGroup && $currentPage === 'dhcp_leases.php') ? 'active' : '' ?>" href="<?= $webRoot ?>diagnostics/dhcp_leases.php">
              <svg class="svg-item text-indigo" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
              </svg>
              DHCP Server Leases
            </a>
            <a class="dropdown-item <?= ($isDiagGroup && $currentPage === 'arp.php') ? 'active' : '' ?>" href="<?= $webRoot ?>diagnostics/arp.php">
              <svg class="svg-item text-cyan" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              </svg>
              ARP / Neighbor Table
            </a>
            <a class="dropdown-item <?= ($isDiagGroup && $currentPage === 'backup.php') ? 'active' : '' ?>" href="<?= $webRoot ?>diagnostics/backup.php">
              <svg class="svg-item text-success" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
              </svg>
              Backup & Restore
            </a>
          </div>
        </div>

        <?php if ($userRole === 'administrator'): ?>
        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isTerminalGroup ? 'active' : '' ?>" id="nav-terminal" href="<?= $webRoot ?>terminal/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="4 17 10 11 4 5"></polyline>
                <line x1="12" y1="19" x2="20" y2="19"></line>
              </svg>
            </span>
            <span class="nav-text">Web Terminal</span>
          </a>
        </div>
        <?php endif; ?>

        <div class="sidebar-heading">Hardware & Platform</div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= ($isHardwareGroup && $currentPage === 'sfp.php') ? 'active' : '' ?>" id="nav-sfp" href="<?= $webRoot ?>hardware/sfp.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
              </svg>
            </span>
            <span class="nav-text">SFP Diagnostics</span>
          </a>
        </div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= ($isHardwareGroup && $currentPage === 'sensors.php') ? 'active' : '' ?>" id="nav-sensors" href="<?= $webRoot ?>hardware/sensors.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 14.76V3.5a2.5 2.5 0 0 0-5 0v11.26a4.5 4.5 0 1 0 5 0z"></path>
              </svg>
            </span>
            <span class="nav-text">Thermal & Health</span>
          </a>
        </div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= ($isHardwareGroup && $currentPage === 'platform.php') ? 'active' : '' ?>" id="nav-platform" href="<?= $webRoot ?>hardware/platform.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="2"></rect>
                <rect x="6" y="6" width="4" height="4"></rect>
                <rect x="14" y="6" width="4" height="4"></rect>
                <rect x="6" y="14" width="12" height="4"></rect>
              </svg>
            </span>
            <span class="nav-text">ONLP Platform</span>
          </a>
        </div>

        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isPackagesGroup ? 'active' : '' ?>" id="nav-packages" href="<?= $webRoot ?>packages/index.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path
                  d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                </path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
              </svg>
            </span>
            <span class="nav-text">Packages & System Audit</span>
          </a>
        </div>

        <?php if ($userRole === 'administrator'): ?>
        <div class="nav-item-wrapper">
          <a class="nav-item <?= $isSystemGroup ? 'active' : '' ?>" id="nav-users" href="<?= $webRoot ?>system/users.php">
            <span class="nav-icon">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </span>
            <span class="nav-text">Users &amp; Roles</span>
          </a>
        </div>
        <?php endif; ?>
      </nav>

      <div class="sidebar-footer">
        <div class="system-status-indicator">
          <span class="status-dot pulsing"></span>
          <span>MitraNet Rinjani</span>
        </div>
        <div class="version-label">v1.0.0-LTS</div>
      </div>
    </aside>
