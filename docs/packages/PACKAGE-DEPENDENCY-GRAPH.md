# MITRANET — PACKAGE DEPENDENCY GRAPH

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Summary Statistics
- Total Package Store Items: 204
- Packages with Explicit Dependencies: 113
- Leaf Dependencies (packages that depend on nothing else): 91
- Shared/Core Dependencies (depended upon by 5+ packages): 10
- Circular Dependencies Detected: 0 (Acyclic DAG)

## 2. Shared Critical Dependencies
| Dependency Name | Consuming Packages Count | Consumers Sample |
|---|---|---|
| `php85` | 46 | mitranet-Status_Monitoring-php85, mitranet-system, php85-bcmath, php85-bz2... |
| `gettext-runtime` | 14 | dnsmasq, glib, glib-bootstrap, libavif... |
| `libxml2` | 12 | dbus, libxslt, mitranet-system, mobile-broadband-provider-info... |
| `indexinfo` | 9 | gettext-runtime, gmp, libffi, libgpg-error... |
| `php85-pear` | 8 | php85-pear-Auth_RADIUS, php85-pear-Cache_Lite, php85-pear-Crypt_CHAP, php85-pear-HTTP_Request2... |
| `libedit` | 5 | bind-tools, lua54, ntp, php85-readline... |
| `libidn2` | 5 | bind-tools, curl, dnsmasq, libpsl... |
| `libevent` | 5 | check_reload_status, fstrm, links, ntp... |
| `python312` | 5 | glib, glib-bootstrap, kea, py312-packaging... |
| `glib` | 5 | libavif, opensc, polkit, rrdtool... |
| `expat` | 4 | dbus, polkit, python312, unbound... |
| `pcre2` | 4 | glib, glib-bootstrap, nginx, php85... |
| `readline` | 4 | ipmitool, libxml2, python312, wpa_supplicant... |
| `libnghttp2` | 3 | bind-tools, curl, unbound... |
| `zstd` | 3 | boost-libs, curl, links... |

## 3. Package Dependency Relationships

### `bind-tools` (9.20.29)
- **Role**: daemon/service | **Category**: SERVICES
- **Direct Dependencies**:
  - `fstrm` (0.6.1_1)
  - `libedit` (3.1.20260512,1)
  - `libidn2` (2.3.8)
  - `libnghttp2` (1.70.0)
  - `liburcu` (0.15.7)
  - `libuv` (1.52.1)
  - `protobuf-c` (1.5.1_4)

### `boost-libs` (1.91.0)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `icu` (76.1,1)
  - `zstd` (1.5.7_2)

### `check_reload_status` (0.0.17)
- **Role**: daemon/service | **Category**: MANAGEMENT
- **Direct Dependencies**:
  - `libevent` (2.1.12)

### `cpu-microcode` (1.0_1)
- **Role**: firmware | **Category**: DRIVER/FIRMWARE
- **Direct Dependencies**:
  - `cpu-microcode-amd` (20251202)
  - `cpu-microcode-intel` (20260812)

### `cpu-microcode-amd` (20251202)
- **Role**: firmware | **Category**: DRIVER/FIRMWARE
- **Direct Dependencies**:
  - `cpu-microcode-rc` (1.0_2)

### `curl` (8.22.0)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `brotli` (1.2.0,1)
  - `libidn2` (2.3.8)
  - `libnghttp2` (1.70.0)
  - `libpsl` (0.23.3)
  - `libssh2` (1.11.1_1,3)
  - `zstd` (1.5.7_2)

### `dbus` (1.16.2_4,1)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `expat` (2.8.4)
  - `libICE` (1.1.2,1)
  - `libSM` (1.2.6,1)
  - `libX11` (1.8.13_1,1)
  - `libxml2` (2.15.4)

### `dnsmasq` (2.93,1)
- **Role**: daemon/service | **Category**: SERVICES
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `gmp` (6.3.0)
  - `libidn2` (2.3.8)
  - `nettle` (3.10.2)

### `fstrm` (0.6.1_1)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `libevent` (2.1.12)

### `gettext-runtime` (1.0_1)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `indexinfo` (0.3.1_1)

### `glib` (2.88.3,2)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `libffi` (3.8.0)
  - `libiconv` (1.18_1)
  - `pcre2` (10.48)
  - `py312-packaging` (26.3)
  - `python312` (3.12.14_1)

### `glib-bootstrap` (2.88.3,2)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `libffi` (3.8.0)
  - `libiconv` (1.18_1)
  - `pcre2` (10.48)
  - `py312-packaging` (26.3)
  - `python312` (3.12.14_1)

### `gmp` (6.3.0)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `indexinfo` (0.3.1_1)

### `ipmitool` (1.8.19_3)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `readline` (8.3.6)

### `jq` (1.8.2)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `oniguruma` (6.9.10)

### `kea` (3.2.0_1)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `boost-libs` (1.91.0)
  - `log4cplus` (2.2.0.1)
  - `python312` (3.12.14_1)

### `libSM` (1.2.6,1)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `libICE` (1.1.2,1)

### `libXdmcp` (1.1.5)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `xorgproto` (2025.1)

### `libavif` (1.4.2)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `aom` (3.15.1)
  - `dav1d` (1.5.4)
  - `gdk-pixbuf2` (2.44.1)
  - `gettext-runtime` (1.0_1)
  - `glib` (2.88.3,2)
  - `jpeg-turbo` (3.1.4.1)
  - `libyuv` (0.0.1903)
  - `png` (1.6.58)
  - `webp` (1.6.0)

### `libccid` (1.8.2)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `pcsc-lite` (2.5.2,2)

### `libffi` (3.8.0)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `indexinfo` (0.3.1_1)

### `libgcrypt` (1.12.2)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `libgpg-error` (1.61)

### `libgpg-error` (1.61)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `indexinfo` (0.3.1_1)

### `libidn2` (2.3.8)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `indexinfo` (0.3.1_1)
  - `libunistring` (1.4.2)

### `libpsl` (0.23.3)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `libidn2` (2.3.8)
  - `libunistring` (1.4.2)

### `libucl` (0.9.4)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `lua54` (5.4.8)

### `libunistring` (1.4.2)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `indexinfo` (0.3.1_1)

### `libxml2` (2.15.4)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `readline` (8.3.6)

### `libxslt` (1.1.45)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `libgcrypt` (1.12.4)
  - `libgpg-error` (1.61)
  - `libxml2` (2.15.4)

### `libyuv` (0.0.1903)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `jpeg-turbo` (3.1.4.1)

### `links` (2.30_1,1)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `fontconfig` (2.17.1,1)
  - `freetype2` (2.14.3)
  - `jpeg-turbo` (3.1.4.1)
  - `libX11` (1.8.13_1,1)
  - `libavif` (1.4.2)
  - `libevent` (2.1.13)
  - `png` (1.6.58)
  - `tiff` (4.7.2)
  - `webp` (1.6.0)
  - `zstd` (1.5.7_2)

### `lua54` (5.4.8)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `libedit` (3.1.20260512,1)

### `miniupnpd` (2.3.9_1,1)
- **Role**: daemon/service | **Category**: FIREWALL
- **Direct Dependencies**:
  - `libpfctl` (0.17)

### `mitranet` (1.0.0)
- **Role**: composite/meta package | **Category**: BASE
- **Direct Dependencies**:
  - `mitranet-system` (1.0.0)

### `mitranet-Status_Monitoring-php85` (1.9)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `php85` (8.5.7)
  - `php85-pecl-rrd` (2.0.3_1)

### `mitranet-kernel-debian` (1.0.0)
- **Role**: configuration | **Category**: BASE
- **Direct Dependencies**:
  - `mitranet-boot` (1.0.0)

### `mitranet-repoc` (20260919.051732)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `libucl` (0.9.4)

### `mitranet-system` (1.0.0)
- **Role**: configuration | **Category**: BASE
- **Direct Dependencies**:
  - `beep` (1.0_2)
  - `bind-tools` (9.20.23)
  - `bsnmp-regex` (0.6_4)
  - `bsnmp-ucd` (0.4.5_1)
  - `bwi-firmware-kmod` (3.130.20.1600018)
  - `ca_root_nss` (3.124)
  - `check_reload_status` (0.0.17)
  - `choparp` (20150613_1)
  - `cpdup` (1.22_1)
  - `cpu-microcode` (1.0_1)
  - `cpustats` (0.1_1)
  - `dhcp6` (20080615.2_4)
  - `dhcpcd` (10.3.2)
  - `dhcpleases` (0.5_2)
  - `dhcpleases6` (0.1_4)
  - `dmidecode` (3.7)
  - `dnsmasq` (2.93,1)
  - `dpinger` (3.6)
  - `expiretable` (0.6_3)
  - `filterdns` (2.3)
  - `filterlog` (0.1_11)
  - `hostapd` (2.11_3)
  - `if_pppoe-kmod` (1.0.0.1600018_1)
  - `iftop` (1.0.p4_1)
  - `igmpproxy` (0.4_3,1)
  - `ipmitool` (1.8.19_3)
  - `isc-dhcp44-client` (4.4.3P1_2)
  - `isc-dhcp44-relay` (4.4.3P1_4)
  - `isc-dhcp44-server` (4.4.3P1_5)
  - `jq` (1.8.1)
  - `kea` (3.0.4)
  - `libccid` (1.7.1)
  - `libltdl` (2.5.4)
  - `libxml2` (2.15.3)
  - `links` (2.30_1,1)
  - `minicron` (0.0.2)
  - `miniupnpd` (2.3.9_1,1)
  - `mobile-broadband-provider-info` (20251101)
  - `mpd5` (5.9_18)
  - `nginx` (1.30.4,3)
  - `nss_ldap` (1.265_15)
  - `ntp` (4.2.8p18_5)
  - `opensc` (0.27.0)
  - `openvpn` (2.7.5)
  - `openvpn-auth-script` (1.0.0.3)
  - `pam_ldap` (186_2)
  - `pam_mkhomedir` (0.2_1)
  - `mitranet-Status_Monitoring-php85` (1.9)
  - `mitranet-composer-deps` (0.5_1)
  - `mitranet-gnid` (0.21)
  - `mitranet-upgrade` (1.3.40)
  - `pftop` (0.13)
  - `php85` (8.5.7)
  - `php85-bcmath` (8.5.7)
  - `php85-bz2` (8.5.7)
  - `php85-ctype` (8.5.7)
  - `php85-curl` (8.5.7)
  - `php85-dom` (8.5.7)
  - `php85-filter` (8.5.7)
  - `php85-gettext` (8.5.7)
  - `php85-gmp` (8.5.7)
  - `php85-intl` (8.5.7)
  - `php85-ldap` (8.5.7)
  - `php85-mbstring` (8.5.7)
  - `php85-openssl_x509_crl` (1.3_3)
  - `php85-pcntl` (8.5.7)
  - `php85-pdo` (8.5.7)
  - `php85-pdo_sqlite` (8.5.7)
  - `php85-pear-Auth_RADIUS` (1.1.0_5)
  - `php85-pear-Crypt_CHAP` (1.5.0_2)
  - `php85-pear-Mail` (2.0.0,1)
  - `php85-pear-Net_IPv6` (1.3.0.b4_2)
  - `php85-pear-XML_RPC2` (1.1.5)
  - `php85-pecl-mcrypt` (1.0.9)
  - `php85-pecl-radius` (1.4.0b1_6)
  - `php85-mitranet-module` (1.0.0)
  - `php85-phpseclib` (2.0.17)
  - `php85-posix` (8.5.7)
  - `php85-readline` (8.5.7)
  - `php85-session` (8.5.7)
  - `php85-shmop` (8.5.7)
  - `php85-simplexml` (8.5.7)
  - `php85-sockets` (8.5.7)
  - `php85-sqlite3` (8.5.7)
  - `php85-sysvmsg` (8.5.7)
  - `php85-sysvsem` (8.5.7)
  - `php85-sysvshm` (8.5.7)
  - `php85-tokenizer` (8.5.7)
  - `php85-xml` (8.5.7)
  - `php85-xmlreader` (8.5.7)
  - `php85-xmlwriter` (8.5.7)
  - `php85-zlib` (8.5.7)
  - `qstats` (0.2)
  - `radvd` (2.20)
  - `rate` (0.9_4)
  - `scponly` (4.8.20110526_8)
  - `smartmontools` (7.5_2)
  - `ssh_tunnel_shell` (0.2_2)
  - `sshguard` (2.5.1_1,1)
  - `strongswan` (6.1.0_1)
  - `uclcmd` (0.2.20211204)
  - `unbound` (1.26.1)
  - `voucher` (0.1_3)
  - `whois` (5.5.7_1)
  - `wol` (0.7.1_5)
  - `wpa_supplicant` (2.11_7)
  - `wrapalixresetbutton` (0.0.16)
  - `xinetd` (2.3.15_3)

### `mitranet-upgrade` (1.3.40)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `mitranet-repoc` (20260919.051732)

### `mobile-broadband-provider-info` (20251101)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `libxml2` (2.15.4)
  - `libxslt` (1.1.45)

### `nettle` (3.10.2)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `gmp` (6.3.0)
  - `indexinfo` (0.3.1_1)

### `nginx` (1.30.5,3)
- **Role**: daemon/service | **Category**: SERVICES
- **Direct Dependencies**:
  - `pcre2` (10.48)

### `nss_ldap` (1.265_15)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `openldap26-client` (2.6.15_1)

### `ntp` (4.2.8p18_6)
- **Role**: daemon/service | **Category**: SERVICES
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `libedit` (3.1.20260512,1)
  - `libevent` (2.1.13)
  - `perl5` (5.42.3)

### `openldap26-client` (2.6.15_1)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `cyrus-sasl` (2.1.28_6)

### `opensc` (0.27.0)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `glib` (2.88.3,2)
  - `pcsc-lite` (2.5.2,2)

### `openvpn` (2.7.7)
- **Role**: daemon/service | **Category**: VPN
- **Direct Dependencies**:
  - `easy-rsa` (3.2.6,1)
  - `liblz4` (1.10.0_2,1)
  - `lzo2` (2.10_2)
  - `pkcs11-helper` (1.31.0)

### `pam_ldap` (186_2)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `openldap26-client` (2.6.15_1)

### `pcsc-lite` (2.5.2,2)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `polkit` (127)

### `pftop` (0.13)
- **Role**: utility | **Category**: FIREWALL
- **Direct Dependencies**:
  - `libpfctl` (0.17)

### `php85` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libxml2` (2.15.4)
  - `pcre2` (10.48)

### `php85-bcmath` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-bz2` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-ctype` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-curl` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `curl` (8.22.0)
  - `php85` (8.5.10)

### `php85-dom` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libxml2` (2.15.4)
  - `php85` (8.5.10)

### `php85-filter` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-gettext` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `php85` (8.5.10)

### `php85-gmp` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `gmp` (6.3.0)
  - `php85` (8.5.10)

### `php85-intl` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `icu` (76.1,1)
  - `php85` (8.5.10)

### `php85-ldap` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `cyrus-sasl` (2.1.28_6)
  - `openldap26-client` (2.6.15_1)
  - `php85` (8.5.10)

### `php85-mbstring` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `oniguruma` (6.9.10)
  - `php85` (8.5.10)

### `php85-mitranet-module` (1.0.0)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libpfctl` (0.17)
  - `php85` (8.5.7)
  - `strongswan` (6.1.0_1)

### `php85-openssl_x509_crl` (1.3_3)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.7)
  - `php85-bcmath` (8.5.7)

### `php85-pcntl` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-pdo` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-pdo_sqlite` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `php85-pdo` (8.5.10)
  - `sqlite3` (3.53.4,1)

### `php85-pear` (1.10.18)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `php85-xml` (8.5.10)
  - `php85-zlib` (8.5.10)

### `php85-pear-Auth_RADIUS` (1.1.0_5)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.7)
  - `php85-pear` (1.10.18)
  - `php85-pecl-radius` (1.4.0b1_6)

### `php85-pear-Cache_Lite` (1.8.3,1)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `php85-pear` (1.10.18)

### `php85-pear-Crypt_CHAP` (1.5.0_2)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.7)
  - `php85-pear` (1.10.18)
  - `php85-pecl-mcrypt` (1.0.9)

### `php85-pear-HTTP_Request2` (2.7.0,1)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `php85-pear` (1.10.18)
  - `php85-pear-Net_URL2` (2.2.3)

### `php85-pear-Mail` (2.0.0,1)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `php85-pear` (1.10.18)

### `php85-pear-Net_IPv6` (1.3.0.b4_2)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `php85-pear` (1.10.18)

### `php85-pear-Net_URL2` (2.2.3)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `php85-pear` (1.10.18)

### `php85-pear-XML_RPC2` (1.1.5)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `php85-curl` (8.5.10)
  - `php85-pear` (1.10.18)
  - `php85-pear-Cache_Lite` (1.8.3,1)
  - `php85-pear-HTTP_Request2` (2.7.0,1)

### `php85-pecl-mcrypt` (1.0.9)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libltdl` (2.5.4)
  - `libmcrypt` (2.5.8_4)
  - `php85` (8.5.7)

### `php85-pecl-radius` (1.4.0b1_6)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.7)

### `php85-pecl-rrd` (2.0.4)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `rrdtool` (1.11.0)

### `php85-phpseclib` (2.0.17)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.7)

### `php85-posix` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-readline` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libedit` (3.1.20260512,1)
  - `php85` (8.5.10)

### `php85-session` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-shmop` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-simplexml` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libxml2` (2.15.4)
  - `php85` (8.5.10)

### `php85-sockets` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-sqlite3` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)
  - `sqlite3` (3.53.4,1)

### `php85-sysvmsg` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-sysvsem` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-sysvshm` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-tokenizer` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `php85-xml` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libxml2` (2.15.4)
  - `php85` (8.5.10)

### `php85-xmlreader` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libxml2` (2.15.4)
  - `php85` (8.5.10)
  - `php85-dom` (8.5.10)

### `php85-xmlwriter` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `libxml2` (2.15.4)
  - `php85` (8.5.10)

### `php85-zlib` (8.5.10)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `php85` (8.5.10)

### `polkit` (127)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `dbus` (1.16.2_4,1)
  - `duktape-lib` (2.7.0_1)
  - `expat` (2.8.2)
  - `gettext-runtime` (1.0_1)
  - `glib` (2.86.4,2)

### `protobuf` (29.6,1)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `abseil` (20250127.1_1)
  - `jsoncpp` (1.9.6_1)

### `protobuf-c` (1.5.1_4)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `abseil` (20250127.1_1)
  - `protobuf` (29.6,1)

### `py312-packaging` (26.3)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `python312` (3.12.14_1)

### `python3` (3_4)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `python312` (3.12.14_1)

### `python312` (3.12.14_1)
- **Role**: runtime dependency | **Category**: RUNTIME
- **Direct Dependencies**:
  - `expat` (2.8.4)
  - `gettext-runtime` (1.0_1)
  - `libffi` (3.8.0)
  - `mpdecimal` (4.0.1)
  - `readline` (8.3.6)

### `readline` (8.3.6)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `indexinfo` (0.3.1_1)

### `rrdtool` (1.9.0_1)
- **Role**: utility | **Category**: MONITORING
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `glib` (2.86.4,2)
  - `libxml2` (2.15.3)
  - `perl5` (5.42.2)

### `shared-mime-info` (2.4_2)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `glib` (2.88.3,2)
  - `libxml2` (2.15.4)

### `sqlite3` (3.53.4,1)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `libedit` (3.1.20260512,1)

### `strongswan` (6.1.0_1)
- **Role**: daemon/service | **Category**: VPN
- **Direct Dependencies**:
  - `curl` (8.20.0)
  - `ldns` (1.9.2)
  - `unbound` (1.26.1)
  - `vstr` (1.0.15_2)

### `uclcmd` (0.2.20211204)
- **Role**: utility | **Category**: MANAGEMENT
- **Direct Dependencies**:
  - `libucl` (0.9.4)

### `unbound` (1.26.1)
- **Role**: daemon/service | **Category**: SERVICES
- **Direct Dependencies**:
  - `expat` (2.8.4)
  - `libevent` (2.1.13)
  - `libnghttp2` (1.70.0)
  - `libsodium` (1.0.22)

### `whois` (5.5.7_1)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `libidn2` (2.3.8)

### `wol` (0.7.1_5)
- **Role**: utility | **Category**: UTILITY
- **Direct Dependencies**:
  - `gettext-runtime` (1.0_1)
  - `indexinfo` (0.3.1_1)

### `wpa_supplicant` (2.12_1)
- **Role**: daemon/service | **Category**: WIRELESS
- **Direct Dependencies**:
  - `dbus` (1.16.2_4,1)
  - `readline` (8.3.6)

### `xinetd` (2.3.15_3)
- **Role**: daemon/service | **Category**: MANAGEMENT
- **Direct Dependencies**:
  - `perl5` (5.42.3)

### `zstd` (1.5.7_2)
- **Role**: library | **Category**: LIBRARY
- **Direct Dependencies**:
  - `liblz4` (1.10.0_2,1)

