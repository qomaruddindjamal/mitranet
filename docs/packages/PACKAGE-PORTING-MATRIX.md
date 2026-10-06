# Package Porting & Migration Decision Matrix

## Overview

This matrix documents all 252 discovered packages and components, detailing whether FreeBSD-specific code is present, what Linux equivalent exists, the implementation strategy, whether building a native package is required, runtime verification strategy, and security audit requirements.

| Package | Function | Source | FreeBSD-Specific | Linux Equivalent | Strategy | Build Required | Runtime Test | Security Audit | Status |
|---|---|---|---|---|---|---|---|---|---|
| abseil | Abseil Common Libraries (C++) | local_installed | NO | Debian package: abseil | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| aom | AV1 reference encoder/decoder | local_installed | NO | Debian package: aom | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| autoconf | Generate configure scripts and rela | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| autoconf-switch | Wrapper script to switch between au | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| automake | GNU Standards-compliant Makefile ge | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| beep | Beeps a certain duration and pitch  | local_installed | NO | Debian package: beep | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| bind-tools | Command line tools from BIND: delv, | local_installed | NO | Debian package: bind-tools | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| boost-libs | Free portable C++ libraries (withou | local_installed | NO | Debian package: boost-libs | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| brotli | Generic-purpose lossless compressio | local_installed | NO | Debian package: brotli | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| bsnmp-regex | bsnmpd module allowing creation of  | local_installed | NO | Debian package: bsnmp-regex | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| bsnmp-ucd | bsnmpd module that implements parts | local_installed | NO | Debian package: bsnmp-ucd | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| bwi-firmware-kmod | Broadcom AirForce IEEE 802.11 Firmw | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| ca_root_nss | Root certificate bundle from the Mo | local_installed | NO | Debian package: ca_root_nss | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| check_reload_status | run various pfSense scripts on even | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| choparp | Simple proxy arp daemon | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| cmake-core | Cross-platform Makefile generator | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| cpdup | Comprehensive filesystem mirroring  | local_installed | NO | rsync / cp --archive | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| cpu-microcode | Meta-package for CPU microcode upda | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| cpu-microcode-amd | AMD CPU microcode updates | local_installed | NO | Debian package: cpu-microcode-amd | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| cpu-microcode-intel | Intel CPU microcode updates | local_installed | NO | Debian package: cpu-microcode-intel | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| cpu-microcode-rc | RC script for CPU microcode updates | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| cpustats | cpustats | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| curl | Command line tool and library for t | local_installed | NO | Debian package: curl | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| cyrus-sasl | RFC 2222 SASL (Simple Authenticatio | local_installed | NO | Debian package: cyrus-sasl | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| dav1d | Small and fast AV1 decoder | local_installed | NO | Debian package: dav1d | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| dbus | Message bus system for inter-applic | local_installed | NO | Debian package: dbus | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| dhcp6 | KAME DHCP6 client, server, and rela | local_installed | NO | Debian package: dhcp6 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| dhcpcd | DHCP/IPv4LL/IPv6RS/DHCPv6 client | local_installed | NO | Debian package: dhcpcd | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| dhcpleases | read dhpcd.lease file and add it to | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| dhcpleases6 | read dhpcd6.leases file and trigger | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| dmidecode | Tool for dumping DMI (SMBIOS) conte | local_installed | NO | Debian package: dmidecode | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| dnsmasq | Lightweight DNS forwarder, DHCP, an | local_installed | NO | Debian package: dnsmasq | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| dpinger | IP device monitoring tool | local_installed | YES | fping / iputils-ping / systemd-networkd  | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| duktape-lib | Embeddable Javascript engine (share | local_installed | NO | Debian package: duktape-lib | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| easy-rsa | Small RSA key management package ba | local_installed | NO | Debian package: easy-rsa | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| expat | XML 1.0 parser written in C | local_installed | NO | Debian package: expat | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| expiretable | Utility to remove entries from the  | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| fcgi-devkit | FastCGI Development Kit | local_installed | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| filterdns | filterdns | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| filterlog | filterlog | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| fontconfig | XML-based font configuration API fo | local_installed | NO | Debian package: fontconfig | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| freetype2 | Free and portable TrueType font ren | local_installed | NO | Debian package: freetype2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| fstrm | Implementation of the Frame Streams | local_installed | NO | Debian package: fstrm | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| gdk-pixbuf2 | Graphic library for GTK | local_installed | NO | Debian package: gdk-pixbuf2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| gettext-runtime | GNU gettext runtime libraries and p | local_installed | NO | Debian package: gettext-runtime | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| gettext-tools | GNU gettext development and transla | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| giflib | Tools and library routines for work | local_installed | NO | Debian package: giflib | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| glib | Some useful routines of C programmi | local_installed | NO | Debian package: glib | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| glib-bootstrap | Some useful routines of C programmi | local_installed | NO | Debian package: glib-bootstrap | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| gmake | GNU version of 'make' utility | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| gmp | Free library for arbitrary precisio | local_installed | NO | Debian package: gmp | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| graphite2 | Rendering capabilities for complex  | local_installed | NO | Debian package: graphite2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| help2man | Automatically generating simple man | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| hostapd | IEEE 802.11 AP, IEEE 802.1X/WPA/WPA | local_installed | NO | Debian package: hostapd | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| icu | International Components for Unicod | local_installed | NO | Debian package: icu | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| if_pppoe-kmod | PPPoE Kernel Driver | local_installed | YES | Debian package: if_pppoe-kmod | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| iftop | Display bandwidth usage on an inter | local_installed | NO | Debian package: iftop | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| igmpproxy | Multicast forwarding IGMP proxy | local_installed | NO | Debian package: igmpproxy | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| indexinfo | Utility to regenerate the GNU info  | local_installed | NO | dpkg / apt / libjson-c | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| ipmitool | CLI to manage IPMI systems | local_installed | NO | Debian package: ipmitool | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| isc-dhcp44-client | The ISC Dynamic Host Configuration  | local_installed | NO | Debian package: isc-dhcp44-client | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| isc-dhcp44-relay | The ISC Dynamic Host Configuration  | local_installed | NO | Debian package: isc-dhcp44-relay | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| isc-dhcp44-server | ISC Dynamic Host Configuration Prot | local_installed | NO | Debian package: isc-dhcp44-server | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| jbigkit | Lossless compression for bi-level i | local_installed | NO | Debian package: jbigkit | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| jpeg-turbo | SIMD-accelerated JPEG codec which r | local_installed | NO | Debian package: jpeg-turbo | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| jq | Lightweight and flexible command-li | local_installed | NO | Debian package: jq | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| jsoncpp | JSON reader and writer library for  | local_installed | NO | Debian package: jsoncpp | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| kea | Alternative DHCP implementation by  | local_installed | NO | Debian package: kea | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| kvm | KVM / Bhyve Hypervisor & aaPanel De | local_installed | NO | qemu-system-x86 + libvirt + KVM (kernel) | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| ldns | Library for programs conforming to  | local_installed | NO | Debian package: ldns | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| lerc | C++ library for Limited Error Raste | local_installed | NO | Debian package: lerc | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libICE | Inter Client Exchange library for X | local_installed | NO | Debian package: libICE | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libSM | Session Management library for X11 | local_installed | NO | Debian package: libSM | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libX11 | Core X11 protocol client library | local_installed | NO | Debian package: libX11 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libXau | Authentication Protocol library for | local_installed | NO | Debian package: libXau | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libXdmcp | X Display Manager Control Protocol  | local_installed | NO | Debian package: libXdmcp | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libavif | Library for encoding and decoding . | local_installed | NO | Debian package: libavif | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libccid | Generic USB CCID and ICCD driver | local_installed | NO | Debian package: libccid | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libdeflate | Fast, whole-buffer DEFLATE-based co | local_installed | NO | Debian package: libdeflate | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libedit | Command line editor library | local_installed | NO | Debian package: libedit | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libevent | API for executing callback function | local_installed | NO | Debian package: libevent | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libffi | Foreign Function Interface | local_installed | NO | Debian package: libffi | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libfontenc | The fontenc Library | local_installed | NO | Debian package: libfontenc | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libgcrypt | General purpose cryptographic libra | local_installed | NO | Debian package: libgcrypt | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libgpg-error | Common error values for all GnuPG c | local_installed | NO | Debian package: libgpg-error | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libiconv | Character set conversion library | local_installed | NO | Debian package: libiconv | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libidn2 | Implementation of IDNA2008 internat | local_installed | NO | Debian package: libidn2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libltdl | System independent dlopen wrapper | local_installed | NO | Debian package: libltdl | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| liblz4 | LZ4 compression library, lossless a | local_installed | NO | Debian package: liblz4 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libmcrypt | Multi-cipher cryptographic library  | local_installed | NO | Debian package: libmcrypt | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libnghttp2 | HTTP/2 C Library | local_installed | NO | Debian package: libnghttp2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libpfctl | Library for interaction with pf(4) | local_installed | YES | nftables (nft monitor / nft list ruleset | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| libpsl | C library to handle the Public Suff | local_installed | NO | Debian package: libpsl | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libsodium | Library to build higher-level crypt | local_installed | NO | Debian package: libsodium | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libssh2 | Library implementing the SSH2 proto | local_installed | NO | Debian package: libssh2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libtextstyle | Text styling library | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| libtool | Generic shared library support scri | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| libucl | Universal configuration library par | local_installed | NO | dpkg / apt / libjson-c | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| libunistring | Unicode string library | local_installed | NO | Debian package: libunistring | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| liburcu | Userspace read-copy-update (RCU) da | local_installed | NO | Debian package: liburcu | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libuv | Multi-platform support library with | local_installed | NO | Debian package: libuv | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libxcb | The X protocol C-language Binding ( | local_installed | NO | Debian package: libxcb | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libxml2 | XML parser library for GNOME | local_installed | NO | Debian package: libxml2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libxslt | XML stylesheet transformation libra | local_installed | NO | Debian package: libxslt | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| libyuv | Library for freeswitch yuv graphics | local_installed | NO | Debian package: libyuv | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| links | Lynx-like text WWW browser | local_installed | NO | Debian package: links | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| log4cplus | Logging library for C++ | local_installed | NO | Debian package: log4cplus | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| lua-resty-core | New FFI-based Lua API for OpenResty | local_installed | NO | Debian package: lua-resty-core | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| lua-resty-lrucache | Lua-land LRU cache based on the Lua | local_installed | NO | Debian package: lua-resty-lrucache | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| lua54 | Powerful, efficient, lightweight, e | local_installed | NO | Debian package: lua54 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| luajit-openresty | Just-In-Time Compiler for Lua (Open | local_installed | NO | Debian package: luajit-openresty | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| lzo2 | Portable speedy, lossless data comp | local_installed | NO | Debian package: lzo2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| m4 | GNU M4 | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| meson | High performance build system | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| minicron | very small cron | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| miniupnpd | Lightweight UPnP IGD & PCP/NAT-PMP  | local_installed | NO | Debian package: miniupnpd | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| mobile-broadband-provider-info | Service mobile broadband provider d | local_installed | NO | Debian package: mobile-broadband-provide | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| mpd5 | Multi-link PPP daemon based on netg | local_installed | YES | accel-ppp / pppd / xl2tpd / strongSwan | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| mpdecimal | C/C++ arbitrary precision decimal f | local_installed | NO | Debian package: mpdecimal | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| nettle | Low-level cryptographic library | local_installed | NO | Debian package: nettle | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| nginx | Robust and small WWW server | local_installed | NO | Debian package: nginx | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| ninja | Small build system closest in spiri | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| nss_ldap | RFC 2307 NSS module | local_installed | NO | Debian package: nss_ldap | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| ntp | The Network Time Protocol Distribut | local_installed | NO | Debian package: ntp | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| oniguruma | Regular expressions library compati | local_installed | NO | Debian package: oniguruma | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| openldap26-client | Open source LDAP client implementat | local_installed | NO | Debian package: openldap26-client | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| opensc | Libraries and utilities to access s | local_installed | NO | Debian package: opensc | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| openvpn | Secure IP/Ethernet tunnel daemon | local_installed | NO | Debian package: openvpn | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| openvpn-auth-script | Generic script-based deferred auth  | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| p5-Locale-gettext | Message handling functions | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| p5-Locale-libintl | Internationalization library for Pe | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| p5-Text-Unidecode | US-ASCII transliterations of Unicod | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| p5-Unicode-EastAsianWidth | East Asian Width properties | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pam_ldap | PAM module for authenticating with  | local_installed | NO | Debian package: pam_ldap | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| pam_mkhomedir | Create HOME with a PAM module on de | local_installed | NO | Debian package: pam_mkhomedir | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| pcre2 | Perl Compatible Regular Expressions | local_installed | NO | Debian package: pcre2 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| pcsc-lite | Middleware library to access a smar | local_installed | NO | Debian package: pcsc-lite | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| perl5 | Practical Extraction and Report Lan | local_installed | NO | Debian package: perl5 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| pfSense | Main pfSense package | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-Status_Monitoring-php85 | pfSense Status Monitoring | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-base | pfSense core files | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-boot | pfSense boot files | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-composer-deps | pfSense deps from composer | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-default-config | Default config.xml | local_installed | YES | MitraNet Native Schema & Config Engine ( | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| pfSense-gnid | GNID tool. | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-installer | pfSense dynamic repository client | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-kernel-pfSense | pfSense kernel (pfSense) | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-pkg-WireGuard | pfSense package WireGuard | local_installed | YES | wireguard-tools + Linux in-tree WireGuar | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| pfSense-repoc | pfSense dynamic repository client | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-system | pfSense system package | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pfSense-upgrade | pfSense upgrade script | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pftop | Utility for real-time display of st | local_installed | YES | nftables (nft monitor / nft list ruleset | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| php85 | PHP Scripting Language (8.5.X branc | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-bcmath | The bcmath shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-bz2 | The bz2 shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-ctype | The ctype shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-curl | The curl shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-dom | The dom shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-filter | The filter shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-gettext | The gettext shared extension for ph | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-gmp | The gmp shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-intl | The intl shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-ldap | The ldap shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-mbstring | The mbstring shared extension for p | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-openssl_x509_crl | PHP Class to create openssl Certifi | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pcntl | The pcntl shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pdo | The pdo shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pdo_sqlite | The pdo_sqlite shared extension for | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear | PEAR framework for PHP | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear-Auth_RADIUS | PEAR wrapper classes for the RADIUS | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear-Cache_Lite | Fast and Safe little cache system | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear-Crypt_CHAP | PEAR class for generating CHAP pack | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear-HTTP_Request2 | PEAR classes providing an easy way  | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear-Mail | PEAR class that provides multiple i | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear-Net_IPv6 | Check and validate IPv6 addresses | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear-Net_URL2 | PEAR Class for parsing and handling | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pear-XML_RPC2 | XML-RPC client/server library | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pecl-mcrypt | PHP extension for mcrypt, removed i | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pecl-radius | Radius client library for PHP | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pecl-rrd | PHP bindings to rrd tool system | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-pfSense-module | Library for getting useful info | local_installed | YES | N/A (FreeBSD kernel, bootloader, or pfSe | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-phpseclib | PHP arbitrary-precision integer ari | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-posix | The posix shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-readline | The readline shared extension for p | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-session | The session shared extension for ph | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-shmop | The shmop shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-simplexml | The simplexml shared extension for  | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-sockets | The sockets shared extension for ph | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-sqlite3 | The sqlite3 shared extension for ph | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-sysvmsg | The sysvmsg shared extension for ph | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-sysvsem | The sysvsem shared extension for ph | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-sysvshm | The sysvshm shared extension for ph | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-tokenizer | The tokenizer shared extension for  | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-xml | The xml shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-xmlreader | The xmlreader shared extension for  | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-xmlwriter | The xmlwriter shared extension for  | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| php85-zlib | The zlib shared extension for php | local_installed | NO | MitraNet Native Python / FastAPI Core En | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pkcs11-helper | Helper library for multiple PKCS#11 | local_installed | NO | Debian package: pkcs11-helper | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| pkg | Package manager | local_installed | YES | dpkg / apt / libjson-c | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| pkgconf | Utility to help to configure compil | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| png | Library for manipulating PNG images | local_installed | NO | Debian package: png | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| polkit | Framework for controlling access to | local_installed | NO | Debian package: polkit | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| protobuf | Data interchange format library | local_installed | NO | Debian package: protobuf | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| protobuf-c | Code generator and libraries to use | local_installed | NO | Debian package: protobuf-c | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| public_suffix_list | Public Suffix List by Mozilla | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| py311-build | PEP517 package builder | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| py311-flit-core | Distribution-building parts of Flit | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| py311-installer | Library for installing Python wheel | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| py311-packaging | Core utilities for Python packages | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| py311-pyproject_hooks | Wrappers to call pyproject.toml-bas | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| py311-setuptools | Python packages installer | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| py311-wheel | Built-package format for Python | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| py312-packaging | Core utilities for Python packages | local_installed | NO | Debian package: py312-packaging | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| python3 | Meta-port for the Python interprete | local_installed | NO | Debian package: python3 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| python311 | Interpreted object-oriented program | local_installed | NO | Debian package: python311 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| python312 | Interpreted object-oriented program | local_installed | NO | Debian package: python312 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| qstats | read dhpcd.lease file and add it to | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| radvd | Linux/BSD IPv6 router advertisement | local_installed | NO | Debian package: radvd | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| rate | Traffic analysis command-line utili | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| readline | Library for editing command lines a | local_installed | NO | Debian package: readline | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| rhash | Utility and library for computing a | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| rrdtool | Round Robin Database Tools | local_installed | NO | Debian package: rrdtool | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| scponly | Tiny shell that only permits scp an | local_installed | NO | rssh / internal-sftp ChrootDirectory | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| shared-mime-info | MIME types database from the freede | local_installed | NO | Debian package: shared-mime-info | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| smartmontools | S.M.A.R.T. disk monitoring tools | local_installed | NO | Debian package: smartmontools | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| speedtest | Internet Bandwidth & Latency Speedt | custom_offline_pkg | NO | Debian package: speedtest | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| sqlite3 | SQL database engine in a C library | local_installed | NO | Debian package: sqlite3 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| ssh_tunnel_shell | SSH tunnel shell | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| sshguard | Protect hosts from brute-force atta | local_installed | NO | Debian package: sshguard | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| strongswan | Open Source IKEv2 IPsec-based VPN s | local_installed | NO | Debian package: strongswan | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| swig | Generate wrappers for calling C/C++ | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| tcl86 | Tool Command Language | local_installed | NO | Debian package: tcl86 | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| texinfo | Typeset documentation system with m | upstream_repo_db | NO | Debian build-essential / upstream apt bu | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| tiff | Tools and library routines for work | local_installed | NO | Debian package: tiff | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| uclcmd | Command line tool for working with  | local_installed | NO | dpkg / apt / libjson-c | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| unbound | Validating, recursive, and caching  | local_installed | NO | Debian package: unbound | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| unzip | List, test, and extract compressed  | local_installed | NO | Debian package: unzip | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| vmaf | Perceptual video quality assessment | local_installed | NO | Debian package: vmaf | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| voucher | Voucher support | local_installed | YES | MitraNet Core Services / nftables / syst | REIMPLEMENT | NATIVE-CODE | Core Regressions | MANDATORY | **DECIDED** |
| vstr | General purpose string library for  | local_installed | NO | Debian package: vstr | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| webp | Google WebP image format conversion | local_installed | NO | Debian package: webp | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| whois | Marco d'Itri whois client | local_installed | NO | Debian package: whois | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| wifi | Wireless Network & AP Manager with  | custom_offline_pkg | NO | hostapd + wpasupplicant + iw + wireless- | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| wireguard-pfsense | WireGuard VPN service and manager f | local_installed | YES | wireguard-tools + Linux in-tree WireGuar | REPLACE | NO (Standard Debian) | Core Regressions | MANDATORY | **DECIDED** |
| wol | Tool to wake up Wake-On-LAN complia | local_installed | NO | Debian package: wol | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| wpa_supplicant | Supplicant (client) for WPA/802.1x  | local_installed | NO | Debian package: wpa_supplicant | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| wrapalixresetbutton | Utility to detect platform reset bu | local_installed | YES | N/A (Obsolete PC Engines ALIX hardware) | EXCLUDE | NO (Standard Debian) | N/A (Excluded) | UPSTREAM-AUDITED | **DECIDED** |
| xinetd | Replacement for inetd with better c | local_installed | NO | Debian package: xinetd | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| xmlstarlet | Command Line XML Toolkit | local_installed | NO | Debian package: xmlstarlet | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| xorgproto | X Window System unified protocol de | local_installed | NO | Debian package: xorgproto | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| xray-pfsense | Xray-core multi-protocol proxy (VLE | local_installed | YES | Debian package: xray-pfsense | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
| zstd | Fast real-time compression algorith | local_installed | NO | Debian package: zstd | DIRECT-DEBIAN | NO (Standard Debian) | Apt Verified | UPSTREAM-AUDITED | **DECIDED** |
