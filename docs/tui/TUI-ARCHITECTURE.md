# MITRANET — TUI ARCHITECTURE & MENU WORKFLOW

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Overview
The MitraNet Console/TUI is a lightweight menu-driven interface executed via `scripts/mitranet_tui.py`. It interfaces directly with the shared `api/config_engine.py` without replicating any business rules, validation schemas, or transaction logic.

## 2. Menu Navigation
1. **Interfaces & IP Assignment**: View network links, MTU settings, and assigned zones.
2. **Routing & Gateways**: Inspect static default and network routes.
3. **Firewall & Packet Filter**: View active rules and state policies.
4. **System Platform Info**: Verify release and foundation identity.
5. **Configuration Diff & Validation**: Execute pre-apply syntax/semantic audits.
6. **Rollback Previous Change**: Safely revert to previous backup snapshot.
0. **Exit**: Return to system shell.
