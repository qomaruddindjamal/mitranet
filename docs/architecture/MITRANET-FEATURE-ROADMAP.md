# MitraNet Feature Roadmap: From Phase 0 to Production NOS

```text
  Phase 0: Blueprints, Discovery, Foundation Engine, Core Models, Migration Skeletons
    ↓
  Phase 1: Linux Networking Core (nftables, Netlink, Interfaces, Routing, WireGuard)
    ↓
  Phase 2: Services & Daemon Orchestration (Kea, Unbound, Multi-WAN, HA, REST API)
    ↓
  Phase 3: Whitebox & Switchdev Integration (ONLP, SFP DOM, VLAN Switch, Mellanox/Edgecore)
    ↓
  Phase 4: Web Management & Enterprise Carrier Features (React WebGUI, BGP/OSPF, EVPN-VXLAN)
    ↓
  Phase 5: Debian ISO Mastering, Bare Metal & ONIE Distribution Installer
```

---

## Roadmap Breakdown

### Phase 0: Blueprints & Technical Foundation (CURRENT)
- [x] pfSense offline ISO extraction, verification, filesystem and package discovery.
- [x] pfSense config.xml schema analysis and migration model design.
- [x] RouterOS feature analysis and gap elimination blueprint.
- [x] OpenNetLinux hardware abstraction audit (ONLP, ONIE, Whitebox hardware).
- [x] Enterprise NOS matrix (Junos, EOS, IOS XE, VyOS, SONiC).
- [x] Master architecture specification & technology mapping.
- [x] MitraNet project repository initialization.
- [x] Core schema engine, CLI skeleton, REST API, migration engine, and test framework.

### Phase 1: Linux Networking & Engine Core
- Integration with Linux kernel Netfilter via dynamic `nftables` compiler.
- Direct Netlink interface manipulation (`pyroute2`) for physical NICs, VLANs, bridges, and bonding.
- In-kernel WireGuard peer generation and key exchange management.
- Multi-WAN gateway probing daemon (`mitranet-gwmon`) and PCC marking engine.

### Phase 2: Core Services & Enterprise High Availability
- Kea DHCPv4 / DHCPv6 configuration driver and dynamic lease monitor.
- Unbound DNS engine compiler with DNSSEC, host overrides, and domain forwarding.
- VRRP failover engine via Keepalived + connection state sync via `conntrackd`.
- Comprehensive OpenAPI REST management server with JWT authentication.

### Phase 3: Hardware Platform & Whitebox Switching
- Chassis daemon (`mitranet-platformd`) with dynamic platform autodetection.
- Front-panel SFP/SFP+/QSFP transceiver telemetry and optical DOM reading.
- Chassis thermal zone monitoring and intelligent fan PWM speed curves.
- Switchdev driver integration for hardware-accelerated L2/L3 switching.

### Phase 4: Modern Web GUI & Carrier Protocols
- High-performance, responsive Single Page Application (React, TypeScript, TailwindCSS/Vanilla CSS).
- Dynamic real-time graphs (traffic, states, memory, CPU) via WebSockets.
- FRRouting integration for BGP, OSPFv2, OSPFv3, and EVPN-VXLAN datacenter fabrics.

### Phase 5: Distribution Packaging & Release Engineering
- Live build framework creating installable Debian 13-based MitraNet ISOs.
- Creation of `mitranet-onie-installer.bin` for bare-metal whitebox network switches.
- Integration tests across KVM, QEMU, and physical bare-metal hardware.
