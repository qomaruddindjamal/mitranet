# MitraNet OS - Multi-Architecture Hardware & Target Matrix

Dokumen ini mendefinisikan dukungan arsitektur CPU, chipset Single Board Computer (SBC), platform Silicon, router embedded, dan Virtual Machine (VM) yang didukung oleh **MitraNet OS**.

---

## 1. Tabel Matriks Arsitektur CPU

| Kode Arsitektur | Kategori | Target Perangkat / Chipset | GOOS / GOARCH / Flags | Format Output |
| :--- | :--- | :--- | :--- | :--- |
| **`x86_64Bit`** (amd64) | Desktop / Server / VM | Intel Core / Xeon, AMD Ryzen / EPYC, KVM, Proxmox | `GOARCH=amd64` | Hybrid ISO (UEFI/BIOS), QCOW2, RAW |
| **`arm64`** (aarch64) | SBC / Server / Silicon | Raspberry Pi 3/4/5, RK3588, Orange Pi 5, Ampere Altra | `GOARCH=arm64` | UEFI ISO, SD Card `.img.gz` |
| **`arm`** (armv7/armhf) | SBC / Router Embedded | Raspberry Pi 2, Allwinner H3/H5, MikroTik RB3011/RB4011 | `GOARCH=arm GOARM=7` | SD Card `.img`, Rootfs Tarball |
| **`arm`** (armv6/armel) | Low-power SBC | Raspberry Pi 1, Pi Zero W | `GOARCH=arm GOARM=6` | SD Card `.img`, Rootfs Tarball |
| **`mipsbe`** | Router Embedded | Atheros AR9344/QCA9531, MikroTik RB750/RB951/RB2011 | `GOARCH=mips GOMIPS=softfloat` | Rootfs Tarball, Kernel+Initramfs |
| **`mmips`** / **`mipsle`** | Router Embedded | MediaTek MT7621A, MT7628, hEX (RB750Gr3), OpenWrt | `GOARCH=mipsle GOMIPS=softfloat` | Rootfs Tarball, Firmware Pack |
| **`smips`** | Ultra-Low Flash MIPS | MikroTik hAP lite (16MB Flash, 32MB RAM) | `GOARCH=mips GOMIPS=softfloat -ldflags="-s -w"` | Micro-Package (< 8MB stripped) |
| **`ppc`** / **`ppc64`** | PowerPC / Network Gear | Freescale PowerPC, MikroTik RB1000 / RB1100 / RB1200 | `GOARCH=ppc64` / `ppc64le` | Rootfs Tarball, ELF payload |
| **`silicon`** | Apple Silicon / ARM ARMv8+ | Apple M1/M2/M3/M4 (UTM, Parallels, Docker, Asahi Linux) | `GOARCH=arm64` | UEFI VMDK, QCOW2, ISO aarch64 |
| **`all singleboard`** | Unified SBCs | Raspberry Pi, Orange Pi, NanoPi, Banana Pi, Rock Pi | Unified ARM/ARM64 DTB Engine | Raw disk image dengan U-Boot |
| **`vm`** | Hypervisors | Proxmox VE, VMware ESXi, VirtualBox, Hyper-V, bhyve | Multiformat Drivers (VirtIO, VMXNET3) | ISO, OVA, VMDK, QCOW2, VHDX |

---

## 2. Profil Optimasi Memori Berdasarkan Arsitektur

### A. High-Performance Profile (x86_64, arm64, silicon, vm > 2GB RAM)
- Buffer transmisi Xray: 16 MB - 64 MB.
- Sniffing lengkap (HTTP, TLS, QUIC) dengan DNS cache in-memory penuh.
- Multi-core multiplexing (Mux.Cool) aktif.

### B. Standard SBC Profile (armv7, arm64, 512MB - 1GB RAM)
- Buffer transmisi Xray: 4 MB - 8 MB.
- Direct memory pooling untuk meminimalisir GC (Garbage Collection) latency.

### C. Low-Resource Router Profile (mipsbe, mmips, mipsle, smips, ppc, 32MB - 128MB RAM)
- Binary Xray / V2Ray dikompilasi dengan `softfloat` dan flag stripped: `-ldflags="-s -w"`.
- Mode hemat RAM:
  - Cache DNS dibatasi ke 256 record.
  - Inbound stream socket buffer diperkecil (128 KB).
  - TProxy dikonfigurasi tanpa memory-heavy domain routing rule.
