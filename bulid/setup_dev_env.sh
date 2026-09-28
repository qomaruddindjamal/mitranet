#!/bin/bash
# MitraNet - Development Environment Initialization Script
set -e

echo "=== [MitraNet] Setting up Dev Environment ==="

# Check required tools
REQUIRED_TOOLS=("xorriso" "7z" "python3" "curl" "jq")
MISSING=()

for tool in "${REQUIRED_TOOLS[@]}"; do
    if ! command -v "$tool" &> /dev/null; then
        MISSING+=("$tool")
    fi
done

if [ ${#MISSING[@]} -gt 0 ]; then
    echo "[!] Missing tools: ${MISSING[*]}"
    if [ -f /etc/debian_version ]; then
        echo "[*] Attempting apt-get installation..."
        sudo apt-get update && sudo apt-get install -y "${MISSING[@]}"
    else
        echo "[-] Please install: ${MISSING[*]}"
        exit 1
    fi
fi

# Ensure Python requirements
python3 -c "import requests, fastapi, uvicorn" 2>/dev/null || {
    echo "[*] Installing Python test packages..."
    pip3 install --no-cache-dir requests fastapi uvicorn pydantic 2>/dev/null || true
}

# Fetch FreeBSD Xray if needed
if [ ! -f "programs/xray/bin/xray" ]; then
    if [ -f "/opt/xray-freebsd/xray" ]; then
        mkdir -p programs/xray/bin
        cp /opt/xray-freebsd/xray programs/xray/bin/
        [ -f "/opt/xray-freebsd/geoip.dat" ] && cp /opt/xray-freebsd/*.dat programs/xray/bin/ || true
        chmod +x programs/xray/bin/xray
        echo "[+] Copied Xray FreeBSD binary from container cache."
    else
        bash programs/xray/bin/fetch_xray_freebsd.sh || echo "[!] Could not download Xray binary automatically. Run fetch_xray_freebsd.sh manually."
    fi
fi

echo "[+] MitraNet dev environment ready!"
