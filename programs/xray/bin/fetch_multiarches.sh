#!/bin/bash
# MitraNet - Download Xray-core for All Supported Architectures
set -e

XRAY_VER="${1:-v24.9.30}"
BASE_DIR="${2:-/workspace/programs/xray/bin/arches}"

echo "=== [MitraNet] Multi-Architecture Xray Downloader (${XRAY_VER}) ==="
mkdir -p "${BASE_DIR}"

declare -A ARCH_MAP=(
    ["x86_64"]="Xray-linux-64.zip"
    ["freebsd_amd64"]="Xray-freebsd-64.zip"
    ["arm64"]="Xray-linux-arm64-v8a.zip"
    ["armv7"]="Xray-linux-arm32-v7a.zip"
    ["armv6"]="Xray-linux-arm32-v6.zip"
    ["mipsbe"]="Xray-linux-mips32.zip"
    ["mipsle"]="Xray-linux-mips32le.zip"
    ["ppc64le"]="Xray-linux-ppc64le.zip"
    ["riscv64"]="Xray-linux-riscv64.zip"
)

for arch in "${!ARCH_MAP[@]}"; do
    filename="${ARCH_MAP[$arch]}"
    outdir="${BASE_DIR}/${arch}"
    mkdir -p "${outdir}"
    
    url="https://github.com/XTLS/Xray-core/releases/download/${XRAY_VER}/${filename}"
    echo "[*] Fetching ${arch} from ${filename}..."
    
    tmp_zip="/tmp/${filename}"
    if curl -sSL -f "${url}" -o "${tmp_zip}"; then
        7z x "${tmp_zip}" -o"${outdir}" -y xray geoip.dat geosite.dat > /dev/null 2>&1 || true
        [ -f "${outdir}/xray" ] && chmod +x "${outdir}/xray"
        rm -f "${tmp_zip}"
        echo "[+] Successfully downloaded: ${arch}"
    else
        echo "[!] Warning: Download failed for ${arch} (${url}). Can be built from source."
        rm -f "${tmp_zip}"
    fi
done

echo "=== [MitraNet] Multi-Architecture Binaries Prepared in ${BASE_DIR} ==="
ls -lh "${BASE_DIR}"/*
