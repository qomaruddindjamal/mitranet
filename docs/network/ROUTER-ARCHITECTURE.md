# MITRANET — ROUTER ARCHITECTURE & IMPLEMENTATION

**Official Identity:**
- **Project**: MitraNet
- **Version**: 0.1.0-dev
- **Foundation**: MitraOS 1.0.0
- **Code OS**: Rinjani
- **Architecture**: amd64
- **Interface**: CLI + TUI + Web UI

## 1. Capabilities
MitraNet operates as a full-featured enterprise L3 router:
- Dual-stack IPv4 / IPv6 addressing.
- Static, policy-based, and dynamic routing (FRR suite).
- Multi-interface WAN/LAN inter-zone routing.
- Default gateway resolution and connected route propagation.

## 2. Configuration Model
All routing changes pass through `api/config_engine.py` under the `routes` and `addresses` domain entities before being synchronized with the Linux kernel Netlink routing table.
