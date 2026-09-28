# Reverse Engineering Report: OSNetwork Base Image

Dokumentasi hasil rekayasa balik (*reverse engineering*) terhadap image OSNetwork sumber (`sources/netgate-installer-amd64.iso`).

---

## 1. Identifikasi File & Checksum

| Atribut | Nilai |
| :--- | :--- |
| **Nama File** | `netgate-installer-amd64.iso` |
| **Ukuran** | 989,732,864 bytes (~944 MiB) |
| **Algoritma Hash** | SHA-256 |
| **SHA-256 Hash** | `5E1E2302F5E4668F4E72951670CD1E7ACB075537F7FE58A241F2BB9821C6DC8D` |
| **Volume Label** | `PFSENSE` |
| **Publisher** | `THE FREEBSD PROJECT. HTTPS://WWW.FREEBSD.ORG/` |
| **Target Arsitektur** | `amd64` (x86_64) |
| **Sistem Operasi Dasar**| FreeBSD 14.x / Netgate OS Installer |

---

## 2. Struktur Bootloader & Partisi ISO

Image ISO ini merupakan **Hybrid Bootable ISO** yang mendukung dua mode boot:

1. **Legacy BIOS Boot:**
   - Boot catalog: `[BOOT]/1-Boot-NoEmul.img` (2,048 bytes).
   - Boot loader stage: `boot/cdboot` -> `boot/loader`.
   - Konfigurasi: `boot/loader.conf` dan `boot/loader.conf.lua`.

2. **UEFI Boot:**
   - Partisi EFI terpisah: `[BOOT]/2-Boot-NoEmul.img` (2,097,152 bytes = 2 MiB, sistem berkas FAT12/FAT16).
   - EFI loader: `EFI/BOOT/BOOTX64.EFI` (berakar dari `boot/loader.efi`).

3. **Deteksi Perangkat Keras Dinamis (`boot/loader.conf.lua`):**
   - Skrip Lua membaca `smbios.system.product` dan `smbios.planar.product`.
   - Mengonfigurasi konsol (`efi` vs `comconsole`), serial baudrate (115200), LED status GPIO, dan driver NIC Intel/Marvell secara otomatis untuk perangkat Netgate maupun PC umum.

---

## 3. Alur Inisialisasi Sistem (*Boot Sequence*)

Saat boot selesai, kernel FreeBSD mengeksekusi `/etc/rc` -> `/etc/rc.local`:

```
+-----------------------------------------------------------+
| 1. Boot Kernel (/boot/kernel/kernel)                      |
+-----------------------------------------------------------+
                             |
                             v
+-----------------------------------------------------------+
| 2. /etc/rc.local Dijalankan                                |
|    - Membuat RAM disk md3 (8MB) berformat UFS             |
|    - Me-mount md3 ke /etc agar direktori konfigurasi rw   |
|    - Membuat direktori /tmp/bsdinstall_etc & /var/log/nginx|
+-----------------------------------------------------------+
                             |
                             v
+-----------------------------------------------------------+
| 3. Inisialisasi Sertifikat & Layanan Web                   |
|    - Eksekusi /usr/local/libexec/installer/pfSense-cert   |
|    - Menjalankan backend FastCGI:                         |
|      /usr/local/bin/cgi-fcgi -start -connect              |
|      127.0.0.1:9000 /usr/local/sbin/pfSense-installer    |
+-----------------------------------------------------------+
                             |
                             v
+-----------------------------------------------------------+
| 4. Daemon Pengelola Proses:                                |
|    /usr/local/libexec/installer/pfSense-installerd.sh &   |
|    - Membuat FIFO pipe /tmp/installer.pipe                |
|    - Menjalankan Nginx (/usr/local/etc/rc.d/nginx start)  |
+-----------------------------------------------------------+
                             |
                             v
+-----------------------------------------------------------+
| 5. Web GUI Aktif di Port 80 (HTTP redirect) & 443 (HTTPS) |
|    - Web root: /usr/local/www/web-installer               |
|    - Endpoint API FastCGI: /installer -> 127.0.0.1:9000   |
+-----------------------------------------------------------+
```

---

## 4. Analisis Nginx & Arsitektur API

Konfigurasi Nginx di `/usr/local/etc/nginx/nginx.conf`:
- **Port 80:** Mengalihkan semua lalu lintas ke HTTPS (301).
- **Port 443:** Menggunakan TLS v1.2/v1.3 dengan HTTP/2.
- **Frontend SPA:** Single Page App modern berbasis Vite/Vue di `/usr/local/www/web-installer`.
- **Backend IPC:** FastCGI proxy ke socket `127.0.0.1:9000` (`pfSense-installer`).

---

## 5. Analisis Paket yang Terpasang (`var/db/pkg/local.sqlite`)

Beberapa paket kunci yang sudah ada di dalam sistem dasar:
- `nginx` (1.26.1): Web server dan reverse proxy.
- `luajit-openresty` & `lua-resty-core`: Eksekusi Lua di Nginx.
- `python311` (3.11.9): Interpreter Python 3.11.
- `unbound` (1.20.0): Recursive DNS resolver terenkripsi.
- `isc-dhcp44-server` (4.4.3): Server DHCP IPv4/IPv6.
- `mpd5` (5.9): Multi-link PPP daemon (PPPoE / L2TP).
- `pf`: Paket Filter firewall bawaan kernel FreeBSD.
- `pkg` (1.21.3): FreeBSD Package Manager.
- `curl`, `jq`, `libsodium`, `xmlstarlet`.

---

## 6. Titik Injeksi Fitur Baru MitraNet

Untuk menambahkan fitur **V2Ray / Xray (VLESS & VMESS)**:

1. **Binary Xray FreeBSD:**
   - Ditempatkan pada `/usr/local/bin/xray`.
   - File pendukung geodata (`geoip.dat`, `geosite.dat`) di `/usr/local/share/xray/`.

2. **Layanan Startup (`rc.d`):**
   - Skrip service `/usr/local/etc/rc.d/xray` untuk manajemen start/stop/restart otomatis saat sistem boot.

3. **Firewall Redirection (`pf.conf`):**
   - Aturan packet filter untuk menangkap lalu lintas TCP/UDP LAN dan mengarahkannya ke port Transparent Proxy Xray.

4. **MitraNet Management Daemon & CLI:**
   - CLI tool `/usr/local/bin/mitranet-cli` (dan `/usr/local/bin/mitra-v2ray`).
   - Web API daemon `/usr/local/bin/mitranet-web` (port 8080) diintegrasikan ke Nginx via `/api/mitranet`.
