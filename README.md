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
3. **MitraNet CLI Suite (`mitranet-cli` & `mitra-v2ray`):**
   - Manajemen node, impor link instan (`vless://...`, `vmess://...`), switch node, tes latensi/ping, kontrol daemon, dan monitoring log.
4. **Web API & Dashboard Integration:**
   - Daemon REST API ringan (`mitranet-web`) pada port `8080`, terhubung ke Nginx pada path `/api/mitranet`.
5. **Siap Pakai di GitHub Codespaces:**
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

Di dalam sistem MitraNet OS (atau di container testing):

```bash
# 1. Cek status daemon
mitranet-cli status

# 2. Impor link node VLESS atau VMESS
mitranet-cli import-link "vless://e4d29f8a-5c17-4933-bf92-628d02c7a109@sg.vpn.com:443?security=reality&sni=www.apple.com&pbk=...#SG-Node"

# 3. Aktifkan node
mitranet-cli use-node SG-Node

# 4. Tes koneksi & latensi
mitranet-cli ping-node

# 5. Aktifkan transparent proxy router (pf firewall redirection)
mitranet-cli tproxy enable
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

## 📄 Lisensi & Kontribusi
Proyek ini dilisensikan di bawah lisensi BSD 2-Clause. Kontribusi dan saran penambahan fitur jaringan dipersilakan melalui *Pull Request* dan *Issues*.
