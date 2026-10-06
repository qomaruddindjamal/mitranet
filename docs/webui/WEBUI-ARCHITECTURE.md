# MITRANET — WEB UI ARCHITECTURE

**Official Identity:**
- **Project**: MitraNet
- **Version**: 0.1.0-dev
- **Foundation**: MitraOS 1.0.0
- **Code OS**: Rinjani
- **Architecture**: amd64
- **Interface**: CLI + TUI + Web UI

## 1. Architectural Role
The MitraNet Web UI resides canonically in `public_html/`. It operates as a presentation layer rendered through PHP 8.5 and vanilla JS/CSS/Bootstrap components. It communicates with the control plane strictly over the REST API (`http://localhost:8080/api/`) without executing business logic or raw configuration scripts locally.

## 2. Integration Pipeline
```text
Browser User Interface (public_html/ :80/:443)
       │
       ▼ (HTTP REST JSON requests via includes/common.js)
REST API Controller (api/REST/server.py :8080)
       │
       ▼ (Atomic Schema Validation & Transactions)
Configuration Engine (api/config_engine.py)
       │
       ▼ (Controlled Adapters & Netlink Sockets)
MitraOS 1.0.0 / System Kernel
```
