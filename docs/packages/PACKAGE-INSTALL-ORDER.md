# MITRANET — PACKAGE INSTALLATION & ACTIVATION ORDER MATRIX

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

This matrix defines the strict chronological activation and shutdown order for all 204 packages.

| Order | Package Name | Layer | Category | Runtime Role | Activation Order | Shutdown Order | Failure Action |
|---|---|---|---|---|---|---|---|
| 1 | `cpu-microcode` | Layer 0 | BASE | BOOT / SYSTEM | `A-00-001` | `S-14-203` | HALT_BOOT |
| 2 | `cpu-microcode-amd` | Layer 0 | BASE | BOOT / SYSTEM | `A-00-002` | `S-14-202` | HALT_BOOT |
| 3 | `cpu-microcode-intel` | Layer 0 | BASE | BOOT / SYSTEM | `A-00-003` | `S-14-201` | HALT_BOOT |
| 4 | `cpu-microcode-rc` | Layer 0 | BASE | BOOT / SYSTEM | `A-00-004` | `S-14-200` | HALT_BOOT |
| 5 | `mitranet-boot` | Layer 0 | BASE | BOOT / SYSTEM | `A-00-005` | `S-14-199` | HALT_BOOT |
| 6 | `mitranet-kernel-debian` | Layer 0 | BASE | BOOT / SYSTEM | `A-00-006` | `S-14-198` | HALT_BOOT |
| 7 | `pkg` | Layer 0 | BASE | BOOT / SYSTEM | `A-00-007` | `S-14-197` | HALT_BOOT |
| 8 | `uclcmd` | Layer 0 | BASE | BOOT / SYSTEM | `A-00-008` | `S-14-196` | HALT_BOOT |
| 9 | `bwi-firmware-kmod` | Layer 0 | DRIVER/FIRMWARE | BOOT / SYSTEM | `A-00-009` | `S-14-195` | HALT_BOOT |
| 10 | `php85` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-010` | `S-13-194` | HALT_BOOT |
| 11 | `php85-bcmath` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-011` | `S-13-193` | HALT_BOOT |
| 12 | `php85-bz2` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-012` | `S-13-192` | HALT_BOOT |
| 13 | `php85-ctype` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-013` | `S-13-191` | HALT_BOOT |
| 14 | `php85-curl` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-014` | `S-13-190` | HALT_BOOT |
| 15 | `php85-dom` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-015` | `S-13-189` | HALT_BOOT |
| 16 | `php85-filter` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-016` | `S-13-188` | HALT_BOOT |
| 17 | `php85-gettext` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-017` | `S-13-187` | HALT_BOOT |
| 18 | `php85-gmp` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-018` | `S-13-186` | HALT_BOOT |
| 19 | `php85-intl` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-019` | `S-13-185` | HALT_BOOT |
| 20 | `php85-ldap` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-020` | `S-13-184` | HALT_BOOT |
| 21 | `php85-mbstring` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-021` | `S-13-183` | HALT_BOOT |
| 22 | `php85-mitranet-module` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-022` | `S-13-182` | HALT_BOOT |
| 23 | `php85-openssl_x509_crl` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-023` | `S-13-181` | HALT_BOOT |
| 24 | `php85-pcntl` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-024` | `S-13-180` | HALT_BOOT |
| 25 | `php85-pdo` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-025` | `S-13-179` | HALT_BOOT |
| 26 | `php85-pdo_sqlite` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-026` | `S-13-178` | HALT_BOOT |
| 27 | `php85-pear` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-027` | `S-13-177` | HALT_BOOT |
| 28 | `php85-pear-Auth_RADIUS` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-028` | `S-13-176` | HALT_BOOT |
| 29 | `php85-pear-Cache_Lite` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-029` | `S-13-175` | HALT_BOOT |
| 30 | `php85-pear-Crypt_CHAP` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-030` | `S-13-174` | HALT_BOOT |
| 31 | `php85-pear-HTTP_Request2` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-031` | `S-13-173` | HALT_BOOT |
| 32 | `php85-pear-Mail` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-032` | `S-13-172` | HALT_BOOT |
| 33 | `php85-pear-Net_IPv6` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-033` | `S-13-171` | HALT_BOOT |
| 34 | `php85-pear-Net_URL2` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-034` | `S-13-170` | HALT_BOOT |
| 35 | `php85-pear-XML_RPC2` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-035` | `S-13-169` | HALT_BOOT |
| 36 | `php85-pecl-mcrypt` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-036` | `S-13-168` | HALT_BOOT |
| 37 | `php85-pecl-radius` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-037` | `S-13-167` | HALT_BOOT |
| 38 | `php85-pecl-rrd` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-038` | `S-13-166` | HALT_BOOT |
| 39 | `php85-phpseclib` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-039` | `S-13-165` | HALT_BOOT |
| 40 | `php85-posix` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-040` | `S-13-164` | HALT_BOOT |
| 41 | `php85-readline` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-041` | `S-13-163` | HALT_BOOT |
| 42 | `php85-session` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-042` | `S-13-162` | HALT_BOOT |
| 43 | `php85-shmop` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-043` | `S-13-161` | HALT_BOOT |
| 44 | `php85-simplexml` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-044` | `S-13-160` | HALT_BOOT |
| 45 | `php85-sockets` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-045` | `S-13-159` | HALT_BOOT |
| 46 | `php85-sqlite3` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-046` | `S-13-158` | HALT_BOOT |
| 47 | `php85-sysvmsg` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-047` | `S-13-157` | HALT_BOOT |
| 48 | `php85-sysvsem` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-048` | `S-13-156` | HALT_BOOT |
| 49 | `php85-sysvshm` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-049` | `S-13-155` | HALT_BOOT |
| 50 | `php85-tokenizer` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-050` | `S-13-154` | HALT_BOOT |
| 51 | `php85-xml` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-051` | `S-13-153` | HALT_BOOT |
| 52 | `php85-xmlreader` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-052` | `S-13-152` | HALT_BOOT |
| 53 | `php85-xmlwriter` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-053` | `S-13-151` | HALT_BOOT |
| 54 | `php85-zlib` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-054` | `S-13-150` | HALT_BOOT |
| 55 | `python3` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-055` | `S-13-149` | HALT_BOOT |
| 56 | `python312` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-056` | `S-13-148` | HALT_BOOT |
| 57 | `tcl86` | Layer 1 | RUNTIME | RUNTIME DEPENDENCY | `A-01-057` | `S-13-147` | HALT_BOOT |
| 58 | `boost-libs` | Layer 2 | LIBRARY | LIBRARY | `A-02-058` | `S-12-146` | RETRY_OR_FALLBACK |
| 59 | `ca_root_nss` | Layer 2 | LIBRARY | LIBRARY | `A-02-059` | `S-12-145` | RETRY_OR_FALLBACK |
| 60 | `curl` | Layer 2 | LIBRARY | LIBRARY | `A-02-060` | `S-12-144` | RETRY_OR_FALLBACK |
| 61 | `expat` | Layer 2 | LIBRARY | LIBRARY | `A-02-061` | `S-12-143` | RETRY_OR_FALLBACK |
| 62 | `gmp` | Layer 2 | LIBRARY | LIBRARY | `A-02-062` | `S-12-142` | RETRY_OR_FALLBACK |
| 63 | `icu` | Layer 2 | LIBRARY | LIBRARY | `A-02-063` | `S-12-141` | RETRY_OR_FALLBACK |
| 64 | `igmpproxy` | Layer 2 | LIBRARY | LIBRARY | `A-02-064` | `S-12-140` | RETRY_OR_FALLBACK |
| 65 | `libICE` | Layer 2 | LIBRARY | LIBRARY | `A-02-065` | `S-12-139` | RETRY_OR_FALLBACK |
| 66 | `libSM` | Layer 2 | LIBRARY | LIBRARY | `A-02-066` | `S-12-138` | RETRY_OR_FALLBACK |
| 67 | `libXau` | Layer 2 | LIBRARY | LIBRARY | `A-02-067` | `S-12-137` | RETRY_OR_FALLBACK |
| 68 | `libXdmcp` | Layer 2 | LIBRARY | LIBRARY | `A-02-068` | `S-12-136` | RETRY_OR_FALLBACK |
| 69 | `libavif` | Layer 2 | LIBRARY | LIBRARY | `A-02-069` | `S-12-135` | RETRY_OR_FALLBACK |
| 70 | `libccid` | Layer 2 | LIBRARY | LIBRARY | `A-02-070` | `S-12-134` | RETRY_OR_FALLBACK |
| 71 | `libdeflate` | Layer 2 | LIBRARY | LIBRARY | `A-02-071` | `S-12-133` | RETRY_OR_FALLBACK |
| 72 | `libedit` | Layer 2 | LIBRARY | LIBRARY | `A-02-072` | `S-12-132` | RETRY_OR_FALLBACK |
| 73 | `libevent` | Layer 2 | LIBRARY | LIBRARY | `A-02-073` | `S-12-131` | RETRY_OR_FALLBACK |
| 74 | `libffi` | Layer 2 | LIBRARY | LIBRARY | `A-02-074` | `S-12-130` | RETRY_OR_FALLBACK |
| 75 | `libgcrypt` | Layer 2 | LIBRARY | LIBRARY | `A-02-075` | `S-12-129` | RETRY_OR_FALLBACK |
| 76 | `libgpg-error` | Layer 2 | LIBRARY | LIBRARY | `A-02-076` | `S-12-128` | RETRY_OR_FALLBACK |
| 77 | `libiconv` | Layer 2 | LIBRARY | LIBRARY | `A-02-077` | `S-12-127` | RETRY_OR_FALLBACK |
| 78 | `libidn2` | Layer 2 | LIBRARY | LIBRARY | `A-02-078` | `S-12-126` | RETRY_OR_FALLBACK |
| 79 | `libltdl` | Layer 2 | LIBRARY | LIBRARY | `A-02-079` | `S-12-125` | RETRY_OR_FALLBACK |
| 80 | `liblz4` | Layer 2 | LIBRARY | LIBRARY | `A-02-080` | `S-12-124` | RETRY_OR_FALLBACK |
| 81 | `libmcrypt` | Layer 2 | LIBRARY | LIBRARY | `A-02-081` | `S-12-123` | RETRY_OR_FALLBACK |
| 82 | `libnghttp2` | Layer 2 | LIBRARY | LIBRARY | `A-02-082` | `S-12-122` | RETRY_OR_FALLBACK |
| 83 | `libpfctl` | Layer 2 | LIBRARY | LIBRARY | `A-02-083` | `S-12-121` | RETRY_OR_FALLBACK |
| 84 | `libpsl` | Layer 2 | LIBRARY | LIBRARY | `A-02-084` | `S-12-120` | RETRY_OR_FALLBACK |
| 85 | `libsodium` | Layer 2 | LIBRARY | LIBRARY | `A-02-085` | `S-12-119` | RETRY_OR_FALLBACK |
| 86 | `libssh2` | Layer 2 | LIBRARY | LIBRARY | `A-02-086` | `S-12-118` | RETRY_OR_FALLBACK |
| 87 | `libucl` | Layer 2 | LIBRARY | LIBRARY | `A-02-087` | `S-12-117` | RETRY_OR_FALLBACK |
| 88 | `libunistring` | Layer 2 | LIBRARY | LIBRARY | `A-02-088` | `S-12-116` | RETRY_OR_FALLBACK |
| 89 | `liburcu` | Layer 2 | LIBRARY | LIBRARY | `A-02-089` | `S-12-115` | RETRY_OR_FALLBACK |
| 90 | `libuv` | Layer 2 | LIBRARY | LIBRARY | `A-02-090` | `S-12-114` | RETRY_OR_FALLBACK |
| 91 | `libxml2` | Layer 2 | LIBRARY | LIBRARY | `A-02-091` | `S-12-113` | RETRY_OR_FALLBACK |
| 92 | `libxslt` | Layer 2 | LIBRARY | LIBRARY | `A-02-092` | `S-12-112` | RETRY_OR_FALLBACK |
| 93 | `libyuv` | Layer 2 | LIBRARY | LIBRARY | `A-02-093` | `S-12-111` | RETRY_OR_FALLBACK |
| 94 | `pkcs11-helper` | Layer 2 | LIBRARY | LIBRARY | `A-02-094` | `S-12-110` | RETRY_OR_FALLBACK |
| 95 | `protobuf` | Layer 2 | LIBRARY | LIBRARY | `A-02-095` | `S-12-109` | RETRY_OR_FALLBACK |
| 96 | `protobuf-c` | Layer 2 | LIBRARY | LIBRARY | `A-02-096` | `S-12-108` | RETRY_OR_FALLBACK |
| 97 | `sqlite3` | Layer 2 | LIBRARY | LIBRARY | `A-02-097` | `S-12-107` | RETRY_OR_FALLBACK |
| 98 | `zstd` | Layer 2 | LIBRARY | LIBRARY | `A-02-098` | `S-12-106` | RETRY_OR_FALLBACK |
| 99 | `abseil` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-099` | `S-12-105` | RETRY_OR_FALLBACK |
| 100 | `beep` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-100` | `S-12-104` | RETRY_OR_FALLBACK |
| 101 | `brotli` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-101` | `S-12-103` | RETRY_OR_FALLBACK |
| 102 | `cpdup` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-102` | `S-12-102` | RETRY_OR_FALLBACK |
| 103 | `cpustats` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-103` | `S-12-101` | RETRY_OR_FALLBACK |
| 104 | `cyrus-sasl` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-104` | `S-12-100` | RETRY_OR_FALLBACK |
| 105 | `dbus` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-105` | `S-12-099` | RETRY_OR_FALLBACK |
| 106 | `dmidecode` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-106` | `S-12-098` | RETRY_OR_FALLBACK |
| 107 | `dpinger` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-107` | `S-12-097` | RETRY_OR_FALLBACK |
| 108 | `duktape-lib` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-108` | `S-12-096` | RETRY_OR_FALLBACK |
| 109 | `easy-rsa` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-109` | `S-12-095` | RETRY_OR_FALLBACK |
| 110 | `expiretable` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-110` | `S-12-094` | RETRY_OR_FALLBACK |
| 111 | `fstrm` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-111` | `S-12-093` | RETRY_OR_FALLBACK |
| 112 | `gettext-runtime` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-112` | `S-12-092` | RETRY_OR_FALLBACK |
| 113 | `glib` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-113` | `S-12-091` | RETRY_OR_FALLBACK |
| 114 | `glib-bootstrap` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-114` | `S-12-090` | RETRY_OR_FALLBACK |
| 115 | `graphite2` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-115` | `S-12-089` | RETRY_OR_FALLBACK |
| 116 | `hostapd` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-116` | `S-12-088` | RETRY_OR_FALLBACK |
| 117 | `if_pppoe-kmod` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-117` | `S-12-087` | RETRY_OR_FALLBACK |
| 118 | `iftop` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-118` | `S-12-086` | RETRY_OR_FALLBACK |
| 119 | `indexinfo` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-119` | `S-12-085` | RETRY_OR_FALLBACK |
| 120 | `ipmitool` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-120` | `S-12-084` | RETRY_OR_FALLBACK |
| 121 | `jbigkit` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-121` | `S-12-083` | RETRY_OR_FALLBACK |
| 122 | `jq` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-122` | `S-12-082` | RETRY_OR_FALLBACK |
| 123 | `jsoncpp` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-123` | `S-12-081` | RETRY_OR_FALLBACK |
| 124 | `kea` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-124` | `S-12-080` | RETRY_OR_FALLBACK |
| 125 | `kvm` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-125` | `S-12-079` | RETRY_OR_FALLBACK |
| 126 | `ldns` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-126` | `S-12-078` | RETRY_OR_FALLBACK |
| 127 | `lerc` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-127` | `S-12-077` | RETRY_OR_FALLBACK |
| 128 | `links` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-128` | `S-12-076` | RETRY_OR_FALLBACK |
| 129 | `log4cplus` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-129` | `S-12-075` | RETRY_OR_FALLBACK |
| 130 | `lua54` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-130` | `S-12-074` | RETRY_OR_FALLBACK |
| 131 | `lzo2` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-131` | `S-12-073` | RETRY_OR_FALLBACK |
| 132 | `minicron` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-132` | `S-12-072` | RETRY_OR_FALLBACK |
| 133 | `mitranet-Status_Monitoring-php85` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-133` | `S-12-071` | RETRY_OR_FALLBACK |
| 134 | `mitranet-composer-deps` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-134` | `S-12-070` | RETRY_OR_FALLBACK |
| 135 | `mitranet-repoc` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-135` | `S-12-069` | RETRY_OR_FALLBACK |
| 136 | `mobile-broadband-provider-info` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-136` | `S-12-068` | RETRY_OR_FALLBACK |
| 137 | `mpdecimal` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-137` | `S-12-067` | RETRY_OR_FALLBACK |
| 138 | `nettle` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-138` | `S-12-066` | RETRY_OR_FALLBACK |
| 139 | `nss_ldap` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-139` | `S-12-065` | RETRY_OR_FALLBACK |
| 140 | `oniguruma` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-140` | `S-12-064` | RETRY_OR_FALLBACK |
| 141 | `openldap26-client` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-141` | `S-12-063` | RETRY_OR_FALLBACK |
| 142 | `opensc` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-142` | `S-12-062` | RETRY_OR_FALLBACK |
| 143 | `pam_ldap` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-143` | `S-12-061` | RETRY_OR_FALLBACK |
| 144 | `pam_mkhomedir` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-144` | `S-12-060` | RETRY_OR_FALLBACK |
| 145 | `pcre2` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-145` | `S-12-059` | RETRY_OR_FALLBACK |
| 146 | `pcsc-lite` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-146` | `S-12-058` | RETRY_OR_FALLBACK |
| 147 | `perl5` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-147` | `S-12-057` | RETRY_OR_FALLBACK |
| 148 | `polkit` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-148` | `S-12-056` | RETRY_OR_FALLBACK |
| 149 | `py312-packaging` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-149` | `S-12-055` | RETRY_OR_FALLBACK |
| 150 | `readline` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-150` | `S-12-054` | RETRY_OR_FALLBACK |
| 151 | `shared-mime-info` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-151` | `S-12-053` | RETRY_OR_FALLBACK |
| 152 | `ssh_tunnel_shell` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-152` | `S-12-052` | RETRY_OR_FALLBACK |
| 153 | `unzip` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-153` | `S-12-051` | RETRY_OR_FALLBACK |
| 154 | `vstr` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-154` | `S-12-050` | RETRY_OR_FALLBACK |
| 155 | `whois` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-155` | `S-12-049` | RETRY_OR_FALLBACK |
| 156 | `wol` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-156` | `S-12-048` | RETRY_OR_FALLBACK |
| 157 | `wrapalixresetbutton` | Layer 2 | UTILITY | SYSTEM UTILITY | `A-02-157` | `S-12-047` | RETRY_OR_FALLBACK |
| 158 | `choparp` | Layer 3 | NETWORK | NETWORK DAEMON / UTILITY | `A-03-158` | `S-11-046` | RETRY_OR_FALLBACK |
| 159 | `qstats` | Layer 3 | NETWORK | NETWORK DAEMON / UTILITY | `A-03-159` | `S-11-045` | RETRY_OR_FALLBACK |
| 160 | `radvd` | Layer 3 | NETWORK | NETWORK DAEMON / UTILITY | `A-03-160` | `S-11-044` | RETRY_OR_FALLBACK |
| 161 | `rate` | Layer 3 | NETWORK | NETWORK DAEMON / UTILITY | `A-03-161` | `S-11-043` | RETRY_OR_FALLBACK |
| 162 | `scponly` | Layer 3 | NETWORK | NETWORK DAEMON / UTILITY | `A-03-162` | `S-11-042` | RETRY_OR_FALLBACK |
| 163 | `wifi` | Layer 4 | WIRELESS | DAEMON / UTILITY | `A-04-163` | `S-10-041` | RETRY_OR_FALLBACK |
| 164 | `wpa_supplicant` | Layer 4 | WIRELESS | DAEMON / UTILITY | `A-04-164` | `S-10-040` | RETRY_OR_FALLBACK |
| 165 | `mpd5` | Layer 5 | ROUTING | DAEMON / SERVICE | `A-05-165` | `S-09-039` | RETRY_OR_FALLBACK |
| 166 | `filterdns` | Layer 6 | FIREWALL | DAEMON / SERVICE | `A-06-166` | `S-08-038` | RETRY_OR_FALLBACK |
| 167 | `filterlog` | Layer 6 | FIREWALL | DAEMON / SERVICE | `A-06-167` | `S-08-037` | RETRY_OR_FALLBACK |
| 168 | `miniupnpd` | Layer 6 | FIREWALL | DAEMON / SERVICE | `A-06-168` | `S-08-036` | RETRY_OR_FALLBACK |
| 169 | `pfSense-base` | Layer 6 | FIREWALL | DAEMON / SERVICE | `A-06-169` | `S-08-035` | RETRY_OR_FALLBACK |
| 170 | `pftop` | Layer 6 | FIREWALL | DAEMON / SERVICE | `A-06-170` | `S-08-034` | RETRY_OR_FALLBACK |
| 171 | `dhcp6` | Layer 7 | DHCP | DAEMON / SERVICE | `A-07-171` | `S-07-033` | RETRY_OR_FALLBACK |
| 172 | `dhcpcd` | Layer 7 | DHCP | DAEMON / SERVICE | `A-07-172` | `S-07-032` | RETRY_OR_FALLBACK |
| 173 | `dhcpleases` | Layer 7 | DHCP | DAEMON / SERVICE | `A-07-173` | `S-07-031` | RETRY_OR_FALLBACK |
| 174 | `dhcpleases6` | Layer 7 | DHCP | DAEMON / SERVICE | `A-07-174` | `S-07-030` | RETRY_OR_FALLBACK |
| 175 | `isc-dhcp44-client` | Layer 7 | DHCP | DAEMON / SERVICE | `A-07-175` | `S-07-029` | RETRY_OR_FALLBACK |
| 176 | `isc-dhcp44-relay` | Layer 7 | DHCP | DAEMON / SERVICE | `A-07-176` | `S-07-028` | RETRY_OR_FALLBACK |
| 177 | `isc-dhcp44-server` | Layer 7 | DHCP | DAEMON / SERVICE | `A-07-177` | `S-07-027` | RETRY_OR_FALLBACK |
| 178 | `bind-tools` | Layer 7 | DNS | DAEMON / SERVICE | `A-07-178` | `S-07-026` | RETRY_OR_FALLBACK |
| 179 | `dnsmasq` | Layer 7 | DNS | DAEMON / SERVICE | `A-07-179` | `S-07-025` | RETRY_OR_FALLBACK |
| 180 | `unbound` | Layer 7 | DNS | DAEMON / SERVICE | `A-07-180` | `S-07-024` | RETRY_OR_FALLBACK |
| 181 | `ntp` | Layer 7 | SERVICES | DAEMON / SERVICE | `A-07-181` | `S-07-023` | RETRY_OR_FALLBACK |
| 182 | `mitranet-pkg-WireGuard` | Layer 7 | VPN | DAEMON / SERVICE | `A-07-182` | `S-07-022` | RETRY_OR_FALLBACK |
| 183 | `openvpn` | Layer 7 | VPN | DAEMON / SERVICE | `A-07-183` | `S-07-021` | RETRY_OR_FALLBACK |
| 184 | `openvpn-auth-script` | Layer 7 | VPN | DAEMON / SERVICE | `A-07-184` | `S-07-020` | RETRY_OR_FALLBACK |
| 185 | `strongswan` | Layer 7 | VPN | DAEMON / SERVICE | `A-07-185` | `S-07-019` | RETRY_OR_FALLBACK |
| 186 | `wireguard-mitranet` | Layer 7 | VPN | DAEMON / SERVICE | `A-07-186` | `S-07-018` | RETRY_OR_FALLBACK |
| 187 | `xray-mitranet` | Layer 7 | VPN | DAEMON / SERVICE | `A-07-187` | `S-07-017` | RETRY_OR_FALLBACK |
| 188 | `sshguard` | Layer 8 | SECURITY | DAEMON / SERVICE | `A-08-188` | `S-06-016` | LOG_AND_DEGRADE |
| 189 | `voucher` | Layer 8 | SECURITY | DAEMON / SERVICE | `A-08-189` | `S-06-015` | LOG_AND_DEGRADE |
| 190 | `bsnmp-regex` | Layer 9 | MONITORING | DAEMON / UTILITY | `A-09-190` | `S-05-014` | LOG_AND_DEGRADE |
| 191 | `bsnmp-ucd` | Layer 9 | MONITORING | DAEMON / UTILITY | `A-09-191` | `S-05-013` | LOG_AND_DEGRADE |
| 192 | `rrdtool` | Layer 9 | MONITORING | DAEMON / UTILITY | `A-09-192` | `S-05-012` | LOG_AND_DEGRADE |
| 193 | `smartmontools` | Layer 9 | MONITORING | DAEMON / UTILITY | `A-09-193` | `S-05-011` | LOG_AND_DEGRADE |
| 194 | `speedtest` | Layer 9 | MONITORING | DAEMON / UTILITY | `A-09-194` | `S-05-010` | LOG_AND_DEGRADE |
| 195 | `mitranet` | Layer 10 | BASE | CORE FOUNDATION | `A-10-195` | `S-04-009` | LOG_AND_DEGRADE |
| 196 | `mitranet-base-1.0.0.partaa` | Layer 10 | BASE | CORE FOUNDATION | `A-10-196` | `S-04-008` | LOG_AND_DEGRADE |
| 197 | `mitranet-base-1.0.0.partab` | Layer 10 | BASE | CORE FOUNDATION | `A-10-197` | `S-04-007` | LOG_AND_DEGRADE |
| 198 | `mitranet-default-config` | Layer 10 | BASE | CORE FOUNDATION | `A-10-198` | `S-04-006` | LOG_AND_DEGRADE |
| 199 | `mitranet-gnid` | Layer 10 | BASE | CORE FOUNDATION | `A-10-199` | `S-04-005` | LOG_AND_DEGRADE |
| 200 | `mitranet-system` | Layer 10 | BASE | CORE FOUNDATION | `A-10-200` | `S-04-004` | LOG_AND_DEGRADE |
| 201 | `mitranet-upgrade` | Layer 10 | BASE | CORE FOUNDATION | `A-10-201` | `S-04-003` | LOG_AND_DEGRADE |
| 202 | `check_reload_status` | Layer 11 | MANAGEMENT | DAEMON / SERVICE | `A-11-202` | `S-03-002` | LOG_AND_DEGRADE |
| 203 | `nginx` | Layer 11 | MANAGEMENT | DAEMON / SERVICE | `A-11-203` | `S-03-001` | LOG_AND_DEGRADE |
| 204 | `xinetd` | Layer 11 | MANAGEMENT | DAEMON / SERVICE | `A-11-204` | `S-03-000` | LOG_AND_DEGRADE |
