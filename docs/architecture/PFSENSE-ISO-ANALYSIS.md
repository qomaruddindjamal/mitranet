# pfSense ISO Filesystem & Architecture Analysis

## 1. Metadata & ISO Verification
- **Target ISO**: `pfsense-offline-installer.iso`
- **Location**: `C:\mitranet\pfsense-offline-installer.iso`
- **File Size**: 1,188,745,216 bytes (~1.11 GiB)
- **SHA-256 Hash**: `16EBD1682C7F18D0B40300EC486611979F0D0C090300EFE2BFFD7C209A624291`
- **Debian Netinst Companion ISO**: `debian-13.7.0-amd64-netinst.iso`
- **Debian SHA-256 Hash**: `A7EF94AC2FB9A7FEC454552ABD629B7CC9D5155C886165A45649F5CE6167E355`
- **Verified Status**: Both ISOs intact, verified read-only.

## 2. Bootloader & Kernel Architecture
- **Bootloader**: FreeBSD loader with Lua scripting engine (`boot/lua/brand-pfSense.lua`, `menu.lua`, `config.lua`). Supports BIOS and UEFI boot modes.
- **Kernel**: FreeBSD GENERIC customized kernel package `pfSense-kernel-pfSense-2.9.0.pkg` (26,663,713 bytes).
- **Core OS Layer**: FreeBSD 14-RELEASE / 15-CURRENT foundation.

## 3. Installer Architecture
pfSense offline installer features a multi-modal installer subsystem:
- **CLI / Console Installer**: `usr/local/libexec/installer/pfSense-installer.sh` calling `bsdinstall` subroutines.
- **Web Installer**: `usr/local/www/web-installer/` providing an embedded micro-web server daemon (`pfSense-installerd.sh`) enabling graphical setup out-of-the-box over HTTP/HTTPS.
- **Disk Partitioning**: `pfSense-disk-part` automating ZFS root (mirror, stripe, raidz) and UFS2 partitioning.
- **Config Initialization**: `pfSense-copy-configxml` and `pfSense-default-config-2.9.0.pkg` extracting factory template into `/conf.default/config.xml` (internal version schema `24.5`).

## 4. Package Subsystem & Off-line Repository
The ISO includes an offline pkg repo under `/packages/All` containing **219 packages** (525 MB total).
Key core packages inspected:
- **Core Engine**: `pfSense-2.9.0`, `pfSense-base-2.9.0`, `pfSense-system-2.9.0`
- **Language / Runtime**: PHP 8.5.10 (`php85`, `php85-sockets`, `php85-pcntl`, `php85-filter`, `php85-curl`, `php85-simplexml`, `php85-mbstring`), Python 3.11 & 3.12, Perl 5.42.
- **Web Server**: `nginx-1.30.5` + PHP-FPM for the WebGUI.
- **DNS**: `unbound-1.26.1` (DNS resolver), `dnsmasq` capability.
- **VPN**: `strongswan-6.1.0_1` (IPsec/IKEv2), `openvpn-2.7.7`, `pfSense-pkg-WireGuard-0.2.13_4` / `wireguard-pfsense`.
- **Tunnel / Routing Utilities**: `mpd5-5.9_19` (PPPoE/PPTP/L2TP), `radvd-2.20` (IPv6 Router Advertisements).
- **Security / Filtering**: `sshguard-2.5.1_2`, `voucher-0.1_3` (captive portal token generator).
- **Hardware & Telemetry**: `smartmontools-7.5_2`, `rrdtool-1.9.0_1`, `wrapalixresetbutton`.

## 5. Startup & Configuration Lifecycle
- **Initialization Script**: `/etc/rc.bootup` executed at boot.
- **Dynamic Event Scripts**: `/etc/rc.newwanip`, `/etc/rc.linkup`, `/etc/rc.filter_configure`, `/etc/rc.carpmaster`, `/etc/rc.carpbackup`.
- **Console Menu**: `/etc/rc.initial` offering interactive CLI (assign interfaces, configure IP, reset passwords, factory reset, reboot, shell).
- **Include Libraries (`/etc/inc/`)**:
  - `config.inc` / `config.lib.inc`: Configuration loader, parser, and XML persistence.
  - `filter.inc`: Rules compiler translating config XML into `pf` syntax (`/tmp/rules.debug`).
  - `interfaces.inc`: Dynamic network adapter configuration, VLANs, bridges, LAGG/LACP, PPPoE.
  - `gwlb.inc`: Multi-WAN gateway monitoring (`dpinger`), failover tiers, load balancing groups.
  - `services.inc`: Daemon lifecycle management (Unbound, Kea/DHCPd, NTP, SNMP).
  - `captiveportal.inc`: Captive portal web authentication and state manipulation.
  - `certs.inc`: OpenSSL-based X.509 Certificate Authority, Server, and User certificate generation.
  - `xmlrpc_client.inc`: High Availability configuration synchronization across CARP cluster nodes.
