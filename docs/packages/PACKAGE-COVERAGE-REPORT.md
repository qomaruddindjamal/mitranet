# MitraNet Rinjani 1.0.2 - Phase 2A Package Coverage Report

## 1. Executive Summary & Audit Verification

Phase 2A establishes the comprehensive package discovery, reverse engineering, functional classification, and Debian native repository strategy for MitraNet Rinjani 1.0.2. In strict accordance with the project guidelines:

- **Zero Blind Renaming:** No FreeBSD .pkg was renamed to .deb.
- **Zero Fake Compatibility:** No FreeBSD binaries were bundled into Debian archives.
- **Reference Isolation:** FreeBSD / pfSense remains strictly a migration and reference source, with 0 runtime dependencies.
- **Functional Priority:** Coverage is measured across functional domains rather than naive archive counts.

## 2. Quantitative Coverage Metrics

| Metric | Count | Percentage | Status |
|---|---|---|---|
| Total Discovered Source Packages | 479 | 100.0% | Complete Discovery |
| Unique Discovered Packages (Deduplicated) | 252 | 100.0% | Fully Audited |
| Redundant / Duplicate Instances Deduplicated | 227 | 100.0% | Deduplicated |
| Core Functions Discovered | 42 | 100.0% | Mapped |
| Core Functions Required by MitraNet | 35 | 100.0% | Specified |
| Direct Debian Equivalents (Category A) | 134 | 53.2% | Available in Debian 13 |
| Native Reimplementations (Category C) | 15 | 6.0% | MitraNet Native Core Engine |
| Replaced with Linux-Native (Category D) | 9 | 3.6% | Modern Linux In-Tree Stack |
| Excluded: Not Required (Category E) | 78 | 31.0% | Technically Justified |
| Excluded: Incompatible / Unsuitable (Category F) | 16 | 6.3% | Technically Justified |
| Port Required (Category B) | 0 | 0.0% | None Needed (Debian has upstream) |
| Discovery Coverage | 100% | 100.0% | **PASS** |
| Classification Coverage | 100% | 100.0% | **PASS** |
| Decision Coverage | 100% | 100.0% | **PASS** |

## 3. Coverage Analysis by Strategy

### A. Direct Debian Equivalents (134 Packages)
Standard software including dnsmasq, unbound, kea, strongswan, openvpn,
ginx,
rdtool, smartmontools, and base libraries (libxml2, curl, openssl). These packages are installed and updated directly through the official Debian GNU/Linux 13 Trixie pool, avoiding maintenance fork overhead.

### B. Linux-Native Replacements (9 Packages)
FreeBSD-specific subsystems replaced by Linux kernel facilities:
- wireguard-pfsense / pfSense-pkg-WireGuard -> Linux in-tree WireGuard module (wireguard-tools)
- wifi -> Linux mac80211/cfg80211 (hostapd, wpasupplicant, iw)
- kvm -> Linux KVM kernel driver + QEMU (qemu-system-x86, libvirt)
- pftop / libpfctl -> Netfilter /
ftables connection state tables (conntrack, iptstate)
- mpd5 -> Linux ccel-ppp / pppd
- dpinger -> Linux ping / BFD / systemd-networkd gateway probes
- cpdup -> Linux standard
sync / cp

### C. MitraNet Native Reimplementations (15 Packages)
pfSense helper daemons tightly bound to BSD IPC, pf(4), and syslog:
- pfSense-default-config -> MitraNet /etc/mitranet/config.json schema validation & transaction engine
- check_reload_status -> MitraNet async event loop & systemd service managers
- ilterlog -> MitraNet systemd-journald structured logging
- ilterdns -> MitraNet DNS resolver set-updater (nftables sets)
- expiretable -> Native in-kernel nftables stateful set timeouts
- dhcpleases / dhcpleases6 -> MitraNet lease monitor daemon
- qstats /
ate / cpustats -> MitraNet metrics exporter querying /proc and /sys
- minicron -> Debian systemd timer units
- choparp -> Linux kernel in-tree proxy ARP (proxy_arp sysctl)

### D. Legitimate Exclusions (94 Packages)
Technical justifications documented in detail in PACKAGE-EXCLUDED.md:
- 16 Incompatible packages: FreeBSD kernel (pfSense-kernel-pfSense), kmods (if_pppoe_kmod, wi-firmware-kmod), pfSense base system scripts, and obsolete hardware utils (wrapalixresetbutton).
- 78 Not Required packages: Legacy PHP 8.5 WebGUI stack (php85-*, PEAR), FreeBSD pkg-ng packaging tools, and upstream build-time compilers.
