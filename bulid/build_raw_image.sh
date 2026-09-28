#!/bin/bash
# MitraNet - Build Raw Disk Image for VPS Auto-Installer
set -e

SOURCE_ISO="${1:-images/MitraNet-OS-amd64.iso}"
[ ! -f "${SOURCE_ISO}" ] && SOURCE_ISO="sources/netgate-installer-amd64.iso"

OUTPUT_RAW="images/releases/MitraNet-OS-amd64.raw"
OUTPUT_GZ="${OUTPUT_RAW}.gz"
DISK_SIZE_MB="2048"

echo "=== [MitraNet] Building Raw VPS Disk Image ==="
echo "[*] Source ISO : ${SOURCE_ISO}"
echo "[*] Output RAW : ${OUTPUT_GZ}"

mkdir -p "images/releases"

if [ ! -f "${SOURCE_ISO}" ]; then
    echo "[-] Error: Source ISO not found at ${SOURCE_ISO}"
    exit 1
fi

echo "[*] Allocating ${DISK_SIZE_MB}MB raw disk..."
dd if=/dev/zero of="${OUTPUT_RAW}" bs=1M count="${DISK_SIZE_MB}" status=none

echo "[*] Writing ISO structure and boot records to raw disk..."
dd if="${SOURCE_ISO}" of="${OUTPUT_RAW}" bs=4M conv=notrunc status=none

echo "[*] Compressing with gzip..."
gzip -f -9 "${OUTPUT_RAW}"

echo "[+] Successfully created: ${OUTPUT_GZ} ($(ls -lh "${OUTPUT_GZ}" | awk '{print $5}'))"
sha256sum "${OUTPUT_GZ}" > "${OUTPUT_GZ}.sha256"
echo "[+] SHA256: $(cat "${OUTPUT_GZ}.sha256")"
