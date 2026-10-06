# MitraNet Master Feature Matrix

Status Definitions:
- **IMPLEMENTED**: Fully functional and operational in current codebase.
- **PROTOTYPE**: Core logic and foundational implementation written in Phase 0.
- **DESIGN ONLY**: Architecturally specified with clear implementation pathway.
- **PLANNED**: Scheduled for subsequent implementation phases.
- **NOT IMPLEMENTED**: Recognized capability not yet scheduled.
- **INCOMPATIBLE**: Functionality replaced with modern Linux alternative.

| Category | Feature | pfSense Baseline | RouterOS (P2) | Enterprise NOS | OpenNetLinux | Linux Backend Component | MitraNet Phase 0 Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **System** | Role-Based Access Control (RBAC) | Yes | Yes | Yes | No | Linux PAM + JWT API | PROTOTYPE |
| **System** | Transactional Commit / Rollback | No | Yes (Safe Mode)| Yes (Junos/EOS)| No | MitraNet Config Engine | PROTOTYPE |
| **System** | Backup & Restore (JSON/XML) | Yes (XML)| Yes (Binary/RSC)| Yes | No | MitraNet Config Engine | PROTOTYPE |
| **System** | pfSense config.xml Ingestion | Baseline | No | No | No | `mitranet.migration.pfsense` | PROTOTYPE |
| **System** | RouterOS Script/RSC Ingestion | No | Baseline | No | No | `mitranet.migration.routeros` | PROTOTYPE |
| **System** | Hierarchical CLI | Menu only | Yes | Yes | No | MitraNet CLI (`click`/`rich`) | PROTOTYPE |
| **System** | REST Management API | Thirdparty | Yes | Yes (eAPI/REST)| No | FastAPI / Unix Socket | PROTOTYPE |
| **System** | Modern Web GUI | Yes (PHP)| Yes (WebFig)| CloudVision | No | React + Vite Single-Page App | PLANNED (Phase 2) |
| **Interfaces** | Interface Assignment & Mapping | Yes | Yes | Yes | Yes | Linux Netlink / `iproute2` | PROTOTYPE |
| **Interfaces** | 802.1Q VLANs | Yes | Yes | Yes | Yes | Linux `ip link type vlan` | PROTOTYPE |
| **Interfaces** | 802.1ad QinQ | Yes | Yes | Yes | Yes | Nested Linux 802.1ad interfaces | DESIGN ONLY |
| **Interfaces** | Link Aggregation (LACP / LAGG) | Yes | Yes | Yes | Yes | Linux `bonding` (802.3ad) | DESIGN ONLY |
| **Interfaces** | Network Bridge | Yes | Yes | Yes | Yes | Linux Kernel Bridge | PROTOTYPE |
| **Interfaces** | PPPoE Client | Yes | Yes | Yes | No | `pppd` + `rp-pppoe` | DESIGN ONLY |
| **Interfaces** | VRF (Virtual Routing & Forward) | No | Yes | Yes | Yes | Linux VRF devices | DESIGN ONLY |
| **Firewall** | Stateful Packet Filtering | Yes (pf) | Yes | Yes | No | `nftables` + `conntrack` | PROTOTYPE |
| **Firewall** | Floating Rules | Yes (pf) | Yes (Raw/Filter)| Yes | No | `nftables` priority chains | DESIGN ONLY |
| **Firewall** | Dynamic / Static Aliases | Yes | Yes (Addr-list)| Yes (Object-groups)| No | `nftables` named sets (`set`) | PROTOTYPE |
| **Firewall** | GeoIP & URL Table Blocking | Yes | Yes | Yes | No | `nftables` sets + update daemon | DESIGN ONLY |
| **Firewall** | FastPath / Flowtable Offload | No | Yes (FastTrack)| Yes | No | Linux `nftables flowtable` | DESIGN ONLY |
| **NAT** | Outbound NAT (Masquerade / SNAT) | Yes | Yes | Yes | No | `nftables nat postrouting` | PROTOTYPE |
| **NAT** | Port Forwarding (DNAT) | Yes | Yes | Yes | No | `nftables nat prerouting` | PROTOTYPE |
| **NAT** | 1:1 Bidirectional NAT (BINAT) | Yes | Yes | Yes | No | `nftables` paired DNAT/SNAT | DESIGN ONLY |
| **NAT** | Hairpin NAT / Reflection | Yes | Yes | Yes | No | `nftables` reflection chains | DESIGN ONLY |
| **Routing** | Static Routing & Next-Hop | Yes | Yes | Yes | Yes | Linux Kernel Routing Table (FIB)| PROTOTYPE |
| **Routing** | Multi-WAN Failover & Probing | Yes (dpinger)| Yes (Netwatch)| Yes (IP SLA) | No | `mitranet-gwmon` + `ip rule` | PROTOTYPE |
| **Routing** | Per-Connection Classifier (PCC)| No | Yes | No | No | `nftables jhash` + mark + `ip rule`| PROTOTYPE |
| **Routing** | BGPv4 / BGP-4 MP | Pkg | Yes | Yes | Yes | FRRouting (`bgpd`) | DESIGN ONLY |
| **Routing** | OSPFv2 / OSPFv3 | Pkg | Yes | Yes | Yes | FRRouting (`ospfd`) | DESIGN ONLY |
| **Routing** | BFD Sub-second Detection | Pkg | Yes | Yes | Yes | FRRouting (`bfdd`) | DESIGN ONLY |
| **Services** | DHCPv4 Server & Reservations | Yes | Yes | Yes | No | Kea DHCPv4 / dnsmasq | PROTOTYPE |
| **Services** | DHCPv6 Server & RA | Yes | Yes | Yes | No | Kea DHCPv6 + `radvd` | DESIGN ONLY |
| **Services** | DNS Resolver with DNSSEC | Yes (Unbound)| Yes | Basic | No | Unbound DNS Resolver | PROTOTYPE |
| **Services** | Dynamic DNS Client | Yes | Yes | Yes | No | `inadyn` / `mitranet-dyndns` | DESIGN ONLY |
| **VPN** | WireGuard | Yes | Yes | No | No | Linux in-kernel WireGuard | PROTOTYPE |
| **VPN** | IPsec IKEv2 (Site-to-Site) | Yes | Yes | Yes | Yes | strongSwan (`swanctl`) | DESIGN ONLY |
| **VPN** | OpenVPN | Yes | Yes | No | No | OpenVPN 2.6+ daemon | DESIGN ONLY |
| **QoS** | Bandwidth Limiter & Shaper | Yes (ALTQ)| Yes (HTB/PCQ) | Yes | No | Linux `tc` (HTB / CAKE) | PROTOTYPE |
| **HA** | Virtual IP Failover | Yes (CARP)| Yes (VRRP) | Yes (HSRP/VRRP)| No | Keepalived (VRRP v2/v3) | DESIGN ONLY |
| **HA** | Conntrack State Synchronization | Yes (pfsync)| No | Yes | No | Linux `conntrackd` | DESIGN ONLY |
| **Hardware** | SFP / QSFP DOM Telemetry | No | Yes | Yes | Yes (ONLP) | `ethtool -m` + `libonlp` wrapper | PROTOTYPE |
| **Hardware** | Thermal & Fan PWM Management | No | Yes | Yes | Yes (ONLP) | Linux `hwmon` sysfs + ONLP | PROTOTYPE |
| **Hardware** | Switch Silicon Offload (ASIC) | No | Yes | Yes | Yes (Switchdev)| Linux `switchdev` / DSA | DESIGN ONLY |
| **Diagnostics**| Live Packet Capture & Ping/Trace | Yes | Yes | Yes | Yes | `tcpdump`, `iputils-ping`, `ss` | PROTOTYPE |
