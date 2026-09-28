#!/bin/bash
# MitraNet - Download Xray-core for FreeBSD amd64
set -e

XRAY_VER="${1:-v24.9.30}"
TARGET_DIR="${2:-/workspace/programs/xray/bin}"

echo "[*] Fetching Xray-core ${XRAY_VER} for FreeBSD 64-bit..."
mkdir -p "${TARGET_DIR}"
TMP_ZIP="/tmp/xray-freebsd.zip"

curl -sSL "https://github.com/XTLS/Xray-core/releases/download/${XRAY_VER}/Xray-freebsd-64.zip" -o "${TMP_ZIP}"

7z x "${TMP_ZIP}" -o"${TARGET_DIR}" -y xray geoip.dat geosite.dat > /dev/null
chmod +x "${TARGET_DIR}/xray"
rm -f "${TMP_ZIP}"

echo "[+] Successfully downloaded Xray binary for FreeBSD to ${TARGET_DIR}/xray"
ls -lh "${TARGET_DIR}"
