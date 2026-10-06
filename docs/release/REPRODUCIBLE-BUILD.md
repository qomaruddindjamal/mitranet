# MITRANET REPRODUCIBLE BUILD SPECIFICATION & PROCEDURE

## 1. OBJECTIVE & DETERMINISTIC PRINCIPLES
This specification defines the deterministic, bit-for-bit reproducible build process for generating the canonical release ISO artifact:
```text
mitranet-rinjani-installer.1.0.0.iso
```

### Determinism Rules:
1. **Timestamp Normalization**: `SOURCE_DATE_EPOCH` must be set to the git commit timestamp to prevent varying file metadata.
2. **File Ordering**: Inode scanning must be sorted deterministically.
3. **No Developer-Specific Paths**: Build tooling must never embed local machine usernames, drive letters (`C:\`), or workstation-specific variables.
4. **Frozen Package Inputs**: All 204 packages from `packages/` must match their SHA256 hashes defined in `docs/packages/packages.pkg.json`.

---

## 2. BUILD ENVIRONMENT REQUIREMENTS

### Host / Container Specifications:
- **Architecture**: Linux x86_64 / amd64 (Debian 13 Trixie / Ubuntu 24.04 LTS recommended)
- **Minimum Memory**: 4 GB RAM
- **Minimum Storage**: 20 GB available disk space

### Required Build Utilities:
```bash
sudo apt-get update && sudo apt-get install -y \
    xorriso \
    isolinux \
    syslinux-utils \
    grub-pc-bin \
    grub-efi-amd64-bin \
    mtools \
    squashfs-tools \
    rsync \
    python3 \
    git
```

---

## 3. CANONICAL BUILD PIPELINE

```text
SOURCE REPOSITORY
       │
       ▼
1. VERIFY PACKAGES (204 packages via SHA256)
       │
       ▼
2. PREPARE ROOTFS (SquashFS v4, xz compression)
       │
       ▼
3. DEPLOY CANONICAL INSTALLER (scripts/install_mitranet.sh)
       │
       ▼
4. ASSEMBLE BOOTLOADER (GRUB 2.12 i386-pc + x86_64-efi)
       │
       ▼
5. MASTER HYBRID ISO (xorriso with El Torito dual boot catalog)
       │
       ▼
6. CHECKSUM VERIFICATION (SHA256 generation)
       │
       ▼
mitranet-rinjani-installer.1.0.0.iso
```

---

## 4. STEP-BY-STEP BUILD EXECUTION PROCEDURE

### Step 1: Clone & Verify Environment
```bash
git clone https://github.com/qomaruddindjamal/mitranet.git
cd mitranet
python3 scripts/verify_project.py
```

### Step 2: Set Build Parameters
```bash
export SOURCE_DATE_EPOCH=$(git log -1 --format=%ct)
export RELEASE_VER="1.0.0"
export ISO_OUT="../mitranet-rinjani-installer.${RELEASE_VER}.iso"
```

### Step 3: Master Hybrid Dual-Boot ISO with xorriso
The ISO layout mirrors the verified El Torito architecture:
```bash
xorriso -as mkisofs \
    -r -V "MITRANET_100" \
    -o "${ISO_OUT}" \
    -J -joliet-long \
    -b boot/grub/i386-pc/eltorito.img \
    -c boot.cat \
    -no-emul-boot -boot-load-size 4 -boot-info-table \
    --embedded-boot boot/grub/i386-pc/eltorito.img \
    --protective-msdos-label \
    -eltorito-alt-boot \
    -e efi.img \
    -no-emul-boot -isohybrid-gpt-basdat \
    iso_root/
```

### Step 4: Generate Checksums
```bash
sha256sum "${ISO_OUT}" > "${ISO_OUT}.sha256"
cat "${ISO_OUT}.sha256"
```

---

## 5. CONTRIBUTOR REPRODUCIBILITY VALIDATION
Any contributor running the steps above on a clean amd64 Linux environment will reproduce identical boot sectors, directory layout, and binary payload matching the release manifest.
