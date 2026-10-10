# MITRANET RINJANI 1.0.2 — OFFICIAL RELEASE MANIFEST

- **Product Name:** MitraNet OS Rinjani
- **Version:** 1.0.2
- **Codename:** rinjani
- **Release Target:** x86_64 Bare-Metal Appliance & Router Appliance
- **Base OS:** Debian GNU/Linux 13 (Trixie)
- **Source Git Commit:** `5b6f091`
- **Git Branch:** `main`
- **Repository URL:** `https://github.com/qomaruddindjamal/mitranet.git`
- **Release Timestamp:** 2026-10-10 15:08:30 WIB

---

## 1. ISO Release Artifacts & Checksums

| File Artifact | Size (Bytes) | Size (Human) | SHA-256 Checksum |
|---|---|---|---|
| `iso/MitraNet-Rinjani-1.0.2-amd64.iso` | 1,017,139,200 | ~969.95 MB | `C0F69F83CB03AA0A37EE496C4A55CF7B2270BDF58281A7B41E63D56F943E9097` |
| `iso/mitranet-core_1.0.2-1~deb13u1_all.deb` | 4,397,152 | ~4.19 MB | `168A65163D8FC04CD6FFDF25FF7626F7454A633F2E45D76615DF7DCB6049C5EE` |

---

## 2. Verified Production Services & Ports

- **WebUI Interface:** `http://<device-ip>:8000` (PHP built-in WebUI engine)
- **REST Management API:** `http://<device-ip>:8443/api/v1` (Python asynchronous API daemon)
- **SSH Management:** `tcp/22` (OpenSSH server)
- **DNS / DHCP Services:** `dnsmasq` (`udp/53`, `tcp/53`, `udp/67`)
- **Primary VPN Uplink:** WireGuard `wg0` (`udp/51820`, connect to VPS `103.93.162.168:13231`)
- **Cloud Speed Booster Multi-Stream:** WireGuard `wgboost1`..`wgboost4` (`udp/51831`..`51834`)

---

## 3. Operational Sign-Off & Verification Status

- **Unit & Regression Suites:** **PASS** (4/4 test suites, 100% pass)
- **Mini PC Deployment (`10.10.66.228`):** **PASS** (Active, zero errors)
- **Multi-Stream VPS Tunnel Validation:** **PASS** (Handshake verified, 0% packet loss)
- **Gateway & Routing Rollback Safety:** **PASS** (Multi-default routes strictly preserved)
- **ISO Installer Boot Verification:** **NOT TESTED** (Hyper-V VM blocked by host Windows RAM allocation; physical/VM boot test required before bare-metal deployment).
