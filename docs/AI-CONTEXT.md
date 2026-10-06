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
| **Phase 2C** | Native Package Migration & Reimplementation | **PASS / LOCKED** |
| **Phase 3A** | Security Hardening & Firewall Core | **PASS / LOCKED** |
| **WebUI v1** | Management Layer & REST API v1 | **PASS / LOCKED** |
| **Phase 3B** | NAT & Connection Tracking | **NEXT (UNFINISHED)** |

---

## 3. Latest Verified Git Checkpoint

- **Phase 3A Baseline:** `ef2558d535add049dea9e06a2d69ca52460eadd9`
- **WebUI v1 Milestone:** Implemented, verified on live Debian VM (`systemd`, port `8443`, full REST API, auth/CSRF, test suite PASS).
- **Branch:** `main`
- **Remote:** `origin` (`https://github.com/qomaruddindjamal/mitranet.git`)
- **Working Tree:** Clean


---

## 4. Phase 2C Migration & Reimplementation Verification Summary

- **Status:** **PASS / LOCKED**
- **252-Item Canonical Package Findings Resolution:**
  - **Category A (DIRECT-DEBIAN-EQUIVALENT):** 134 items (using official upstream Debian 13 packages)
  - **Category B (PORT-REQUIRED):** 0 items (no missing dependencies requiring porting)
  - **Category C (REIMPLEMENT-NATIVELY):** 15 items implemented natively in `core/services/`:
    `filterlog`, `filterdns`, `check_reload_status`, `dhcpleases`, `dhcpleases6`, `expiretable`, `cpustats`, `rate`, `qstats`, `choparp`, `minicron`, `openvpn-auth-script`, `pfSense-default-config`, `ssh_tunnel_shell`, `voucher`
  - **Category D (REPLACE-WITH-LINUX-NATIVE):** 9 items (`nftables`, `conntrack`, `wireguard-tools`, `hostapd`, `rsync`, `fping`, `qemu-system-x86`, `accel-ppp`, in-tree kernel wireguard)
  - **Category E (NOT-REQUIRED):** 78 items (build toolchains, legacy PHP 8.5 WebGUI scripts, pkg-ng)
  - **Category F (INCOMPATIBLE / UNSUITABLE):** 16 items (FreeBSD kernel modules, pfSense base/repoc/upgrade daemons)
  - **Total:** 252 / 252 accounted for (100% resolved, 0 pending, 0 unknown)
- **Native MitraNet Packages Owned & Built:**
  - `mitranet-core` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-config-engine` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-network-engine` (`1.0.2-1~deb13u1_all.deb`)
  - `mitranet-gateway-monitor` (`1.0.2-1~deb13u1_all.deb`)
- **Architecture Validation:**
  - All 4 packages built with `Architecture: all`.
  - Zero ELF executables or shared libraries in package payloads.
- **Package Content Security Audit:**
  - 0 FreeBSD binaries or shared libraries.
  - 0 pfSense binaries or BSD rc.d scripts.
  - 0 private keys / secrets / development artifacts.
  - All maintainer scripts enforce `set -e`.
- **Package Tests Verified on Live Debian 13 VM:**
  - Build via `dpkg-deb --root-owner-group --build`: **PASS**
  - Metadata & control validation: **PASS**
  - Simultaneous install (`dpkg -i`): **PASS** (status `ii`)
  - Conffile preservation on upgrade (`1.0.2-2`): **PASS**
  - Package removal (`dpkg -r`) & service stop: **PASS**
  - Package reinstall & service resume: **PASS**
  - Systemd daemon unit active: **PASS** (`mitranet-gateway-monitor.service`)
  - CLI execution: **PASS** (`/usr/bin/mitranet version`, `/usr/bin/mitranet interface list`)
  - Security audit (zero SUID/SGID, root-owner): **PASS**
- **Repository Architecture Verified:**
  - Layout: `dists/rinjani/main/binary-all`, `binary-amd64`, `pool/main/m/`
  - Signed indices: `InRelease` and `Release.gpg` via GPG RSA key (`support@mitranet.id`)
  - Private key storage: Kept strictly outside the repository and Git working tree.
  - Offline client indexing: `apt-get update` against local file repository `file:/var/local/repository` **PASS** with zero external network connectivity.
- **System Regression:**
  - `168/168` tests passed on live Linux guest VM kernel (0 failures, 0 errors, 0 skipped).
- **ISO Verification:**
  - Integrated ISO generated at `build/MitraNet-Rinjani-1.0.2-amd64.iso`
  - Size: `998,858,752 bytes`
  - SHA256: `1864DBECDF01F558F89FCB4D53BC4F979CE683117D8E21C1CDF7CB7BBDD5AA67`

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
6. Determine the first unfinished phase (currently **Phase 3A — Security Hardening & Firewall Core**).
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
- **Verified MitraNet ISO Baseline (with WebUI Management Layer v1):**
  `build/MitraNet-Rinjani-1.0.2-amd64.iso` — SHA256: `336B47E93C401A22B2C8F68C97335C4B7FAC0AFE278586440277341EE67C4536` (Verified)

---

## 8. WebUI Management Status

MitraNet WebUI Management Layer v1 is **PASS / LOCKED** and fully operational:
- Runtime: Native Python `http.server` backend + Vanilla HTML/CSS/JS frontend SPA.
- Service: `mitranet-webui.service` (systemd, port 8443).
- Security: PBKDF2 authentication, HttpOnly SameSite=Strict cookies, CSRF protection.
- Fully offline capable, zero external CDN dependencies.

