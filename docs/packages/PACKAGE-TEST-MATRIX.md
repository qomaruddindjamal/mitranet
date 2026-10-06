# MITRANET — PACKAGE TEST MATRIX & VALIDATION ARCHITECTURE

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Test Layers & Methodologies

- **Layer 1: Package Integrity Test**: Validates SHA256 hashes and tar archive headers.
- **Layer 2: Dependency DAG Test**: Validates topological sorting without circular deadlocks.
- **Layer 3: Runtime Import/Load Test**: Checks shared object linkage (`dlopen`) and interpreter syntax.
- **Layer 4: Service Startup & Probe Test**: Asserts daemons start cleanly and answer health check probes.
- **Layer 5: Network Capability Test**: Verifies kernel capability bounds (VLAN, bridge, TUN, PF).
- **Layer 6: API Integration Test**: Verifies REST endpoints under `/api/REST/` handle service operations.
- **Layer 7: Web UI End-to-End Test**: Asserts PHP frontend consumes backend status cleanly.

## 2. Package-Specific Test Specifications (204 Packages)

| Package | Category | Test Type | Kernel Requirement | Health Check Probe | Test Status |
|---|---|---|---|---|---|
| `cpu-microcode` | BASE | Bootstrap Verification | Hardware / Bootloader | Process / Linkage Check | NOT TESTED |
| `cpu-microcode-amd` | BASE | Bootstrap Verification | Hardware / Bootloader | Process / Linkage Check | NOT TESTED |
| `cpu-microcode-intel` | BASE | Bootstrap Verification | Hardware / Bootloader | Process / Linkage Check | NOT TESTED |
| `cpu-microcode-rc` | BASE | Bootstrap Verification | Hardware / Bootloader | Process / Linkage Check | NOT TESTED |
| `mitranet-boot` | BASE | Bootstrap Verification | Hardware / Bootloader | Process / Linkage Check | NOT TESTED |
| `mitranet-kernel-debian` | BASE | Bootstrap Verification | Hardware / Bootloader | Process / Linkage Check | NOT TESTED |
| `pkg` | BASE | Bootstrap Verification | Hardware / Bootloader | Process / Linkage Check | NOT TESTED |
| `uclcmd` | BASE | Bootstrap Verification | Hardware / Bootloader | Process / Linkage Check | NOT TESTED |
| `bwi-firmware-kmod` | DRIVER/FIRMWARE | Bootstrap Verification | Kernel Driver Hooks | Process / Linkage Check | NOT TESTED |
| `php85` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-bcmath` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-bz2` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-ctype` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-curl` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-dom` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-filter` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-gettext` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-gmp` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-intl` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-ldap` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-mbstring` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-mitranet-module` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-openssl_x509_crl` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pcntl` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pdo` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pdo_sqlite` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear-Auth_RADIUS` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear-Cache_Lite` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear-Crypt_CHAP` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear-HTTP_Request2` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear-Mail` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear-Net_IPv6` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear-Net_URL2` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pear-XML_RPC2` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pecl-mcrypt` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pecl-radius` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-pecl-rrd` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-phpseclib` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-posix` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-readline` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-session` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-shmop` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-simplexml` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-sockets` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-sqlite3` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-sysvmsg` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-sysvsem` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-sysvshm` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-tokenizer` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-xml` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-xmlreader` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-xmlwriter` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `php85-zlib` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `python3` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `python312` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `tcl86` | RUNTIME | Runtime Load Test | Userspace Runtime | Process / Linkage Check | NOT TESTED |
| `boost-libs` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `ca_root_nss` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `curl` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `expat` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `gmp` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `icu` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `igmpproxy` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libICE` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libSM` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libXau` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libXdmcp` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libavif` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libccid` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libdeflate` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libedit` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libevent` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libffi` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libgcrypt` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libgpg-error` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libiconv` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libidn2` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libltdl` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `liblz4` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libmcrypt` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libnghttp2` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libpfctl` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libpsl` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libsodium` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libssh2` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libucl` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libunistring` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `liburcu` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libuv` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libxml2` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libxslt` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `libyuv` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `pkcs11-helper` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `protobuf` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `protobuf-c` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `sqlite3` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `zstd` | LIBRARY | Shared Library Link Test | Dynamic Linker | Process / Linkage Check | NOT TESTED |
| `abseil` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `beep` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `brotli` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `cpdup` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `cpustats` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `cyrus-sasl` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `dbus` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `dmidecode` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `dpinger` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `duktape-lib` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `easy-rsa` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `expiretable` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `fstrm` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `gettext-runtime` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `glib` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `glib-bootstrap` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `graphite2` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `hostapd` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `if_pppoe-kmod` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `iftop` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `indexinfo` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `ipmitool` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `jbigkit` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `jq` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `jsoncpp` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `kea` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `kvm` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `ldns` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `lerc` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `links` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `log4cplus` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `lua54` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `lzo2` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `minicron` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `mitranet-Status_Monitoring-php85` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `mitranet-composer-deps` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `mitranet-repoc` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `mobile-broadband-provider-info` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `mpdecimal` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `nettle` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `nss_ldap` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `oniguruma` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `openldap26-client` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `opensc` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `pam_ldap` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `pam_mkhomedir` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `pcre2` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `pcsc-lite` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `perl5` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `polkit` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `py312-packaging` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `readline` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `shared-mime-info` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `ssh_tunnel_shell` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `unzip` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `vstr` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `whois` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `wol` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `wrapalixresetbutton` | UTILITY | Command Execution Test | Userspace | Process / Linkage Check | NOT TESTED |
| `choparp` | NETWORK | Kernel Network Hook Test | Raw Sockets / Interface Hooks | Process / Linkage Check | NOT TESTED |
| `qstats` | NETWORK | Kernel Network Hook Test | Raw Sockets / Interface Hooks | Process / Linkage Check | NOT TESTED |
| `radvd` | NETWORK | Kernel Network Hook Test | Raw Sockets / Interface Hooks | Process / Linkage Check | NOT TESTED |
| `rate` | NETWORK | Kernel Network Hook Test | Raw Sockets / Interface Hooks | Process / Linkage Check | NOT TESTED |
| `scponly` | NETWORK | Kernel Network Hook Test | Raw Sockets / Interface Hooks | Process / Linkage Check | NOT TESTED |
| `wifi` | WIRELESS | Wireless Association Test | Wireless NIC Drivers | Process / Linkage Check | NOT TESTED |
| `wpa_supplicant` | WIRELESS | Wireless Association Test | Wireless NIC Drivers | Process / Linkage Check | NOT TESTED |
| `mpd5` | ROUTING | BGP/OSPF Routing Table Test | Routing Netlink / Sockets | Process / Linkage Check | NOT TESTED |
| `filterdns` | FIREWALL | Packet Filter State Test | Packet Filtering / Conntrack | Process / Linkage Check | NOT TESTED |
| `filterlog` | FIREWALL | Packet Filter State Test | Packet Filtering / Conntrack | Process / Linkage Check | NOT TESTED |
| `miniupnpd` | FIREWALL | Packet Filter State Test | Packet Filtering / Conntrack | Process / Linkage Check | NOT TESTED |
| `pfSense-base` | FIREWALL | Packet Filter State Test | Packet Filtering / Conntrack | Process / Linkage Check | NOT TESTED |
| `pftop` | FIREWALL | Packet Filter State Test | Packet Filtering / Conntrack | Process / Linkage Check | NOT TESTED |
| `dhcp6` | DHCP | DHCP Lease Test | Raw BPF / Sockets | Process / Linkage Check | NOT TESTED |
| `dhcpcd` | DHCP | DHCP Lease Test | Raw BPF / Sockets | Process / Linkage Check | NOT TESTED |
| `dhcpleases` | DHCP | DHCP Lease Test | Raw BPF / Sockets | Process / Linkage Check | NOT TESTED |
| `dhcpleases6` | DHCP | DHCP Lease Test | Raw BPF / Sockets | Process / Linkage Check | NOT TESTED |
| `isc-dhcp44-client` | DHCP | DHCP Lease Test | Raw BPF / Sockets | Process / Linkage Check | NOT TESTED |
| `isc-dhcp44-relay` | DHCP | DHCP Lease Test | Raw BPF / Sockets | Process / Linkage Check | NOT TESTED |
| `isc-dhcp44-server` | DHCP | DHCP Lease Test | Raw BPF / Sockets | Process / Linkage Check | NOT TESTED |
| `bind-tools` | DNS | DNS Resolution Test | Network Sockets | Process / Linkage Check | NOT TESTED |
| `dnsmasq` | DNS | DNS Resolution Test | Network Sockets | Process / Linkage Check | NOT TESTED |
| `unbound` | DNS | DNS Resolution Test | Network Sockets | Process / Linkage Check | NOT TESTED |
| `ntp` | SERVICES | Time Sync Test | Network Sockets | Process / Linkage Check | NOT TESTED |
| `mitranet-pkg-WireGuard` | VPN | VPN Tunnel Connectivity Test | TUN / TAP / WireGuard Driver | Process / Linkage Check | NOT TESTED |
| `openvpn` | VPN | VPN Tunnel Connectivity Test | TUN / TAP / WireGuard Driver | Process / Linkage Check | NOT TESTED |
| `openvpn-auth-script` | VPN | VPN Tunnel Connectivity Test | TUN / TAP / WireGuard Driver | Process / Linkage Check | NOT TESTED |
| `strongswan` | VPN | VPN Tunnel Connectivity Test | TUN / TAP / WireGuard Driver | Process / Linkage Check | NOT TESTED |
| `wireguard-mitranet` | VPN | VPN Tunnel Connectivity Test | TUN / TAP / WireGuard Driver | Process / Linkage Check | NOT TESTED |
| `xray-mitranet` | VPN | VPN Tunnel Connectivity Test | TUN / TAP / WireGuard Driver | Process / Linkage Check | NOT TESTED |
| `sshguard` | SECURITY | Security Alert Trigger Test | Packet Capture (pcap/netmap) | Process / Linkage Check | NOT TESTED |
| `voucher` | SECURITY | Security Alert Trigger Test | Packet Capture (pcap/netmap) | Process / Linkage Check | NOT TESTED |
| `bsnmp-regex` | MONITORING | Metrics Collection Test | Hardware & Interface Telemetry | Process / Linkage Check | NOT TESTED |
| `bsnmp-ucd` | MONITORING | Metrics Collection Test | Hardware & Interface Telemetry | Process / Linkage Check | NOT TESTED |
| `rrdtool` | MONITORING | Metrics Collection Test | Hardware & Interface Telemetry | Process / Linkage Check | NOT TESTED |
| `smartmontools` | MONITORING | Metrics Collection Test | Hardware & Interface Telemetry | Process / Linkage Check | NOT TESTED |
| `speedtest` | MONITORING | Metrics Collection Test | Hardware & Interface Telemetry | Process / Linkage Check | NOT TESTED |
| `mitranet` | BASE | Core Initialization Test | Full System Capabilities | Process / Linkage Check | NOT TESTED |
| `mitranet-base-1.0.0.partaa` | BASE | Core Initialization Test | Full System Capabilities | Process / Linkage Check | NOT TESTED |
| `mitranet-base-1.0.0.partab` | BASE | Core Initialization Test | Full System Capabilities | Process / Linkage Check | NOT TESTED |
| `mitranet-default-config` | BASE | Core Initialization Test | Full System Capabilities | Process / Linkage Check | NOT TESTED |
| `mitranet-gnid` | BASE | Core Initialization Test | Full System Capabilities | Process / Linkage Check | NOT TESTED |
| `mitranet-system` | BASE | Core Initialization Test | Full System Capabilities | Process / Linkage Check | NOT TESTED |
| `mitranet-upgrade` | BASE | Core Initialization Test | Full System Capabilities | Process / Linkage Check | NOT TESTED |
| `check_reload_status` | MANAGEMENT | Web Server HTTP Response Test | Network Sockets | Process / Linkage Check | NOT TESTED |
| `nginx` | MANAGEMENT | Web Server HTTP Response Test | Network Sockets | Process / Linkage Check | NOT TESTED |
| `xinetd` | MANAGEMENT | Web Server HTTP Response Test | Network Sockets | Process / Linkage Check | NOT TESTED |
