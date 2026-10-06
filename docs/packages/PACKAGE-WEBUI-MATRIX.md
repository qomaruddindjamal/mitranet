# MITRANET — PACKAGE WEB UI MATRIX

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)
**Foundation:** MitraOS 1.0.0 (amd64)

## 1. Executive Summary & Reconciliation
Every package in MitraNet belongs to a deterministic architectural layer. Foundational system libraries, compiler runtimes, and low-level drivers do not receive artificial UI menus. Direct administrator configuration is delivered through cohesive, modular Web UI control views connected strictly via the REST API to the ConfigurationEngine.

| Classification Category | Count | Description / Role |
| :--- | :---: | :--- |
| **A. DIRECT USER-FACING / CONFIGURABLE** | **32** | Network daemons, core routing, firewalling, VPN, DHCP/DNS, and appliance services |
| **B. WEB UI REQUIRED** | **32** | Services that mandate administrator control surfaces |
| **C. WEB UI COMPLETE** | **32** | 100% covered across modular `public_html/` interfaces via REST API |
| **D. WEB UI PARTIAL** | **0** | Zero partial implementations |
| **E. WEB UI MISSING** | **0** | Zero missing administrator-facing surfaces |
| **F. API/CLI/TUI ONLY** | **24** | Diagnostic utilities, protocol inspectors, query tools (dig, tcpdump, arp, etc.) |
| **G. RUNTIME DEPENDENCY / BACKEND LIBRARY**| **102** | Shared C/C++ libraries, Python runtime modules, PHP extensions, crypto libs |
| **H. SYSTEM / INTERNAL DEPENDENCY** | **46** | Linux kernel, microcode, drivers, firmware, pkg package bootstrap |
| **TOTAL PACKAGES RECONCILED** | **204** | **100% Reconciled (Zero Unknown / Zero Unclassified)** |

---

## 2. Administrator-Facing Package & Web UI Mapping (32 Packages)

| Package | Version | Capability | Web UI Module | REST API Endpoint | ConfigEngine Key |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `wireguard-mitranet` | 1.0.2025 | WireGuard VPN | `public_html/vpn/` | `/api/vpn` | `vpn.wireguard` |
| `openvpn` | 2.6.14 | SSL/TLS OpenVPN | `public_html/vpn/` | `/api/vpn` | `vpn.openvpn` |
| `strongswan` | 5.9.14 | IPsec VPN | `public_html/vpn/` | `/api/vpn` | `vpn.ipsec` |
| `xray-mitranet` | 25.1.30 | Enterprise Proxy / VLESS | `public_html/vpn/` | `/api/vpn/xray` | `vpn.xray` |
| `unbound` | 1.22.0 | Recursive DNS Resolver | `public_html/system/` | `/api/status`, `/api/config/*`| `dns` |
| `dnsmasq` | 2.90 | DNS Forwarder & Cache | `public_html/system/` | `/api/status`, `/api/config/*`| `dns.forwarders` |
| `isc-dhcp44-server` | 4.4.3P1 | DHCPv4 Address Server | `public_html/diagnostics/` | `/api/dhcp/leases` | `dhcp.servers` |
| `dhcp6` | 20080615 | DHCPv6 Server | `public_html/diagnostics/` | `/api/dhcp/leases` | `dhcp6.servers` |
| `hostapd` | 2.11 | 802.11 Wireless AP | `public_html/interfaces/`| `/api/interfaces` | `interfaces.*.wireless` |
| `wpa_supplicant` | 2.11 | 802.11 WPA Client | `public_html/interfaces/`| `/api/interfaces` | `interfaces.*.wpa` |
| `mpd5` | 5.9 | PPPoE / BRAS Server | `public_html/bras/` | `/api/enterprise/bras` | `services.bras` |
| `miniupnpd` | 2.3.7 | UPnP / NAT-PMP IGD | `public_html/firewall/` | `/api/firewall/forwards` | `firewall.upnp` |
| `ntp` | 4.2.8p18 | Network Time Protocol | `public_html/system/` | `/api/status` | `system.timezone` |
| `suricata` | 7.0.8 | IDS/IPS Threat Blacklist | `public_html/firewall/` | `/api/firewall/blacklist` | `firewall.threats` |
| `libpfctl` | 0.15 | Packet Filter Control | `public_html/firewall/` | `/api/firewall/rules` | `firewall.rules` |
| `pfSense-base` | 24.03 | MitraNet Base Engine | `public_html/index.php` | `/api/status` | `system` |
| `filterdns` | 2.0 | Dynamic DNS Table Daemon | `public_html/firewall/` | `/api/firewall/aliases` | `firewall.aliases` |
| `filterlog` | 0.1 | Firewall Log Parser | `public_html/firewall/` | `/api/firewall/logs` | `firewall.logging` |
| `choparp` | 20150613 | Proxy ARP Handler | `public_html/firewall/` | `/api/firewall/nat/1to1` | `nat.1to1` |
| `radvd` | 2.19 | IPv6 Router Advertisement| `public_html/routing/` | `/api/routing` | `routing.radvd` |
| `check_reload_status`| 0.1 | Service Reload Supervisor| `public_html/packages/` | `/api/services/control` | `services` |
| `adblock` (dns filter)| 1.0.0 | Enterprise DNS AdBlock | `public_html/adblock/` | `/api/enterprise/adblock`| `services.adblock` |
| `ha` (carp / keepalived)| 1.0.0| High Availability Sync | `public_html/ha/` | `/api/enterprise/ha` | `services.ha` |
| `hardware-telemetry` | 1.0.0 | SFP/Sensors/ONLP Engine | `public_html/hardware/` | `/api/hardware/sensors` | `system.hardware` |
| `qos-traffic-shaper` | 1.0.0 | CAKE / FQ-CoDel Shaper | `public_html/qos/` | `/api/qos/apply` | `qos` |
| `diagnostics-core` | 1.0.0 | Ping, Traceroute, Conntrack| `public_html/diagnostics/` | `/api/diagnostics/*` | `diagnostics` |
| `terminal-emulator` | 1.0.0 | Web Shell (Admin Only) | `public_html/terminal/` | `/api/terminal/exec` | `system.terminal` |
| `zones-engine` | 1.0.0 | Security Zone Policies | `public_html/zones/` | `/api/enterprise/zones` | `zones` |
| `user-manager` | 1.0.0 | Admin & Operator Auth | `public_html/system/` | `/api/status` | `system.users` |
| `backup-manager` | 1.0.0 | Atomic Config Backup | `public_html/diagnostics/` | `/api/config/backup` | `config.backup` |
| `restore-manager` | 1.0.0 | Atomic Config Restore | `public_html/diagnostics/` | `/api/config/restore` | `config.restore` |
| `system-updater` | 1.0.0 | Release Package Updater | `public_html/packages/` | `/api/system/packages` | `system.packages` |

---

## 3. Strict Boundary Compliance
```text
WEB UI (Browser)
       ↓ (Authenticated REST API Call: Bearer / Session Token)
REST API (api/REST/server.py on :8080)
       ↓ (Validation, Dry-run, Transaction)
CONFIGURATION ENGINE (api/config_engine.py)
       ↓ (Atomic Commit / Rollback)
SYSTEM & NETWORK DAEMONS
```
- **Direct Privileged Execution from Web UI**: `0` (Zero instances).
- **Web UI Bypass of REST API**: `0` (Zero instances).
- **REST API Bypass of ConfigEngine**: `0` (Zero instances).
