# MitraNet Native Debian Package Build System

## 1. Overview & Architecture

MitraNet Rinjani 1.0.2 builds genuine native Debian packages (`.deb`) compliant with Debian Policy and Debian GNU/Linux 13 (Trixie) standards. The packaging framework adheres strictly to the **Non-Negotiable Rule**:
- Zero FreeBSD `.pkg` conversion or renaming.
- Zero FreeBSD ABI binaries wrapped in `.deb` archives.
- Clean separation between Debian upstream packages and native MitraNet-owned packages.

---

## 2. Package Ownership Model

| Package Name | Architecture | Role & Component Payload | Dependencies |
|---|---|---|---|
| **`mitranet-core`** | `all` | System identity (`/etc/os-release`), CLI entry point (`/usr/bin/mitranet`), CLI modules, and migration exporters | `python3 (>= 3.13)`, `systemd`, `nftables (>= 1.1.0)`, `iproute2 (>= 6.12)`, `mitranet-config-engine`, `mitranet-network-engine` |
| **`mitranet-config-engine`** | `all` | Native config schema (`/etc/mitranet/config.schema.json`), defaults, JSON validation, candidate/running separation, and Phase 1F transaction engine | `python3 (>= 3.13)`, `python3-jsonschema \| python3` |
| **`mitranet-network-engine`** | `all` | Native Linux network layer (Interfaces, Static Routes, 802.1Q VLANs, Bridges, LACP Bonding, VRF Lite, and nftables rulesets) | `python3 (>= 3.13)`, `iproute2 (>= 6.12)`, `nftables (>= 1.1.0)` |
| **`mitranet-gateway-monitor`** | `all` | Native gateway latency and healthcheck monitor daemon replacing legacy BSD dpinger (`/usr/bin/mitranet-gateway-monitor`, systemd unit) | `python3 (>= 3.13)`, `iputils-ping \| fping`, `systemd` |

---

## 3. Package Build Workflow

Packages are staged under `packages/debian/<package_name>/` and built deterministically using `dpkg-deb`:

```sh
cd /mitranet/packages/debian

# Build packages with root owner mapping
dpkg-deb --root-owner-group --build mitranet-config-engine /mitranet/packages/pool/mitranet-config-engine_1.0.2-1~deb13u1_all.deb
dpkg-deb --root-owner-group --build mitranet-network-engine /mitranet/packages/pool/mitranet-network-engine_1.0.2-1~deb13u1_all.deb
dpkg-deb --root-owner-group --build mitranet-gateway-monitor /mitranet/packages/pool/mitranet-gateway-monitor_1.0.2-1~deb13u1_all.deb
dpkg-deb --root-owner-group --build mitranet-core /mitranet/packages/pool/mitranet-core_1.0.2-1~deb13u1_all.deb
```

---

## 4. Conffile & Configuration Safety Rules

Configuration files in `/etc/mitranet/` (`config.json`, `config.schema.json`, `defaults.json`) are registered in `DEBIAN/conffiles`. Upgrades preserve modified user configurations across package revisions without data loss.

---

## 5. Security & Systemd Standards

- Maintainer scripts run with `set -e`.
- Service units declare security hardening directives (`ProtectSystem=strict`, `ProtectHome=true`, `CapabilityBoundingSet`).
- Services are stopped gracefully during `prerm` and removed cleanly upon package removal.
