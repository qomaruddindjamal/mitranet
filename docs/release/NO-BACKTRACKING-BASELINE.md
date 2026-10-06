# MITRANET NO-BACKTRACKING ARCHITECTURAL BASELINE & EXTENSION GUIDE

## 1. OFFICIAL IDENTITY & CANONICAL HIERARCHY
```text
MITRANET
   ↓
MITRAOS 1.0.0
   ↓
CODE OS : RINJANI
```
- **Project**: MitraNet
- **Current Development Version**: `0.1.0-dev`
- **Canonical Release Target**: `1.0.0`
- **Architecture**: `amd64`
- **Foundation**: `MitraOS 1.0.0` (Debian 13 Trixie base, Linux Kernel `6.12.111+deb13-amd64`)
- **Canonical Release ISO Target**: `mitranet-rinjani-installer.1.0.0.iso`

---

## 2. WHAT IS FROZEN & MUST NOT CHANGE
1. **MitraOS 1.0.0**: The underlying OS foundation is frozen.
2. **MitraOS-Apollo-amd64.iso**:
   - SHA256: `8e414785059f42b5f1cf7872cf926213c2b037e6291e9ba0d194bb3c9dbe1392`
   - Size: `99,774,464` bytes.
   - Status: Untracked binary baseline. Must NEVER be overwritten, renamed, or modified.
3. **Canonical Package Store (`packages/`)**:
   - Exactly 204 packages.
   - Zero duplicates.
   - Split package sets (`mitranet-base-1.0.0.pkg.partaa` & `partab`) must remain split.
   - Binary packages must not be modified, rebuilt, or converted.
4. **Canonical Structure**:
   - Only `api/`, `packages/`, `public_html/`, `docs/`, `scripts/`, `.gitignore`, `.gitattributes`, `README.md`.
   - FORBIDDEN: `src/`, `core/`, `engine/`, `platform/`, `installer/`, `build/`, `rootfs/`, `systemd/`, `release/`, `network/`.

---

## 3. CANONICAL LAYERS & FLOW OF CONTROL
```text
MITRANET WEB UI (public_html/)
       ↓ (HTTP REST Calls with Admin Token)
REST API (api/REST/server.py on port 8080)
       ↓ (In-memory Python API calls)
CONFIGURATION ENGINE (api/config_engine.py)
       ↓ (Atomic Commit / Dynamic Health Check)
MITRANET PLATFORM & MANAGERS (api/REST/enterprise_network_manager.py, firewall_manager.py)
       ↓ (Sysfs, iproute2, nftables, FRR, ONLP)
MITRAOS 1.0.0 SYSTEM KERNEL & PACKAGES
```

### Strict Boundary Rules:
- **Web UI never bypasses REST API**: Web UI scripts execute `fetch('/api/...')`, NEVER direct shell commands or php `exec()` against the host.
- **REST API never bypasses ConfigurationEngine**: Configuration mutations stage into candidate config, run validation, execute diff, and commit atomically.
- **CLI and TUI share ConfigurationEngine**: Both [`scripts/mitranet_cli.py`](file:///c:/mitranet/scripts/mitranet_cli.py) and [`scripts/mitranet_tui.py`](file:///c:/mitranet/scripts/mitranet_tui.py) import `ConfigurationEngine` directly from `api/config_engine.py`. Zero business logic is duplicated.

---

## 4. HOW TO EXTEND MITRANET WITHOUT BACKTRACKING

### 1. How to Add a Core Network Feature:
- Define domain models and JSON schema keys in `api/config_engine.py`.
- Add validation rules in `ConfigurationEngine.validate()`.
- Implement execution hooks in `api/REST/enterprise_network_manager.py` or `firewall_manager.py`.
- Expose REST API endpoint in `api/REST/server.py`.
- Bind Web UI views in `public_html/` and CLI command handlers in `scripts/mitranet_cli.py`.

### 2. How to Add or Update Packages:
- Package files must be placed exclusively in `packages/`.
- Register filename, SHA256, dependencies, and install tier in `docs/packages/packages.pkg.json`.
- Synchronize matrices in `docs/packages/PACKAGE-MANIFEST.md` and `PACKAGE-INSTALL-ORDER.md`.
- Run `python scripts/test_all_packages.py` to verify 100% graph integrity.

### 3. How to Deploy to Target Hardware:
- Use the validated canonical installer script: [`scripts/install_mitranet.sh`](file:///c:/mitranet/scripts/install_mitranet.sh).
- Never write ad-hoc formatting or partitioning scripts.
