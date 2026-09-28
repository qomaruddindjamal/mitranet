# MitraNet - GitHub Codespaces Implementation Guide

Panduan lengkap untuk menjalankan reverse engineering, pengembangan fitur OSNetwork, dan pembuatan ISO MitraNet langsung di cloud melalui **GitHub Codespaces**.

---

## 1. Apa itu GitHub Codespaces pada Proyek MitraNet?

GitHub Codespaces menyediakan lingkungan pengembangan berbasis cloud (Docker Linux x86_64) yang sudah dilengkapi dengan:
- **Alat Reverse Engineering & ISO:** `xorriso`, `7z` (p7zip), `mtools`, `dosfstools`, `makefs`.
- **Emulator & Pengujian OS:** `qemu-system-x86_64` dengan dukungan UEFI (OVMF) & BIOS.
- **Xray & V2Ray Core:** Binary Xray untuk Linux (local testing) & FreeBSD 64-bit (injeksi ke OSNetwork).
- **Python & Go:** Runtime untuk CLI `mitranet-cli` dan API daemon `mitranet-web`.

---

## 2. Cara Menjalankan di GitHub Codespaces (1-Click)

1. Buka repositori GitHub:
   [https://github.com/qomaruddindjamal/mitranet](https://github.com/qomaruddindjamal/mitranet)
2. Klik tombol hijau **`Code`** > pilih tab **`Codespaces`**.
3. Klik **`Create codespace on main`**.
4. GitHub akan membaca `.devcontainer/devcontainer.json` dan membangun container secara otomatis.

---

## 3. Struktur Penggunaan Port di Codespaces

GitHub Codespaces secara otomatis melakukan port forwarding untuk layanan berikut:
- **Port 443:** Web Installer & Dashboard pfSense / OSNetwork.
- **Port 8080:** MitraNet V2Ray/Xray API Daemon (`mitranet-web`).
- **Port 9000:** FastCGI backend internal (`pfSense-installer`).
- **Port 10808:** SOCKS5 Inbound Proxy Xray.
- **Port 10809:** HTTP Inbound Proxy Xray.

---

## 4. Alur Kerja (Workflow) Cepat di Codespaces Terminal

### Langkah 1: Persiapan Environment
```bash
bash bulid/setup_dev_env.sh
```

### Langkah 2: Ekstraksi ISO Sumber (OSNetwork)
```bash
bash bulid/unpack_iso.sh
```

### Langkah 3: Injeksi Fitur Xray/V2Ray & MitraNet CLI
```bash
bash bulid/inject_features.sh
```

### Langkah 4: Build Ulang Menjadi ISO MitraNet Bootable
```bash
bash bulid/build_iso.sh
```

### Langkah 5: Uji Coba Booting ISO dengan QEMU
```bash
# Menjalankan virtual machine tanpa GUI (output serial console)
bash test/test_qemu.sh --headless

# Atau uji coba konfigurasi Xray VLESS/VMESS
bash test/test_v2ray.sh
```

---

## 5. Tips Pengembangan di Codespaces
- Jangan commit file ISO ke git (`.gitignore` sudah dikonfigurasi).
- Gunakan `git status` dan `git commit` untuk menyimpan skrip, konfigurasi, dan dokumentasi.
- Jika Codespace terhenti, Anda dapat melanjutkannya kapan saja tanpa kehilangan state di direktori kerja.
