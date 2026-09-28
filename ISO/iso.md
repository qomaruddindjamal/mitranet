# MitraNet - ISO Specifications & Release Guide

Direktori ini merupakan lokasi output dari image ISO hasil build dan kustomisasi MitraNet OS.

---

## 1. Spesifikasi Target Image

| Parameter | Spesifikasi |
| :--- | :--- |
| **Nama File Image** | `MitraNet-OS-amd64.iso` |
| **Arsitektur CPU** | x86_64 / amd64 |
| **Volume Label** | `MITRANET` |
| **Format Sistem Berkas** | ISO9660 Level 3 + Joliet (`-J`) + RockRidge (`-R`) |
| **Mode Booting** | Hybrid UEFI & Legacy BIOS (El Torito) |
| **Bootloader UEFI** | `EFI/BOOT/BOOTX64.EFI` (FreeBSD `loader.efi`) |
| **Bootloader BIOS** | `boot/cdboot` -> `boot/loader` |
| **Fitur Bawaan Tambahan** | Xray-core (VLESS & VMESS), MitraNet CLI, Web API, pf Firewall TProxy |

---

## 2. Cara Membuat Bootable USB Flashdrive

Setelah image `MitraNet-OS-amd64.iso` dihasilkan:

### A. Menggunakan `dd` di Linux / macOS:
```bash
sudo dd if=ISO/MitraNet-OS-amd64.iso of=/dev/sdX bs=4M status=progress conv=fsync
```
*(Ganti `/dev/sdX` dengan identifier flashdrive USB Anda)*

### B. Menggunakan Rufus atau BalenaEtcher di Windows:
1. Masukkan Flashdrive USB (minimal 2 GB).
2. Buka Rufus atau BalenaEtcher.
3. Pilih file `ISO/MitraNet-OS-amd64.iso`.
4. Pilih skema partisi **GPT** untuk target sistem UEFI (atau **MBR** untuk BIOS).
5. Klik **Start / Flash**.

---

## 3. Verifikasi Keaslian Image (Checksum)

Setiap proses build akan menghasilkan file hash `MitraNet-OS-amd64.iso.sha256`. Untuk memverifikasinya:

```bash
# Di Linux / Codespace
sha256sum -c ISO/MitraNet-OS-amd64.iso.sha256

# Di Windows PowerShell
Get-FileHash ISO\MitraNet-OS-amd64.iso -Algorithm SHA256
```
