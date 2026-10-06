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
| **Phase 2A** | Package Discovery & Native Strategy | **PASS / LOCKED** |
| **Phase 2B** | Native Debian Package System & Repository | **PASS / LOCKED** |

**CURRENT NEXT PHASE:** **Phase 3A / Next Release Pipeline**

---

## 3. Latest Verified Git Checkpoint

- **Phase 2A Baseline:** `b9e3176042447515cd3b8259273da76bffd7deb3`
- **Phase 2B Implementation:** Implemented, verified on live Debian Linux VM, and committed.
- **Branch:** `main`
- **Remote:** `origin` (`https://github.com/qomaruddindjamal/mitranet.git`)
- **Remote State:** Synchronized with `origin/main`.

---

## 4. Phase 2B Native Packages & Repository Verification

- **Status:** **PASS / LOCKED**
- **Native MitraNet Packages Owned & Built:**
  - `mitranet-core` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-config-engine` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-network-engine` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-gateway-monitor` (`1.0.2-1~deb13u1_all.deb`)
- **Package Tests Verified on Real Debian 13 VM:**
  - Build via `dpkg-deb`: **PASS**
  - Metadata & control validation: **PASS**
  - Dependency failure & satisfaction test: **PASS**
  - Simultaneous install (`dpkg -i`): **PASS**
  - Conffile preservation on upgrade (`1.0.2-2`): **PASS**
  - Package removal (`dpkg -r`) & service stop: **PASS**
  - Package reinstall & service resume: **PASS**
  - Systemd daemon unit active: **PASS** (`mitranet-gateway-monitor.service`)
  - CLI execution: **PASS** (`/usr/bin/mitranet --version`)
  - Security audit (zero SUID/SGID, root-owner, set -e maintainer scripts): **PASS**
- **Repository Architecture Verified:**
  - Layout: `dists/rinjani/main/binary-all` and `binary-amd64`
  - Signed indices: `InRelease` and `Release.gpg` via GPG RSA key
  - Client indexing: `apt-get update` against local file repository **PASS**
- **System Regression:**
  - `163/163` tests passed on live Linux guest VM kernel.

---

## 5. Critical Package & Runtime Rules

1. **A FreeBSD/pfSense `.pkg` is NOT a Debian `.deb`.**
2. **Never rename `.pkg` to `.deb`.**
3. **Never copy a FreeBSD binary into a `.deb`.**
4. **Never wrap FreeBSD binaries inside Debian archives.**
5. **Never fabricate package compatibility.**
6. pfSense and FreeBSD are strictly **migration and architectural reference baselines** with **0 runtime dependencies**.
7. Functional coverage and verified Linux runtime behavior govern all packaging decisions.

---

## 6. Reference ISO Checksums

- **Debian Source ISO (`debian-13.7.0-amd64-netinst.iso`):**
  `A7EF94AC2FB9A7FEC454552ABD629B7CC9D5155C886165A45649F5CE6167E355` (Read-only)
- **pfSense Source ISO (`pfsense-offline-installer.iso`):**
  `16EBD1682C7F18D0B40300EC486611979F0D0C090300EFE2BFFD7C209A624291` (Read-only)
- **Verified MitraNet ISO Checkpoint:**
  `build/MitraNet-Rinjani-1.0.2-amd64.iso` — SHA256: `CD2E296DEFE30546751597B866ECF71B26BB829D3B73510DAD3BAA130F116A19`

---

## 7. WebUI Freeze Status

The WebUI frontend (`React`, `Vite`, `web-src`, `www`, `frontend`) is **STRICTLY FROZEN**.
