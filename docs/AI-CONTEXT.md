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

## 2. Current Verified Project Status & Roadmap

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
| **Phase 2A** | Package Discovery & Native Strategy | **PASS / LOCKED** |
| **Phase 2B** | Native Debian Package System & Repository | **PASS / LOCKED** |
| **Phase 2C** | Compatibility / Porting | **NEXT (UNFINISHED)** |

---

## 3. Latest Verified Git Checkpoint

- **Phase 2A Baseline:** `b9e3176042447515cd3b8259273da76bffd7deb3`
- **Phase 2B Verified Commit:** `c8077dd274ddd5e3486ebc7d78b5d8ce09731201`
- **Branch:** `main`
- **Remote:** `origin` (`https://github.com/qomaruddindjamal/mitranet.git`)
- **Remote Synchronization:** `origin/main` == `c8077dd274ddd5e3486ebc7d78b5d8ce09731201` (Verified via `git ls-remote`)
- **Working Tree:** Clean

---

## 4. Phase 2B Native Packages & Repository Verification Summary

- **Status:** **PASS / LOCKED**
- **Native MitraNet Packages Owned & Built:**
  - `mitranet-core` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-config-engine` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-network-engine` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-gateway-monitor` (`1.0.2-1~deb13u1_all.deb`)
- **Architecture Validation:**
  - All 4 packages built with `Architecture: all`.
  - Zero ELF executables, shared objects, or kernel modules in package payloads.
- **Package Content Security Audit:**
  - 0 FreeBSD binaries or shared libraries.
  - 0 pfSense binaries or BSD rc.d scripts.
  - 0 private keys / secrets / development artifacts.
  - All maintainer scripts enforce `set -e`.
- **Package Tests Verified on Live Debian 13 VM:**
  - Build via `dpkg-deb --root-owner-group --build`: **PASS**
  - Metadata & control validation: **PASS**
  - Dependency failure & satisfaction test: **PASS**
  - Simultaneous install (`dpkg -i`): **PASS** (status `ii`)
  - Conffile preservation on upgrade (`1.0.2-2`): **PASS**
  - Package removal (`dpkg -r`) & service stop: **PASS**
  - Package reinstall & service resume: **PASS**
  - Systemd daemon unit active: **PASS** (`mitranet-gateway-monitor.service`)
  - CLI execution: **PASS** (`/usr/bin/mitranet --version`)
  - Security audit (zero SUID/SGID, root-owner): **PASS**
- **Repository Architecture Verified:**
  - Layout: `dists/rinjani/main/binary-all`, `binary-amd64`, `pool/main/m/`
  - Signed indices: `InRelease` and `Release.gpg` via GPG RSA key (`support@mitranet.id`)
  - Private key storage: Kept strictly outside the repository and Git working tree.
  - Offline client indexing: `apt-get update` against local file repository `file:/var/www/html/mitranet-repo` **PASS** with zero external network connectivity.
- **System Regression:**
  - `163/163` tests passed on live Linux guest VM kernel (0 failures, 0 errors, 0 skipped).
- **ISO Status:**
  - `Phase 2B ISO rebuild: NOT REQUIRED / PACKAGE SYSTEM EXTERNAL TO CURRENT ISO`
  - Verified baseline installer ISO intact: `CD2E296DEFE30546751597B866ECF71B26BB829D3B73510DAD3BAA130F116A19`

---

## 5. Critical Package & Runtime Rules

1. **A FreeBSD/pfSense `.pkg` is NOT a Debian `.deb`.**
2. **Never rename `.pkg` to `.deb`.**
3. **Never copy a FreeBSD binary into a `.deb`.**
4. **Never wrap FreeBSD binaries inside Debian archives.**
5. **Never fabricate package compatibility.**
6. pfSense and FreeBSD are strictly **migration and architectural reference baselines** with **0 runtime dependencies**.
7. Functional coverage and verified Linux runtime behavior govern all packaging decisions.
8. Debian-native packages preferred; Linux-native implementations preferred.
9. Incompatible packages explicitly excluded.
10. Package ownership must remain deterministic.

---

## 6. AI Recovery Instructions

When a new AI/Antigravity conversation starts:

1. Read `docs/AI-CONTEXT.md` first.
2. Audit `git status`.
3. Audit current HEAD (`git rev-parse HEAD`).
4. Audit `origin/main` (`git ls-remote origin main`).
5. Read latest phase documentation in `docs/`.
6. Determine the first unfinished phase (currently **Phase 2C — Compatibility / Porting**).
7. Treat all **PASS / LOCKED** phases as immutable. Do not reopen, redesign, or rewrite them.
8. Continue ONLY from the next unfinished phase.
9. Never assume a report is evidence — verify actual files, commits, and VM results.
10. Never rename `.pkg` to `.deb`.
11. Never bundle FreeBSD/pfSense binaries.
12. Never introduce FreeBSD/pfSense runtime dependencies.
13. WebUI remains strictly frozen.
14. Never perform report-only implementation.
15. Every major phase requires implementation + testing + real Linux validation where applicable + Git verification.

---

## 7. Reference ISO Checksums

- **Debian Source ISO (`debian-13.7.0-amd64-netinst.iso`):**
  `A7EF94AC2FB9A7FEC454552ABD629B7CC9D5155C886165A45649F5CE6167E355` (Read-only, verified)
- **pfSense Source ISO (`pfsense-offline-installer.iso`):**
  `16EBD1682C7F18D0B40300EC486611979F0D0C090300EFE2BFFD7C209A624291` (Read-only, verified)
- **Verified MitraNet ISO Baseline:**
  `build/MitraNet-Rinjani-1.0.2-amd64.iso` — SHA256: `CD2E296DEFE30546751597B866ECF71B26BB829D3B73510DAD3BAA130F116A19` (Intact)

---

## 8. WebUI Freeze Status

The WebUI frontend (`React`, `Vite`, `web-src`, `www`, `frontend`) is **STRICTLY FROZEN**.
No modifications permitted.
