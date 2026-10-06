# MITRANET — SWITCH ARCHITECTURE & IMPLEMENTATION

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Capabilities
MitraNet provides logical Layer 2 software switching across physical Ethernet ports:
- **802.1Q VLAN**: Tagged trunk and untagged access port segmentation.
- **Linux Bridge**: Multi-port L2 bridging with Rapid Spanning Tree Protocol (STP) protection.
- **Bonding (LAG)**: 802.3ad LACP dynamic link aggregation and active-backup fault tolerance.

## 2. Hardware vs Software Switching
- **Software Bridging**: Fully functional on all generic x86_64 NICs via Linux kernel bridge module.
- **Hardware Switch Offload (ASIC)**: Hardware-dependent (requires ONLP/Switchdev drivers).
