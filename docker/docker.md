# MitraNet - Docker Environment Guide

Panduan menjalankan container Docker secara lokal (di Linux / macOS / Windows dengan Docker Desktop atau WSL2) untuk merekayasa balik OSNetwork dan membuild ISO MitraNet.

---

## 1. Membangun Container Docker Lokal

Jalankan perintah berikut di root folder proyek:

```bash
docker compose -f docker/docker-compose.yml build
```

Atau langsung dengan docker build:
```bash
docker build -t mitranet-builder -f docker/Dockerfile .
```

---

## 2. Masuk ke Lingkungan Interaktif Docker

```bash
docker compose -f docker/docker-compose.yml up -d
docker exec -it mitranet-dev-env bash
```

Di dalam container, direktori root `/workspace` sudah otomatis ter-mount dengan sinkronisasi dua arah ke folder kerja komputer Anda.

---

## 3. Menjalankan Pipeline Lengkap dalam Container

Di dalam terminal container:

```bash
# 1. Pastikan ISO sumber ada di sources/netgate-installer-amd64.iso
ls -lh sources/

# 2. Ekstraksi ISO
bash bulid/unpack_iso.sh

# 3. Injeksi fitur Xray (VLESS / VMESS) & pf firewall rules
bash bulid/inject_features.sh

# 4. Repackaging ISO MitraNet
bash bulid/build_iso.sh

# 5. Output ISO akan berada di:
ls -lh ISO/MitraNet-OS-amd64.iso
```

---

## 4. Keuntungan Menggunakan Docker
- **Isolasi Penuh:** Tidak mengubah konfigurasi host OS.
- **Portabilitas:** Dapat dijalankan di mesin developer mana pun (Ubuntu, Debian, macOS, Windows).
- **Tooling Lengkap:** Semua paket Linux (`xorriso`, `qemu`, `p7zip`, `python3`) langsung siap pakai.
