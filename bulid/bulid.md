# MitraNet - Build System Guide

Panduan lengkap mengenai alur kerja sistem build (*build system*), rekayasa ulang ISO, dan otomatisasi pembuatan image **MitraNet OS**.

---

## 1. Alur Kerja Rekayasa & Build ISO

Pipeline build MitraNet dirancang modular dan dapat dieksekusi secara otomatis baik di terminal Linux lokal, container Docker, maupun GitHub Codespaces:

```
[ sources/netgate-installer-amd64.iso ]
                   |
                   v (1. unpack_iso.sh / .ps1)
        [ bulid/iso_root/ ] <--- Ekstraksi rootfs FreeBSD
                   |
                   v (2. inject_features.sh / .ps1)
    Injeksi Xray FreeBSD + Config VLESS/VMESS + CLI + Web API
                   |
                   v (3. build_iso.sh / .ps1)
       Repackaging dengan xorriso (Hybrid UEFI / BIOS)
                   |
                    v
     [ images/MitraNet-OS-amd64.iso ]
```

---

## 2. Rincian Skrip di Direktori `bulid/`

1. **`setup_dev_env.sh`:**
   - Memeriksa ketersediaan paket `xorriso`, `7z`, `python3`, `curl`, `jq`.
   - Mengunduh dependensi pengujian dan binary Xray FreeBSD jika belum tersedia.

2. **`unpack_iso.sh` & `unpack_iso.ps1`:**
   - Mengekstrak isi ISO sumber ke folder staging `bulid/iso_root/`.
   - Menyelamatkan partisi El Torito boot:
     - `biosboot.img` (BIOS stage boot)
     - `efiboot.img` (UEFI stage FAT image)

3. **`inject_features.sh` & `inject_features.ps1`:**
   - Menyalin binary Xray ke `/usr/local/bin/xray`.
   - Menyalin geodata (`geoip.dat`, `geosite.dat`) ke `/usr/local/share/xray/`.
   - Menyalin konfigurasi template (VLESS Reality, VLESS WS, VMESS WS, Transparent Proxy) ke `/usr/local/etc/xray/`.
   - Memasang skrip service FreeBSD `/usr/local/etc/rc.d/xray`.
   - Menambahkan aturan redirection firewall `/usr/local/etc/xray/pf_xray.conf`.
   - Memasang CLI management `/usr/local/bin/mitranet-cli` & `/usr/local/bin/mitra-v2ray`.
   - Memasang Web API daemon `/usr/local/bin/mitranet-web`.
   - Menambahkan auto-start ke `/etc/rc.local` dan menambahkan lokasi `/api/mitranet` ke Nginx.

4. **`build_iso.sh` & `build_iso.ps1`:**
   - Menyusun ulang struktur ISO dengan `xorriso`.
   - Menetapkan Volume ID `MITRANET`.
   - Mengonfigurasi boot loader hybrid (BIOS `-b boot/cdboot` dan UEFI `-e boot/efiboot.img`).
   - Menghasilkan file output `images/MitraNet-OS-amd64.iso` beserta file checksum SHA256 (`.sha256`).

5. **`Makefile`:**
   - Menyediakan target eksekusi terpadu: `make all`, `make unpack`, `make inject`, `make build`, `make test`, `make clean`.

---

## 3. Cara Menjalankan Build

### Menggunakan Makefile (di Linux / Codespace):
```bash
make all
```

### Menggunakan Skrip Individual:
```bash
bash bulid/unpack_iso.sh
bash bulid/inject_features.sh
bash bulid/build_iso.sh
```

### Menggunakan PowerShell (di Windows):
```powershell
.\bulid\unpack_iso.ps1
.\bulid\inject_features.ps1
.\bulid\build_iso.ps1
```
