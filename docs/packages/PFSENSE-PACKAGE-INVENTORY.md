# pfSense Package Inventory & Technical Analysis

## Overview

This inventory contains all packages discovered from the pfSense reference offline installer (pfsense-offline-installer.iso), including system meta-packages, kernel packages, pfSense-specific extensions, and custom offline packages. In accordance with MitraNet Phase 2A non-negotiable guidelines, pfSense / FreeBSD is strictly an architectural and reference baseline, not a runtime dependency.

| Package ID | Source | Version | Architecture | Primary Function | Implementation Strategy | Status |
|---|---|---|---|---|---|---|
| kvm | local_installed, custom_offline_pkg | 1.1.0 | FreeBSD:14:amd64 | KVM / Bhyve Hypervisor & aaPanel Default Virtual Machine for pfSense | REPLACE | **DECIDED** |
| pfSense | local_installed, packagesite_offline_repo | 2.9.0 | freebsd:16:x86:64 | Main pfSense package | EXCLUDE | **DECIDED** |
| pfSense-Status_Monitoring-php85 | local_installed, packagesite_offline_repo | 1.9 | freebsd:16:x86:64 | pfSense Status Monitoring | EXCLUDE | **DECIDED** |
| pfSense-base | local_installed, packagesite_offline_repo | 2.9.0 | freebsd:16:x86:64 | pfSense core files | EXCLUDE | **DECIDED** |
| pfSense-boot | local_installed, packagesite_offline_repo | 2.9.0 | freebsd:16:x86:64 | pfSense boot files | EXCLUDE | **DECIDED** |
| pfSense-composer-deps | local_installed, packagesite_offline_repo | 0.5_1 | freebsd:16:x86:64 | pfSense deps from composer | EXCLUDE | **DECIDED** |
| pfSense-default-config | local_installed, packagesite_offline_repo | 2.9.0 | freebsd:16:x86:64 | Default config.xml | REIMPLEMENT | **DECIDED** |
| pfSense-gnid | local_installed, packagesite_offline_repo | 0.21 | freebsd:16:x86:64 | GNID tool. | EXCLUDE | **DECIDED** |
| pfSense-installer | local_installed, upstream_repo_db | 20240916 | FreeBSD:15:amd64 | pfSense dynamic repository client | EXCLUDE | **DECIDED** |
| pfSense-kernel-pfSense | local_installed, packagesite_offline_repo | 2.9.0 | freebsd:16:x86:64 | pfSense kernel (pfSense) | EXCLUDE | **DECIDED** |
| pfSense-pkg-WireGuard | local_installed, packagesite_offline_repo | 0.2.13_4 | freebsd:16:x86:64 | pfSense package WireGuard | REPLACE | **DECIDED** |
| pfSense-repoc | local_installed, packagesite_offline_repo, upstream_repo_db | 20260919.051732 | freebsd:16:x86:64 | pfSense dynamic repository client | EXCLUDE | **DECIDED** |
| pfSense-system | local_installed, packagesite_offline_repo | 2.9.0 | freebsd:16:x86:64 | pfSense system package | EXCLUDE | **DECIDED** |
| pfSense-upgrade | local_installed, packagesite_offline_repo | 1.3.40 | freebsd:16:x86:64 | pfSense upgrade script | EXCLUDE | **DECIDED** |
| php85-pfSense-module | local_installed, packagesite_offline_repo | 2.9.0 | freebsd:16:x86:64 | Library for getting useful info | EXCLUDE | **DECIDED** |
| speedtest | custom_offline_pkg | 1.0.0 | FreeBSD:14:amd64 | Internet Bandwidth & Latency Speedtest Tool (Ookla Native & GitHub CLI) for pfSense | DIRECT-DEBIAN | **DECIDED** |
| wifi | custom_offline_pkg | 1.0.0 | FreeBSD:14:amd64 | Wireless Network & AP Manager with Native Hardware Auto-detection for pfSense | REPLACE | **DECIDED** |
| wireguard-pfsense | local_installed, custom_offline_pkg | 1.0.0 | FreeBSD:14:amd64 | WireGuard VPN service and manager for pfSense / FreeBSD | REPLACE | **DECIDED** |
| xray-pfsense | local_installed, custom_offline_pkg | 1.8.24 | FreeBSD:14:amd64 | Xray-core multi-protocol proxy (VLESS, VMess, Trojan, Socks, TUN, Routing) for pfSense | DIRECT-DEBIAN | **DECIDED** |


## Detailed Technical Records

### kvm

- **PACKAGE-ID:** kvm
- **SOURCE:** ['local_installed', 'custom_offline_pkg']
- **NAME:** kvm
- **VERSION:** 1.1.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** FreeBSD:14:amd64
- **FUNCTION:** KVM / Bhyve Hypervisor & aaPanel Default Virtual Machine for pfSense
- **DEPENDENCIES:** []
- **CATEGORY:** REPLACE-WITH-LINUX-NATIVE
- **IMPLEMENTATION STRATEGY:** REPLACE
- **LINUX/DEBIAN EQUIVALENT:** qemu-system-x86 + libvirt + KVM (kernel)
- **TECHNICAL JUSTIFICATION:** FreeBSD bhyve virtualization wrapper replaced by native Linux KVM and QEMU.
- **STATUS:** DECIDED

### pfSense

- **PACKAGE-ID:** pfSense
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense
- **VERSION:** 2.9.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** Main pfSense package
- **DEPENDENCIES:** ['pfSense-system']
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-Status_Monitoring-php85

- **PACKAGE-ID:** pfSense-Status_Monitoring-php85
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-Status_Monitoring-php85
- **VERSION:** 1.9
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense Status Monitoring
- **DEPENDENCIES:** ['php85', 'php85-pecl-rrd']
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-base

- **PACKAGE-ID:** pfSense-base
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-base
- **VERSION:** 2.9.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense core files
- **DEPENDENCIES:** []
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-boot

- **PACKAGE-ID:** pfSense-boot
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-boot
- **VERSION:** 2.9.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense boot files
- **DEPENDENCIES:** []
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-composer-deps

- **PACKAGE-ID:** pfSense-composer-deps
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-composer-deps
- **VERSION:** 0.5_1
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense deps from composer
- **DEPENDENCIES:** []
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-default-config

- **PACKAGE-ID:** pfSense-default-config
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-default-config
- **VERSION:** 2.9.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** Default config.xml
- **DEPENDENCIES:** []
- **CATEGORY:** REIMPLEMENT-NATIVELY
- **IMPLEMENTATION STRATEGY:** REIMPLEMENT
- **LINUX/DEBIAN EQUIVALENT:** MitraNet Native Schema & Config Engine (/etc/mitranet/config.json)
- **TECHNICAL JUSTIFICATION:** pfSense XML configuration is replaced by native MitraNet validated schema.
- **STATUS:** DECIDED

### pfSense-gnid

- **PACKAGE-ID:** pfSense-gnid
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-gnid
- **VERSION:** 0.21
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** GNID tool.
- **DEPENDENCIES:** []
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-installer

- **PACKAGE-ID:** pfSense-installer
- **SOURCE:** ['local_installed', 'upstream_repo_db']
- **NAME:** pfSense-installer
- **VERSION:** 20240916
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** FreeBSD:15:amd64
- **FUNCTION:** pfSense dynamic repository client
- **DEPENDENCIES:** []
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-kernel-pfSense

- **PACKAGE-ID:** pfSense-kernel-pfSense
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-kernel-pfSense
- **VERSION:** 2.9.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense kernel (pfSense)
- **DEPENDENCIES:** ['pfSense-boot']
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-pkg-WireGuard

- **PACKAGE-ID:** pfSense-pkg-WireGuard
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-pkg-WireGuard
- **VERSION:** 0.2.13_4
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense package WireGuard
- **DEPENDENCIES:** []
- **CATEGORY:** REPLACE-WITH-LINUX-NATIVE
- **IMPLEMENTATION STRATEGY:** REPLACE
- **LINUX/DEBIAN EQUIVALENT:** wireguard-tools + Linux in-tree WireGuard kernel module
- **TECHNICAL JUSTIFICATION:** FreeBSD WireGuard kmod and pfSense PHP GUI replaced by Linux kernel WireGuard.
- **STATUS:** DECIDED

### pfSense-repoc

- **PACKAGE-ID:** pfSense-repoc
- **SOURCE:** ['local_installed', 'packagesite_offline_repo', 'upstream_repo_db']
- **NAME:** pfSense-repoc
- **VERSION:** 20260919.051732
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense dynamic repository client
- **DEPENDENCIES:** ['libucl']
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-system

- **PACKAGE-ID:** pfSense-system
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-system
- **VERSION:** 2.9.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense system package
- **DEPENDENCIES:** ['beep', 'bind-tools', 'bsnmp-regex', 'bsnmp-ucd', 'bwi-firmware-kmod', 'ca_root_nss', 'check_reload_status', 'choparp', 'cpdup', 'cpu-microcode', 'cpustats', 'dhcp6', 'dhcpcd', 'dhcpleases', 'dhcpleases6', 'dmidecode', 'dnsmasq', 'dpinger', 'expiretable', 'filterdns', 'filterlog', 'hostapd', 'if_pppoe-kmod', 'iftop', 'igmpproxy', 'ipmitool', 'isc-dhcp44-client', 'isc-dhcp44-relay', 'isc-dhcp44-server', 'jq', 'kea', 'libccid', 'libltdl', 'libxml2', 'links', 'minicron', 'miniupnpd', 'mobile-broadband-provider-info', 'mpd5', 'nginx', 'nss_ldap', 'ntp', 'opensc', 'openvpn', 'openvpn-auth-script', 'pam_ldap', 'pam_mkhomedir', 'pfSense-Status_Monitoring-php85', 'pfSense-composer-deps', 'pfSense-gnid', 'pfSense-upgrade', 'pftop', 'php85', 'php85-bcmath', 'php85-bz2', 'php85-ctype', 'php85-curl', 'php85-dom', 'php85-filter', 'php85-gettext', 'php85-gmp', 'php85-intl', 'php85-ldap', 'php85-mbstring', 'php85-openssl_x509_crl', 'php85-pcntl', 'php85-pdo', 'php85-pdo_sqlite', 'php85-pear-Auth_RADIUS', 'php85-pear-Crypt_CHAP', 'php85-pear-Mail', 'php85-pear-Net_IPv6', 'php85-pear-XML_RPC2', 'php85-pecl-mcrypt', 'php85-pecl-radius', 'php85-pfSense-module', 'php85-phpseclib', 'php85-posix', 'php85-readline', 'php85-session', 'php85-shmop', 'php85-simplexml', 'php85-sockets', 'php85-sqlite3', 'php85-sysvmsg', 'php85-sysvsem', 'php85-sysvshm', 'php85-tokenizer', 'php85-xml', 'php85-xmlreader', 'php85-xmlwriter', 'php85-zlib', 'qstats', 'radvd', 'rate', 'scponly', 'smartmontools', 'ssh_tunnel_shell', 'sshguard', 'strongswan', 'uclcmd', 'unbound', 'voucher', 'whois', 'wol', 'wpa_supplicant', 'wrapalixresetbutton', 'xinetd']
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### pfSense-upgrade

- **PACKAGE-ID:** pfSense-upgrade
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** pfSense-upgrade
- **VERSION:** 1.3.40
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** pfSense upgrade script
- **DEPENDENCIES:** ['pfSense-repoc']
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### php85-pfSense-module

- **PACKAGE-ID:** php85-pfSense-module
- **SOURCE:** ['local_installed', 'packagesite_offline_repo']
- **NAME:** php85-pfSense-module
- **VERSION:** 2.9.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** freebsd:16:x86:64
- **FUNCTION:** Library for getting useful info
- **DEPENDENCIES:** ['libpfctl', 'php85', 'strongswan']
- **CATEGORY:** INCOMPATIBLE / UNSUITABLE
- **IMPLEMENTATION STRATEGY:** EXCLUDE
- **LINUX/DEBIAN EQUIVALENT:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **TECHNICAL JUSTIFICATION:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.
- **STATUS:** DECIDED

### speedtest

- **PACKAGE-ID:** speedtest
- **SOURCE:** ['custom_offline_pkg']
- **NAME:** speedtest
- **VERSION:** 1.0.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** FreeBSD:14:amd64
- **FUNCTION:** Internet Bandwidth & Latency Speedtest Tool (Ookla Native & GitHub CLI) for pfSense
- **DEPENDENCIES:** []
- **CATEGORY:** DIRECT-DEBIAN-EQUIVALENT
- **IMPLEMENTATION STRATEGY:** DIRECT-DEBIAN
- **LINUX/DEBIAN EQUIVALENT:** Debian package: speedtest
- **TECHNICAL JUSTIFICATION:** Directly provided by Debian GNU/Linux 13 Trixie repositories.
- **STATUS:** DECIDED

### wifi

- **PACKAGE-ID:** wifi
- **SOURCE:** ['custom_offline_pkg']
- **NAME:** wifi
- **VERSION:** 1.0.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** FreeBSD:14:amd64
- **FUNCTION:** Wireless Network & AP Manager with Native Hardware Auto-detection for pfSense
- **DEPENDENCIES:** []
- **CATEGORY:** REPLACE-WITH-LINUX-NATIVE
- **IMPLEMENTATION STRATEGY:** REPLACE
- **LINUX/DEBIAN EQUIVALENT:** hostapd + wpasupplicant + iw + wireless-regdb
- **TECHNICAL JUSTIFICATION:** FreeBSD ifconfig wlan replaced by modern Linux mac80211 / nl80211 stack (iw, hostapd).
- **STATUS:** DECIDED

### wireguard-pfsense

- **PACKAGE-ID:** wireguard-pfsense
- **SOURCE:** ['local_installed', 'custom_offline_pkg']
- **NAME:** wireguard-pfsense
- **VERSION:** 1.0.0
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** FreeBSD:14:amd64
- **FUNCTION:** WireGuard VPN service and manager for pfSense / FreeBSD
- **DEPENDENCIES:** []
- **CATEGORY:** REPLACE-WITH-LINUX-NATIVE
- **IMPLEMENTATION STRATEGY:** REPLACE
- **LINUX/DEBIAN EQUIVALENT:** wireguard-tools + Linux in-tree WireGuard kernel module
- **TECHNICAL JUSTIFICATION:** FreeBSD WireGuard kmod and pfSense PHP GUI replaced by Linux kernel WireGuard.
- **STATUS:** DECIDED

### xray-pfsense

- **PACKAGE-ID:** xray-pfsense
- **SOURCE:** ['local_installed', 'custom_offline_pkg']
- **NAME:** xray-pfsense
- **VERSION:** 1.8.24
- **TYPE:** pfSense Extension / Core Package
- **ARCHITECTURE:** FreeBSD:14:amd64
- **FUNCTION:** Xray-core multi-protocol proxy (VLESS, VMess, Trojan, Socks, TUN, Routing) for pfSense
- **DEPENDENCIES:** []
- **CATEGORY:** DIRECT-DEBIAN-EQUIVALENT
- **IMPLEMENTATION STRATEGY:** DIRECT-DEBIAN
- **LINUX/DEBIAN EQUIVALENT:** Debian package: xray-pfsense
- **TECHNICAL JUSTIFICATION:** Directly provided by Debian GNU/Linux 13 Trixie repositories.
- **STATUS:** DECIDED
