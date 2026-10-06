# MitraNet OS Build & Packaging Architecture

## 1. Build System Overview
MitraNet is built using a reproducible, multi-target Debian packaging and image construction pipeline. It builds on Debian 13 (Trixie) amd64, using the companion netinst image (`debian-13.7.0-amd64-netinst.iso`) as an offline repository foundation.

## 2. Artifact Output Types

1. **MitraNet Bare-Metal / Appliance ISO** (`mitranet-<version>-amd64.iso`):
   - Bootable Live & Installation ISO utilizing Debian `live-build` and `calamares` / customized text installer.
   - Installs MitraNet with ZFS or ext4 root, preconfigured systemd services, and out-of-the-box management on LAN `192.168.1.1/24`.

2. **MitraNet ONIE Installer Image** (`mitranet-onie-installer-<version>-amd64.bin`):
   - Self-extracting shell payload formatted specifically for OCP bare-metal switches running ONIE bootloader.
   - Detects whitebox platform CPLD/EEPROM and writes rootfs to internal eMMC / NVMe storage.

3. **Debian Packages** (`.deb`):
   - `mitranet-core`: Central configuration engine, daemon controllers, schema models.
   - `mitranet-network`: nftables compiler, pyroute2 netlink drivers, routing orchestrators.
   - `mitranet-cli`: Interactive CLI, autocompletion, prompt shell.
   - `mitranet-api`: FastAPI management service.
   - `mitranet-platformd`: Hardware telemetry, ONLP C library bindings, fan/thermal monitor.
   - `mitranet-webgui`: Compiled React/Vite web interface frontend.

## 3. Build Workflow Diagram

```text
  ┌───────────────────────┐   ┌───────────────────────┐
  │   Debian 13 Rootfs    │   │ MitraNet Python/Rust  │
  │   (Base Packages)     │   │ Code Repositories     │
  └───────────┬───────────┘   └───────────┬───────────┘
              │                           │
              ▼                           ▼
  ┌───────────────────────────────────────────────────┐
  │         Debian Package Builder (dpkg-deb)         │
  │        Creates .deb packages for MitraNet         │
  └─────────────────────────┬─────────────────────────┘
                            │
            ┌───────────────┴───────────────┐
            ▼                               ▼
  ┌───────────────────┐           ┌───────────────────┐
  │    Live-Build     │           │   ONIE Packager   │
  │   Hybrid ISO      │           │    Binary .bin    │
  └─────────┬─────────┘           └─────────┬─────────┘
            │                               │
            ▼                               ▼
  mitranet-amd64.iso              mitranet-onie.bin
```
