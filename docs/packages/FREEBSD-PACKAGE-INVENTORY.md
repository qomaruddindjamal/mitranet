# FreeBSD Base & Ports Package Inventory

## Overview

This document inventories all 233 FreeBSD ports and utilities present in the pfSense distribution repository and installation database. Every package has been reverse engineered, its metadata analyzed, its dependencies traced, and its role mapped to standard Debian GNU/Linux 13 Trixie facilities.

| Package ID | Version | Origin | Primary Function | Classification | Strategy | Status |
|---|---|---|---|---|---|---|
| abseil | 20250127.1_2 | devel/abseil | Abseil Common Libraries (C++) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| aom | 3.15.1 | multimedia/aom | AV1 reference encoder/decoder | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| autoconf | 2.72 | devel/autoconf | Generate configure scripts and related files | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| autoconf-switch | 20220527 | devel/autoconf-switch | Wrapper script to switch between autoconf versions | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| automake | 1.16.5_2 | devel/automake | GNU Standards-compliant Makefile generator | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| beep | 1.0_2 | audio/beep | Beeps a certain duration and pitch out of the PC Speaker | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| bind-tools | 9.20.29 | dns/bind-tools | Command line tools from BIND: delv, dig, host, nslookup... | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| boost-libs | 1.91.0 | devel/boost-libs | Free portable C++ libraries (without Boost.Python) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| brotli | 1.2.0,1 | archivers/brotli | Generic-purpose lossless compression algorithm | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| bsnmp-regex | 0.6_4 | net-mgmt/bsnmp-regex | bsnmpd module allowing creation of counters from log files | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| bsnmp-ucd | 0.4.5_1 | net-mgmt/bsnmp-ucd | bsnmpd module that implements parts of UCD-SNMP-MIB | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| bwi-firmware-kmod | 3.130.20.1600018 | net/bwi-firmware-kmod | Broadcom AirForce IEEE 802.11 Firmware Kernel Module | INCOMPATIBLE / UNSUITABLE | EXCLUDE | **DECIDED** |
| ca_root_nss | 3.130 | security/ca_root_nss | Root certificate bundle from the Mozilla Project | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| check_reload_status | 0.0.17 | sysutils/check_reload_status | run various pfSense scripts on event. | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| choparp | 20150613_1 | net-mgmt/choparp | Simple proxy arp daemon | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| cmake-core | 3.29.6 | devel/cmake-core | Cross-platform Makefile generator | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| cpdup | 1.22_1 | sysutils/cpdup | Comprehensive filesystem mirroring and backup program | REPLACE-WITH-LINUX-NATIVE | REPLACE | **DECIDED** |
| cpu-microcode | 1.0_1 | sysutils/cpu-microcode | Meta-package for CPU microcode updates | INCOMPATIBLE / UNSUITABLE | EXCLUDE | **DECIDED** |
| cpu-microcode-amd | 20251202 | sysutils/cpu-microcode-amd | AMD CPU microcode updates | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| cpu-microcode-intel | 20260812 | sysutils/cpu-microcode-intel | Intel CPU microcode updates | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| cpu-microcode-rc | 1.0_2 | sysutils/cpu-microcode-rc | RC script for CPU microcode updates | INCOMPATIBLE / UNSUITABLE | EXCLUDE | **DECIDED** |
| cpustats | 0.1_1 | sysutils/cpustats | cpustats | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| curl | 8.22.0 | ftp/curl | Command line tool and library for transferring data with URLs | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| cyrus-sasl | 2.1.28_6 | security/cyrus-sasl2 | RFC 2222 SASL (Simple Authentication and Security Layer) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| dav1d | 1.5.4 | multimedia/dav1d | Small and fast AV1 decoder | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| dbus | 1.16.2_4,1 | devel/dbus | Message bus system for inter-application communication | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| dhcp6 | 20080615.2_4 | net/dhcp6 | KAME DHCP6 client, server, and relay | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| dhcpcd | 10.5.2 | net/dhcpcd | DHCP/IPv4LL/IPv6RS/DHCPv6 client | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| dhcpleases | 0.5_2 | sysutils/dhcpleases | read dhpcd.lease file and add it to hosts file | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| dhcpleases6 | 0.1_4 | sysutils/dhcpleases6 | read dhpcd6.leases file and trigger command on modification | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| dmidecode | 3.7 | sysutils/dmidecode | Tool for dumping DMI (SMBIOS) contents in human-readable format | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| dnsmasq | 2.93,1 | dns/dnsmasq | Lightweight DNS forwarder, DHCP, and TFTP server | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| dpinger | 3.6 | net/dpinger | IP device monitoring tool | REPLACE-WITH-LINUX-NATIVE | REPLACE | **DECIDED** |
| duktape-lib | 2.7.0_1 | lang/duktape-lib | Embeddable Javascript engine (shared lib) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| easy-rsa | 3.2.6,1 | security/easy-rsa | Small RSA key management package based on openssl | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| expat | 2.8.4 | textproc/expat2 | XML 1.0 parser written in C | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| expiretable | 0.6_3 | security/expiretable | Utility to remove entries from the pf(4) table based on their age | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| fcgi-devkit | 2.4.0_6 | www/fcgi | FastCGI Development Kit | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| filterdns | 2.3 | net/filterdns | filterdns | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| filterlog | 0.1_11 | sysutils/filterlog | filterlog | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| fontconfig | 2.17.1,1 | x11-fonts/fontconfig | XML-based font configuration API for X Windows | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| freetype2 | 2.14.3 | print/freetype2 | Free and portable TrueType font rendering engine | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| fstrm | 0.6.1_1 | devel/fstrm | Implementation of the Frame Streams data transport protocol in C | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| gdk-pixbuf2 | 2.44.1 | graphics/gdk-pixbuf2 | Graphic library for GTK | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| gettext-runtime | 1.0_1 | devel/gettext-runtime | GNU gettext runtime libraries and programs | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| gettext-tools | 0.22.5 | devel/gettext-tools | GNU gettext development and translation tools | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| giflib | 6.1.3 | graphics/giflib | Tools and library routines for working with GIF images | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| glib | 2.88.3,2 | devel/glib20 | Some useful routines of C programming (current stable version) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| glib-bootstrap | 2.88.3,2 | devel/glib20 | Some useful routines of C programming (current stable version) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| gmake | 4.4.1 | devel/gmake | GNU version of 'make' utility | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| gmp | 6.3.0 | math/gmp | Free library for arbitrary precision arithmetic | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| graphite2 | 1.3.15 | graphics/graphite2 | Rendering capabilities for complex non-Roman writing systems | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| help2man | 1.49.3_1 | misc/help2man | Automatically generating simple manual pages from program output | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| hostapd | 2.12_2 | net/hostapd | IEEE 802.11 AP, IEEE 802.1X/WPA/WPA2/EAP/RADIUS Authenticator | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| icu | 76.1,1 | devel/icu | International Components for Unicode (from IBM) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| if_pppoe-kmod | 2.9.0.1600018_1 | net/if_pppoe-kmod | PPPoE Kernel Driver | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| iftop | 1.0.p4_1 | net-mgmt/iftop | Display bandwidth usage on an interface by host | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| igmpproxy | 0.4_3,1 | net/igmpproxy | Multicast forwarding IGMP proxy | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| indexinfo | 0.3.1_1 | print/indexinfo | Utility to regenerate the GNU info page index | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| ipmitool | 1.8.19_3 | sysutils/ipmitool | CLI to manage IPMI systems | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| isc-dhcp44-client | 4.4.3P1_2 | net/isc-dhcp44-client | The ISC Dynamic Host Configuration Protocol client | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| isc-dhcp44-relay | 4.4.3P1_4 | net/isc-dhcp44-relay | The ISC Dynamic Host Configuration Protocol relay | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| isc-dhcp44-server | 4.4.3P1_5 | net/isc-dhcp44-server | ISC Dynamic Host Configuration Protocol server | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| jbigkit | 2.1_3 | graphics/jbigkit | Lossless compression for bi-level images such as scanned pages, faxes | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| jpeg-turbo | 3.1.4.1 | graphics/jpeg-turbo | SIMD-accelerated JPEG codec which replaces libjpeg | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| jq | 1.8.2 | textproc/jq | Lightweight and flexible command-line JSON processor | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| jsoncpp | 1.9.8 | devel/jsoncpp | JSON reader and writer library for C++ | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| kea | 3.2.0_1 | net/kea | Alternative DHCP implementation by ISC | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| ldns | 1.9.2 | dns/ldns | Library for programs conforming to DNS RFCs and drafts | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| lerc | 4.2.0 | graphics/lerc | C++ library for Limited Error Raster Compression | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libICE | 1.1.2,1 | x11/libICE | Inter Client Exchange library for X11 | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libSM | 1.2.6,1 | x11/libSM | Session Management library for X11 | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libX11 | 1.8.13_1,1 | x11/libX11 | Core X11 protocol client library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libXau | 1.0.12 | x11/libXau | Authentication Protocol library for X11 | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libXdmcp | 1.1.5 | x11/libXdmcp | X Display Manager Control Protocol library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libavif | 1.4.2 | graphics/libavif | Library for encoding and decoding .avif files | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libccid | 1.8.2 | devel/libccid | Generic USB CCID and ICCD driver | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libdeflate | 1.26 | archivers/libdeflate | Fast, whole-buffer DEFLATE-based compression library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libedit | 3.1.20260512,1 | devel/libedit | Command line editor library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libevent | 2.1.13 | devel/libevent | API for executing callback functions on events or timeouts | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libffi | 3.8.0 | devel/libffi | Foreign Function Interface | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libfontenc | 1.1.9 | x11-fonts/libfontenc | The fontenc Library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libgcrypt | 1.12.2 | security/libgcrypt | General purpose cryptographic library based on the code from GnuPG | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libgpg-error | 1.61 | security/libgpg-error | Common error values for all GnuPG components | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libiconv | 1.18_1 | converters/libiconv | Character set conversion library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libidn2 | 2.3.8 | dns/libidn2 | Implementation of IDNA2008 internationalized domain names | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libltdl | 2.6.2 | devel/libltdl | System independent dlopen wrapper | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| liblz4 | 1.10.0_2,1 | archivers/liblz4 | LZ4 compression library, lossless and very fast | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libmcrypt | 2.5.8_4 | security/libmcrypt | Multi-cipher cryptographic library (used in PHP) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libnghttp2 | 1.70.0 | www/libnghttp2 | HTTP/2 C Library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libpfctl | 0.17 | net/libpfctl | Library for interaction with pf(4) | REPLACE-WITH-LINUX-NATIVE | REPLACE | **DECIDED** |
| libpsl | 0.23.3 | dns/libpsl | C library to handle the Public Suffix List | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libsodium | 1.0.22 | security/libsodium | Library to build higher-level cryptographic tools | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libssh2 | 1.11.1_1,3 | security/libssh2 | Library implementing the SSH2 protocol | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libtextstyle | 0.22.5 | devel/libtextstyle | Text styling library | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| libtool | 2.4.7_2 | devel/libtool | Generic shared library support script | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| libucl | 0.9.4 | textproc/libucl | Universal configuration library parser | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| libunistring | 1.4.2 | devel/libunistring | Unicode string library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| liburcu | 0.15.7 | sysutils/liburcu | Userspace read-copy-update (RCU) data synchronization library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libuv | 1.52.1 | devel/libuv | Multi-platform support library with a focus on asynchronous I/O | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libxcb | 1.17.0 | x11/libxcb | The X protocol C-language Binding (XCB) library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libxml2 | 2.15.4 | textproc/libxml2 | XML parser library for GNOME | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libxslt | 1.1.45 | textproc/libxslt | XML stylesheet transformation library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| libyuv | 0.0.1903 | graphics/libyuv | Library for freeswitch yuv graphics manipulation | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| links | 2.30_1,1 | www/links | Lynx-like text WWW browser | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| log4cplus | 2.2.0.1 | devel/log4cplus | Logging library for C++ | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| lua-resty-core | 0.1.28 | www/lua-resty-core | New FFI-based Lua API for OpenResty NGINX Lua modules | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| lua-resty-lrucache | 0.13 | www/lua-resty-lrucache | Lua-land LRU cache based on the LuaJIT FFI | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| lua54 | 5.4.8 | lang/lua54 | Powerful, efficient, lightweight, embeddable scripting language | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| luajit-openresty | 2.1.20240314 | lang/luajit-openresty | Just-In-Time Compiler for Lua (OpenResty branch) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| lzo2 | 2.10_2 | archivers/lzo2 | Portable speedy, lossless data compression library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| m4 | 1.4.19_1,1 | devel/m4 | GNU M4 | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| meson | 1.4.1 | devel/meson | High performance build system | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| minicron | 0.0.2 | sysutils/minicron | very small cron | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| miniupnpd | 2.3.9_1,1 | net/miniupnpd | Lightweight UPnP IGD & PCP/NAT-PMP daemon which uses pf | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| mobile-broadband-provider-info | 20251101 | net/mobile-broadband-provider-info | Service mobile broadband provider database | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| mpd5 | 5.9_19 | net/mpd5 | Multi-link PPP daemon based on netgraph(4) | REPLACE-WITH-LINUX-NATIVE | REPLACE | **DECIDED** |
| mpdecimal | 4.0.1 | math/mpdecimal | C/C++ arbitrary precision decimal floating point libraries | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| nettle | 3.10.2 | security/nettle | Low-level cryptographic library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| nginx | 1.30.5,3 | www/nginx | Robust and small WWW server | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| ninja | 1.11.1,4 | devel/ninja | Small build system closest in spirit to Make | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| nss_ldap | 1.265_15 | net/nss_ldap | RFC 2307 NSS module | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| ntp | 4.2.8p18_6 | net/ntp | The Network Time Protocol Distribution | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| oniguruma | 6.9.10 | devel/oniguruma | Regular expressions library compatible with POSIX/GNU/Perl | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| openldap26-client | 2.6.15_1 | databases/openldap26-client | Open source LDAP client implementation | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| opensc | 0.27.0 | security/opensc | Libraries and utilities to access smart cards | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| openvpn | 2.7.7 | security/openvpn | Secure IP/Ethernet tunnel daemon | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| openvpn-auth-script | 1.0.0.3 | security/openvpn-auth-script | Generic script-based deferred auth plugin for OpenVPN | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| p5-Locale-gettext | 1.07 | devel/p5-Locale-gettext | Message handling functions | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| p5-Locale-libintl | 1.33 | devel/p5-Locale-libintl | Internationalization library for Perl | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| p5-Text-Unidecode | 1.30 | converters/p5-Text-Unidecode | US-ASCII transliterations of Unicode text | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| p5-Unicode-EastAsianWidth | 12.0 | textproc/p5-Unicode-EastAsianWidth | East Asian Width properties | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| pam_ldap | 186_2 | security/pam_ldap | PAM module for authenticating with LDAP | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| pam_mkhomedir | 0.2_1 | security/pam_mkhomedir | Create HOME with a PAM module on demand | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| pcre2 | 10.48 | devel/pcre2 | Perl Compatible Regular Expressions library, version 2 | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| pcsc-lite | 2.5.2,2 | devel/pcsc-lite | Middleware library to access a smart card using SCard API (PC/SC) | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| perl5 | 5.42.3 | lang/perl5.42 | Practical Extraction and Report Language | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| pftop | 0.13 | sysutils/pftop | Utility for real-time display of statistics for pf | REPLACE-WITH-LINUX-NATIVE | REPLACE | **DECIDED** |
| php85 | 8.5.10 | lang/php85 | PHP Scripting Language (8.5.X branch) | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-bcmath | 8.5.10 | math/php85-bcmath | The bcmath shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-bz2 | 8.5.10 | archivers/php85-bz2 | The bz2 shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-ctype | 8.5.10 | textproc/php85-ctype | The ctype shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-curl | 8.5.10 | ftp/php85-curl | The curl shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-dom | 8.5.10 | textproc/php85-dom | The dom shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-filter | 8.5.10 | security/php85-filter | The filter shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-gettext | 8.5.10 | devel/php85-gettext | The gettext shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-gmp | 8.5.10 | math/php85-gmp | The gmp shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-intl | 8.5.10 | devel/php85-intl | The intl shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-ldap | 8.5.10 | net/php85-ldap | The ldap shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-mbstring | 8.5.10 | converters/php85-mbstring | The mbstring shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-openssl_x509_crl | 1.3_3 | security/php-openssl_x509_crl | PHP Class to create openssl Certificate Revocation List (CRL) | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pcntl | 8.5.10 | devel/php85-pcntl | The pcntl shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pdo | 8.5.10 | databases/php85-pdo | The pdo shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pdo_sqlite | 8.5.10 | databases/php85-pdo_sqlite | The pdo_sqlite shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear | 1.10.18 | devel/pear | PEAR framework for PHP | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear-Auth_RADIUS | 1.1.0_5 | net/pear-Auth_RADIUS | PEAR wrapper classes for the RADIUS PECL | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear-Cache_Lite | 1.8.3,1 | sysutils/pear-Cache_Lite | Fast and Safe little cache system | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear-Crypt_CHAP | 1.5.0_2 | security/pear-Crypt_CHAP | PEAR class for generating CHAP packets | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear-HTTP_Request2 | 2.7.0,1 | www/pear-HTTP_Request2 | PEAR classes providing an easy way to perform HTTP requests | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear-Mail | 2.0.0,1 | mail/pear-Mail | PEAR class that provides multiple interfaces for sending emails | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear-Net_IPv6 | 1.3.0.b4_2 | net/pear-Net_IPv6 | Check and validate IPv6 addresses | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear-Net_URL2 | 2.2.3 | net/pear-Net_URL2 | PEAR Class for parsing and handling URL | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pear-XML_RPC2 | 1.1.5 | net/pear-XML_RPC2 | XML-RPC client/server library | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pecl-mcrypt | 1.0.9 | security/pecl-mcrypt | PHP extension for mcrypt, removed in PHP 7.2 | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pecl-radius | 1.4.0b1_6 | net/pecl-radius | Radius client library for PHP | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-pecl-rrd | 2.0.4 | databases/pecl-rrd | PHP bindings to rrd tool system | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-phpseclib | 2.0.17 | security/phpseclib | PHP arbitrary-precision integer arithmetic library | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-posix | 8.5.10 | sysutils/php85-posix | The posix shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-readline | 8.5.10 | devel/php85-readline | The readline shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-session | 8.5.10 | www/php85-session | The session shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-shmop | 8.5.10 | devel/php85-shmop | The shmop shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-simplexml | 8.5.10 | textproc/php85-simplexml | The simplexml shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-sockets | 8.5.10 | net/php85-sockets | The sockets shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-sqlite3 | 8.5.10 | databases/php85-sqlite3 | The sqlite3 shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-sysvmsg | 8.5.10 | devel/php85-sysvmsg | The sysvmsg shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-sysvsem | 8.5.10 | devel/php85-sysvsem | The sysvsem shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-sysvshm | 8.5.10 | devel/php85-sysvshm | The sysvshm shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-tokenizer | 8.5.10 | devel/php85-tokenizer | The tokenizer shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-xml | 8.5.10 | textproc/php85-xml | The xml shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-xmlreader | 8.5.10 | textproc/php85-xmlreader | The xmlreader shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-xmlwriter | 8.5.10 | textproc/php85-xmlwriter | The xmlwriter shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| php85-zlib | 8.5.10 | archivers/php85-zlib | The zlib shared extension for php | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| pkcs11-helper | 1.31.0 | security/pkcs11-helper | Helper library for multiple PKCS#11 providers | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| pkg | 2.8.4 | ports-mgmt/pkg | Package manager | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| pkgconf | 2.2.0,1 | devel/pkgconf | Utility to help to configure compiler and linker flags | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| png | 1.6.58 | graphics/png | Library for manipulating PNG images | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| polkit | 127 | sysutils/polkit | Framework for controlling access to system-wide components | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| protobuf | 29.6,1 | devel/protobuf | Data interchange format library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| protobuf-c | 1.5.1_4 | devel/protobuf-c | Code generator and libraries to use Protocol Buffers from pure C | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| public_suffix_list | 20240531 | dns/public_suffix_list | Public Suffix List by Mozilla | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| py311-build | 1.2.1 | devel/py-build | PEP517 package builder | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| py311-flit-core | 3.9.0 | devel/py-flit-core | Distribution-building parts of Flit | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| py311-installer | 0.7.0 | devel/py-installer | Library for installing Python wheels | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| py311-packaging | 24.1 | devel/py-packaging | Core utilities for Python packages | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| py311-pyproject_hooks | 1.1.0 | devel/py-pyproject_hooks | Wrappers to call pyproject.toml-based build backend hooks | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| py311-setuptools | 63.1.0_1 | devel/py-setuptools | Python packages installer | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| py311-wheel | 0.43.0 | devel/py-wheel | Built-package format for Python | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| py312-packaging | 26.3 | devel/py-packaging | Core utilities for Python packages | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| python3 | 3_4 | lang/python3 | Meta-port for the Python interpreter 3.x | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| python311 | 3.11.16 | lang/python311 | Interpreted object-oriented programming language | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| python312 | 3.12.14_1 | lang/python312 | Interpreted object-oriented programming language | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| qstats | 0.2 | sysutils/qstats | read dhpcd.lease file and add it to hosts file | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| radvd | 2.20 | net/radvd | Linux/BSD IPv6 router advertisement daemon | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| rate | 0.9_4 | net-mgmt/rate | Traffic analysis command-line utility | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| readline | 8.3.6 | devel/readline | Library for editing command lines as they are typed | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| rhash | 1.4.4_1 | security/rhash | Utility and library for computing and checking of file hashes | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| rrdtool | 1.9.0_1 | databases/rrdtool | Round Robin Database Tools | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| scponly | 4.8.20110526_8 | shells/scponly | Tiny shell that only permits scp and sftp | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| shared-mime-info | 2.4_2 | misc/shared-mime-info | MIME types database from the freedesktop.org project | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| smartmontools | 7.5_2 | sysutils/smartmontools | S.M.A.R.T. disk monitoring tools | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| sqlite3 | 3.53.4,1 | databases/sqlite3 | SQL database engine in a C library | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| ssh_tunnel_shell | 0.2_2 | sysutils/ssh_tunnel_shell | SSH tunnel shell | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| sshguard | 2.5.1_2,1 | security/sshguard | Protect hosts from brute-force attacks against SSH and other services | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| strongswan | 6.1.0_1 | security/strongswan | Open Source IKEv2 IPsec-based VPN solution | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| swig | 4.1.1 | devel/swig | Generate wrappers for calling C/C++ code from other languages | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| tcl86 | 8.6.18_1 | lang/tcl86 | Tool Command Language | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| texinfo | 7.1_2,1 | print/texinfo | Typeset documentation system with multiple format output | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| tiff | 4.7.2 | graphics/tiff | Tools and library routines for working with TIFF images | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| uclcmd | 0.2.20211204 | devel/uclcmd | Command line tool for working with UCL config files | NOT-REQUIRED | EXCLUDE | **DECIDED** |
| unbound | 1.26.1 | dns/unbound | Validating, recursive, and caching DNS resolver | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| unzip | 6.0_8 | archivers/unzip | List, test, and extract compressed files from a ZIP archive | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| vmaf | 3.2.1 | multimedia/vmaf | Perceptual video quality assessment based on multi-method fusion | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| voucher | 0.1_3 | sysutils/voucher | Voucher support | REIMPLEMENT-NATIVELY | REIMPLEMENT | **DECIDED** |
| vstr | 1.0.15_2 | devel/vstr | General purpose string library for C | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| webp | 1.6.0 | graphics/webp | Google WebP image format conversion tool | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| whois | 5.5.7_1 | net/whois | Marco d'Itri whois client | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| wol | 0.7.1_5 | net/wol | Tool to wake up Wake-On-LAN compliant computers | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| wpa_supplicant | 2.12_1 | security/wpa_supplicant | Supplicant (client) for WPA/802.1x protocols | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| wrapalixresetbutton | 0.0.16 | sysutils/wrapalixresetbutton | Utility to detect platform reset button state for use in scripting | INCOMPATIBLE / UNSUITABLE | EXCLUDE | **DECIDED** |
| xinetd | 2.3.15_3 | security/xinetd | Replacement for inetd with better control and logging | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| xmlstarlet | 1.6.1_4 | textproc/xmlstarlet | Command Line XML Toolkit | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| xorgproto | 2025.1 | x11/xorgproto | X Window System unified protocol definitions | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |
| zstd | 1.5.7_2 | archivers/zstd | Fast real-time compression algorithm | DIRECT-DEBIAN-EQUIVALENT | DIRECT-DEBIAN | **DECIDED** |


## Technical Inventory Details

### abseil
- **Package Name:** abseil (20250127.1_2)
- **Origin:** devel/abseil
- **Description:** Abseil Common Libraries (C++)
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: abseil
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### aom
- **Package Name:** aom (3.15.1)
- **Origin:** multimedia/aom
- **Description:** AV1 reference encoder/decoder
- **Dependencies:** ['vmaf']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: aom
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### autoconf
- **Package Name:** autoconf (2.72)
- **Origin:** devel/autoconf
- **Description:** Generate configure scripts and related files
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### autoconf-switch
- **Package Name:** autoconf-switch (20220527)
- **Origin:** devel/autoconf-switch
- **Description:** Wrapper script to switch between autoconf versions
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### automake
- **Package Name:** automake (1.16.5_2)
- **Origin:** devel/automake
- **Description:** GNU Standards-compliant Makefile generator
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### beep
- **Package Name:** beep (1.0_2)
- **Origin:** audio/beep
- **Description:** Beeps a certain duration and pitch out of the PC Speaker
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: beep
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### bind-tools
- **Package Name:** bind-tools (9.20.29)
- **Origin:** dns/bind-tools
- **Description:** Command line tools from BIND: delv, dig, host, nslookup...
- **Dependencies:** ['fstrm', 'libedit', 'libidn2', 'libnghttp2', 'liburcu', 'libuv', 'protobuf-c']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: bind-tools
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### boost-libs
- **Package Name:** boost-libs (1.91.0)
- **Origin:** devel/boost-libs
- **Description:** Free portable C++ libraries (without Boost.Python)
- **Dependencies:** ['icu', 'zstd']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: boost-libs
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### brotli
- **Package Name:** brotli (1.2.0,1)
- **Origin:** archivers/brotli
- **Description:** Generic-purpose lossless compression algorithm
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: brotli
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### bsnmp-regex
- **Package Name:** bsnmp-regex (0.6_4)
- **Origin:** net-mgmt/bsnmp-regex
- **Description:** bsnmpd module allowing creation of counters from log files
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: bsnmp-regex
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### bsnmp-ucd
- **Package Name:** bsnmp-ucd (0.4.5_1)
- **Origin:** net-mgmt/bsnmp-ucd
- **Description:** bsnmpd module that implements parts of UCD-SNMP-MIB
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: bsnmp-ucd
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### bwi-firmware-kmod
- **Package Name:** bwi-firmware-kmod (3.130.20.1600018)
- **Origin:** net/bwi-firmware-kmod
- **Description:** Broadcom AirForce IEEE 802.11 Firmware Kernel Module
- **Dependencies:** []
- **Classification:** INCOMPATIBLE / UNSUITABLE
- **Strategy:** EXCLUDE
- **Linux Equivalent:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **Technical Justification:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.

### ca_root_nss
- **Package Name:** ca_root_nss (3.130)
- **Origin:** security/ca_root_nss
- **Description:** Root certificate bundle from the Mozilla Project
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: ca_root_nss
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### check_reload_status
- **Package Name:** check_reload_status (0.0.17)
- **Origin:** sysutils/check_reload_status
- **Description:** run various pfSense scripts on event.
- **Dependencies:** ['libevent']
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### choparp
- **Package Name:** choparp (20150613_1)
- **Origin:** net-mgmt/choparp
- **Description:** Simple proxy arp daemon
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### cmake-core
- **Package Name:** cmake-core (3.29.6)
- **Origin:** devel/cmake-core
- **Description:** Cross-platform Makefile generator
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### cpdup
- **Package Name:** cpdup (1.22_1)
- **Origin:** sysutils/cpdup
- **Description:** Comprehensive filesystem mirroring and backup program
- **Dependencies:** []
- **Classification:** REPLACE-WITH-LINUX-NATIVE
- **Strategy:** REPLACE
- **Linux Equivalent:** rsync / cp --archive
- **Technical Justification:** DragonFly/FreeBSD mirror copy tool replaced by standard Linux rsync/cp.

### cpu-microcode
- **Package Name:** cpu-microcode (1.0_1)
- **Origin:** sysutils/cpu-microcode
- **Description:** Meta-package for CPU microcode updates
- **Dependencies:** ['cpu-microcode-amd', 'cpu-microcode-intel']
- **Classification:** INCOMPATIBLE / UNSUITABLE
- **Strategy:** EXCLUDE
- **Linux Equivalent:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **Technical Justification:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.

### cpu-microcode-amd
- **Package Name:** cpu-microcode-amd (20251202)
- **Origin:** sysutils/cpu-microcode-amd
- **Description:** AMD CPU microcode updates
- **Dependencies:** ['cpu-microcode-rc']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: cpu-microcode-amd
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### cpu-microcode-intel
- **Package Name:** cpu-microcode-intel (20260812)
- **Origin:** sysutils/cpu-microcode-intel
- **Description:** Intel CPU microcode updates
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: cpu-microcode-intel
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### cpu-microcode-rc
- **Package Name:** cpu-microcode-rc (1.0_2)
- **Origin:** sysutils/cpu-microcode-rc
- **Description:** RC script for CPU microcode updates
- **Dependencies:** []
- **Classification:** INCOMPATIBLE / UNSUITABLE
- **Strategy:** EXCLUDE
- **Linux Equivalent:** N/A (FreeBSD kernel, bootloader, or pfSense PHP/pkg-ng engine)
- **Technical Justification:** Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime.

### cpustats
- **Package Name:** cpustats (0.1_1)
- **Origin:** sysutils/cpustats
- **Description:** cpustats
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### curl
- **Package Name:** curl (8.22.0)
- **Origin:** ftp/curl
- **Description:** Command line tool and library for transferring data with URLs
- **Dependencies:** ['brotli', 'libidn2', 'libnghttp2', 'libpsl', 'libssh2', 'zstd']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: curl
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### cyrus-sasl
- **Package Name:** cyrus-sasl (2.1.28_6)
- **Origin:** security/cyrus-sasl2
- **Description:** RFC 2222 SASL (Simple Authentication and Security Layer)
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: cyrus-sasl
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### dav1d
- **Package Name:** dav1d (1.5.4)
- **Origin:** multimedia/dav1d
- **Description:** Small and fast AV1 decoder
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: dav1d
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### dbus
- **Package Name:** dbus (1.16.2_4,1)
- **Origin:** devel/dbus
- **Description:** Message bus system for inter-application communication
- **Dependencies:** ['expat', 'libICE', 'libSM', 'libX11', 'libxml2']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: dbus
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### dhcp6
- **Package Name:** dhcp6 (20080615.2_4)
- **Origin:** net/dhcp6
- **Description:** KAME DHCP6 client, server, and relay
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: dhcp6
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### dhcpcd
- **Package Name:** dhcpcd (10.5.2)
- **Origin:** net/dhcpcd
- **Description:** DHCP/IPv4LL/IPv6RS/DHCPv6 client
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: dhcpcd
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### dhcpleases
- **Package Name:** dhcpleases (0.5_2)
- **Origin:** sysutils/dhcpleases
- **Description:** read dhpcd.lease file and add it to hosts file
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### dhcpleases6
- **Package Name:** dhcpleases6 (0.1_4)
- **Origin:** sysutils/dhcpleases6
- **Description:** read dhpcd6.leases file and trigger command on modification
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### dmidecode
- **Package Name:** dmidecode (3.7)
- **Origin:** sysutils/dmidecode
- **Description:** Tool for dumping DMI (SMBIOS) contents in human-readable format
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: dmidecode
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### dnsmasq
- **Package Name:** dnsmasq (2.93,1)
- **Origin:** dns/dnsmasq
- **Description:** Lightweight DNS forwarder, DHCP, and TFTP server
- **Dependencies:** ['gettext-runtime', 'gmp', 'libidn2', 'nettle']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: dnsmasq
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### dpinger
- **Package Name:** dpinger (3.6)
- **Origin:** net/dpinger
- **Description:** IP device monitoring tool
- **Dependencies:** []
- **Classification:** REPLACE-WITH-LINUX-NATIVE
- **Strategy:** REPLACE
- **Linux Equivalent:** fping / iputils-ping / systemd-networkd / BFD
- **Technical Justification:** BSD ICMP gateway latency monitor replaced by standard Linux network monitors.

### duktape-lib
- **Package Name:** duktape-lib (2.7.0_1)
- **Origin:** lang/duktape-lib
- **Description:** Embeddable Javascript engine (shared lib)
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: duktape-lib
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### easy-rsa
- **Package Name:** easy-rsa (3.2.6,1)
- **Origin:** security/easy-rsa
- **Description:** Small RSA key management package based on openssl
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: easy-rsa
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### expat
- **Package Name:** expat (2.8.4)
- **Origin:** textproc/expat2
- **Description:** XML 1.0 parser written in C
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: expat
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### expiretable
- **Package Name:** expiretable (0.6_3)
- **Origin:** security/expiretable
- **Description:** Utility to remove entries from the pf(4) table based on their age
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### fcgi-devkit
- **Package Name:** fcgi-devkit (2.4.0_6)
- **Origin:** www/fcgi
- **Description:** FastCGI Development Kit
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### filterdns
- **Package Name:** filterdns (2.3)
- **Origin:** net/filterdns
- **Description:** filterdns
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### filterlog
- **Package Name:** filterlog (0.1_11)
- **Origin:** sysutils/filterlog
- **Description:** filterlog
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### fontconfig
- **Package Name:** fontconfig (2.17.1,1)
- **Origin:** x11-fonts/fontconfig
- **Description:** XML-based font configuration API for X Windows
- **Dependencies:** ['expat', 'freetype2', 'gettext-runtime']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: fontconfig
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### freetype2
- **Package Name:** freetype2 (2.14.3)
- **Origin:** print/freetype2
- **Description:** Free and portable TrueType font rendering engine
- **Dependencies:** ['brotli', 'png']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: freetype2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### fstrm
- **Package Name:** fstrm (0.6.1_1)
- **Origin:** devel/fstrm
- **Description:** Implementation of the Frame Streams data transport protocol in C
- **Dependencies:** ['libevent']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: fstrm
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### gdk-pixbuf2
- **Package Name:** gdk-pixbuf2 (2.44.1)
- **Origin:** graphics/gdk-pixbuf2
- **Description:** Graphic library for GTK
- **Dependencies:** ['gettext-runtime', 'glib', 'jpeg-turbo', 'libxml2', 'png', 'shared-mime-info', 'tiff']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: gdk-pixbuf2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### gettext-runtime
- **Package Name:** gettext-runtime (1.0_1)
- **Origin:** devel/gettext-runtime
- **Description:** GNU gettext runtime libraries and programs
- **Dependencies:** ['indexinfo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: gettext-runtime
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### gettext-tools
- **Package Name:** gettext-tools (0.22.5)
- **Origin:** devel/gettext-tools
- **Description:** GNU gettext development and translation tools
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### giflib
- **Package Name:** giflib (6.1.3)
- **Origin:** graphics/giflib
- **Description:** Tools and library routines for working with GIF images
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: giflib
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### glib
- **Package Name:** glib (2.88.3,2)
- **Origin:** devel/glib20
- **Description:** Some useful routines of C programming (current stable version)
- **Dependencies:** ['gettext-runtime', 'libffi', 'libiconv', 'pcre2', 'py312-packaging', 'python312']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: glib
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### glib-bootstrap
- **Package Name:** glib-bootstrap (2.88.3,2)
- **Origin:** devel/glib20
- **Description:** Some useful routines of C programming (current stable version)
- **Dependencies:** ['gettext-runtime', 'libffi', 'libiconv', 'pcre2', 'py312-packaging', 'python312']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: glib-bootstrap
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### gmake
- **Package Name:** gmake (4.4.1)
- **Origin:** devel/gmake
- **Description:** GNU version of 'make' utility
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### gmp
- **Package Name:** gmp (6.3.0)
- **Origin:** math/gmp
- **Description:** Free library for arbitrary precision arithmetic
- **Dependencies:** ['indexinfo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: gmp
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### graphite2
- **Package Name:** graphite2 (1.3.15)
- **Origin:** graphics/graphite2
- **Description:** Rendering capabilities for complex non-Roman writing systems
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: graphite2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### help2man
- **Package Name:** help2man (1.49.3_1)
- **Origin:** misc/help2man
- **Description:** Automatically generating simple manual pages from program output
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### hostapd
- **Package Name:** hostapd (2.12_2)
- **Origin:** net/hostapd
- **Description:** IEEE 802.11 AP, IEEE 802.1X/WPA/WPA2/EAP/RADIUS Authenticator
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: hostapd
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### icu
- **Package Name:** icu (76.1,1)
- **Origin:** devel/icu
- **Description:** International Components for Unicode (from IBM)
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: icu
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### if_pppoe-kmod
- **Package Name:** if_pppoe-kmod (2.9.0.1600018_1)
- **Origin:** net/if_pppoe-kmod
- **Description:** PPPoE Kernel Driver
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: if_pppoe-kmod
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### iftop
- **Package Name:** iftop (1.0.p4_1)
- **Origin:** net-mgmt/iftop
- **Description:** Display bandwidth usage on an interface by host
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: iftop
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### igmpproxy
- **Package Name:** igmpproxy (0.4_3,1)
- **Origin:** net/igmpproxy
- **Description:** Multicast forwarding IGMP proxy
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: igmpproxy
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### indexinfo
- **Package Name:** indexinfo (0.3.1_1)
- **Origin:** print/indexinfo
- **Description:** Utility to regenerate the GNU info page index
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** dpkg / apt / libjson-c
- **Technical Justification:** FreeBSD pkg-ng package management infrastructure not required on Debian system.

### ipmitool
- **Package Name:** ipmitool (1.8.19_3)
- **Origin:** sysutils/ipmitool
- **Description:** CLI to manage IPMI systems
- **Dependencies:** ['readline']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: ipmitool
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### isc-dhcp44-client
- **Package Name:** isc-dhcp44-client (4.4.3P1_2)
- **Origin:** net/isc-dhcp44-client
- **Description:** The ISC Dynamic Host Configuration Protocol client
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: isc-dhcp44-client
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### isc-dhcp44-relay
- **Package Name:** isc-dhcp44-relay (4.4.3P1_4)
- **Origin:** net/isc-dhcp44-relay
- **Description:** The ISC Dynamic Host Configuration Protocol relay
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: isc-dhcp44-relay
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### isc-dhcp44-server
- **Package Name:** isc-dhcp44-server (4.4.3P1_5)
- **Origin:** net/isc-dhcp44-server
- **Description:** ISC Dynamic Host Configuration Protocol server
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: isc-dhcp44-server
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### jbigkit
- **Package Name:** jbigkit (2.1_3)
- **Origin:** graphics/jbigkit
- **Description:** Lossless compression for bi-level images such as scanned pages, faxes
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: jbigkit
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### jpeg-turbo
- **Package Name:** jpeg-turbo (3.1.4.1)
- **Origin:** graphics/jpeg-turbo
- **Description:** SIMD-accelerated JPEG codec which replaces libjpeg
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: jpeg-turbo
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### jq
- **Package Name:** jq (1.8.2)
- **Origin:** textproc/jq
- **Description:** Lightweight and flexible command-line JSON processor
- **Dependencies:** ['oniguruma']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: jq
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### jsoncpp
- **Package Name:** jsoncpp (1.9.8)
- **Origin:** devel/jsoncpp
- **Description:** JSON reader and writer library for C++
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: jsoncpp
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### kea
- **Package Name:** kea (3.2.0_1)
- **Origin:** net/kea
- **Description:** Alternative DHCP implementation by ISC
- **Dependencies:** ['boost-libs', 'log4cplus', 'python312']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: kea
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### ldns
- **Package Name:** ldns (1.9.2)
- **Origin:** dns/ldns
- **Description:** Library for programs conforming to DNS RFCs and drafts
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: ldns
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### lerc
- **Package Name:** lerc (4.2.0)
- **Origin:** graphics/lerc
- **Description:** C++ library for Limited Error Raster Compression
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: lerc
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libICE
- **Package Name:** libICE (1.1.2,1)
- **Origin:** x11/libICE
- **Description:** Inter Client Exchange library for X11
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libICE
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libSM
- **Package Name:** libSM (1.2.6,1)
- **Origin:** x11/libSM
- **Description:** Session Management library for X11
- **Dependencies:** ['libICE']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libSM
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libX11
- **Package Name:** libX11 (1.8.13_1,1)
- **Origin:** x11/libX11
- **Description:** Core X11 protocol client library
- **Dependencies:** ['libxcb']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libX11
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libXau
- **Package Name:** libXau (1.0.12)
- **Origin:** x11/libXau
- **Description:** Authentication Protocol library for X11
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libXau
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libXdmcp
- **Package Name:** libXdmcp (1.1.5)
- **Origin:** x11/libXdmcp
- **Description:** X Display Manager Control Protocol library
- **Dependencies:** ['xorgproto']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libXdmcp
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libavif
- **Package Name:** libavif (1.4.2)
- **Origin:** graphics/libavif
- **Description:** Library for encoding and decoding .avif files
- **Dependencies:** ['aom', 'dav1d', 'gdk-pixbuf2', 'gettext-runtime', 'glib', 'jpeg-turbo', 'libyuv', 'png', 'webp']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libavif
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libccid
- **Package Name:** libccid (1.8.2)
- **Origin:** devel/libccid
- **Description:** Generic USB CCID and ICCD driver
- **Dependencies:** ['pcsc-lite']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libccid
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libdeflate
- **Package Name:** libdeflate (1.26)
- **Origin:** archivers/libdeflate
- **Description:** Fast, whole-buffer DEFLATE-based compression library
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libdeflate
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libedit
- **Package Name:** libedit (3.1.20260512,1)
- **Origin:** devel/libedit
- **Description:** Command line editor library
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libedit
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libevent
- **Package Name:** libevent (2.1.13)
- **Origin:** devel/libevent
- **Description:** API for executing callback functions on events or timeouts
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libevent
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libffi
- **Package Name:** libffi (3.8.0)
- **Origin:** devel/libffi
- **Description:** Foreign Function Interface
- **Dependencies:** ['indexinfo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libffi
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libfontenc
- **Package Name:** libfontenc (1.1.9)
- **Origin:** x11-fonts/libfontenc
- **Description:** The fontenc Library
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libfontenc
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libgcrypt
- **Package Name:** libgcrypt (1.12.2)
- **Origin:** security/libgcrypt
- **Description:** General purpose cryptographic library based on the code from GnuPG
- **Dependencies:** ['libgpg-error']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libgcrypt
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libgpg-error
- **Package Name:** libgpg-error (1.61)
- **Origin:** security/libgpg-error
- **Description:** Common error values for all GnuPG components
- **Dependencies:** ['gettext-runtime', 'indexinfo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libgpg-error
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libiconv
- **Package Name:** libiconv (1.18_1)
- **Origin:** converters/libiconv
- **Description:** Character set conversion library
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libiconv
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libidn2
- **Package Name:** libidn2 (2.3.8)
- **Origin:** dns/libidn2
- **Description:** Implementation of IDNA2008 internationalized domain names
- **Dependencies:** ['indexinfo', 'libunistring']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libidn2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libltdl
- **Package Name:** libltdl (2.6.2)
- **Origin:** devel/libltdl
- **Description:** System independent dlopen wrapper
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libltdl
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### liblz4
- **Package Name:** liblz4 (1.10.0_2,1)
- **Origin:** archivers/liblz4
- **Description:** LZ4 compression library, lossless and very fast
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: liblz4
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libmcrypt
- **Package Name:** libmcrypt (2.5.8_4)
- **Origin:** security/libmcrypt
- **Description:** Multi-cipher cryptographic library (used in PHP)
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libmcrypt
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libnghttp2
- **Package Name:** libnghttp2 (1.70.0)
- **Origin:** www/libnghttp2
- **Description:** HTTP/2 C Library
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libnghttp2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libpfctl
- **Package Name:** libpfctl (0.17)
- **Origin:** net/libpfctl
- **Description:** Library for interaction with pf(4)
- **Dependencies:** []
- **Classification:** REPLACE-WITH-LINUX-NATIVE
- **Strategy:** REPLACE
- **Linux Equivalent:** nftables (nft monitor / nft list ruleset) / conntrack / iptstate
- **Technical Justification:** pf(4) state table viewer replaced by netfilter / nftables connection tracking tools.

### libpsl
- **Package Name:** libpsl (0.23.3)
- **Origin:** dns/libpsl
- **Description:** C library to handle the Public Suffix List
- **Dependencies:** ['libidn2', 'libunistring']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libpsl
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libsodium
- **Package Name:** libsodium (1.0.22)
- **Origin:** security/libsodium
- **Description:** Library to build higher-level cryptographic tools
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libsodium
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libssh2
- **Package Name:** libssh2 (1.11.1_1,3)
- **Origin:** security/libssh2
- **Description:** Library implementing the SSH2 protocol
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libssh2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libtextstyle
- **Package Name:** libtextstyle (0.22.5)
- **Origin:** devel/libtextstyle
- **Description:** Text styling library
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### libtool
- **Package Name:** libtool (2.4.7_2)
- **Origin:** devel/libtool
- **Description:** Generic shared library support script
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### libucl
- **Package Name:** libucl (0.9.4)
- **Origin:** textproc/libucl
- **Description:** Universal configuration library parser
- **Dependencies:** ['lua54']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** dpkg / apt / libjson-c
- **Technical Justification:** FreeBSD pkg-ng package management infrastructure not required on Debian system.

### libunistring
- **Package Name:** libunistring (1.4.2)
- **Origin:** devel/libunistring
- **Description:** Unicode string library
- **Dependencies:** ['indexinfo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libunistring
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### liburcu
- **Package Name:** liburcu (0.15.7)
- **Origin:** sysutils/liburcu
- **Description:** Userspace read-copy-update (RCU) data synchronization library
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: liburcu
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libuv
- **Package Name:** libuv (1.52.1)
- **Origin:** devel/libuv
- **Description:** Multi-platform support library with a focus on asynchronous I/O
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libuv
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libxcb
- **Package Name:** libxcb (1.17.0)
- **Origin:** x11/libxcb
- **Description:** The X protocol C-language Binding (XCB) library
- **Dependencies:** ['libXau', 'libXdmcp']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libxcb
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libxml2
- **Package Name:** libxml2 (2.15.4)
- **Origin:** textproc/libxml2
- **Description:** XML parser library for GNOME
- **Dependencies:** ['readline']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libxml2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libxslt
- **Package Name:** libxslt (1.1.45)
- **Origin:** textproc/libxslt
- **Description:** XML stylesheet transformation library
- **Dependencies:** ['libgcrypt', 'libgpg-error', 'libxml2']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libxslt
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### libyuv
- **Package Name:** libyuv (0.0.1903)
- **Origin:** graphics/libyuv
- **Description:** Library for freeswitch yuv graphics manipulation
- **Dependencies:** ['jpeg-turbo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: libyuv
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### links
- **Package Name:** links (2.30_1,1)
- **Origin:** www/links
- **Description:** Lynx-like text WWW browser
- **Dependencies:** ['fontconfig', 'freetype2', 'jpeg-turbo', 'libX11', 'libavif', 'libevent', 'png', 'tiff', 'webp', 'zstd']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: links
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### log4cplus
- **Package Name:** log4cplus (2.2.0.1)
- **Origin:** devel/log4cplus
- **Description:** Logging library for C++
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: log4cplus
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### lua-resty-core
- **Package Name:** lua-resty-core (0.1.28)
- **Origin:** www/lua-resty-core
- **Description:** New FFI-based Lua API for OpenResty NGINX Lua modules
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: lua-resty-core
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### lua-resty-lrucache
- **Package Name:** lua-resty-lrucache (0.13)
- **Origin:** www/lua-resty-lrucache
- **Description:** Lua-land LRU cache based on the LuaJIT FFI
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: lua-resty-lrucache
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### lua54
- **Package Name:** lua54 (5.4.8)
- **Origin:** lang/lua54
- **Description:** Powerful, efficient, lightweight, embeddable scripting language
- **Dependencies:** ['libedit']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: lua54
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### luajit-openresty
- **Package Name:** luajit-openresty (2.1.20240314)
- **Origin:** lang/luajit-openresty
- **Description:** Just-In-Time Compiler for Lua (OpenResty branch)
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: luajit-openresty
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### lzo2
- **Package Name:** lzo2 (2.10_2)
- **Origin:** archivers/lzo2
- **Description:** Portable speedy, lossless data compression library
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: lzo2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### m4
- **Package Name:** m4 (1.4.19_1,1)
- **Origin:** devel/m4
- **Description:** GNU M4
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### meson
- **Package Name:** meson (1.4.1)
- **Origin:** devel/meson
- **Description:** High performance build system
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### minicron
- **Package Name:** minicron (0.0.2)
- **Origin:** sysutils/minicron
- **Description:** very small cron
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### miniupnpd
- **Package Name:** miniupnpd (2.3.9_1,1)
- **Origin:** net/miniupnpd
- **Description:** Lightweight UPnP IGD & PCP/NAT-PMP daemon which uses pf
- **Dependencies:** ['libpfctl']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: miniupnpd
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### mobile-broadband-provider-info
- **Package Name:** mobile-broadband-provider-info (20251101)
- **Origin:** net/mobile-broadband-provider-info
- **Description:** Service mobile broadband provider database
- **Dependencies:** ['libxml2', 'libxslt']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: mobile-broadband-provider-info
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### mpd5
- **Package Name:** mpd5 (5.9_19)
- **Origin:** net/mpd5
- **Description:** Multi-link PPP daemon based on netgraph(4)
- **Dependencies:** []
- **Classification:** REPLACE-WITH-LINUX-NATIVE
- **Strategy:** REPLACE
- **Linux Equivalent:** accel-ppp / pppd / xl2tpd / strongSwan
- **Technical Justification:** FreeBSD Netgraph PPP daemon replaced by Linux native accel-ppp and pppd stack.

### mpdecimal
- **Package Name:** mpdecimal (4.0.1)
- **Origin:** math/mpdecimal
- **Description:** C/C++ arbitrary precision decimal floating point libraries
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: mpdecimal
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### nettle
- **Package Name:** nettle (3.10.2)
- **Origin:** security/nettle
- **Description:** Low-level cryptographic library
- **Dependencies:** ['gmp', 'indexinfo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: nettle
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### nginx
- **Package Name:** nginx (1.30.5,3)
- **Origin:** www/nginx
- **Description:** Robust and small WWW server
- **Dependencies:** ['pcre2']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: nginx
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### ninja
- **Package Name:** ninja (1.11.1,4)
- **Origin:** devel/ninja
- **Description:** Small build system closest in spirit to Make
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### nss_ldap
- **Package Name:** nss_ldap (1.265_15)
- **Origin:** net/nss_ldap
- **Description:** RFC 2307 NSS module
- **Dependencies:** ['openldap26-client']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: nss_ldap
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### ntp
- **Package Name:** ntp (4.2.8p18_6)
- **Origin:** net/ntp
- **Description:** The Network Time Protocol Distribution
- **Dependencies:** ['gettext-runtime', 'libedit', 'libevent', 'perl5']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: ntp
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### oniguruma
- **Package Name:** oniguruma (6.9.10)
- **Origin:** devel/oniguruma
- **Description:** Regular expressions library compatible with POSIX/GNU/Perl
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: oniguruma
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### openldap26-client
- **Package Name:** openldap26-client (2.6.15_1)
- **Origin:** databases/openldap26-client
- **Description:** Open source LDAP client implementation
- **Dependencies:** ['cyrus-sasl']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: openldap26-client
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### opensc
- **Package Name:** opensc (0.27.0)
- **Origin:** security/opensc
- **Description:** Libraries and utilities to access smart cards
- **Dependencies:** ['gettext-runtime', 'glib', 'pcsc-lite']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: opensc
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### openvpn
- **Package Name:** openvpn (2.7.7)
- **Origin:** security/openvpn
- **Description:** Secure IP/Ethernet tunnel daemon
- **Dependencies:** ['easy-rsa', 'liblz4', 'lzo2', 'pkcs11-helper']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: openvpn
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### openvpn-auth-script
- **Package Name:** openvpn-auth-script (1.0.0.3)
- **Origin:** security/openvpn-auth-script
- **Description:** Generic script-based deferred auth plugin for OpenVPN
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### p5-Locale-gettext
- **Package Name:** p5-Locale-gettext (1.07)
- **Origin:** devel/p5-Locale-gettext
- **Description:** Message handling functions
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### p5-Locale-libintl
- **Package Name:** p5-Locale-libintl (1.33)
- **Origin:** devel/p5-Locale-libintl
- **Description:** Internationalization library for Perl
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### p5-Text-Unidecode
- **Package Name:** p5-Text-Unidecode (1.30)
- **Origin:** converters/p5-Text-Unidecode
- **Description:** US-ASCII transliterations of Unicode text
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### p5-Unicode-EastAsianWidth
- **Package Name:** p5-Unicode-EastAsianWidth (12.0)
- **Origin:** textproc/p5-Unicode-EastAsianWidth
- **Description:** East Asian Width properties
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### pam_ldap
- **Package Name:** pam_ldap (186_2)
- **Origin:** security/pam_ldap
- **Description:** PAM module for authenticating with LDAP
- **Dependencies:** ['openldap26-client']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: pam_ldap
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### pam_mkhomedir
- **Package Name:** pam_mkhomedir (0.2_1)
- **Origin:** security/pam_mkhomedir
- **Description:** Create HOME with a PAM module on demand
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: pam_mkhomedir
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### pcre2
- **Package Name:** pcre2 (10.48)
- **Origin:** devel/pcre2
- **Description:** Perl Compatible Regular Expressions library, version 2
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: pcre2
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### pcsc-lite
- **Package Name:** pcsc-lite (2.5.2,2)
- **Origin:** devel/pcsc-lite
- **Description:** Middleware library to access a smart card using SCard API (PC/SC)
- **Dependencies:** ['polkit']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: pcsc-lite
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### perl5
- **Package Name:** perl5 (5.42.3)
- **Origin:** lang/perl5.42
- **Description:** Practical Extraction and Report Language
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: perl5
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### pftop
- **Package Name:** pftop (0.13)
- **Origin:** sysutils/pftop
- **Description:** Utility for real-time display of statistics for pf
- **Dependencies:** ['libpfctl']
- **Classification:** REPLACE-WITH-LINUX-NATIVE
- **Strategy:** REPLACE
- **Linux Equivalent:** nftables (nft monitor / nft list ruleset) / conntrack / iptstate
- **Technical Justification:** pf(4) state table viewer replaced by netfilter / nftables connection tracking tools.

### php85
- **Package Name:** php85 (8.5.10)
- **Origin:** lang/php85
- **Description:** PHP Scripting Language (8.5.X branch)
- **Dependencies:** ['libxml2', 'pcre2']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-bcmath
- **Package Name:** php85-bcmath (8.5.10)
- **Origin:** math/php85-bcmath
- **Description:** The bcmath shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-bz2
- **Package Name:** php85-bz2 (8.5.10)
- **Origin:** archivers/php85-bz2
- **Description:** The bz2 shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-ctype
- **Package Name:** php85-ctype (8.5.10)
- **Origin:** textproc/php85-ctype
- **Description:** The ctype shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-curl
- **Package Name:** php85-curl (8.5.10)
- **Origin:** ftp/php85-curl
- **Description:** The curl shared extension for php
- **Dependencies:** ['curl', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-dom
- **Package Name:** php85-dom (8.5.10)
- **Origin:** textproc/php85-dom
- **Description:** The dom shared extension for php
- **Dependencies:** ['libxml2', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-filter
- **Package Name:** php85-filter (8.5.10)
- **Origin:** security/php85-filter
- **Description:** The filter shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-gettext
- **Package Name:** php85-gettext (8.5.10)
- **Origin:** devel/php85-gettext
- **Description:** The gettext shared extension for php
- **Dependencies:** ['gettext-runtime', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-gmp
- **Package Name:** php85-gmp (8.5.10)
- **Origin:** math/php85-gmp
- **Description:** The gmp shared extension for php
- **Dependencies:** ['gmp', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-intl
- **Package Name:** php85-intl (8.5.10)
- **Origin:** devel/php85-intl
- **Description:** The intl shared extension for php
- **Dependencies:** ['icu', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-ldap
- **Package Name:** php85-ldap (8.5.10)
- **Origin:** net/php85-ldap
- **Description:** The ldap shared extension for php
- **Dependencies:** ['cyrus-sasl', 'openldap26-client', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-mbstring
- **Package Name:** php85-mbstring (8.5.10)
- **Origin:** converters/php85-mbstring
- **Description:** The mbstring shared extension for php
- **Dependencies:** ['oniguruma', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-openssl_x509_crl
- **Package Name:** php85-openssl_x509_crl (1.3_3)
- **Origin:** security/php-openssl_x509_crl
- **Description:** PHP Class to create openssl Certificate Revocation List (CRL)
- **Dependencies:** ['php85', 'php85-bcmath']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pcntl
- **Package Name:** php85-pcntl (8.5.10)
- **Origin:** devel/php85-pcntl
- **Description:** The pcntl shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pdo
- **Package Name:** php85-pdo (8.5.10)
- **Origin:** databases/php85-pdo
- **Description:** The pdo shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pdo_sqlite
- **Package Name:** php85-pdo_sqlite (8.5.10)
- **Origin:** databases/php85-pdo_sqlite
- **Description:** The pdo_sqlite shared extension for php
- **Dependencies:** ['php85', 'php85-pdo', 'sqlite3']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear
- **Package Name:** php85-pear (1.10.18)
- **Origin:** devel/pear
- **Description:** PEAR framework for PHP
- **Dependencies:** ['php85', 'php85-xml', 'php85-zlib']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear-Auth_RADIUS
- **Package Name:** php85-pear-Auth_RADIUS (1.1.0_5)
- **Origin:** net/pear-Auth_RADIUS
- **Description:** PEAR wrapper classes for the RADIUS PECL
- **Dependencies:** ['php85', 'php85-pear', 'php85-pecl-radius']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear-Cache_Lite
- **Package Name:** php85-pear-Cache_Lite (1.8.3,1)
- **Origin:** sysutils/pear-Cache_Lite
- **Description:** Fast and Safe little cache system
- **Dependencies:** ['php85', 'php85-pear']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear-Crypt_CHAP
- **Package Name:** php85-pear-Crypt_CHAP (1.5.0_2)
- **Origin:** security/pear-Crypt_CHAP
- **Description:** PEAR class for generating CHAP packets
- **Dependencies:** ['php85', 'php85-pear', 'php85-pecl-mcrypt']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear-HTTP_Request2
- **Package Name:** php85-pear-HTTP_Request2 (2.7.0,1)
- **Origin:** www/pear-HTTP_Request2
- **Description:** PEAR classes providing an easy way to perform HTTP requests
- **Dependencies:** ['php85', 'php85-pear', 'php85-pear-Net_URL2']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear-Mail
- **Package Name:** php85-pear-Mail (2.0.0,1)
- **Origin:** mail/pear-Mail
- **Description:** PEAR class that provides multiple interfaces for sending emails
- **Dependencies:** ['php85', 'php85-pear']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear-Net_IPv6
- **Package Name:** php85-pear-Net_IPv6 (1.3.0.b4_2)
- **Origin:** net/pear-Net_IPv6
- **Description:** Check and validate IPv6 addresses
- **Dependencies:** ['php85', 'php85-pear']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear-Net_URL2
- **Package Name:** php85-pear-Net_URL2 (2.2.3)
- **Origin:** net/pear-Net_URL2
- **Description:** PEAR Class for parsing and handling URL
- **Dependencies:** ['php85', 'php85-pear']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pear-XML_RPC2
- **Package Name:** php85-pear-XML_RPC2 (1.1.5)
- **Origin:** net/pear-XML_RPC2
- **Description:** XML-RPC client/server library
- **Dependencies:** ['php85', 'php85-curl', 'php85-pear', 'php85-pear-Cache_Lite', 'php85-pear-HTTP_Request2']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pecl-mcrypt
- **Package Name:** php85-pecl-mcrypt (1.0.9)
- **Origin:** security/pecl-mcrypt
- **Description:** PHP extension for mcrypt, removed in PHP 7.2
- **Dependencies:** ['libltdl', 'libmcrypt', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pecl-radius
- **Package Name:** php85-pecl-radius (1.4.0b1_6)
- **Origin:** net/pecl-radius
- **Description:** Radius client library for PHP
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-pecl-rrd
- **Package Name:** php85-pecl-rrd (2.0.4)
- **Origin:** databases/pecl-rrd
- **Description:** PHP bindings to rrd tool system
- **Dependencies:** ['php85', 'rrdtool']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-phpseclib
- **Package Name:** php85-phpseclib (2.0.17)
- **Origin:** security/phpseclib
- **Description:** PHP arbitrary-precision integer arithmetic library
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-posix
- **Package Name:** php85-posix (8.5.10)
- **Origin:** sysutils/php85-posix
- **Description:** The posix shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-readline
- **Package Name:** php85-readline (8.5.10)
- **Origin:** devel/php85-readline
- **Description:** The readline shared extension for php
- **Dependencies:** ['libedit', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-session
- **Package Name:** php85-session (8.5.10)
- **Origin:** www/php85-session
- **Description:** The session shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-shmop
- **Package Name:** php85-shmop (8.5.10)
- **Origin:** devel/php85-shmop
- **Description:** The shmop shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-simplexml
- **Package Name:** php85-simplexml (8.5.10)
- **Origin:** textproc/php85-simplexml
- **Description:** The simplexml shared extension for php
- **Dependencies:** ['libxml2', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-sockets
- **Package Name:** php85-sockets (8.5.10)
- **Origin:** net/php85-sockets
- **Description:** The sockets shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-sqlite3
- **Package Name:** php85-sqlite3 (8.5.10)
- **Origin:** databases/php85-sqlite3
- **Description:** The sqlite3 shared extension for php
- **Dependencies:** ['php85', 'sqlite3']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-sysvmsg
- **Package Name:** php85-sysvmsg (8.5.10)
- **Origin:** devel/php85-sysvmsg
- **Description:** The sysvmsg shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-sysvsem
- **Package Name:** php85-sysvsem (8.5.10)
- **Origin:** devel/php85-sysvsem
- **Description:** The sysvsem shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-sysvshm
- **Package Name:** php85-sysvshm (8.5.10)
- **Origin:** devel/php85-sysvshm
- **Description:** The sysvshm shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-tokenizer
- **Package Name:** php85-tokenizer (8.5.10)
- **Origin:** devel/php85-tokenizer
- **Description:** The tokenizer shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-xml
- **Package Name:** php85-xml (8.5.10)
- **Origin:** textproc/php85-xml
- **Description:** The xml shared extension for php
- **Dependencies:** ['libxml2', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-xmlreader
- **Package Name:** php85-xmlreader (8.5.10)
- **Origin:** textproc/php85-xmlreader
- **Description:** The xmlreader shared extension for php
- **Dependencies:** ['libxml2', 'php85', 'php85-dom']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-xmlwriter
- **Package Name:** php85-xmlwriter (8.5.10)
- **Origin:** textproc/php85-xmlwriter
- **Description:** The xmlwriter shared extension for php
- **Dependencies:** ['libxml2', 'php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### php85-zlib
- **Package Name:** php85-zlib (8.5.10)
- **Origin:** archivers/php85-zlib
- **Description:** The zlib shared extension for php
- **Dependencies:** ['php85']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** MitraNet Native Python / FastAPI Core Engine
- **Technical Justification:** Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI.

### pkcs11-helper
- **Package Name:** pkcs11-helper (1.31.0)
- **Origin:** security/pkcs11-helper
- **Description:** Helper library for multiple PKCS#11 providers
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: pkcs11-helper
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### pkg
- **Package Name:** pkg (2.8.4)
- **Origin:** ports-mgmt/pkg
- **Description:** Package manager
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** dpkg / apt / libjson-c
- **Technical Justification:** FreeBSD pkg-ng package management infrastructure not required on Debian system.

### pkgconf
- **Package Name:** pkgconf (2.2.0,1)
- **Origin:** devel/pkgconf
- **Description:** Utility to help to configure compiler and linker flags
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### png
- **Package Name:** png (1.6.58)
- **Origin:** graphics/png
- **Description:** Library for manipulating PNG images
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: png
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### polkit
- **Package Name:** polkit (127)
- **Origin:** sysutils/polkit
- **Description:** Framework for controlling access to system-wide components
- **Dependencies:** ['dbus', 'duktape-lib', 'expat', 'gettext-runtime', 'glib']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: polkit
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### protobuf
- **Package Name:** protobuf (29.6,1)
- **Origin:** devel/protobuf
- **Description:** Data interchange format library
- **Dependencies:** ['abseil', 'jsoncpp']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: protobuf
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### protobuf-c
- **Package Name:** protobuf-c (1.5.1_4)
- **Origin:** devel/protobuf-c
- **Description:** Code generator and libraries to use Protocol Buffers from pure C
- **Dependencies:** ['abseil', 'protobuf']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: protobuf-c
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### public_suffix_list
- **Package Name:** public_suffix_list (20240531)
- **Origin:** dns/public_suffix_list
- **Description:** Public Suffix List by Mozilla
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### py311-build
- **Package Name:** py311-build (1.2.1)
- **Origin:** devel/py-build
- **Description:** PEP517 package builder
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### py311-flit-core
- **Package Name:** py311-flit-core (3.9.0)
- **Origin:** devel/py-flit-core
- **Description:** Distribution-building parts of Flit
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### py311-installer
- **Package Name:** py311-installer (0.7.0)
- **Origin:** devel/py-installer
- **Description:** Library for installing Python wheels
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### py311-packaging
- **Package Name:** py311-packaging (24.1)
- **Origin:** devel/py-packaging
- **Description:** Core utilities for Python packages
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### py311-pyproject_hooks
- **Package Name:** py311-pyproject_hooks (1.1.0)
- **Origin:** devel/py-pyproject_hooks
- **Description:** Wrappers to call pyproject.toml-based build backend hooks
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### py311-setuptools
- **Package Name:** py311-setuptools (63.1.0_1)
- **Origin:** devel/py-setuptools
- **Description:** Python packages installer
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### py311-wheel
- **Package Name:** py311-wheel (0.43.0)
- **Origin:** devel/py-wheel
- **Description:** Built-package format for Python
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### py312-packaging
- **Package Name:** py312-packaging (26.3)
- **Origin:** devel/py-packaging
- **Description:** Core utilities for Python packages
- **Dependencies:** ['python312']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: py312-packaging
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### python3
- **Package Name:** python3 (3_4)
- **Origin:** lang/python3
- **Description:** Meta-port for the Python interpreter 3.x
- **Dependencies:** ['python312']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: python3
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### python311
- **Package Name:** python311 (3.11.16)
- **Origin:** lang/python311
- **Description:** Interpreted object-oriented programming language
- **Dependencies:** ['expat', 'gettext-runtime', 'libffi', 'mpdecimal', 'readline']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: python311
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### python312
- **Package Name:** python312 (3.12.14_1)
- **Origin:** lang/python312
- **Description:** Interpreted object-oriented programming language
- **Dependencies:** ['expat', 'gettext-runtime', 'libffi', 'mpdecimal', 'readline']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: python312
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### qstats
- **Package Name:** qstats (0.2)
- **Origin:** sysutils/qstats
- **Description:** read dhpcd.lease file and add it to hosts file
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### radvd
- **Package Name:** radvd (2.20)
- **Origin:** net/radvd
- **Description:** Linux/BSD IPv6 router advertisement daemon
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: radvd
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### rate
- **Package Name:** rate (0.9_4)
- **Origin:** net-mgmt/rate
- **Description:** Traffic analysis command-line utility
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### readline
- **Package Name:** readline (8.3.6)
- **Origin:** devel/readline
- **Description:** Library for editing command lines as they are typed
- **Dependencies:** ['indexinfo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: readline
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### rhash
- **Package Name:** rhash (1.4.4_1)
- **Origin:** security/rhash
- **Description:** Utility and library for computing and checking of file hashes
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### rrdtool
- **Package Name:** rrdtool (1.9.0_1)
- **Origin:** databases/rrdtool
- **Description:** Round Robin Database Tools
- **Dependencies:** ['gettext-runtime', 'glib', 'libxml2', 'perl5']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: rrdtool
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### scponly
- **Package Name:** scponly (4.8.20110526_8)
- **Origin:** shells/scponly
- **Description:** Tiny shell that only permits scp and sftp
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** rssh / internal-sftp ChrootDirectory
- **Technical Justification:** Legacy restricted shell; OpenSSH native internal-sftp ChrootDirectory is standard.

### shared-mime-info
- **Package Name:** shared-mime-info (2.4_2)
- **Origin:** misc/shared-mime-info
- **Description:** MIME types database from the freedesktop.org project
- **Dependencies:** ['gettext-runtime', 'glib', 'libxml2']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: shared-mime-info
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### smartmontools
- **Package Name:** smartmontools (7.5_2)
- **Origin:** sysutils/smartmontools
- **Description:** S.M.A.R.T. disk monitoring tools
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: smartmontools
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### sqlite3
- **Package Name:** sqlite3 (3.53.4,1)
- **Origin:** databases/sqlite3
- **Description:** SQL database engine in a C library
- **Dependencies:** ['libedit']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: sqlite3
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### ssh_tunnel_shell
- **Package Name:** ssh_tunnel_shell (0.2_2)
- **Origin:** sysutils/ssh_tunnel_shell
- **Description:** SSH tunnel shell
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### sshguard
- **Package Name:** sshguard (2.5.1_2,1)
- **Origin:** security/sshguard
- **Description:** Protect hosts from brute-force attacks against SSH and other services
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: sshguard
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### strongswan
- **Package Name:** strongswan (6.1.0_1)
- **Origin:** security/strongswan
- **Description:** Open Source IKEv2 IPsec-based VPN solution
- **Dependencies:** ['curl', 'ldns', 'unbound', 'vstr']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: strongswan
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### swig
- **Package Name:** swig (4.1.1)
- **Origin:** devel/swig
- **Description:** Generate wrappers for calling C/C++ code from other languages
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### tcl86
- **Package Name:** tcl86 (8.6.18_1)
- **Origin:** lang/tcl86
- **Description:** Tool Command Language
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: tcl86
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### texinfo
- **Package Name:** texinfo (7.1_2,1)
- **Origin:** print/texinfo
- **Description:** Typeset documentation system with multiple format output
- **Dependencies:** []
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** Debian build-essential / upstream apt build dependencies
- **Technical Justification:** Upstream build/packaging toolchain not needed in runtime firewall router image.

### tiff
- **Package Name:** tiff (4.7.2)
- **Origin:** graphics/tiff
- **Description:** Tools and library routines for working with TIFF images
- **Dependencies:** ['jbigkit', 'jpeg-turbo', 'lerc', 'libdeflate', 'zstd']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: tiff
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### uclcmd
- **Package Name:** uclcmd (0.2.20211204)
- **Origin:** devel/uclcmd
- **Description:** Command line tool for working with UCL config files
- **Dependencies:** ['libucl']
- **Classification:** NOT-REQUIRED
- **Strategy:** EXCLUDE
- **Linux Equivalent:** dpkg / apt / libjson-c
- **Technical Justification:** FreeBSD pkg-ng package management infrastructure not required on Debian system.

### unbound
- **Package Name:** unbound (1.26.1)
- **Origin:** dns/unbound
- **Description:** Validating, recursive, and caching DNS resolver
- **Dependencies:** ['expat', 'libevent', 'libnghttp2', 'libsodium']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: unbound
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### unzip
- **Package Name:** unzip (6.0_8)
- **Origin:** archivers/unzip
- **Description:** List, test, and extract compressed files from a ZIP archive
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: unzip
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### vmaf
- **Package Name:** vmaf (3.2.1)
- **Origin:** multimedia/vmaf
- **Description:** Perceptual video quality assessment based on multi-method fusion
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: vmaf
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### voucher
- **Package Name:** voucher (0.1_3)
- **Origin:** sysutils/voucher
- **Description:** Voucher support
- **Dependencies:** []
- **Classification:** REIMPLEMENT-NATIVELY
- **Strategy:** REIMPLEMENT
- **Linux Equivalent:** MitraNet Core Services / nftables / systemd-journald / Python daemons
- **Technical Justification:** pfSense BSD helper daemon; replaced by native MitraNet systemd/nftables/Python service.

### vstr
- **Package Name:** vstr (1.0.15_2)
- **Origin:** devel/vstr
- **Description:** General purpose string library for C
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: vstr
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### webp
- **Package Name:** webp (1.6.0)
- **Origin:** graphics/webp
- **Description:** Google WebP image format conversion tool
- **Dependencies:** ['giflib', 'jpeg-turbo', 'png', 'tiff']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: webp
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### whois
- **Package Name:** whois (5.5.7_1)
- **Origin:** net/whois
- **Description:** Marco d'Itri whois client
- **Dependencies:** ['gettext-runtime', 'libidn2']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: whois
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### wol
- **Package Name:** wol (0.7.1_5)
- **Origin:** net/wol
- **Description:** Tool to wake up Wake-On-LAN compliant computers
- **Dependencies:** ['gettext-runtime', 'indexinfo']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: wol
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### wpa_supplicant
- **Package Name:** wpa_supplicant (2.12_1)
- **Origin:** security/wpa_supplicant
- **Description:** Supplicant (client) for WPA/802.1x protocols
- **Dependencies:** ['dbus', 'readline']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: wpa_supplicant
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### wrapalixresetbutton
- **Package Name:** wrapalixresetbutton (0.0.16)
- **Origin:** sysutils/wrapalixresetbutton
- **Description:** Utility to detect platform reset button state for use in scripting
- **Dependencies:** []
- **Classification:** INCOMPATIBLE / UNSUITABLE
- **Strategy:** EXCLUDE
- **Linux Equivalent:** N/A (Obsolete PC Engines ALIX hardware)
- **Technical Justification:** Legacy PC Engines ALIX hardware-specific GPIO reset button utility; obsolete hardware.

### xinetd
- **Package Name:** xinetd (2.3.15_3)
- **Origin:** security/xinetd
- **Description:** Replacement for inetd with better control and logging
- **Dependencies:** ['perl5']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: xinetd
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### xmlstarlet
- **Package Name:** xmlstarlet (1.6.1_4)
- **Origin:** textproc/xmlstarlet
- **Description:** Command Line XML Toolkit
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: xmlstarlet
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### xorgproto
- **Package Name:** xorgproto (2025.1)
- **Origin:** x11/xorgproto
- **Description:** X Window System unified protocol definitions
- **Dependencies:** []
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: xorgproto
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.

### zstd
- **Package Name:** zstd (1.5.7_2)
- **Origin:** archivers/zstd
- **Description:** Fast real-time compression algorithm
- **Dependencies:** ['liblz4']
- **Classification:** DIRECT-DEBIAN-EQUIVALENT
- **Strategy:** DIRECT-DEBIAN
- **Linux Equivalent:** Debian package: zstd
- **Technical Justification:** Directly provided by Debian GNU/Linux 13 Trixie repositories.
