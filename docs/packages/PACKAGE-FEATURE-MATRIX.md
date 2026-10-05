# MITRANET — PACKAGE FEATURE MATRIX

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

| Package | Category | Role | Feature | Component | API Integration | Web UI Module | Runtime Req | Priority | Status |
|---|---|---|---|---|---|---|---|---|---|
| `abseil` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `beep` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `bind-tools` | SERVICES | daemon/service | DNS | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/system/` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `boost-libs` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `brotli` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `bsnmp-regex` | MONITORING | daemon/service | MONITORING | Telemetry & Performance Metrics | `api/REST/mitranet_platform.py` | `public_html/diagnostics/index.php` | Hardware & Interface Telemetry | P4 | NOT TESTED |
| `bsnmp-ucd` | MONITORING | daemon/service | MONITORING | Telemetry & Performance Metrics | `api/REST/mitranet_platform.py` | `public_html/diagnostics/index.php` | Hardware & Interface Telemetry | P4 | NOT TESTED |
| `bwi-firmware-kmod` | DRIVER/FIRMWARE | firmware | MANAGEMENT | Hardware Platform Support | `NO CURRENT API CONSUMER` | `hardware (platform/sensors)` | Kernel / Hardware | P0 | NOT TESTED |
| `ca_root_nss` | SECURITY | utility | SECURITY | Intrusion Prevention & Threat Mitigation | `api/REST/firewall_manager.py` | `public_html/firewall/blacklist.php` | Packet Capture & Sockets | P3 | NOT TESTED |
| `check_reload_status` | MANAGEMENT | daemon/service | MANAGEMENT | Package & Daemon Management | `api/REST/server.py` | `public_html/packages/index.php` | Userspace Service Control | P0 | NOT TESTED |
| `choparp` | FIREWALL | utility | FIREWALL | Packet Filtering & State Engine | `api/REST/firewall_manager.py` | `public_html/firewall/rules.php` | Packet Filtering / Kernel Hooks | P1 | NOT TESTED |
| `cpdup` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `cpu-microcode` | DRIVER/FIRMWARE | firmware | MANAGEMENT | Hardware Platform Support | `NO CURRENT API CONSUMER` | `hardware (platform/sensors)` | Kernel / Hardware | P0 | NOT TESTED |
| `cpu-microcode-amd` | DRIVER/FIRMWARE | firmware | MANAGEMENT | Hardware Platform Support | `NO CURRENT API CONSUMER` | `hardware (platform/sensors)` | Kernel / Hardware | P0 | NOT TESTED |
| `cpu-microcode-intel` | DRIVER/FIRMWARE | firmware | MANAGEMENT | Hardware Platform Support | `NO CURRENT API CONSUMER` | `hardware (platform/sensors)` | Kernel / Hardware | P0 | NOT TESTED |
| `cpu-microcode-rc` | DRIVER/FIRMWARE | firmware | MANAGEMENT | Hardware Platform Support | `NO CURRENT API CONSUMER` | `hardware (platform/sensors)` | Kernel / Hardware | P0 | NOT TESTED |
| `cpustats` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `curl` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `cyrus-sasl` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `dbus` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `dhcp6` | SERVICES | daemon/service | DHCP | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/diagnostics/dhcp_leases.php` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `dhcpcd` | SERVICES | daemon/service | DHCP | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/diagnostics/dhcp_leases.php` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `dhcpleases` | SERVICES | daemon/service | DHCP | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/diagnostics/dhcp_leases.php` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `dhcpleases6` | SERVICES | daemon/service | DHCP | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/diagnostics/dhcp_leases.php` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `dmidecode` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `dnsmasq` | SERVICES | daemon/service | DNS | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/system/` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `dpinger` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `duktape-lib` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `easy-rsa` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `expat` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `expiretable` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `filterdns` | FIREWALL | utility | FIREWALL | Packet Filtering & State Engine | `api/REST/firewall_manager.py` | `public_html/firewall/rules.php` | Packet Filtering / Kernel Hooks | P1 | NOT TESTED |
| `filterlog` | FIREWALL | utility | FIREWALL | Packet Filtering & State Engine | `api/REST/firewall_manager.py` | `public_html/firewall/rules.php` | Packet Filtering / Kernel Hooks | P1 | NOT TESTED |
| `fstrm` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `gettext-runtime` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `glib` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `glib-bootstrap` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `gmp` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `graphite2` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `hostapd` | WIRELESS | daemon/service | WIRELESS | 802.11 Wireless Subsystem | `NO CURRENT API CONSUMER` | `public_html/interfaces/index.php` | Wireless NIC / RF Subsystem | P3 | NOT TESTED |
| `icu` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `if_pppoe-kmod` | DRIVER/FIRMWARE | driver | MANAGEMENT | Hardware Platform Support | `NO CURRENT API CONSUMER` | `hardware (platform/sensors)` | Kernel / Hardware | P0 | NOT TESTED |
| `iftop` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `igmpproxy` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `indexinfo` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `ipmitool` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `isc-dhcp44-client` | SERVICES | daemon/service | DHCP | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/diagnostics/dhcp_leases.php` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `isc-dhcp44-relay` | SERVICES | daemon/service | DHCP | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/diagnostics/dhcp_leases.php` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `isc-dhcp44-server` | SERVICES | daemon/service | DHCP | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/diagnostics/dhcp_leases.php` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `jbigkit` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `jq` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `jsoncpp` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `kea` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `kvm` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `ldns` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `lerc` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `libICE` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libSM` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libXau` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libXdmcp` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libavif` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libccid` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libdeflate` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libedit` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libevent` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libffi` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libgcrypt` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libgpg-error` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libiconv` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libidn2` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libltdl` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `liblz4` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libmcrypt` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libnghttp2` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libpfctl` | FIREWALL | utility | FIREWALL | Packet Filtering & State Engine | `api/REST/firewall_manager.py` | `public_html/firewall/rules.php` | Packet Filtering / Kernel Hooks | P1 | NOT TESTED |
| `libpsl` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libsodium` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libssh2` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libucl` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libunistring` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `liburcu` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libuv` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libxml2` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libxslt` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `libyuv` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `links` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `log4cplus` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `lua54` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `lzo2` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `minicron` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `miniupnpd` | FIREWALL | daemon/service | FIREWALL | Packet Filtering & State Engine | `api/REST/firewall_manager.py` | `public_html/firewall/rules.php` | Packet Filtering / Kernel Hooks | P1 | NOT TESTED |
| `mitranet` | BASE | composite/meta package | MANAGEMENT | MitraNet Core OS & Foundation | `api/REST/mitranet_platform.py` | `system/users.php & dashboard` | Kernel & Userspace Base | P0 | NOT TESTED |
| `mitranet-Status_Monitoring-php85` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `pfSense-base` | BASE | configuration | MANAGEMENT | MitraNet Core OS & Foundation | `api/REST/mitranet_platform.py` | `system/users.php & dashboard` | Kernel & Userspace Base | P0 | NOT TESTED |
| `mitranet-base-1.0.0.partaa` | BASE | configuration | MANAGEMENT | MitraNet Core OS & Foundation | `api/REST/mitranet_platform.py` | `system/users.php & dashboard` | Kernel & Userspace Base | P0 | NOT TESTED |
| `mitranet-base-1.0.0.partab` | BASE | configuration | MANAGEMENT | MitraNet Core OS & Foundation | `api/REST/mitranet_platform.py` | `system/users.php & dashboard` | Kernel & Userspace Base | P0 | NOT TESTED |
| `mitranet-boot` | BASE | configuration | MANAGEMENT | MitraNet Core OS & Foundation | `api/REST/mitranet_platform.py` | `system/users.php & dashboard` | Kernel & Userspace Base | P0 | NOT TESTED |
| `mitranet-composer-deps` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `mitranet-default-config` | BASE | configuration | MANAGEMENT | MitraNet Core OS & Foundation | `api/REST/mitranet_platform.py` | `system/users.php & dashboard` | Kernel & Userspace Base | P0 | NOT TESTED |
| `mitranet-gnid` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `mitranet-kernel-debian` | BASE | configuration | MANAGEMENT | MitraNet Core OS & Foundation | `api/REST/mitranet_platform.py` | `system/users.php & dashboard` | Kernel & Userspace Base | P0 | NOT TESTED |
| `mitranet-pkg-WireGuard` | VPN | daemon/service | VPN | Encrypted Tunnel & Remote Access | `api/REST/enterprise_network_manager.py` | `public_html/vpn/index.php` | Tunnel Interfaces / Crypto | P3 | NOT TESTED |
| `mitranet-repoc` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `mitranet-system` | BASE | configuration | MANAGEMENT | MitraNet Core OS & Foundation | `api/REST/mitranet_platform.py` | `system/users.php & dashboard` | Kernel & Userspace Base | P0 | NOT TESTED |
| `mitranet-upgrade` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `mobile-broadband-provider-info` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `mpd5` | ROUTING | daemon/service | ROUTING | Dynamic Routing & Subscriber Access | `api/REST/enterprise_network_manager.py` | `public_html/routing/index.php` | Kernel Routing Table & Interfaces | P1 | NOT TESTED |
| `mpdecimal` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `nettle` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `nginx` | SERVICES | daemon/service | MANAGEMENT | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/system/` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `nss_ldap` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `ntp` | SERVICES | daemon/service | MANAGEMENT | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/system/` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `oniguruma` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `openldap26-client` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `opensc` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `openvpn` | VPN | daemon/service | VPN | Encrypted Tunnel & Remote Access | `api/REST/enterprise_network_manager.py` | `public_html/vpn/index.php` | Tunnel Interfaces / Crypto | P3 | NOT TESTED |
| `openvpn-auth-script` | VPN | daemon/service | VPN | Encrypted Tunnel & Remote Access | `api/REST/enterprise_network_manager.py` | `public_html/vpn/index.php` | Tunnel Interfaces / Crypto | P3 | NOT TESTED |
| `pam_ldap` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `pam_mkhomedir` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `pcre2` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `pcsc-lite` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `perl5` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `pftop` | FIREWALL | utility | FIREWALL | Packet Filtering & State Engine | `api/REST/firewall_manager.py` | `public_html/firewall/rules.php` | Packet Filtering / Kernel Hooks | P1 | NOT TESTED |
| `php85` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-bcmath` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-bz2` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-ctype` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-curl` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-dom` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-filter` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-gettext` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-gmp` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-intl` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-ldap` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-mbstring` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-mitranet-module` | RUNTIME | runtime dependency | MANAGEMENT | Web Engine / PHP Extension | `api/REST/server.py` | `public_html/includes/guiconfig.php` | PHP 8.5 Runtime | P0 | NOT TESTED |
| `php85-openssl_x509_crl` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pcntl` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pdo` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pdo_sqlite` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear-Auth_RADIUS` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear-Cache_Lite` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear-Crypt_CHAP` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear-HTTP_Request2` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear-Mail` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear-Net_IPv6` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear-Net_URL2` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pear-XML_RPC2` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pecl-mcrypt` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pecl-radius` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-pecl-rrd` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-phpseclib` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-posix` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-readline` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-session` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-shmop` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-simplexml` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-sockets` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-sqlite3` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-sysvmsg` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-sysvsem` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-sysvshm` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-tokenizer` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-xml` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-xmlreader` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-xmlwriter` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `php85-zlib` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `public_html/ (PHP Engine)` | Userspace Runtime | P1 | NOT TESTED |
| `pkcs11-helper` | SECURITY | utility | SECURITY | Intrusion Prevention & Threat Mitigation | `api/REST/firewall_manager.py` | `public_html/firewall/blacklist.php` | Packet Capture & Sockets | P3 | NOT TESTED |
| `pkg` | MANAGEMENT | utility | MANAGEMENT | Package & Daemon Management | `api/REST/server.py` | `public_html/packages/index.php` | Userspace Service Control | P0 | NOT TESTED |
| `polkit` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `protobuf` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `protobuf-c` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `py312-packaging` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `python3` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `api/REST/` | `NO CURRENT WEB CONSUMER` | Userspace Runtime | P1 | NOT TESTED |
| `python312` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `api/REST/` | `NO CURRENT WEB CONSUMER` | Userspace Runtime | P1 | NOT TESTED |
| `qstats` | QOS | utility | QOS | Traffic Shaping & Bandwidth Limiting | `api/REST/enterprise_network_manager.py` | `public_html/qos/index.php` | Kernel Queuing & Interfaces | P2 | NOT TESTED |
| `radvd` | FIREWALL | daemon/service | FIREWALL | Packet Filtering & State Engine | `api/REST/firewall_manager.py` | `public_html/firewall/rules.php` | Packet Filtering / Kernel Hooks | P1 | NOT TESTED |
| `rate` | QOS | utility | QOS | Traffic Shaping & Bandwidth Limiting | `api/REST/enterprise_network_manager.py` | `public_html/qos/index.php` | Kernel Queuing & Interfaces | P2 | NOT TESTED |
| `readline` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `rrdtool` | MONITORING | utility | MONITORING | Telemetry & Performance Metrics | `api/REST/mitranet_platform.py` | `public_html/diagnostics/index.php` | Hardware & Interface Telemetry | P4 | NOT TESTED |
| `scponly` | MANAGEMENT | daemon/service | MANAGEMENT | Package & Daemon Management | `api/REST/server.py` | `public_html/packages/index.php` | Userspace Service Control | P4 | NOT TESTED |
| `shared-mime-info` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `smartmontools` | MONITORING | utility | MONITORING | Telemetry & Performance Metrics | `api/REST/mitranet_platform.py` | `public_html/diagnostics/index.php` | Hardware & Interface Telemetry | P4 | NOT TESTED |
| `speedtest` | MONITORING | utility | MONITORING | Telemetry & Performance Metrics | `api/REST/mitranet_platform.py` | `public_html/diagnostics/index.php` | Hardware & Interface Telemetry | P4 | NOT TESTED |
| `sqlite3` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
| `ssh_tunnel_shell` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `sshguard` | SECURITY | daemon/service | SECURITY | Intrusion Prevention & Threat Mitigation | `api/REST/firewall_manager.py` | `public_html/firewall/blacklist.php` | Packet Capture & Sockets | P3 | NOT TESTED |
| `strongswan` | VPN | daemon/service | VPN | Encrypted Tunnel & Remote Access | `api/REST/enterprise_network_manager.py` | `public_html/vpn/index.php` | Tunnel Interfaces / Crypto | P3 | NOT TESTED |
| `tcl86` | RUNTIME | runtime dependency | MANAGEMENT | Scripting & Execution Engine | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace Runtime | P1 | NOT TESTED |
| `uclcmd` | MANAGEMENT | utility | MANAGEMENT | Package & Daemon Management | `api/REST/server.py` | `public_html/packages/index.php` | Userspace Service Control | P4 | NOT TESTED |
| `unbound` | SERVICES | daemon/service | DNS | Network Infrastructure Daemon | `api/REST/enterprise_network_manager.py` | `public_html/system/` | Network Sockets / Interfaces | P2 | NOT TESTED |
| `unzip` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `voucher` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `vstr` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `whois` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `wifi` | WIRELESS | utility | WIRELESS | 802.11 Wireless Subsystem | `NO CURRENT API CONSUMER` | `public_html/interfaces/index.php` | Wireless NIC / RF Subsystem | P3 | NOT TESTED |
| `wireguard-mitranet` | VPN | daemon/service | VPN | Encrypted Tunnel & Remote Access | `api/REST/enterprise_network_manager.py` | `public_html/vpn/index.php` | Tunnel Interfaces / Crypto | P3 | NOT TESTED |
| `wol` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `wpa_supplicant` | WIRELESS | daemon/service | WIRELESS | 802.11 Wireless Subsystem | `NO CURRENT API CONSUMER` | `public_html/interfaces/index.php` | Wireless NIC / RF Subsystem | P3 | NOT TESTED |
| `wrapalixresetbutton` | UTILITY | utility | MANAGEMENT | System Utility | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Userspace | P5 | NOT TESTED |
| `xinetd` | MANAGEMENT | daemon/service | MANAGEMENT | Package & Daemon Management | `api/REST/server.py` | `public_html/packages/index.php` | Userspace Service Control | P4 | NOT TESTED |
| `xray-mitranet` | VPN | daemon/service | VPN | Encrypted Tunnel & Remote Access | `api/REST/enterprise_network_manager.py` | `public_html/vpn/index.php` | Tunnel Interfaces / Crypto | P3 | NOT TESTED |
| `zstd` | LIBRARY | library | MANAGEMENT | System Shared Library | `NO CURRENT API CONSUMER` | `NO CURRENT WEB CONSUMER` | Dynamic Linker | P1 | NOT TESTED |
