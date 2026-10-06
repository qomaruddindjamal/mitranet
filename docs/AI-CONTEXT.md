# MitraNet Rinjani 1.0.2 — AI Context & Recovery Checkpoint

## 1. Project Identity

- **Project:** MitraNet
- **Codename:** Rinjani
- **Version:** 1.0.2
- **Base:** Debian GNU/Linux 13 Trixie
- **Architecture:** amd64 / x86_64
- **Project Root:** `C:\mitranet`
- **Git Branch:** `main`

---

## 2. Current Verified Project Status

| Phase | Description | Status |
|---|---|---|
| **Phase 0.5** | Native Configuration | **PASS / LOCKED** |
| **Phase 0.6** | Rebaseline | **PASS / LOCKED** |
| **Phase 1A** | Interface Discovery | **PASS / LOCKED** |
| **Phase 1B** | Interface Configuration | **PASS / LOCKED** |
| **Phase 1C** | Routing Core | **PASS / LOCKED** |
| **Phase 1D** | VLAN / Bridge / Bond / LACP | **PASS / LOCKED** |
| **Phase 1E** | VRF Core | **PASS / LOCKED** |
| **Phase 1E Addendum** | Environment Gate Hardening | **PASS / LOCKED** |
| **Phase 1F** | Transaction / Recovery Engine | **PASS / LOCKED** |
| **Phase 2A** | Package Discovery, Reverse Engineering & Native Debian Strategy | **PASS / LOCKED** |

**CURRENT NEXT PHASE:** **Phase 2B — Native Debian Package System**

---

## 3. Latest Verified Git Checkpoint

- **Latest Verified Commit:** `b9e3176042447515cd3b8259273da76bffd7deb3`
- **Commit Message:** `docs/packages: establish native Debian package strategy`
- **Branch:** `main`
- **Remote:** `origin` (`https://github.com/qomaruddindjamal/mitranet.git`)
- **Remote State:** Local HEAD and `origin/main` verified in exact sync.
- **Previous Locked Baseline:** `6e69c561c310f8dc251fc41f0d77330565916c21` (Phase 1F Final Acceptance).

---

## 4. Phase 2A Final Acceptance Summary

- **Status:** **PASS / LOCKED**
- **Discovered Source Packages:**
  - pfSense local SQLite database (`/VAR/DB/PKG/LOCAL.SQLITE`): **222** packages
  - pfSense repository index (`/VAR/DB/PKG/REPOS/PFSENSE/DB`): **71** packages
  - `packagesite.yaml` manifests (`/PACKAGES/PACKAGESITE.PKG`): **213** packages
  - Offline `.pkg` archives (`/PACKAGES/`): **219** archives
  - Custom offline packages: **5** (`wireguard-pfsense`, `xray-pfsense`, `kvm`, `speedtest`, `wifi`)
  - Debian reference `.deb` pool: **495** packages
  - **Deduplicated Unique Packages:** **252** packages
- **Classification Reconciliation:**
  - **A — DIRECT-DEBIAN-EQUIVALENT:** **134** (Available in Debian 13)
  - **B — PORT-REQUIRED:** **0** (No custom ports needed; upstream available)
  - **C — REIMPLEMENT-NATIVELY:** **15** (MitraNet native Python/systemd/nftables services)
  - **D — REPLACE-WITH-LINUX-NATIVE:** **9** (Modern Linux in-tree kernel subsystems)
  - **E — NOT-REQUIRED:** **78** (Legacy PHP 8.5 WebGUI stack, PEAR, pkg-ng, buildtools)
  - **F — INCOMPATIBLE / UNSUITABLE:** **16** (FreeBSD kernel, kmods, obsolete hardware utils)
  - **Total Classified:** **252**
  - **Total Excluded (E + F):** **94**
- **Coverage Metrics:**
  - Discovery Coverage: **100%**
  - Classification Coverage: **100%**
  - Function Mapping Coverage: **100%**
  - Decision Coverage: **100%**

---

## 5. Phase 2A Authoritative Documentation

All package discovery and architectural strategy artifacts reside under `docs/packages/`:
- `docs/packages/FREEBSD-PACKAGE-INVENTORY.md`: Comprehensive inventory of 233 FreeBSD ports/packages.
- `docs/packages/PFSENSE-PACKAGE-INVENTORY.md`: Detailed records for 19 pfSense core extensions and custom packages.
- `docs/packages/PACKAGE-MAPPING.md`: Domain-by-domain mapping manifesto across 9 functional categories.
- `docs/packages/PACKAGE-PORTING-MATRIX.md`: Complete porting and migration decision matrix for all 252 packages.
- `docs/packages/PACKAGE-EXCLUDED.md`: Technical and architectural justifications for all 94 excluded packages.
- `docs/packages/PACKAGE-COVERAGE-REPORT.md`: Full quantitative coverage audit report.
- `docs/packages/REPOSITORY-DESIGN.md`: Specification for native Debian APT pool, GPG signing, and rollback integration.

---

## 6. Critical Package Rules

1. **A FreeBSD/pfSense `.pkg` is NOT a Debian `.deb`.**
2. **Never rename `.pkg` to `.deb`.**
3. **Never copy a FreeBSD binary into a `.deb`.**
4. **Never wrap FreeBSD binaries inside Debian archives.**
5. **Never fabricate package compatibility.**
6. Native Debian packages must be genuinely Linux/Debian compatible and compile/run natively against the Linux kernel and glibc.
7. Package archive count is not a metric of success; **functional coverage** and verified runtime behavior govern all packaging decisions.

---

## 7. pfSense / FreeBSD Runtime Isolation

- pfSense and FreeBSD are strictly **migration and architectural reference baselines**.
- They are **NOT** the MitraNet runtime platform.
- Zero runtime dependencies are permitted on:
  - FreeBSD kernel or kernel modules (`.ko`)
  - FreeBSD ABI or syscall emulation
  - FreeBSD `rc.d` service scripts
  - FreeBSD `pkg` or `pkg-static`
  - FreeBSD userland binaries or libraries

---

## 8. MitraNet System Architecture

```
         WEB / CLI / API
                ↓
      CONFIGURATION ENGINE
                ↓
          MITRANET CORE
                ↓
    NETWORK / FIREWALL / ROUTING
                ↓
       SERVICE ORCHESTRATOR
                ↓
        LINUX NETWORK STACK
  (iproute2, netlink, nftables, sysfs)
                ↓
           LINUX KERNEL
                ↓
NIC / switchdev / DSA / Hardware Interfaces
```

---

## 9. Native Configuration Layout

All system configuration is strictly decoupled into `/etc/mitranet/`:
```
/etc/mitranet/
├── config.json           # Active validated configuration
├── config.schema.json    # JSON schema validator
├── running.json          # Active running runtime state
├── candidate.json        # Staged candidate configuration
├── defaults.json         # Factory default configuration
├── state.json            # Transient system state cache
├── migrations/           # Versioned migration schemas
├── snapshots/            # Atomic pre-transaction snapshots
└── secrets/              # Cryptographic tokens and keys
```

Candidate and running configurations are completely decoupled. All changes flow through the Phase 1F Transaction & Recovery Engine.

---

## 10. Phase 1F Transaction & Recovery Engine

Phase 1F is **LOCKED and fully verified**:
- **Lifecycle:** Candidate configuration → Dry-run validation → Transaction planning → Pre-apply snapshot → Atomic apply → Runtime healthcheck verification → Commit or Rollback.
- **Resilience:** Automatic recovery from crashes, `RECOVERY_REQUIRED` safety locks, snapshot cryptographic integrity, management plane isolation (preventing operator lockout), and strict idempotency.
- **Verified Regression Baseline:** 163/163 test cases passing on guest Debian Linux VM.

---

## 11. Reference ISO Checksums

- **Debian Source ISO (`debian-13.7.0-amd64-netinst.iso`):**
  `A7EF94AC2FB9A7FEC454552ABD629B7CC9D5155C886165A45649F5CE6167E355`
- **pfSense Source ISO (`pfsense-offline-installer.iso`):**
  `16EBD1682C7F18D0B40300EC486611979F0D0C090300EFE2BFFD7C209A624291`
- **Latest Verified Build ISO (`build/MitraNet-Rinjani-1.0.2-amd64.iso`):**
  `CD2E296DEFE30546751597B866ECF71B26BB829D3B73510DAD3BAA130F116A19`

All source ISOs are read-only reference assets.

---

## 12. WebUI Freeze Status

The WebUI frontend (`React`, `Vite`, `web-src`, `www`, `frontend`) is **STRICTLY FROZEN**. No changes to frontend components, build scripts, or UI dependencies are permitted unless explicitly authorized by a future UI phase.

---

## 13. Phase 2B Roadmap & Objectives

**Phase 2B Objective:** Native Debian Package System.
- Construct authentic, reproducible native Debian packages (`.deb`) for MitraNet core services and required daemons.
- Follow standard Debian packaging conventions (`debian/control`, `debian/rules`, proper systemd integration, maintainer scripts with `set -e`).
- Validate package installation (`dpkg -i`), dependency resolution (`apt install`), daemon startup, runtime testing, and purge/upgrade cycles on a live Debian Linux VM.

---

## 14. Git Safety & Integrity Rules

- **Strictly Prohibited:** `git reset --hard`, `git clean -fd`, `git push --force`, and Git history rewriting.
- **Pre-Commit Verification:** Run `git status`, `git diff --check`, and ensure working tree is clean.
- **Post-Push Verification:** Always verify `git ls-remote origin main` matches local `HEAD`.

---

## 15. AI / Antigravity Recovery Protocol

When starting or resuming a session in this repository:
1. **Read this file (`docs/AI-CONTEXT.md`) first.**
2. Inspect the working tree with `git status` and verify branch is `main`.
3. Check latest commits with `git log -5 --oneline`.
4. Verify remote alignment with `git remote -v` and `git ls-remote origin main`.
5. **Treat Phases 0.5 through 2A as LOCKED.** Do not refactor or rewrite completed phases.
6. Continue directly from the next scheduled phase (**Phase 2B**).
7. Maintain the **NO REPORT-ONLY RULE**: All tasks must be backed by real code, audit trails, and regression testing.
