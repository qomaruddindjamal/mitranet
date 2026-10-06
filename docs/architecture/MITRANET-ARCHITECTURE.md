# MitraNet Master Architecture Specification

## 1. Architectural Philosophy
MitraNet is designed as a carrier-grade, highly reliable Network Operating System (NOS) built on top of Debian GNU/Linux. Unlike conventional Linux distributions running uncoordinated userland daemons, MitraNet implements a **unified, transactional state machine and hardware abstraction pipeline**.

```text
  ┌──────────────────────────────────────────────────────────────┐
  │                      PRESENTATION LAYER                      │
  │     Modern WebGUI (React + Vite)  │  Hierarchical CLI / SSH  │
  └──────────────────────────────┬───────────────────────────────┘
                                 │
                                 ▼
  ┌──────────────────────────────────────────────────────────────┐
  │                 CENTRAL MANAGEMENT & API LAYER               │
  │                 FastAPI REST Daemon & Unix Socket            │
  └──────────────────────────────┬───────────────────────────────┘
                                 │
                                 ▼
  ┌──────────────────────────────────────────────────────────────┐
  │                 CONFIGURATION ENGINE (CORE)                  │
  │    Schema Validation (Pydantic v2)  │  Config DB (JSON/YAML) │
  │    Candidate -> Validation -> Commit-Confirm -> Rollback     │
  └──────────────────────────────┬───────────────────────────────┘
                                 │
                                 ▼
  ┌──────────────────────────────────────────────────────────────┐
  │                   SERVICE ORCHESTRATION                      │
  │   - nftables Rules Compiler (Firewall / NAT / Mangle / PCC)  │
  │   - Kea DHCP Manager   │  Unbound DNS Engine                 │
  │   - FRRouting Manager  │  WireGuard / strongSwan Manager     │
  │   - Keepalived / Conntrackd High-Availability Orchestrator   │
  └──────────────────────────────┬───────────────────────────────┘
                                 │
                                 ▼
  ┌──────────────────────────────────────────────────────────────┐
  │               NETWORK ABSTRACTION LAYER (NAL)                │
  │   Linux netlink (PyRoute2)  │  ethtool  │  tc Traffic Control│
  │   Hardware Platform Daemon (mitranet-platformd - ONLP/I2C)   │
  └──────────────────────────────┬───────────────────────────────┘
                                 │
                                 ▼
  ┌──────────────────────────────────────────────────────────────┐
  │                 LINUX KERNEL & HARDWARE CORE                 │
  │   Debian GNU/Linux Kernel  │  Netfilter  │  Switchdev / DSA  │
  │   Standard x86 / ARM64 Server  │  OCP Bare-metal Whitebox    │
  └──────────────────────────────────────────────────────────────┘
```

---

## 2. Core Subsystems

### 2.1 Configuration Engine
- **Single Source of Truth**: `/etc/mitranet/config.json`.
- **Atomic Transactions**:
  1. User edits candidate configuration.
  2. Engine performs deep schema validation and dependency checks (e.g. verifying an interface referenced in a firewall rule actually exists).
  3. Transaction executed with an automatic rollback timer (`commit confirmed <timeout>` like Junos / RouterOS Safe Mode). If the operator loses connectivity, configuration rolls back automatically.
  4. Versioned backup snapshots kept under `/var/lib/mitranet/backups/`.

### 2.2 Network Abstraction Layer (NAL)
- Isolates high-level MitraNet business logic from low-level Linux kernel command strings.
- Communicates directly via Linux **Netlink sockets** (using pyroute2 / standard netlink bindings) rather than invoking error-prone ad-hoc bash scripts.
- Supports physical NICs, 802.1Q VLANs, bridges, link aggregation (bonds/LACP), WireGuard interfaces, and VRFs.

### 2.3 Firewall & NAT Subsystem
- Fully powered by modern `nftables`.
- High-performance state tracking via kernel `conntrack`.
- Atomic rule commit using `nft -f -` transaction batches. No packet loss or state clearing during rule reload.
- Full support for connection marking, packet classification (PCC for Multi-WAN), and GeoIP/URL table dynamic sets.

### 2.4 Hardware Abstraction Subsystem (`mitranet-platformd`)
- Automatically detects machine archetype:
  - **Type A (Standard Appliance / VM)**: KVM, VMware, Hyper-V, standard PC, server with Intel/Broadcom NICs. Disables whitebox drivers to save resources.
  - **Type B (OCP Whitebox Switch)**: Bare metal switch platform. Loads ONLP bindings (`libonlp`), reads front-panel SFP/QSFP EEPROM DOM levels, monitors chassis thermal sensors, controls PWM fan speeds, and manages front-panel LED indicators.
