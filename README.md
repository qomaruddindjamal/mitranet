# MitraNet — Production-Grade Network Operating Environment

**Official Hierarchy:**
```text
MITRANET
   ↓
MITRAOS 1.0.0
   ↓
CODE OS : RINJANI
```

- **Project**: MitraNet
- **Version**: 1.0.0 (Released)
- **Foundation**: MitraOS 1.0.0
- **Code OS**: Rinjani
- **Architecture**: amd64
- **Interface**: CLI + TUI + Web UI
- **Purpose**: Production-grade Network Operating Environment / Router OS

---

## 1. Overview
MitraNet is an open, modular, production-ready Network Operating System (NOS) designed for bare-metal x86_64 routers, enterprise appliances, and virtualized edge gateways. Built on the immutable MitraOS 1.0.0 foundation with Code OS Rinjani, MitraNet delivers wire-speed Layer-2 switching, dual-stack Layer-3 routing, stateful NFTables packet filtering, and encrypted VPN mesh termination.

## 2. Architecture & Control Flow
```text
┌────────────────────────────────────────────────────────┐
│               MANAGEMENT INTERFACES                    │
│   Web UI (public_html/) │ CLI (mitranet) │ TUI Console │
└───────────────────────────┬────────────────────────────┘
                            │ HTTP REST / JSON-RPC
┌───────────────────────────▼────────────────────────────┐
│                  REST API (:8080)                      │
│             api/REST/server.py                         │
└───────────────────────────┬────────────────────────────┘
                            │ Single Source of Truth
┌───────────────────────────▼────────────────────────────┐
│             CONFIGURATION ENGINE                       │
│             api/config_engine.py                       │
│    (Atomic Transactions, Schema Diff, Rollback)        │
└───────────────────────────┬────────────────────────────┘
                            │ Netlink / Sysfs / Sockets
┌───────────────────────────▼────────────────────────────┐
│            MITRAOS 1.0.0 (LINUX KERNEL)                │
│    Interfaces, FIB Routing, NFTables, WireGuard, tc    │
└────────────────────────────────────────────────────────┘
```

## 3. Directory Layout
```text
C:\mitranet
├── api/          # REST API server & canonical Configuration Engine
├── docs/         # Architecture, API, Web UI, and audit documentation
├── packages/     # 204 canonical package store (immutable)
├── public_html/  # Modern enterprise Web UI presentation modules
└── scripts/      # Verification suites, CLI, TUI, and automated tests
```

## 4. Quick Start
### Launch CLI
```bash
python scripts/mitranet_cli.py system info
python scripts/mitranet_cli.py interface list
python scripts/mitranet_cli.py config show
```

### Launch Interactive TUI
```bash
python scripts/mitranet_tui.py
```

### Start REST API Server
```bash
python api/REST/server.py
```

### Run Automated System Verification
```bash
python scripts/verify_project.py
python scripts/test_system_integration.py
```

## 5. Security Policy
MitraNet enforces least privilege, strictly disallowing arbitrary shell interpolation. All configuration changes must pass schema validation, dependency resolution, and atomic health checks before commit.
