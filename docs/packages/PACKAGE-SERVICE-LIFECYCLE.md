# MITRANET — PACKAGE SERVICE LIFECYCLE & DAEMON SPECIFICATION

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Managed Daemon Catalog

| Service Name | Source Package | Port / Sockets | Config Location | Health Check Probe | Restart Policy | Failure Behavior |
|---|---|---|---|---|---|---|
| `choparp` | `choparp` | `IPC / Socket` | `/conf/config.xml` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `radvd` | `radvd` | `IPC / Socket` | `/etc/radvd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `wpa_supplicant` | `wifi` | `IPC / Socket` | `/etc/wpa_supplicant.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `wpa_supplicant` | `wpa_supplicant` | `IPC / Socket` | `/etc/wpa_supplicant.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `frr` | `mpd5` | `IPC / Socket` | `/etc/frr/frr.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `pf` | `filterdns` | `IPC / Socket` | `/etc/pf.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `pf` | `filterlog` | `IPC / Socket` | `/etc/pf.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `miniupnpd` | `miniupnpd` | `IPC / Socket` | `/etc/pf.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `pf` | `pfSense-base` | `IPC / Socket` | `/etc/pf.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `pf` | `pftop` | `IPC / Socket` | `/etc/pf.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dhcpd` | `dhcp6` | `67/UDP` | `/etc/dhcpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dhcpd` | `dhcpcd` | `67/UDP` | `/etc/dhcpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dhcpd` | `dhcpleases` | `67/UDP` | `/etc/dhcpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dhcpd` | `dhcpleases6` | `67/UDP` | `/etc/dhcpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dhcpd` | `isc-dhcp44-client` | `67/UDP` | `/etc/dhcpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dhcpd` | `isc-dhcp44-relay` | `67/UDP` | `/etc/dhcpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dhcpd` | `isc-dhcp44-server` | `67/UDP` | `/etc/dhcpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dnsmasq` | `bind-tools` | `53/UDP+TCP` | `/etc/dnsmasq.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `dnsmasq` | `dnsmasq` | `53/UDP+TCP` | `/etc/dnsmasq.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `unbound` | `unbound` | `53/UDP+TCP` | `/etc/unbound/unbound.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `ntpd` | `ntp` | `123/UDP` | `/etc/ntp.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `wireguard` | `mitranet-pkg-WireGuard` | `IPC / Socket` | `/etc/wireguard/wg0.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `openvpn` | `openvpn` | `IPC / Socket` | `/etc/openvpn/server.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `openvpn` | `openvpn-auth-script` | `IPC / Socket` | `/etc/openvpn/server.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `strongswan` | `strongswan` | `IPC / Socket` | `/etc/openvpn/server.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `wireguard` | `wireguard-mitranet` | `IPC / Socket` | `/etc/wireguard/wg0.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `strongswan` | `xray-mitranet` | `IPC / Socket` | `/etc/openvpn/server.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `sshguard` | `sshguard` | `IPC / Socket` | `/etc/sshguard.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `bsnmpd` | `bsnmp-regex` | `161/UDP` | `/etc/snmpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `bsnmpd` | `bsnmp-ucd` | `161/UDP` | `/etc/snmpd.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `check_reload_status` | `check_reload_status` | `IPC / Socket` | `/conf/config.xml` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `nginx` | `nginx` | `80/TCP, 443/TCP` | `/usr/local/etc/nginx/nginx.conf` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |
| `syslogd` | `xinetd` | `IPC / Socket` | `/conf/config.xml` | Process & PID Alive Check | on-failure (3 retries) | Log warning & Safe degrade |

## 2. Startup & Shutdown Orchestration Lifecycle

```text
BOOT
 ↓
DEPENDENCY INITIALIZATION (Layers 0-2: Microcode, C Libs, Python/PHP)
 ↓
NETWORK INITIALIZATION (Layers 3-4: NIC drivers, VLAN, Bridge, Bonding)
 ↓
ROUTING & FIREWALL (Layers 5-6: Netlink routing, PF state table)
 ↓
CORE NETWORK SERVICES (Layer 7: DHCP Server, Unbound DNS, NTP)
 ↓
VPN TUNNELS & SECURITY (Layers 7-8: WireGuard, OpenVPN, Suricata)
 ↓
MITRANET CORE ENGINE (Layer 10: config.xml sync, event loop)
 ↓
MANAGEMENT & WEB UI (Layer 11: Nginx, FastAPI REST API :8080)
 ↓
MONITORING & TELEMETRY (Layer 9: BSNMP, RRDTool statistics)
```
