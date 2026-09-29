#!/bin/bash
# MitraNet - Offline Package Bundler & Repository Generator
set -e

TARGET_DIR="${1:-bulid/iso_root}"
PKG_SOURCE_DIR="${2:-bulid/packages_cache}"

echo "=========================================================="
echo " [MitraNet] Offline Package Bundler (Bash)"
echo "=========================================================="

PKG_DEST="${TARGET_DIR}/packages/All"
REPO_CONF_DIR="${TARGET_DIR}/usr/local/etc/pkg/repos"

mkdir -p "${PKG_DEST}"
mkdir -p "${REPO_CONF_DIR}"
mkdir -p "${PKG_SOURCE_DIR}"

echo "[*] Checking for cached packages in: ${PKG_SOURCE_DIR}"

if compgen -G "${PKG_SOURCE_DIR}/*.pkg" > /dev/null; then
    echo "[*] Copying offline packages to ISO staging: ${PKG_DEST}..."
    cp -f "${PKG_SOURCE_DIR}"/*.pkg "${PKG_DEST}/"
    
    # Generate local pkg repository catalog if pkg tool exists
    if command -v pkg &> /dev/null; then
        echo "[*] Generating repo catalog (packagesite.pkg) using 'pkg repo'..."
        pkg repo "${TARGET_DIR}/packages" || true
    fi
else
    echo "[!] No .pkg files found in ${PKG_SOURCE_DIR}."
    echo "    To extract packages from VM, run inside the VM terminal:"
    echo "      mkdir -p /tmp/pkgs && pkg create -a -o /tmp/pkgs/"
    echo "    Or copy from /var/cache/pkg/*.pkg to ${PKG_SOURCE_DIR}"
fi

# Generate Offline Repository Configuration
echo "[*] Writing offline pkg repository configuration..."
cat > "${REPO_CONF_DIR}/MitraNet-offline.conf" << 'EOF'
MitraNet-Offline: {
  url: "file:///packages",
  mirror_type: "NONE",
  enabled: yes
}

FreeBSD: {
  enabled: no
}

pfSense: {
  enabled: no
}
EOF

echo "[+] Offline repository configuration generated at:"
echo "    ${REPO_CONF_DIR}/MitraNet-offline.conf"
echo "[+] All offline packages ready in: ${PKG_DEST}"
