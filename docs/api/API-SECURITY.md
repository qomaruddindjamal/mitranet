# MITRANET — REST API SECURITY & PRIVILEGE BOUNDARY

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Authentication & Session Security
- **HTTP Basic Authentication**: Enforced across all endpoints (realm `MitraNet Network OS`).
- **User Database**: Validated against `/etc/mitranet/users.json` or fallback system configuration.

## 2. Role-Based Access Control (RBAC)
- **Administrator**: Full privileges (configuration mutations, apply, rollback, service control).
- **Operator**: Status view, non-destructive network telemetry.
- **Viewer**: Read-only access. Mutation requests rejected with `403 Forbidden`.

## 3. Privilege Boundaries
- REST API processes configuration exclusively through `api/config_engine.py`.
- No raw shell commands or user strings passed to subshells.
