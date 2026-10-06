# MitraNet Rinjani 1.0.2 — 252-Item Package Migration Matrix

## 1. Summary of Canonical Classifications

Total canonical findings evaluated: **252**

- **Category A (DIRECT-DEBIAN-EQUIVALENT):** 134 items
- **Category B (PORT-REQUIRED):** 0 items
- **Category C (REIMPLEMENT-NATIVELY):** 15 items
- **Category D (REPLACE-WITH-LINUX-NATIVE):** 9 items
- **Category E (NOT-REQUIRED):** 78 items
- **Category F (INCOMPATIBLE / UNSUITABLE):** 16 items

**Total Accounted:** 134 + 0 + 15 + 9 + 78 + 16 = **252 items (100% complete, 0 pending, 0 unknown)**

---

## 2. Category C: Reimplemented Natively (15 Items)

| Source Component | Original FreeBSD/pfSense Function | Linux Native Reimplementation | Package / Delivery |
|---|---|---|---|
| `check_reload_status` | Script execution event daemon | `StatusReloaderService` (event queues in `/run/mitranet`) | `mitranet-core` |
| `choparp` | Proxy ARP daemon | `ProxyARPService` (in-tree Linux kernel `/proc/sys/net/ipv4/conf/*/proxy_arp`) | `mitranet-core` |
| `cpustats` | CPU performance metrics reader | `SystemMetricsCollector.get_cpu_stats()` (`/proc/stat`) | `mitranet-core` |
| `dhcpleases` | IPv4 lease watcher & DNS sync | `DHCPLeasesWatcher` (dnsmasq / Kea format parser) | `mitranet-core` |
| `dhcpleases6` | IPv6 lease watcher & trigger | `DHCPLeasesWatcher` (Kea / radvd parser) | `mitranet-core` |
| `expiretable` | Table expiration daemon | `TableExpiryService` (in-tree `nftables` stateful set timeout) | `mitranet-core` |
| `filterdns` | Dynamic FQDN DNS resolver for firewall | `FilterDNSService` (`socket.getaddrinfo` + nftables sets) | `mitranet-core` |
| `filterlog` | Firewall packet log analyzer | `FilterLogService` (Linux netfilter / nftables log format) | `mitranet-core` |
| `minicron` | Periodic task scheduler | systemd timers & standard Linux cron | Systemd native |
| `openvpn-auth-script` | Deferred authentication script | Python pam/systemd authentication handler | `mitranet-core` |
| `pfSense-default-config` | Default XML configuration | MitraNet native `config.json` & `defaults.json` | `mitranet-config-engine` |
| `qstats` | Queue traffic stats reader | `SystemMetricsCollector` (`/proc/net/dev` / tc stats) | `mitranet-core` |
| `rate` | Traffic rate calculation | `SystemMetricsCollector.get_interface_traffic()` | `mitranet-core` |
| `ssh_tunnel_shell` | Restricted shell for SSH tunnels | Standard Linux OpenSSH restricted shell (`/bin/rbash` / `mitranet-shell`) | `mitranet-core` |
| `voucher` | Captive portal voucher management | MitraNet native token/auth provider in Core API | `mitranet-core` |

---

## 3. Category D: Linux-Native Replacements (9 Items)

| Source Component | Linux Native Replacement | Debian 13 Delivery |
|---|---|---|
| `cpdup` | `rsync` / `cp --archive` | Standard Debian `rsync` |
| `dpinger` | `mitranet-gateway-monitor` + `fping` | `mitranet-gateway-monitor` (`fping 5.1-1`) |
| `kvm` (bhyve) | Linux KVM + QEMU (`qemu-system-x86`, `libvirt`) | Debian in-tree KVM |
| `libpfctl` | `nftables` / `libnftnl` | Debian `nftables` & in-tree netlink |
| `mpd5` | `accel-ppp` / `pppd` | Debian `pppd` / `accel-ppp` |
| `pfSense-pkg-WireGuard`| Linux in-tree WireGuard module + `wireguard-tools` | Debian `wireguard-tools` |
| `pftop` | `conntrack-tools` / `iptstate` / `nft monitor` | Debian `conntrack` / `nftables` |
| `wifi` | `hostapd` + `nl80211` + `wpa_supplicant` | Debian `hostapd` |
| `wireguard-pfsense` | Linux kernel WireGuard driver | Debian in-tree kernel |

---

## 4. Category E & F: Excluded Packages Summary

- **Category E (78 items):** Build toolchains (`autoconf`, `automake`, `cmake-core`, `gmake`, etc.), obsolete PHP 8.5 WebGUI extensions, FreeBSD package tools (`pkg-ng`, `libucl`), and non-runtime clutter.
- **Category F (16 items):** Incompatible FreeBSD kernel modules (`bwi-firmware-kmod`, `cpu-microcode`, `cpu-microcode-rc`), pfSense-specific base daemons (`pfSense-base`, `pfSense-kernel`, `pfSense-repoc`, `pfSense-upgrade`), and legacy FreeBSD scripts.
