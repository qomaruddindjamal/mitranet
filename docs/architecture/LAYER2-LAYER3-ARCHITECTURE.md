# MITRANET — LAYER 2 & LAYER 3 NETWORK ARCHITECTURE

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Layer 2 Architecture (Switching, VLAN, Bridge, Bond)

```text
Physical Ports (eth0, eth1, eth2...) 
        ↓
Bonding / Link Aggregation (802.3ad LACP, active-backup)
        ↓
Linux Bridge (br0) + 802.1D/802.1w Rapid Spanning Tree (STP)
        ↓
802.1Q VLAN Sub-interfaces (br0.100, eth0.200)
```

## 2. Layer 3 Architecture (Routing, Addressing, VRF)

- **Addressing**: Dual-stack IPv4 and IPv6 support with dynamic EUI-64 / SLAAC detection.
- **FIB (Forwarding Information Base)**: Main kernel routing table managed via Linux Netlink.
- **Dynamic Routing Engine**: FRR (Free Range Routing) daemon suite integration for BGP, OSPF, and RIP.
- **VRF (Virtual Routing & Forwarding)**: Multi-tenant routing table isolation supported on kernel level.
