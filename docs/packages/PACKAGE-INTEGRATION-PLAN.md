# MITRANET — PACKAGE INTEGRATION PLAN (PHASE 2)

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Architectural Layers & Sequence

### Layer 0: base/system
_Critical base platform, kernel headers/drivers, default config, and pkg utility_

Total Packages: 19
Sample Packages: bwi-firmware-kmod, check_reload_status, cpu-microcode, cpu-microcode-amd, cpu-microcode-intel, cpu-microcode-rc, if_pppoe-kmod, mitranet

### Layer 1: runtime/libraries
_Core dynamic shared libraries, C runtime, Python 3.12, PHP 8.5_

Total Packages: 86
Sample Packages: boost-libs, curl, expat, gmp, icu, igmpproxy, libICE, libSM

### Layer 2: network foundation
_Interface management, VLAN, bridge, QoS schedulers_

Total Packages: 2
Sample Packages: qstats, rate

### Layer 3: routing/firewall
_Packet filter hooks, dynamic routing (FRR/BGP), state tracking_

Total Packages: 8
Sample Packages: choparp, filterdns, filterlog, libpfctl, miniupnpd, mpd5, pftop, radvd

### Layer 4: network services
_DHCP servers, Unbound/Dnsmasq resolvers, NTP, Nginx web server_

Total Packages: 12
Sample Packages: bind-tools, dhcp6, dhcpcd, dhcpleases, dhcpleases6, dnsmasq, isc-dhcp44-client, isc-dhcp44-relay

### Layer 5: VPN/security
_WireGuard, OpenVPN, StrongSwan, Xray, Suricata IPS, SSHGuard_

Total Packages: 9
Sample Packages: ca_root_nss, mitranet-pkg-WireGuard, openvpn, openvpn-auth-script, pkcs11-helper, sshguard, strongswan, wireguard-mitranet

### Layer 6: wireless
_Hostapd, WPA supplicant, wireless firmware_

Total Packages: 3
Sample Packages: hostapd, wifi, wpa_supplicant

### Layer 7: monitoring/management
_BSNMP, RRDTool, speedtest, smartmontools_

Total Packages: 65
Sample Packages: abseil, beep, brotli, bsnmp-regex, bsnmp-ucd, cpdup, cpustats, cyrus-sasl

### Layer 8: MitraNet integration
_MitraNet core daemons, check_reload_status, config synchronizers_

Total Packages: 0
Sample Packages: 

### Layer 9: Web/API integration
_FastAPI backend connectors, PHP Web UI orchestration modules_

Total Packages: 0
Sample Packages: 

## 2. Execution Constraints
- **Phase 2A is strictly Audit and Planning.**
- **No packages are installed or extracted during Phase 2A.**
- **All package binaries remain 100% immutable.**
