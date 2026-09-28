#!/bin/bash
# MitraNet - QEMU Virtual Machine Test Runner
set -e

ISO_FILE="${1:-ISO/MitraNet-OS-amd64.iso}"
if [ ! -f "${ISO_FILE}" ]; then
    # Fallback to source ISO if custom ISO hasn't been built yet
    ISO_FILE="sources/netgate-installer-amd64.iso"
fi

MODE="${2:---headless}" # --headless or --gui
RAM="2048"
CPUS="2"

echo "=== [MitraNet] Launching QEMU VM Test ==="
echo "[*] ISO Image : ${ISO_FILE}"
echo "[*] Memory    : ${RAM} MB | CPUs: ${CPUS}"
echo "[*] Mode      : ${MODE}"

if ! command -v qemu-system-x86_64 &> /dev/null; then
    echo "[-] Error: qemu-system-x86_64 is not installed."
    exit 1
fi

# Locate OVMF UEFI firmware if available
OVMF_PATH=""
for p in /usr/share/OVMF/OVMF_CODE.fd /usr/share/ovmf/OVMF.fd /usr/share/edk2-ovmf/x64/OVMF_CODE.fd; do
    if [ -f "$p" ]; then
        OVMF_PATH="$p"
        break
    fi
done

QEMU_ARGS=(
    -m "${RAM}"
    -smp "${CPUS}"
    -cdrom "${ISO_FILE}"
    -boot d
    -net nic,model=virtio
    -net user,hostfwd=tcp::8443-:443,hostfwd=tcp::8080-:8080,hostfwd=tcp::10808-:10808
)

if [ -n "${OVMF_PATH}" ]; then
    echo "[*] Using UEFI Firmware: ${OVMF_PATH}"
    QEMU_ARGS+=(-bios "${OVMF_PATH}")
fi

if [ "${MODE}" = "--headless" ]; then
    echo "[*] Running in headless console mode (press Ctrl+A then X to exit QEMU)..."
    QEMU_ARGS+=(-nographic -serial mon:stdio)
else
    echo "[*] Running with display..."
    QEMU_ARGS+=(-vga virtio)
fi

echo "[*] Port Forwarding configured:"
echo "    Host https://localhost:8443 -> Guest 443 (Web GUI)"
echo "    Host http://localhost:8080   -> Guest 8080 (MitraNet API)"
echo "    Host localhost:10808         -> Guest 10808 (SOCKS5 Proxy)"

qemu-system-x86_64 "${QEMU_ARGS[@]}"
