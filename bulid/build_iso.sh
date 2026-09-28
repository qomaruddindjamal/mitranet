#!/bin/bash
# MitraNet - Bootable Hybrid UEFI/BIOS ISO Rebuilder
set -e

SOURCE_DIR="${1:-bulid/iso_root}"
OUTPUT_ISO="${2:-ISO/MitraNet-OS-amd64.iso}"
VOLUME_LABEL="MITRANET"
BOOT_DIR="bulid/boot"

echo "=== [MitraNet] Building Custom Bootable ISO ==="
echo "[*] Source Filesystem: ${SOURCE_DIR}"
echo "[*] Output ISO       : ${OUTPUT_ISO}"

if [ ! -d "${SOURCE_DIR}" ]; then
    echo "[-] Error: Source directory ${SOURCE_DIR} not found. Unpack and inject first."
    exit 1
fi

mkdir -p "$(dirname "${OUTPUT_ISO}")" "${BOOT_DIR}"

# 1. Prepare EFI boot image if missing
EFI_IMG="${BOOT_DIR}/efiboot.img"
if [ ! -f "${EFI_IMG}" ]; then
    echo "[*] Generating EFI boot image (${EFI_IMG})..."
    dd if=/dev/zero of="${EFI_IMG}" bs=1M count=4 2>/dev/null
    mkfs.vfat "${EFI_IMG}" > /dev/null
    mmd -i "${EFI_IMG}" ::EFI ::EFI/BOOT 2>/dev/null || true
    if [ -f "${SOURCE_DIR}/boot/loader.efi" ]; then
        mcopy -i "${EFI_IMG}" "${SOURCE_DIR}/boot/loader.efi" ::EFI/BOOT/BOOTX64.EFI
    fi
fi

# 2. Run xorriso to build the Hybrid ISO
echo "[*] Running xorriso to generate Hybrid UEFI/BIOS ISO..."

XORRISO_ARGS=(
    -as mkisofs
    -V "${VOLUME_LABEL}"
    -J -R
    -iso-level 3
)

# If BIOS bootloader exists
if [ -f "${SOURCE_DIR}/boot/cdboot" ]; then
    XORRISO_ARGS+=(
        -b "boot/cdboot"
        -no-emul-boot
        -boot-load-size 4
    )
fi

# If EFI boot image exists
if [ -f "${EFI_IMG}" ]; then
    XORRISO_ARGS+=(
        -eltorito-alt-boot
        -e "boot/efiboot.img"
        -no-emul-boot
        -isohybrid-gpt-basdat
    )
    # copy into staging so xorriso finds it
    cp -f "${EFI_IMG}" "${SOURCE_DIR}/boot/efiboot.img"
fi

xorriso "${XORRISO_ARGS[@]}" -o "${OUTPUT_ISO}" "${SOURCE_DIR}"

# Clean temp efi image in source dir
rm -f "${SOURCE_DIR}/boot/efiboot.img"

echo "[+] Successfully created bootable ISO: ${OUTPUT_ISO}"
ls -lh "${OUTPUT_ISO}"
sha256sum "${OUTPUT_ISO}" > "${OUTPUT_ISO}.sha256"
echo "[+] SHA256 Checksum generated: $(cat "${OUTPUT_ISO}.sha256")"
