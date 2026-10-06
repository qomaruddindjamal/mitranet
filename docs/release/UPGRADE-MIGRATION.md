# MITRANET UPGRADE, CONFIGURATION MIGRATION & RECOVERY SPECIFICATION

## 1. OVERVIEW
This document defines the lifecycle and guarantees for migrating MitraNet between versions (e.g. `0.1.0-dev` to `1.0.0`), validating backups across version boundaries, and executing deterministic rollbacks during failure.

---

## 2. CONFIGURATION SCHEMA MIGRATION MODEL
MitraNet configuration is maintained canonically in JSON format (`api/config.json`) with an explicit top-level `"version"` attribute.

### Migration Lifecycle:
```text
CANDIDATE CONFIG (Legacy / Incoming Version)
                     │
                     ▼
1. VERSION IDENTIFICATION (Detect schema version)
                     │
                     ▼
2. SCHEMA UPGRADE DISPATCH (Execute stepwise migrators v1.0.0 -> v1.1.0)
                     │
                     ▼
3. SEMANTIC VALIDATION (Run ConfigEngine.validate())
                     │
                     ▼
4. PRE-UPGRADE SNAPSHOT (Timestamped backup to api/backups/)
                     │
                     ▼
5. ATOMIC COMMIT (os.replace() on config.json)
                     │
                     ▼
6. SERVICE RESTART & HEALTH CHECK
    ├── SUCCESS: Upgrade Complete
    └── FAILURE: Automatic Rollback to Pre-Upgrade Snapshot
```

---

## 3. SEPARATION OF STATE
To ensure seamless upgrades, MitraNet strictly segregates configuration data from volatile runtime state:
- **Persistent Configuration** (Migrated & Backed up):
  - Interface definitions (`eth0`, `eth1`, VLANs, bridges, bonds)
  - IPv4/IPv6 address assignments
  - Static and policy routes
  - Stateful firewall rules and zone assignments
  - NAT mapping (SNAT/DNAT)
  - QoS rate parameters
  - VPN tunnels and crypto identities
  - DHCP and DNS resolver configs
- **Volatile Runtime Data** (Purged upon upgrade/reboot):
  - Dynamic DHCP leases (`/var/lib/dhcp/`)
  - Connection tracking tables (`conntrack`)
  - Dynamic ARP/NDP caches
  - Temporary PID files and unix domain sockets
  - Web UI session tokens

---

## 4. BACKUP & RESTORE COMPATIBILITY MATRIX
1. **Schema Check on Import**: When restoring an external configuration archive, `ConfigEngine` parses the schema version. If the backup version is older than running schema, the automated migration dispatcher upgrades keys to current standards.
2. **Secret Protection**: Private WireGuard keys, IPsec pre-shared keys, and user authentication tokens must be sanitized during non-privileged exports or encrypted with passphrase protection.
3. **Rollback Guarantee**: Every apply or restore operation creates an immutable snapshot in `api/backups/config_<timestamp>_<tx_id>.json`. In case of a failed upgrade transaction or service health check failure, the system rolls back cleanly to the preceding valid state within 50 ms.

---

## 5. INTERRUPTED UPGRADE RECOVERY (FAIL-SAFE)
- **Power Failure during Commit**: The use of atomic rename (`os.replace`) ensures the configuration file is never partially written or corrupted on disk.
- **Service Crash after Upgrade**: If core services fail health checks post-upgrade, the supervisor triggers `ConfigurationEngine.rollback()`, restoring working state.
- **Filesystem Full**: Pre-commit checks evaluate disk space prior to writing backup and candidate files.
