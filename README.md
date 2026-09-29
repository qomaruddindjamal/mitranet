# MitraNet OS - Reverse Engineering & OSNetwork Enhancement

[![GitHub Codespaces](https://img.shields.io/badge/Codespaces-Open%20in%20Cloud-blue?logo=github)](https://github.com/qomaruddindjamal/mitranet)
[![Architecture](https://img.shields.io/badge/Architecture-amd64%20%7C%20FreeBSD%2014-success)](#)
[![Xray-Core](https://img.shields.io/badge/Xray--core-VLESS%20%7C%20VMESS-orange)](#)
[![License](https://img.shields.io/badge/License-BSD%202--Clause-lightgrey)](#)

Repositori ini menyediakan implementasi lengkap **Reverse Engineering** terhadap base OSNetwork (`sources/netgate-installer-amd64.iso`), lingkungan pengembangan **GitHub Codespaces & Docker**, otomatisasi build sistem di folder `bulid/`, paket layanan jaringan di folder `programs/`, serta integrasi protokol tunneling terenkripsi modern **V2Ray / Xray (VLESS & VMESS)** dengan *Transparent Proxying*.

---

## 🌟 Fitur Utama MitraNet OS

1. **Rekayasa Balik Sistem Dasar (OSNetwork FreeBSD/Netgate Base):**
   - Menganalisis dan memanfaatkan arsitektur installer FreeBSD modern berbasis Nginx + FastCGI backend daemon (`pfSense-installer`).
   - Mendukung sistem booting **Hybrid UEFI & Legacy BIOS (El Torito)** dengan deteksi hardware otomatis via Lua loader.
2. **Integrasi V2Ray / Xray Core (FreeBSD 64-bit):**
   - **VLESS Reality (XTLS-Vision):** Protokol anti-censorship dan anti-DPI modern berkecepatan tinggi tanpa memerlukan sertifikat TLS kustom.
   - **VLESS WebSocket + TLS:** Ideal untuk menembus CDN (seperti Cloudflare).
   - **VMESS WebSocket + TLS / TCP:** Protokol standar dengan enkripsi AEAD.
   - **Transparent Proxy (TProxy / Redirect):** Menangkap semua lalu lintas TCP & UDP klien jaringan secara otomatis melalui aturan firewall **FreeBSD pf (Packet Filter)**.
3. **Native pfSense WebConfigurator Integration (Menu VPN):**
   - **`VPN` ➔ `WireGuard`:** Konfigurasi tunnel WireGuard, key generation, dan manajemen peer klien.
   - **`VPN` ➔ `Xray (V2Ray / VLESS)`:** Halaman manajemen Xray ala MikroTik/Winbox (impor link 1-click, daftar node, aktivasi switch node, toggle Transparent Proxy, dan live log monitor).
   - **`Status` ➔ `Xray Core Status`:** Monitoring status service, PID, dan latensi node aktif.
4. **MitraNet CLI Suite (`mitranet-cli`):**
   - Manajemen node via terminal/SSH, impor link instan (`vless://...`, `vmess://...`), switch node, tes latensi/ping, kontrol daemon, dan monitoring log.
5. **Siap Pakai di GitHub Codespaces & Hyper-V:**
   - 1-Click cloud development environment lengkap dengan `xorriso`, `7z`, `python3`, `golang`, dan `qemu-system-x86_64`.

---

## 📁 Struktur Direktori Repositori

```text
MitraNet/
├── .devcontainer/               # Konfigurasi 1-Click GitHub Codespaces
│   └── devcontainer.json
├── .gitignore                   # Mengabaikan file ISO biner besar (>100MB)
├── README.md                    # Dokumentasi utama proyek
├── bulid/                       # Sistem build & otomasi pembuatan ISO
│   ├── Makefile                 # Make automation (all, unpack, inject, build, test)
│   ├── build_iso.sh / .ps1      # Skrip repackaging ISO hybrid UEFI/BIOS dengan xorriso
│   ├── bulid.md                 # Dokumentasi alur build
│   ├── inject_features.sh / .ps1# Injeksi fitur Xray, CLI, Web API, dan pf rules
│   ├── setup_dev_env.sh         # Persiapan dependensi build
│   └── unpack_iso.sh / .ps1     # Ekstraksi ISO sumber dan boot images
├── docker/                      # Lingkungan container Docker & Codespace
│   ├── Dockerfile               # Multi-purpose image build & QEMU testing
│   ├── docker-compose.yml       # Docker compose untuk dev lokal
│   ├── codespace.md             # Panduan GitHub Codespaces
│   └── docker.md                # Panduan Docker lokal
├── images/                      # Output seluruh image OS hasil reverse engineering & build
│   ├── .gitkeep
│   ├── images.md                # Spesifikasi seluruh jenis image (ISO, IMG, QCOW2, Tarball)
│   ├── MitraNet-OS-amd64.iso    # (Dihasilkan saat build x86_64)
│   └── releases/                # Direktori rilis image multi-arsitektur
├── profiles/                    # Konfigurasi profil target arsitektur CPU & Perangkat
│   ├── x86_64.json, arm64.json, arm.json, mipsbe.json, mmips.json, smips.json ...
├── programs/                    # Fitur peningkatan OSNetwork & V2Ray/Xray
│   ├── program.md               # Dokumentasi protokol, arsitektur, dan API
│   └── xray/
│       ├── bin/                 # Skrip fetch binary Xray FreeBSD
│       ├── cli/                 # mitranet-cli & mitra-v2ray CLI manager
│       ├── config/              # Template VLESS Reality, VLESS WS, VMESS, TProxy
│       ├── service/             # Skrip startup /usr/local/etc/rc.d/xray & pf_xray.conf
│       └── web/                 # REST API daemon (mitranet_api.py) & Nginx snippet
├── sources/                     # Image OSNetwork sumber
│   ├── README.md
│   ├── sources.md               # Laporan teknis reverse engineering mendalam
│   └── netgate-installer-amd64.iso # Base ISO (FreeBSD 14.x amd64, ~944MB)
└── test/                        # Pengujian dan verifikasi
    ├── test.md                  # Panduan pengujian & verification checklist
    ├── test_qemu.sh / .ps1      # Script booting VM QEMU (headless / GUI)
    ├── test_v2ray.py            # Automated unit test suite (JSON & link parser)
    └── test_v2ray.sh            # Test runner script
```

---

## 🚀 Panduan Memulai Cepat (Quickstart)

### Cara 1: Mengubah VPS Linux ke MitraNet OS (1-Line Command Seperti MikroTik CHR)
Jika Anda baru saja menyewa VPS Linux (**Ubuntu, Debian, CentOS, AlmaLinux, Rocky**) dan ingin langsung mengubahnya menjadi **MitraNet OS / Netgate Router**:

1. Login ke SSH VPS Anda sebagai `root`.
2. Jalankan satu baris perintah berikut:
   ```bash
   curl -sSL https://raw.githubusercontent.com/qomaruddindjamal/mitranet/main/install.sh | bash
   ```
   *(Atau tanpa prompt konfirmasi: `curl -sSL https://raw.githubusercontent.com/qomaruddindjamal/mitranet/main/install.sh | bash -s -- -y`)*
3. Skrip akan secara otomatis:
   - Mendeteksi IP publik, Gateway, DNS, dan interface VPS.
   - Mengunduh dan menulis image disk MitraNet OS ke hard drive utama (`/dev/vda` / `/dev/sda`).
   - Menginjeksi konfigurasi IP dan gateway agar koneksi internet tetap aktif pasca-reboot.
   - Melakukan reboot otomatis ke MitraNet OS.
4. Akses WebGUI melalui browser: **`https://<IP-VPS-ANDA>`** (User: `admin`, Pass: `MitraNet@2026!`).

---

### Cara 2: Menggunakan GitHub Codespaces (Sangat Direkomendasikan untuk Development)
1. Buka repositori: [https://github.com/qomaruddindjamal/mitranet](https://github.com/qomaruddindjamal/mitranet)
2. Klik tombol **`Code`** -> tab **`Codespaces`** -> **`Create codespace on main`**.
3. Di terminal Codespace, jalankan:
   ```bash
   # Jalankan pengujian unit
   make test

   # Lakukan build ISO lengkap (hasil di folder images/)
   make all
   ```

---

### Cara 3: Menjalankan di Komputer Lokal (Linux / WSL2 / Docker)
```bash
# 1. Masuk ke lingkungan container
docker compose -f docker/docker-compose.yml up -d
docker exec -it mitranet-dev-env bash

# 2. Ekstrak ISO sumber
bash bulid/unpack_iso.sh

# 3. Injeksi fitur Xray, CLI, dan Web API
bash bulid/inject_features.sh

# 4. Bangun ISO baru ke folder images/
bash bulid/build_iso.sh

# 5. Output ISO MitraNet:
ls -lh images/MitraNet-OS-amd64.iso
```

---

## 🌐 Dukungan Multi-Arsitektur (Multi-Arch Matrix)

MitraNet OS dirancang untuk mendukung berbagai macam arsitektur CPU dan perangkat keras jaringan:

| Target Arsitektur | Kategori Perangkat | Perintah Build | Format Output di `images/` |
| :--- | :--- | :--- | :--- |
| **`x86_64Bit`** | PC Desktop, Server, VM KVM/Proxmox | `make arch-x86_64` | `images/MitraNet-OS-x86_64.iso` |
| **`arm64`** | Raspberry Pi 3/4/5, RK3588, Orange Pi 5 | `make arch-arm64` | `images/releases/MitraNet-arm64-sbc-sdcard.img.gz` |
| **`arm`** | 32-bit ARM SBC, Router ARMv7 (RB3011) | `make arch-arm` | `images/releases/MitraNet-arm-rootfs.tar.gz` |
| **`mipsbe`** | MikroTik RB MIPS-BE, Atheros AR9344 | `make arch-mipsbe` | `images/releases/MitraNet-mipsbe-firmware-pack.tar.gz` |
| **`mmips` / `mipsle`** | MediaTek MT7621A, MikroTik hEX (RB750Gr3) | `make arch-mmips` | `images/releases/MitraNet-mmips-firmware-pack.tar.gz` |
| **`smips`** | MikroTik hAP lite (16MB Flash, 32MB RAM) | `make arch-smips` | `images/releases/MitraNet-smips-firmware-pack.tar.gz` |
| **`ppc`** | PowerPC Network Gear, MikroTik RB1100 | `make arch-ppc` | `images/releases/MitraNet-ppc-firmware-pack.tar.gz` |
| **`all singleboard`** | Unified SBC Matrix (U-Boot + DTB) | `make arch-sbc` | `images/releases/MitraNet-sbc_all-sbc-sdcard.img.gz` |
| **`silicon`** | Apple Silicon (M1/M2/M3/M4 UTM/Parallels) | `make arch-silicon` | `images/releases/MitraNet-silicon-vm.qcow2` |
| **`vm`** | Proxmox VE, VMware ESXi, VirtualBox | `make arch-vm` | `images/releases/MitraNet-vm_all-bundle.tar.gz` |
| **`all`** | Seluruh Arsitektur Sekaligus | `make all-arches` | Semua format di `images/releases/` |

---

## ⚙️ Penggunaan MitraNet CLI (`mitranet-cli`)

Di dalam sistem MitraNet OS (dapat dipanggil via SSH atau konsol lokal):

```bash
# 1. Cek status daemon & node aktif
mitranet-cli status

# 2. Periksa arsitektur CPU dan profil perangkat keras
mitranet-cli arch

# 3. Impor link node VLESS atau VMESS
mitranet-cli import-link "vless://uuid@sg.vpn.com:443?security=reality&sni=www.apple.com&pbk=...#SG-Node"

# 4. Tampilkan semua node proxy yang telah di-impor
mitranet-cli list-nodes

# 5. Aktifkan node proxy tertentu dan reload konfigurasi
mitranet-cli use-node SG-Node

# 6. Tes koneksi & latensi internet melalui proxy
mitranet-cli ping-node

# 7. Kontrol service Xray di background
mitranet-cli start      # Menjalankan service proxy
mitranet-cli stop       # Menghentikan service proxy
mitranet-cli restart    # Memulai ulang service proxy

# 8. Aktifkan / matikan transparent proxy router (pf firewall redirection)
mitranet-cli tproxy enable   # Aktifkan pengalihan traffic router ke proxy
mitranet-cli tproxy disable  # Matikan pengalihan traffic

# 9. Pantau 30 baris log aktivitas Xray terbaru
mitranet-cli logs
```

---

## 🧪 Pengujian Booting dengan QEMU

Untuk memverifikasi image ISO bootable di lingkungan virtual:

```bash
# Mode konsol terminal tanpa GUI:
bash test/test_qemu.sh --headless

# Atau dengan tampilan monitor:
bash test/test_qemu.sh --gui
```

Setelah boot, antarmuka web GUI dapat diakses melalui browser host di:
- **Web Dashboard:** `https://localhost:8443`
- **MitraNet API:** `http://localhost:8080/api/mitranet/status`
- **SOCKS5 Proxy:** `127.0.0.1:10808`

---

## 🛡️ Panduan Perintah Administrasi pfSense & FreeBSD

Sebagai sistem yang dibangun di atas pondasi **Netgate pfSense & FreeBSD**, MitraNet OS mendukung seluruh ekosistem perintah konsol, CLI utility, skrip manajemen, dan arsitektur kontrol firewall bawaan pfSense.

### 1. Menu Konsol Interaktif pfSense (0–16)

Saat mengakses konsol utama router melalui monitor lokal, Hyper-V Console, atau SSH, pfSense menyediakan menu navigasi cepat:

| Nomor | Nama Menu | Fungsi Utama |
| :---: | :--- | :--- |
| **`0`** | **Logout (SSH only)** | Keluar dari sesi terminal SSH |
| **`1`** | **Assign Interfaces** | Memetakan interface fisik/virtual (WAN, LAN, OPT) dan konfigurasi VLAN |
| **`2`** | **Set interface(s) IP address** | Konfigurasi IP statik, DHCP client, gateway, subnet mask, dan IPv6 |
| **`3`** | **Reset webConfigurator password** | Mengembalikan password user `admin` ke bawaan (`pfsense`) |
| **`4`** | **Reset to factory defaults** | Menghapus seluruh konfigurasi dan mengembalikan ke setelan awal pabrik |
| **`5`** | **Reboot system** | Memulai ulang sistem operasi router secara bersih |
| **`6`** | **Halt system** | Mematikan daya mesin router/VM secara aman (*graceful shutdown*) |
| **`7`** | **Ping host** | Melakukan tes koneksi ICMP ke gateway, IP internet, atau domain |
| **`8`** | **Shell** | Masuk ke command-line shell FreeBSD (root shell) |
| **`9`** | **pfTop** | Memantau tabel koneksi state, throughput, dan rule firewall secara real-time |
| **`10`** | **Filter Logs** | Melihat stream log packet filter (blokir / allow traffic) secara langsung |
| **`11`** | **Restart webConfigurator** | Me-restart service Nginx dan PHP-FPM WebGUI pfSense |
| **`12`** | **PHP shell + pfSense tools** | Membuka interactive PHP shell (`pfSsh.php`) untuk skrip otomatisasi |
| **`13`** | **Update from console** | Memeriksa dan menjalankan pembaruan sistem / patch pfSense via CLI |
| **`14`** | **Enable/Disable Secure Shell (sshd)** | Mengaktifkan atau menonaktifkan daemon OpenSSH (Port 22) |
| **`15`** | **Restore recent configuration** | Mengembalikan file konfigurasi XML dari backup otomatis sebelumnya |
| **`16`** | **Restart PHP-FPM** | Me-restart backend proses PHP-FPM tanpa mematikan Nginx web server |

---

### 2. Perintah CLI Firewall Packet Filter (`pfctl`)

Mesin penyaring paket utama pada pfSense adalah **OpenBSD Packet Filter (PF)**. Berikut perintah esensial untuk kontrol firewall:

```bash
# Memeriksa status aktif Packet Filter
pfctl -s info

# Mengaktifkan atau menonaktifkan filter firewall
pfctl -e          # Aktifkan (Enable)
pfctl -d          # Nonaktifkan (Disable)

# Memuat ulang seluruh konfigurasi rules firewall
pfctl -f /etc/pf.conf

# Menampilkan semua aturan filter firewall yang sedang berjalan
pfctl -sr

# Menampilkan semua aturan NAT (Port Forwarding & Outbound NAT)
pfctl -sn

# Memeriksa tabel state koneksi yang aktif (koneksi TCP/UDP yang sedang berlangsung)
pfctl -ss

# Menghapus tabel state koneksi (kill all active connections)
pfctl -F state

# Menghapus seluruh aturan, nat, dan state sekaligus
pfctl -F all
```

---

### 3. Skrip Kontrol Service & WebConfigurator pfSense

pfSense menyediakan script bantuan di direktori `/etc/` untuk mengendalikan antarmuka Web GUI dan routing:

```bash
# Restart WebConfigurator GUI (Nginx + PHP-FPM)
/etc/rc.restart_webgui

# Restart PHP-FPM secara spesifik
/etc/rc.php-fpm_restart

# Reload seluruh filter firewall dan tabel NAT
/etc/rc.filter_configure

# Reload seluruh antarmuka dan layanan jaringan sekaligus
/etc/rc.reload_all

# Menampilkan banner info IP interface router
/etc/rc.banner

# Menampilkan kembali menu interaktif 0-16 dari shell
/etc/rc.initial
```

---

### 4. Skrip Otomatisasi PHP Shell (`pfSsh.php`)

`pfSsh.php` adalah engine script internal pfSense untuk mengeksekusi perintah manajemen langsung ke database XML konfigurasi:

```bash
# Masuk ke interactive PHP shell pfSense
pfSsh.php

# Playback Script: Buka akses darurat jika terkunci di antarmuka WAN
pfSsh.php playback enableallowallwan

# Playback Script: Mengubah password akun admin langsung dari terminal
pfSsh.php playback changepassword admin <password_baru>

# Playback Script: Mengontrol service tertentu (restart DNS resolver unbound)
pfSsh.php playback svc restart unbound

# Playback Script: Restart daemon SSH
pfSsh.php playback restartsshd
```

---

### 5. File Konfigurasi & Log Kunci pfSense

| Lokasi File / Direktori | Fungsi & Penjelasan |
| :--- | :--- |
| **`/cf/conf/config.xml`** | Database utama konfigurasi pfSense (users, interfaces, firewall, DHCP, VPN) |
| **`/conf.default/config.xml`** | Template konfigurasi bawaan pabrik (*factory default*) |
| **`/cf/conf/backup/`** | Direktori penyimpanan riwayat snapshot backup otomatis XML |
| **`/usr/local/www/`** | Direktori root seluruh file antarmuka WebConfigurator pfSense |
| **`/etc/inc/`** | Modul library PHP pemroses core kernel, routing, dan firewall pfSense |
| **`/var/log/system.log`** | Circular log buffer sistem (dibaca menggunakan tool `clog`) |

---

### 6. Perintah Inti FreeBSD & Utilitas Khusus pfSense

Sebagai sistem operasi berbasis **FreeBSD 16.0-CURRENT**, berikut adalah perintah-perintah dasar FreeBSD dan utilitas internal Netgate pfSense yang sering digunakan:

#### A. Manajemen Paket FreeBSD (`pkg`)
```bash
# Perintah instalasi lengkap WebConfigurator & seluruh ekosistem GUI pfSense:
pkg install -y pfSense

# Simulasi instalasi tanpa mengunduh/memasang (dry-run untuk cek dependensi):
pkg install -n pfSense

# Memperbarui paksa (force update) seluruh katalog repositori (FreeBSD & Netgate):
pkg update -f

# Memasang paket dari repositori spesifik (misal: repositori pfSense atau FreeBSD-ports):
pkg install -r pfSense -y <nama_paket>
pkg install -r FreeBSD-ports -y <nama_paket>

# Mencari paket software di repositori resmi
pkg search <nama_paket>

# Memasang paket software umum secara otomatis
pkg install -y <nama_paket>

# Melihat daftar paket yang telah terpasang di sistem
pkg info

# Menghapus paket software dari sistem
pkg delete <nama_paket>

# Membersihkan dependensi yang sudah tidak terpakai
pkg autoremove -y

# Menghapus cache unduhan paket (.pkg) di /var/cache/pkg untuk menghemat disk
pkg clean -y

# Memperbarui seluruh paket yang terpasang ke versi terbaru
pkg upgrade -y
```

> **Tips Penanganan Versi OS pada pkg pfSense:**
> Jika muncul peringatan *FreeBSD version mismatch* pada paket Netgate, tambahkan konfigurasi berikut ke `/usr/local/etc/pkg.conf`:
> ```text
> ABI=FreeBSD:16:amd64
> OSVERSION=1600018
> IGNORE_OSVERSION=yes
> ```


#### B. Konfigurasi Sistem & Service FreeBSD (`sysrc` & `service`)
```bash
# Mengatur variabel startup /etc/rc.conf secara aman via CLI
sysrc sshd_enable="YES"       # Mengaktifkan SSH saat booting
sysrc ifconfig_hn0="DHCP"     # Mengatur antarmuka hn0 mode DHCP
sysrc gateway_enable="YES"    # Mengaktifkan fungsi IP Packet Forwarding router

# Memeriksa seluruh isi konfigurasi rc.conf
sysrc -a

# Mengontrol daemon/service secara langsung
service sshd status           # Cek status service SSH (PID)
service sshd restart          # Restart service SSH
service mitranet_web restart  # Restart Web Dashboard MitraNet

# Menampilkan seluruh service yang aktif berjalan saat booting
service -e
```

#### C. Manajemen Storage ZFS (`zpool` & `zfs`)
```bash
# Memeriksa kondisi kesehatan pool ZFS (status: ONLINE, error checksum)
zpool status

# Melihat ringkasan kapasitas pool storage (Alloc, Free, Frag)
zpool list

# Menampilkan seluruh dataset ZFS beserta mountpoint-nya
zfs list

# Membuat snapshot ZFS instan (titik pemulihan sebelum instalasi/uji coba)
zfs snapshot pfSense/ROOT/default@sebelum_upgrade

# Mengembalikan seluruh sistem ke kondisi snapshot secara instan
zfs rollback pfSense/ROOT/default@sebelum_upgrade
```

#### D. Manajemen Swap, Partisi, dan Jaringan
```bash
# Menampilkan struktur tabel partisi GPT hard disk
gpart show

# Mengaktifkan partisi swap secara manual untuk menambah virtual memory
swapon /dev/da0p3

# Memeriksa pemakaian memori swap yang sedang aktif
swapinfo

# Menampilkan informasi detail kartu jaringan (IP, netmask, MAC, MTU)
ifconfig

# Menampilkan tabel routing default gateway internet
netstat -rn

# Memeriksa port TCP/UDP yang sedang listening beserta nama proses & PID
sockstat -4 -l
```

#### E. Utilitas Khusus pfSense (`pfSense-*`)
Netgate menyematkan perintah pembantu khusus yang berawalan `pfSense-`:
* **`pfSense-upgrade`** : Tool resmi untuk mengecek dan melakukan upgrade versi pfSense secara online.
* **`pfSense-repoc`** : Tool sinkronisasi repositori paket dan verifikasi sertifikat/fingerprint resmi Netgate.
* **`pfSense-post-install`** : Skrip installer untuk mendaftarkan EFI Bootloader (`bootx64.efi`), menyetel ZFS boot dataset, dan membuat konfigurasi `loader.conf`.
#### F. ZFS Boot Environments (`bectl`)
Fitur Boot Environment memungkinkan administrator membuat cadangan sistem sebelum melakukan perubahan, dan memilih boot image lama jika update gagal:
```bash
# Menampilkan seluruh Boot Environment yang tersedia beserta status aktif (N=now, R=on reboot)
bectl list

# Membuat Boot Environment baru sebelum melakukan upgrade paket
bectl create pfSense-backup-sebelum-update

# Mengaktifkan Boot Environment cadangan untuk proses booting berikutnya
bectl activate pfSense-backup-sebelum-update

# Menghapus Boot Environment yang sudah tidak digunakan
bectl destroy pfSense-backup-lama
```

#### G. Diagnostik Jaringan Mendalam & Packet Sniffing
```bash
# Menangkap paket jaringan langsung di antarmuka router (hn0) secara real-time
tcpdump -ni hn0 -c 20

# Menangkap lalu lintas port tertentu (misal: DNS port 53 atau HTTPS port 443)
tcpdump -ni hn0 port 53 or port 443

# Simpan capture traffic ke format file pcap untuk dianalisis di Wireshark
tcpdump -ni hn0 -s 0 -w /tmp/capture_router.pcap

# Memeriksa tabel ARP (pemetaan IP lokal ke MAC Address klien LAN)
arp -an

# Hapus entri ARP cache yang usang / bermasalah
arp -d 172.23.110.1

# Melacak lompatan rute paket (network hops) ke domain tujuan
traceroute 1.1.1.1
```

#### H. Monitoring Kinerja & Diagnostik Kernel FreeBSD
```bash
# Task Manager interaktif per core CPU (tekan 'q' untuk keluar)
top -P

# Monitor alokasi memori virtual, disk I/O, dan context switches setiap 1 detik
vmstat 1

# Monitor latensi baca/tulis disk secara real-time
iostat -x 1

# Memeriksa log deteksi perangkat keras kernel saat proses booting
dmesg | grep -i -E "hyper-v|net|da0|zfs"

# Menampilkan seluruh controller hardware PCI (chipset NIC, SCSI bus)
pciconf -lv

# Memeriksa modul kernel yang sedang aktif (loaded drivers)
kldstat

# Menyetel parameter kernel secara langsung (misal: aktifkan stealth mode TCP)
sysctl net.inet.tcp.blackhole=2
```

#### I. Kontrol Otomatisasi VM Hyper-V (`create_hyperv_vm.ps1`)
Di sisi host Windows PowerShell:
```powershell
# Cek status VM MitraNet dan virtual disk VHDX
powershell -ExecutionPolicy Bypass -File vm\create_hyperv_vm.ps1 -Action status

# Menyalakan VM router
powershell -ExecutionPolicy Bypass -File vm\create_hyperv_vm.ps1 -Action start

# Mematikan VM router dengan aman
powershell -ExecutionPolicy Bypass -File vm\create_hyperv_vm.ps1 -Action stop

# Membangun ulang VM router dari awal
powershell -ExecutionPolicy Bypass -File vm\create_hyperv_vm.ps1 -Action recreate
```

#### J. Manajemen Pengguna, Hak Akses & Password (`pw` & `passwd`)
```bash
# Mengganti password akun root secara interaktif
passwd root

# Mengganti password root secara otomatis via stdin (berguna untuk otomatisasi script)
echo "password_baru" | pw mod user root -h 0

# Menambahkan user administrator baru dengan shell bash/sh dan grup wheel (akses sudo/su)
pw useradd operator -m -G wheel -s /bin/sh

# Mengubah shell default user
chsh -s /bin/sh operator

# Menghapus akun user dari sistem
pw userdel operator -r
```

#### K. Manajemen Routing Manual & Gateway (`route` & `dhclient`)
```bash
# Menampilkan jalur routing yang dilewati paket menuju alamat IP tertentu
route -n get 8.8.8.8

# Menambahkan default gateway internet secara manual
route add default 172.23.110.1

# Menghapus default gateway yang sedang aktif
route delete default

# Meminta / memperbarui peminjaman IP dari server DHCP pada antarmuka tertentu
dhclient hn0

# Melepaskan (release) IP DHCP yang sedang dipegang
dhclient -r hn0
```

#### L. Manajemen Tabel & Alias Firewall (`pfctl` Table Blacklist/Whitelist)
Tabel pfSense digunakan untuk menyimpan daftar IP bogon, daftar blokir brute-force, dan alias IP:
```bash
# Menampilkan IP yang terblokir otomatis akibat salah password SSH berulang (sshlockout)
pfctl -t sshlockout -T show

# Membuka blokir (unban) alamat IP tertentu yang terkunci dari tabel sshlockout
pfctl -t sshlockout -T delete 172.23.110.50

# Menambahkan alamat IP berbahaya secara manual ke tabel blokir
pfctl -t sshlockout -T add 203.0.113.10

# Melihat daftar seluruh tabel filter yang aktif di sistem pfSense
pfctl -s Tables

# Mengosongkan seluruh entri IP di dalam suatu tabel
pfctl -t sshlockout -T flush
```

#### M. Pemeliharaan File System & Integritas Storage ZFS
```bash
# Menjalankan verifikasi integritas data ZFS (scrub) untuk memeriksa bit-rot / korupsi data
zpool scrub pfSense

# Memeriksa perkembangan proses scrubbing ZFS
zpool status pfSense

# Menghentikan proses scrub yang sedang berjalan
zpool scrub -s pfSense

# Menjalankan perintah TRIM discard pada storage SSD / virtual disk untuk menjaga performa
zpool trim pfSense

# Menampilkan seluruh disk fisik & controller drive yang terdeteksi di FreeBSD
camcontrol devlist
```

#### N. Prosedur Manual Backup & Restore `config.xml`
Semua aturan firewall, user, routing, DHCP, dan sertifikat pfSense disimpan dalam 1 file XML tunggal:
```bash
# Membuat cadangan (backup) konfigurasi XML saat ini ke direktori root
cp /cf/conf/config.xml /root/config_backup_$(date +%Y%m%d).xml

# Mengembalikan (restore) file konfigurasi dari cadangan
cp /root/config_backup_20260929.xml /cf/conf/config.xml

# Bersihkan cache konfigurasi agar sistem membaca file XML yang baru
rm -f /tmp/config.cache

# Reload seluruh konfigurasi dan service sistem tanpa perlu reboot mesin
/etc/rc.reload_all
```

---

## 🚀 Panduan Pembuatan & Manajemen Akun Xray (VLESS / VMESS / Trojan)

MitraNet OS menyediakan dua cara mudah untuk membuat dan mengelola akun Xray, baik melalui antarmuka visual **WebConfigurator (WebGUI)** ala MikroTik/Winbox maupun melalui **Terminal CLI**.

### 1. Membuat Akun Server Xray (VLESS Reality) di pfSense VPS (WebGUI)

Gunakan metode ini jika pfSense Anda diinstal pada **VPS dengan IP Publik** untuk dijadikan server pusat VPN:

1. Buka browser dan login ke WebGUI: **`https://<IP_VPS_ANDA>`**
2. Masuk ke menu: **`VPN` ➔ `Xray (V2Ray / VLESS)`**
3. Klik tab: **`VPS Server Inbound`**
4. Isi parameter server (sistem sudah mengisinya secara otomatis dengan parameter optimal):
   - **Port Listen:** `443` *(port HTTPS standar agar tersamar sebagai browsing biasa dan anti-blokir)*.
   - **UUID Klien:** ID unik pengguna *(dihasilkan otomatis oleh sistem)*.
   - **Target SNI Samaran:** Domain samaran TLS *(contoh: `gateway.icloud.com` atau domain milik Anda)*.
   - **Private Key & Public Key:** Pasangan kunci enkripsi X25519 *(dibuat otomatis oleh Xray)*.
   - **Short ID:** ID hex pendek untuk autentikasi Reality *(contoh: `1688`)*.
5. Klik tombol **`Terapkan & Jalankan Server di VPS`**.
6. **Selesai!** Pada kotak di bawah formulir akan muncul **Link Akun Klien Siap Pakai**:
   ```text
   vless://<UUID>@<IP_VPS>:443?security=reality&encryption=none&pbk=<PUBLIC_KEY>&headerType=none&fp=chrome&type=tcp&flow=xtls-rprx-vision&sni=<SNI>&sid=<SHORT_ID>#MitraNet_VPS_Server
   ```
   *Salin (Copy) link ini untuk digunakan di router cabang, pfSense rumah, atau aplikasi v2rayNG (Android) / v2rayN (Windows).*

---

### 2. Membuat Akun Server Xray via Terminal CLI (Multi-User)

Jika ingin menambahkan banyak pengguna (multi-user / akun tambahan untuk klien berbeda) via terminal:

```bash
# 1. Menghasilkan UUID baru untuk pengguna baru:
xray uuid

# 2. Menghasilkan pasangan kunci X25519 untuk Reality:
xray x25519

# 3. Masukkan UUID baru ke dalam daftar 'clients' di konfigurasi Xray:
# File: /usr/local/etc/mitranet/config.json
# Tambahkan entri di bagian: inbounds[0].settings.clients
# Contoh:
# {
#   "id": "f3011246-b4c7-4275-80bc-142b047f8f19",
#   "flow": "xtls-rprx-vision",
#   "email": "klien_rumah@mitranet"
# }

# 4. Restart engine Xray agar akun baru aktif:
mitranet-cli restart
```

---

### 3. Mengimpor & Mengaktifkan Akun di Router Klien (pfSense Rumah / Cabang)

Setelah mendapatkan link akun (baik dari VPS sendiri maupun dari penyedia pihak ketiga):

#### A. Melalui WebGUI pfSense (Sangat Mudah):
1. Buka WebGUI pfSense di rumah: **`https://172.23.110.213`**
2. Masuk ke menu **`VPN` ➔ `Xray (V2Ray / VLESS)`**
3. Pada tab **`Dashboard & Klien`**, tempelkan link akun pada kolom **"Tambah / Import Node Baru"**.
4. Klik tombol **`📥 Import & Simpan Node`**.
5. Server akan otomatis muncul pada tabel daftar node. Klik tombol **`🔌 Sambungkan`** untuk mengaktifkan koneksi ke server tersebut.
6. Klik **`Aktifkan TProxy`** agar seluruh perangkat di LAN (komputer, HP, laptop) otomatis berinternet melalui tunnel tanpa perlu setting di masing-masing HP.

#### B. Melalui Terminal / SSH (`mitranet-cli`):
```bash
# 1. Impor akun langsung dari link:
mitranet-cli import-link "vless://uuid@server:443?security=reality..."

# 2. Periksa daftar akun/node yang tersimpan:
mitranet-cli list-nodes

# 3. Pilih dan aktifkan akun yang diinginkan:
mitranet-cli use-node <NAMA_NODE>

# 4. Uji koneksi dan latensi:
mitranet-cli ping-node

# 5. Aktifkan pembelokan otomatis firewall untuk klien LAN:
mitranet-cli tproxy enable
```

---

## 📄 Lisensi & Kontribusi
Proyek ini dilisensikan di bawah lisensi BSD 2-Clause. Kontribusi dan saran penambahan fitur jaringan dipersilakan melalui *Pull Request* dan *Issues*.




