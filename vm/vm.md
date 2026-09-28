# MitraNet - Virtual Machine (VM) Deployment Guide

Panduan penerapan **MitraNet OS** pada berbagai platform virtualisasi (*hypervisor*) seperti Proxmox VE, VMware ESXi / Workstation, VirtualBox, KVM / QEMU, dan Hyper-V.

---

## 1. Rekomendasi Spesifikasi Virtual Machine

| Komponen | Minimal | Direkomendasikan | Catatan |
| :--- | :--- | :--- | :--- |
| **vCPU** | 1 Core | 2 - 4 Cores | Arsitektur x86_64, aktifkan flag AES-NI untuk akselerasi enkripsi TLS |
| **RAM** | 1024 MB (1 GB) | 2048 - 4096 MB | Cukup untuk Xray buffering & routing table |
| **Disk Storage** | 8 GB | 16 - 32 GB | Tipe bus VirtIO Block / SCSI |
| **Network Interface (NIC)** | 2 Adapter | 2 atau lebih | 1 untuk WAN, 1 untuk LAN (model VirtIO-net / Intel e1000) |
| **Firmware Boot** | BIOS / UEFI | UEFI (OVMF) | Mendukung kedua mode hybrid |

---

## 2. Topologi Jaringan Virtual Router

```
                      +-----------------------------+
                      |       Internet (WAN)        |
                      +-----------------------------+
                                     |
                                     v (Bridged / NAT NIC: vtnet0)
             +------------------------------------------------+
             |            MitraNet OS Virtual Machine         |
             |                                                |
             |   - pf Packet Filter NAT & Routing             |
             |   - V2Ray / Xray Transparent Proxy (TProxy)    |
             |   - Unbound DNS Resolver & DHCP Server         |
             |   - MitraNet Web Dashboard & API (Port 80/443) |
             +------------------------------------------------+
                                     |
                                     v (Internal Host-Only / Bridge: vtnet1)
                      +-----------------------------+
                      |      Klien Virtual (LAN)    |
                      |   (Windows / Linux Guest)   |
                      +-----------------------------+
```

Semua lalu lintas dari VM klien di jaringan internal (LAN) akan otomatis melewati router MitraNet, di mana lalu lintas internet di-proxy secara transparan melalui tunnel VLESS / VMESS tanpa perlu konfigurasi proxy manual pada tiap klien.

---

## 3. Panduan Setup per Hypervisor

### A. Proxmox VE (PVE)
1. **Upload ISO:** Unggah `ISO/MitraNet-OS-amd64.iso` ke storage `local (iso)`.
2. **Create VM:**
   - **OS:** Type: `Other`, pilih ISO MitraNet.
   - **System:** BIOS: `OVMF (UEFI)` atau `Default (SeaBIOS)`, Machine: `q35`.
   - **Disks:** Bus/Device: `SCSI`, Cache: `Write back`, Ukuran: `16G`.
   - **CPU:** Cores: `2`, Type: `host` (agar instruksi AES-NI diteruskan ke VM).
   - **Memory:** `2048 MB`.
   - **Network:** Tambahkan 2 NIC:
     - NIC 1: Bridge ke `vmbr0` (WAN).
     - NIC 2: Bridge ke `vmbr1` (LAN internal).
     - Model: `VirtIO (paravirtualized)`.

### B. VMware ESXi / Workstation
1. Buat VM baru dengan OS Type: `FreeBSD 13 or later (64-bit)`.
2. Tentukan RAM 2048 MB, 2 vCPU.
3. Hubungkan CD/DVD drive ke `ISO/MitraNet-OS-amd64.iso` dan centang `Connect at power on`.
4. Tambahkan Network Adapter kedua (Adapter 1: Bridged/WAN, Adapter 2: Custom Host-Only/LAN).
5. Booting VM dan selesaikan konfigurasi interface.

### C. Oracle VirtualBox
1. Buat VM baru: Type: `BSD`, Version: `FreeBSD (64-bit)`.
2. Base Memory: `2048 MB`, Processors: `2`.
3. Storage: Masukkan ISO MitraNet ke Optical Drive.
4. Network:
   - Adapter 1: `Bridged Adapter` (koneksi internet rumah/kantor).
   - Adapter 2: `Internal Network` (nama jaringan: `mitranet-lan`).
5. Jalankan VM.

---

## 4. Verifikasi Konektivitas Pasca-Instalasi

Setelah VM menyala:
1. Hubungi Web GUI melalui IP LAN router di browser: `https://<ip-lan-router>`
2. Akses MitraNet CLI via SSH atau console:
   ```bash
   mitranet-cli status
   mitranet-cli ping-node
   ```
