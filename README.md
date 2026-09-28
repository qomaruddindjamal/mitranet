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
├── ISO/                         # Output image ISO kustom
│   ├── .gitkeep
│   ├── iso.md                   # Spesifikasi teknis ISO, flashing USB, dan checksum
│   └── MitraNet-OS-amd64.iso    # (Dihasilkan saat build selesai)
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

### Cara 1: Menggunakan GitHub Codespaces (Sangat Direkomendasikan)
1. Buka repositori: [https://github.com/qomaruddindjamal/mitranet](https://github.com/qomaruddindjamal/mitranet)
2. Klik tombol **`Code`** -> tab **`Codespaces`** -> **`Create codespace on main`**.
3. Di terminal Codespace, jalankan:
   ```bash
   # Jalankan pengujian
   make test

   # Lakukan build ISO lengkap
   make all
   ```

---

### Cara 2: Menjalankan di Komputer Lokal (Linux / WSL2 / Docker)
```bash
# 1. Masuk ke lingkungan container
docker compose -f docker/docker-compose.yml up -d
docker exec -it mitranet-dev-env bash

# 2. Ekstrak ISO sumber
bash bulid/unpack_iso.sh

# 3. Injeksi fitur Xray, CLI, dan Web API
bash bulid/inject_features.sh

# 4. Bangun ISO baru
bash bulid/build_iso.sh

# 5. Output ISO MitraNet:
ls -lh ISO/MitraNet-OS-amd64.iso
```

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
