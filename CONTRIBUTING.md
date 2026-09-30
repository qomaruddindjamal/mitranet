# Panduan Kontribusi Kode Sumber Terbuka (Open Source) - MitraNet OS

Selamat datang di proyek **MitraNet OS**! Proyek ini dirancang sepenuhnya terbuka (*Open Source*) agar komunitas dapat berkolaborasi, memodifikasi, mengoptimalkan, dan menambahkan paket/fitur jaringan secara mandiri.

---

## 1. Arsitektur Kode Sumber

Repositori MitraNet memiliki struktur kode sumber terbuka yang modular:

```text
MitraNet/
├── programs/                 # KODE SUMBER UTAMA MITRANET
│   └── xray/
│       ├── cli/              # MitraNet CLI (Python & Shell Wrapper)
│       │   ├── mitranet_cli.py
│       │   ├── mitranet-cli
│       │   └── mitra-v2ray
│       ├── web/              # Web GUI & REST API
│       │   ├── mitranet_xray.php   # Native pfSense PHP GUI
│       │   ├── mitranet_xray.xml   # Menu manifest pfSense
│       │   └── mitranet_api.py    # Python REST daemon
│       ├── config/           # Konfigurasi VLESS, VMESS, Transparent Proxy
│       ├── service/          # FreeBSD rc.d init scripts & PF Firewall rules
│       └── bin/              # Skrip kompilasi & fetch multi-arsitektur
│
├── sources/                  # BASIS SISTEM OPERASI & POHON SUMBER
│   ├── netgate/              # Berkas installer OS yang diekstrak & dimodifikasi
│   │   ├── packages/All/     # Direktori penyimpanan paket offline (*.pkg)
│   │   ├── usr/local/www/    # WebGUI pfSense & MitraNet UI
│   │   └── etc/              # Konfigurasi boot & repositori offline
│   └── sources.md            # Laporan rekayasa balik arsitektur
│
├── bulid/                    # SKRIP BUILD OTOMATIS
│   ├── build_iso.ps1         # Builder ISO UEFI/BIOS Hybrid (PowerShell)
│   ├── build_iso.sh          # Builder ISO via xorriso (Linux/Docker/CI)
│   ├── build_raw_image.ps1   # Generator Image VPS CHR-Style (.raw.gz)
│   ├── bundle_offline_pkgs.ps1 # Kompresor paket offline (LZMA2 Ultra)
│   ├── inject_features.ps1   # Penginjeksi modul ke pohon rootfs
│   └── build_multiarch.ps1   # Builder rilis 11 arsitektur perangkat keras
│
├── deploy/                   # SKRIP DEPLOYMENT VPS
│   └── install.sh            # Skrip 1-Baris Reinstall VPS (MikroTik CHR Style)
│
└── vm/                       # TESTBED OTOMASI VM HYPER-V
    ├── auto_installer_monitor.ps1
    ├── send_key.ps1
    └── capture_screen.ps1
```

---

## 2. Cara Mengembangkan & Memperbaiki Fitur

### A. Mengedit Tampilan pfSense WebGUI
Buka berkas `programs/xray/web/mitranet_xray.php`:
- Halaman ini menggunakan komponen Bootstrap & PHP bawaan pfSense.
- Anda dapat menambahkan formulir node baru, validasi UUID/SNI, statistik grafik real-time, atau konfigurasi fallback.

### B. Menambahkan Paket Offline Baru (`.pkg`)
Agar instalasi ISO 100% offline tanpa perlu mengunduh dari server Netgate:
1. Simpan berkas paket FreeBSD/pfSense (`*.pkg`) ke dalam `bulid/packages_cache/` atau langsung ke `sources/netgate/packages/All/`.
2. Jalankan skrip bundler paket:
   ```powershell
   .\bulid\bundle_offline_pkgs.ps1
   ```
3. Skrip akan secara otomatis:
   - Membuat index repositori offline `packagesite.pkg`.
   - Mengonfigurasi `MitraNet-offline.conf` dengan `url: "file:///packages"`.
   - Memampatkan paket menggunakan algoritma kompresi maksimal LZMA2.

### C. Mengompilasi Ulang ISO
Setelah melakukan perubahan kode atau menambahkan paket, build ISO baru:
- **Di Windows:**
  ```powershell
  .\bulid\build_iso.ps1 -SourceDir "sources\netgate" -OutputIso "images\MitraNet-OS-amd64.iso"
  ```
- **Di Linux / Docker / GitHub Codespaces:**
  ```bash
  bash bulid/build_iso.sh sources/netgate images/MitraNet-OS-amd64.iso
  ```

### D. Mengompilasi Image Raw VPS (MikroTik CHR Style)
Untuk memperbarui image deploy VPS 1-baris:
```powershell
.\bulid\build_raw_image.ps1 -SourceVhd "vm\mitranet.vhdx"
```
Hasil file `MitraNet-OS-amd64.raw.gz` siap diunggah ke GitHub Releases.

---

## 3. Alur Pull Request (PR)

1. **Fork** repositori ini ke akun GitHub Anda.
2. Buat branch baru untuk fitur Anda:
   ```bash
   git checkout -b feat/nama-fitur-baru
   ```
3. Lakukan pengujian lokal baik via Hyper-V VM (`vm/`) maupun container Docker.
4. Lakukan commit dengan pesan terstruktur (*Conventional Commits*):
   ```bash
   git commit -m "feat(webgui): tambahkan visualisasi latency node vless"
   ```
5. Push ke GitHub dan buka **Pull Request**.

---

## 4. Lisensi

Dengan berkontribusi pada proyek ini, Anda menyetujui bahwa semua kontribusi kode sumber Anda dilisensikan di bawah lisensi **Apache License 2.0** (dengan tetap menghormati lisensi komponen upstream FreeBSD/pfSense).
