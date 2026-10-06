# MitraNet Rinjani 1.0.2 — Native Package Migration Specification

## 1. Executive Summary

This document specifies the migration of all 252 software components identified in the Phase 2A discovery into Debian GNU/Linux 13 (Trixie) and MitraNet native services.

---

## 2. Package Ownership & Delivery

### 2.1 MitraNet-Owned Native Packages
Built natively using standard Debian packaging conventions (`Architecture: all`):

1. **`mitranet-core` (Version: `1.0.2-1~deb13u1`):**
   - Core CLI (`/usr/bin/mitranet`)
   - Reimplemented services (`FilterLogService`, `FilterDNSService`, `DHCPLeasesWatcher`, `SystemMetricsCollector`, `StatusReloaderService`, `ProxyARPService`, `TableExpiryService`)
   - Migration tools and OS metadata (`/etc/os-release`)

2. **`mitranet-config-engine` (Version: `1.0.2-1~deb13u1`):**
   - Configuration schema validation (`/etc/mitranet/config.schema.json`)
   - Default configurations (`/etc/mitranet/defaults.json`)
   - Phase 1F Transaction & Recovery Engine (`mitranet.core.transaction`)

3. **`mitranet-network-engine` (Version: `1.0.2-1~deb13u1`):**
   - In-tree Linux network drivers: VLAN, Bridge, Bonding/LACP, Routing, VRF

4. **`mitranet-gateway-monitor` (Version: `1.0.2-1~deb13u1`):**
   - Python gateway health monitor daemon (`/usr/bin/mitranet-gateway-monitor`)
   - systemd service unit (`mitranet-gateway-monitor.service`)

---

## 3. Debian-Owned Native Dependencies

Provided directly from Debian 13 (Trixie) and cached in the offline installer repository:
- `fping` (5.1-1)
- `python3-pydantic` (2.10.6-2)
- `python3-pydantic-core` (2.27.2-3+b1)
- `python3-jsonschema` (4.19.2-6)
- `python3-yaml` (6.0.2-1+b2)
- `libyaml-0-2` (0.2.5-2)
- `iproute2` (6.15.0-1)
- `nftables` (1.1.3-1)
- `systemd` (257.13-1~deb13u1)
