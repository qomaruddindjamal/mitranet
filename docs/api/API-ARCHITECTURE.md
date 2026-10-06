# MITRANET — REST API ARCHITECTURE

**Official Identity:**
- **Project**: MitraNet
- **Version**: 0.1.0-dev
- **Foundation**: MitraOS 1.0.0
- **Code OS**: Rinjani
- **Architecture**: amd64
- **Interface**: CLI + TUI + Web UI

## 1. Architectural Role
The REST API server (`api/REST/server.py`) serves as the official control-plane entry point on port 8080. It bridges HTTP requests from the Web UI (`public_html/`) and external automations directly to the canonical Configuration Engine (`api/config_engine.py`).

## 2. Request Flow
```text
HTTP Client (Web UI / External)
       ↓
api/REST/server.py (:8080)
       ↓
Authentication (HTTP Basic Auth / Users DB)
       ↓
RBAC Authorization (Administrator / Operator / Viewer)
       ↓
api/config_engine.py
       ↓
Transaction Pipeline (Diff, Validate, Apply, Rollback)
       ↓
Atomic Persistence (`api/config.json`)
```
