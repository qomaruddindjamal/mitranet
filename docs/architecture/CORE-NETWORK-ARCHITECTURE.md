# MITRANET — CORE NETWORK ARCHITECTURE

**Official Identity:**
- **Project**: MitraNet
- **Version**: 0.1.0-dev
- **Foundation**: MitraOS 1.0.0
- **Code OS**: Rinjani
- **Architecture**: amd64

## 1. Unified Network Architecture Hierarchy

MitraNet implements ONE coherent network control plane operating directly over MitraOS 1.0.0:

```text
┌─────────────────────────────────────────────────────────────┐
│                     MANAGEMENT PLANE                        │
│    Web UI (public_html/ :80/:443)  │  CLI / TUI Engines     │
└──────────────────────────────┬──────────────────────────────┘
                               │ JSON-RPC / REST HTTP
┌──────────────────────────────▼──────────────────────────────┐
│                     CONTROL PLANE (API)                     │
│    api/REST/server.py (:8080)                               │
│    ├── enterprise_network_manager.py (L2/L3, VLAN, BR, VPN) │
│    ├── firewall_manager.py (Filter, NAT, Blacklist, Zones)  │
│    └── mitranet_platform.py (Telemetry, SFP, Hardware)      │
└──────────────────────────────┬──────────────────────────────┘
                               │ Atomic Transactions (/conf/config.xml)
┌──────────────────────────────▼──────────────────────────────┐
│                     MITRANET CORE ENGINE                    │
│    Single Source of Truth Config Store & Daemon Dispatcher   │
│    (check_reload_status, Netlink, Sysfs, Sockets)           │
└──────────────────────────────┬──────────────────────────────┘
                               │ Kernel Syscalls / Netlink
┌──────────────────────────────▼──────────────────────────────┐
│                     DATA PLANE (MITRAOS)                    │
│    Linux Kernel (Netlink, NFTables/PF, Conntrack, WireGuard)│
│    Physical NICs, 802.1Q VLANs, Bridges, Tunnels, VRFs       │
└─────────────────────────────────────────────────────────────┘
```

## 2. Operating Modes

### A. Router Mode
- Multi-interface routing, dynamic routing protocols (FRR), stateful routing table sync.
- DHCP Server / Relay, Unbound DNS caching and local resolving.

### B. Switch Mode
- Pure Layer 2 bridging across physical switch ports, 802.1Q VLAN trunking and access modes.
- Rapid Spanning Tree Protocol (RSTP) topology protection and MAC address table learning.

### C. Firewall / Security Appliance Mode
- Stateful packet inspection via `firewall_manager.py` (Ingress, Egress, Forward chains).
- Dynamic zone grouping (WAN, LAN, DMZ, VPN, MGMT) and SNAT/DNAT/Masquerade translation.
- Inline intrusion prevention and automated IP blacklist enforcement.
