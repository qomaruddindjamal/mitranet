#!/bin/bash
# MitraNet - Unpack OSNetwork ISO
set -e

SOURCE_ISO="${1:-sources/netgate-installer-amd64.iso}"
TARGET_DIR="${2:-bulid/iso_root}"
BOOT_DIR="bulid/boot"

echo "=== [MitraNet] Unpacking OSNetwork ISO ==="
echo "[*] Source ISO : ${SOURCE_ISO}"
echo "[*] Destination: ${TARGET_DIR}"

if [ ! -f "${SOURCE_ISO}" ]; then
    echo "[-] Error: Source ISO not found at ${SOURCE_ISO}"
    exit 1
fi

mkdir -p "${TARGET_DIR}" "${BOOT_DIR}"

echo "[*] Extracting ISO contents with 7z..."
7z x "${SOURCE_ISO}" -o"${TARGET_DIR}" -y > /dev/null

# Extract boot images if present in [BOOT]
if [ -d "${TARGET_DIR}/[BOOT]" ]; then
    echo "[*] Preserving El Torito Boot Images..."
    cp -f "${TARGET_DIR}/[BOOT]/1-Boot-NoEmul.img" "${BOOT_DIR}/biosboot.img" 2>/dev/null || true
    cp -f "${TARGET_DIR}/[BOOT]/2-Boot-NoEmul.img" "${BOOT_DIR}/efiboot.img" 2>/dev/null || true
    rm -rf "${TARGET_DIR}/[BOOT]"
fi

echo "[+] ISO successfully unpacked to ${TARGET_DIR}"
echo "[+] Total files: $(find "${TARGET_DIR}" -type f | wc -l)"
