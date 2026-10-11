# MitraNet Native Debian Package Specifications

## 1. Inventory of Native Packages

### 1.1 `mitranet-core`
- **Version:** `1.0.2-3~deb13u1`
- **Architecture:** `all`
- **Section:** `net`
- **Installed Size:** ~10 MB
- **Payload:**
  - `/usr/bin/mitranet`: CLI wrapper executing Python entry point.
  - `/usr/share/mitranet/web/`: Canonical Webroot (PHP WebUI management layer).
  - `/usr/lib/python3/dist-packages/mitranet/core/`: Firewall, migration, services, and version subsystems.
  - `/usr/lib/python3/dist-packages/mitranet/src/`: Core CLI, API server, and system management modules.
  - `/usr/lib/python3/dist-packages/mitranet/api/`: REST API module alias.
- **Maintainer Scripts:** `postinst` (creates system runtime directories `/var/log/mitranet`, sets permissions on `/usr/share/mitranet/web`, updates `/etc/os-release`, restarts `mitranet-webui.service`), `postrm` (cleans transient caches on purge).

### 1.2 `mitranet-config-engine`
- **Version:** `1.0.2-3~deb13u1`
- **Architecture:** `all`
- **Section:** `net`
- **Installed Size:** ~78 KB
- **Payload:**
  - `/etc/mitranet/config.schema.json`: Active JSON Schema validation specification.
  - `/etc/mitranet/config.json`: Master running configuration (conffile).
  - `/etc/mitranet/defaults.json`: Factory default configuration (conffile).
  - `/usr/lib/python3/dist-packages/mitranet/core/config/`: Configuration model, loader, validation engine.
  - `/usr/lib/python3/dist-packages/mitranet/core/transaction/`: Atomic transaction, planner, snapshot, lock, and recovery engine.
- **Conffiles:** `/etc/mitranet/config.json`, `/etc/mitranet/config.schema.json`, `/etc/mitranet/defaults.json`.

### 1.3 `mitranet-network-engine`
- **Version:** `1.0.2-3~deb13u1`
- **Architecture:** `all`
- **Section:** `net`
- **Installed Size:** ~145 KB
- **Payload:**
  - `/usr/lib/python3/dist-packages/mitranet/core/network/`: Complete in-tree Linux network orchestration layer (interfaces, static routes, 802.1Q VLANs, bridges, bonding, VRF Lite, and nftables firewall compiler).

### 1.4 `mitranet-gateway-monitor`
- **Version:** `1.0.2-3~deb13u1`
- **Architecture:** `all`
- **Section:** `net`
- **Installed Size:** ~4 KB
- **Payload:**
  - `/usr/bin/mitranet-gateway-monitor`: Native async ICMP gateway latency probing daemon.
  - `/lib/systemd/system/mitranet-gateway-monitor.service`: Hardened systemd daemon unit.
- **Maintainer Scripts:** `postinst` (systemctl enable), `prerm` (systemctl stop & disable).
