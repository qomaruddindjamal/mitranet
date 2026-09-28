# MitraNet - Images Directory & Reverse Engineering Outputs

Direktori `images/` merupakan sentral penyimpanan seluruh image sistem operasi hasil dari proses **Reverse Engineering**, remastering, dan multi-architecture build **MitraNet OS**.

---

## 1. Format & Jenis Image yang Dihasilkan

Semua jenis output sistem operasi, baik ISO installer, disk image untuk Single Board Computer (SBC), image virtual machine (VM), maupun paket firmware router disimpan di direktori ini:

| Format Berkas | Kategori Target | Contoh Nama File | Target Perangkat / Platform |
| :--- | :--- | :--- | :--- |
| **`.iso`** | Hybrid Installer Image | `MitraNet-OS-amd64.iso` | PC Desktop, Server x86_64, VM UEFI/BIOS |
| **`.img` / `.img.gz`** | Raw SD Card Disk Image | `MitraNet-arm64-sbc-sdcard.img.gz` | Raspberry Pi 3/4/5, Orange Pi, Rock Pi, NanoPi |
| **`.qcow2`** | KVM / QEMU Virtual Disk | `MitraNet-silicon-vm.qcow2` | Apple Silicon (M1/M2/M3/M4 via UTM), Proxmox VE |
| **`.vmdk`** | VMware Virtual Disk | `MitraNet-vm_all.vmdk` | VMware ESXi, VMware Workstation / Fusion |
| **`.tar.gz`** | Firmware Rootfs Overlay | `MitraNet-mipsbe-firmware-pack.tar.gz` | Router MIPSBE (MikroTik RB, Atheros AR9344) |
| **`.tar.gz`** | Micro-Router Package | `MitraNet-smips-firmware-pack.tar.gz` | MikroTik hAP lite (16MB Flash, 32MB RAM) |
| **`.sha256`** | Checksum Verifikasi | `<nama_file>.sha256` | Verifikasi integritas setiap image yang dibuat |

---

## 2. Cara Menghasilkan Image

### A. Membangun Hybrid ISO (Default x86_64):
```bash
make build
# Output: images/MitraNet-OS-amd64.iso
```

### B. Membangun Image untuk Arsitektur Tertentu:
```bash
# Untuk SBC ARM64 (Raspberry Pi 4/5, Orange Pi):
make arch-arm64
# Output: images/releases/MitraNet-arm64-sbc-sdcard.img.gz

# Untuk Apple Silicon VM:
make arch-silicon
# Output: images/releases/MitraNet-silicon-vm.qcow2

# Untuk Router MIPS (MikroTik / OpenWrt):
make arch-mipsbe
make arch-mmips
make arch-smips
# Output: images/releases/MitraNet-<arch>-firmware-pack.tar.gz

# Untuk Semua Arsitektur Sekaligus:
make all-arches
```

---

## 3. Cara Mem-flash / Menjalankan Image

### Flash SD Card / USB Flashdrive (Linux / macOS):
```bash
# Untuk file .iso:
sudo dd if=images/MitraNet-OS-amd64.iso of=/dev/sdX bs=4M status=progress conv=fsync

# Untuk file .img.gz (SBC):
gzip -dc images/releases/MitraNet-arm64-sbc-sdcard.img.gz | sudo dd of=/dev/sdX bs=4M status=progress conv=fsync
```

### Flash di Windows:
Gunakan **BalenaEtcher** atau **Rufus**, pilih file dari folder `images\`, pilih drive USB/SD Card target, dan klik Flash.

---

## 4. Verifikasi Checksum

```bash
# Linux / macOS:
sha256sum -c images/MitraNet-OS-amd64.iso.sha256

# Windows PowerShell:
Get-FileHash images\MitraNet-OS-amd64.iso -Algorithm SHA256
```
