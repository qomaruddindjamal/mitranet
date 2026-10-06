# Technical Justification for Excluded Packages

## Non-Negotiable Exclusion Policy

In MitraNet Rinjani 1.0.2, packages are never excluded with a vague note of not needed. Every exclusion must provide rigorous technical, architectural, ABI, or security evidence.

### Primary Grounds for Exclusion:
1. **FreeBSD Kernel ABI Incompatibility:** Packages containing FreeBSD kernel modules (.ko), kernel headers, or FreeBSD sysctl/kqueue hooks that do not exist on Linux.
2. **Legacy pfSense PHP WebUI Framework:** Obsolete PHP 8.5 procedural WebGUI scripts, XML config parsers, and custom PECL extensions replaced by MitraNet Python/FastAPI and modern React UI.
3. **FreeBSD Package Management Infrastructure:** pkg-ng, libucl, and UCL utilities that conflict with Debian APT / dpkg.
4. **Upstream Build/Toolchain Clutter:** Build-time compilers, autotools, and temporary packaging tools not needed in a hardened production firewall runtime.
5. **Obsolete / Dead Hardware Support:** Drivers or utilities for discontinued platforms (e.g., PC Engines ALIX).

## Excluded Packages Inventory

| Package Name | Category | Origin / Version | Technical Justification & Architecture Impact |
|---|---|---|---|
| autoconf | NOT-REQUIRED | devel/autoconf (2.72) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| autoconf-switch | NOT-REQUIRED | devel/autoconf-switch (20220527) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| automake | NOT-REQUIRED | devel/automake (1.16.5_2) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| bwi-firmware-kmod | INCOMPATIBLE / UNSUITABLE | net/bwi-firmware-kmod (3.130.20.1600018) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| cmake-core | NOT-REQUIRED | devel/cmake-core (3.29.6) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| cpu-microcode | INCOMPATIBLE / UNSUITABLE | sysutils/cpu-microcode (1.0_1) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| cpu-microcode-rc | INCOMPATIBLE / UNSUITABLE | sysutils/cpu-microcode-rc (1.0_2) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| fcgi-devkit | NOT-REQUIRED | www/fcgi (2.4.0_6) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| gettext-tools | NOT-REQUIRED | devel/gettext-tools (0.22.5) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| gmake | NOT-REQUIRED | devel/gmake (4.4.1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| help2man | NOT-REQUIRED | misc/help2man (1.49.3_1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| indexinfo | NOT-REQUIRED | print/indexinfo (0.3.1_1) | FreeBSD pkg-ng package management infrastructure not required on Debian system. |
| libtextstyle | NOT-REQUIRED | devel/libtextstyle (0.22.5) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| libtool | NOT-REQUIRED | devel/libtool (2.4.7_2) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| libucl | NOT-REQUIRED | textproc/libucl (0.9.4) | FreeBSD pkg-ng package management infrastructure not required on Debian system. |
| m4 | NOT-REQUIRED | devel/m4 (1.4.19_1,1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| meson | NOT-REQUIRED | devel/meson (1.4.1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| ninja | NOT-REQUIRED | devel/ninja (1.11.1,4) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| p5-Locale-gettext | NOT-REQUIRED | devel/p5-Locale-gettext (1.07) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| p5-Locale-libintl | NOT-REQUIRED | devel/p5-Locale-libintl (1.33) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| p5-Text-Unidecode | NOT-REQUIRED | converters/p5-Text-Unidecode (1.30) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| p5-Unicode-EastAsianWidth | NOT-REQUIRED | textproc/p5-Unicode-EastAsianWidth (12.0) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| pfSense | INCOMPATIBLE / UNSUITABLE | security/pfSense (2.9.0) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-Status_Monitoring-php85 | INCOMPATIBLE / UNSUITABLE | sysutils/pfSense-Status_Monitoring (1.9) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-base | INCOMPATIBLE / UNSUITABLE | security/pfSense-base (2.9.0) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-boot | INCOMPATIBLE / UNSUITABLE | security/pfSense-boot (2.9.0) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-composer-deps | INCOMPATIBLE / UNSUITABLE | devel/pfSense-composer-deps (0.5_1) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-gnid | INCOMPATIBLE / UNSUITABLE | security/pfSense-gnid (0.21) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-installer | INCOMPATIBLE / UNSUITABLE | net/pfSense-installer (20240916) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-kernel-pfSense | INCOMPATIBLE / UNSUITABLE | security/pfSense-kernel (2.9.0) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-repoc | INCOMPATIBLE / UNSUITABLE | sysutils/pfSense-repoc (20260919.051732) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-system | INCOMPATIBLE / UNSUITABLE | security/pfSense-system (2.9.0) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| pfSense-upgrade | INCOMPATIBLE / UNSUITABLE | sysutils/pfSense-upgrade (1.3.40) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| php85 | NOT-REQUIRED | lang/php85 (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-bcmath | NOT-REQUIRED | math/php85-bcmath (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-bz2 | NOT-REQUIRED | archivers/php85-bz2 (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-ctype | NOT-REQUIRED | textproc/php85-ctype (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-curl | NOT-REQUIRED | ftp/php85-curl (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-dom | NOT-REQUIRED | textproc/php85-dom (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-filter | NOT-REQUIRED | security/php85-filter (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-gettext | NOT-REQUIRED | devel/php85-gettext (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-gmp | NOT-REQUIRED | math/php85-gmp (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-intl | NOT-REQUIRED | devel/php85-intl (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-ldap | NOT-REQUIRED | net/php85-ldap (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-mbstring | NOT-REQUIRED | converters/php85-mbstring (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-openssl_x509_crl | NOT-REQUIRED | security/php-openssl_x509_crl (1.3_3) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pcntl | NOT-REQUIRED | devel/php85-pcntl (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pdo | NOT-REQUIRED | databases/php85-pdo (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pdo_sqlite | NOT-REQUIRED | databases/php85-pdo_sqlite (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear | NOT-REQUIRED | devel/pear (1.10.18) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear-Auth_RADIUS | NOT-REQUIRED | net/pear-Auth_RADIUS (1.1.0_5) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear-Cache_Lite | NOT-REQUIRED | sysutils/pear-Cache_Lite (1.8.3,1) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear-Crypt_CHAP | NOT-REQUIRED | security/pear-Crypt_CHAP (1.5.0_2) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear-HTTP_Request2 | NOT-REQUIRED | www/pear-HTTP_Request2 (2.7.0,1) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear-Mail | NOT-REQUIRED | mail/pear-Mail (2.0.0,1) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear-Net_IPv6 | NOT-REQUIRED | net/pear-Net_IPv6 (1.3.0.b4_2) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear-Net_URL2 | NOT-REQUIRED | net/pear-Net_URL2 (2.2.3) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pear-XML_RPC2 | NOT-REQUIRED | net/pear-XML_RPC2 (1.1.5) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pecl-mcrypt | NOT-REQUIRED | security/pecl-mcrypt (1.0.9) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pecl-radius | NOT-REQUIRED | net/pecl-radius (1.4.0b1_6) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pecl-rrd | NOT-REQUIRED | databases/pecl-rrd (2.0.4) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-pfSense-module | INCOMPATIBLE / UNSUITABLE | devel/php-pfSense-module (2.9.0) | Tightly coupled to FreeBSD kernel, kmod ABI, or pfSense legacy runtime. |
| php85-phpseclib | NOT-REQUIRED | security/phpseclib (2.0.17) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-posix | NOT-REQUIRED | sysutils/php85-posix (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-readline | NOT-REQUIRED | devel/php85-readline (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-session | NOT-REQUIRED | www/php85-session (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-shmop | NOT-REQUIRED | devel/php85-shmop (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-simplexml | NOT-REQUIRED | textproc/php85-simplexml (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-sockets | NOT-REQUIRED | net/php85-sockets (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-sqlite3 | NOT-REQUIRED | databases/php85-sqlite3 (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-sysvmsg | NOT-REQUIRED | devel/php85-sysvmsg (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-sysvsem | NOT-REQUIRED | devel/php85-sysvsem (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-sysvshm | NOT-REQUIRED | devel/php85-sysvshm (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-tokenizer | NOT-REQUIRED | devel/php85-tokenizer (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-xml | NOT-REQUIRED | textproc/php85-xml (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-xmlreader | NOT-REQUIRED | textproc/php85-xmlreader (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-xmlwriter | NOT-REQUIRED | textproc/php85-xmlwriter (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| php85-zlib | NOT-REQUIRED | archivers/php85-zlib (8.5.10) | Legacy pfSense PHP 8.5 WebGUI stack not needed; MitraNet uses native Python core and React UI. |
| pkg | NOT-REQUIRED | ports-mgmt/pkg (2.8.4) | FreeBSD pkg-ng package management infrastructure not required on Debian system. |
| pkgconf | NOT-REQUIRED | devel/pkgconf (2.2.0,1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| public_suffix_list | NOT-REQUIRED | dns/public_suffix_list (20240531) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| py311-build | NOT-REQUIRED | devel/py-build (1.2.1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| py311-flit-core | NOT-REQUIRED | devel/py-flit-core (3.9.0) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| py311-installer | NOT-REQUIRED | devel/py-installer (0.7.0) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| py311-packaging | NOT-REQUIRED | devel/py-packaging (24.1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| py311-pyproject_hooks | NOT-REQUIRED | devel/py-pyproject_hooks (1.1.0) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| py311-setuptools | NOT-REQUIRED | devel/py-setuptools (63.1.0_1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| py311-wheel | NOT-REQUIRED | devel/py-wheel (0.43.0) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| rhash | NOT-REQUIRED | security/rhash (1.4.4_1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| scponly | NOT-REQUIRED | shells/scponly (4.8.20110526_8) | Legacy restricted shell; OpenSSH native internal-sftp ChrootDirectory is standard. |
| swig | NOT-REQUIRED | devel/swig (4.1.1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| texinfo | NOT-REQUIRED | print/texinfo (7.1_2,1) | Upstream build/packaging toolchain not needed in runtime firewall router image. |
| uclcmd | NOT-REQUIRED | devel/uclcmd (0.2.20211204) | FreeBSD pkg-ng package management infrastructure not required on Debian system. |
| wrapalixresetbutton | INCOMPATIBLE / UNSUITABLE | sysutils/wrapalixresetbutton (0.0.16) | Legacy PC Engines ALIX hardware-specific GPIO reset button utility; obsolete hardware. |
