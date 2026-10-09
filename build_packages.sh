#!/bin/bash
# ============================================================
# MitraNet Package Build Script
# Usage: bash build_packages.sh [version]
# Example: bash build_packages.sh 1.0.2
# Requires: fakeroot, dpkg-deb, rsync (Linux/WSL)
# ============================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR"
PKG_DIR="$PROJECT_ROOT/packages/debian"
WEB_SRC="$PROJECT_ROOT/web"
OUT_DIR="$PKG_DIR"
VERSION="${1:-1.0.2}"
DEB_VERSION="${VERSION}-1~deb13u1"

echo "==================================================="
echo "  MitraNet Package Builder v${VERSION}"
echo "==================================================="

# Step 1: Sync web files
echo ""
echo "[1/5] Syncing web files to package directories..."
SHARE_WEB="$PKG_DIR/mitranet-core/usr/share/mitranet/web"
VAR_WEB="$PKG_DIR/mitranet-core/var/www/mitranet"
mkdir -p "$SHARE_WEB" "$VAR_WEB"
rsync -a --delete "$WEB_SRC/" "$SHARE_WEB/"
rsync -a --delete "$WEB_SRC/" "$VAR_WEB/"
echo "  OK: web/ synced to share and var/www"

# Step 2: Sync Python API source
echo ""
echo "[2/5] Syncing Python API source..."
API_PKG="$PKG_DIR/mitranet-core/usr/lib/python3/dist-packages/mitranet/api"
mkdir -p "$API_PKG"
cp -a "$PROJECT_ROOT/src/api/." "$API_PKG/"
echo "  OK: src/api/ synced"

# Step 3: Update version strings
echo ""
echo "[3/5] Updating version strings..."
for ctrl in "$PKG_DIR"/*/DEBIAN/control; do
    sed -i "s/^Version:.*/Version: ${DEB_VERSION}/" "$ctrl"
    PKG_NAME=$(grep "^Package:" "$ctrl" | awk '{print $2}')
    PKG_ROOT="$(dirname "$(dirname "$ctrl")")"
    SIZE=$(du -sk "$PKG_ROOT" | awk '{print $1}')
    sed -i "s/^Installed-Size:.*/Installed-Size: ${SIZE}/" "$ctrl"
    echo "  OK: $PKG_NAME version=${DEB_VERSION} size=${SIZE}KB"
done

# Step 4: Fix permissions
echo ""
echo "[4/5] Setting file permissions..."
for deb_pkg in "$PKG_DIR"/mitranet-*/; do
    [ -d "$deb_pkg/DEBIAN" ] || continue
    chmod 755 "$deb_pkg/DEBIAN/postinst" 2>/dev/null || true
    chmod 755 "$deb_pkg/DEBIAN/prerm" 2>/dev/null || true
    chmod 644 "$deb_pkg/DEBIAN/control" 2>/dev/null || true
done
echo "  OK: permissions set"

# Step 5: Build .deb files
echo ""
echo "[5/5] Building .deb packages..."
BUILT=0
for deb_pkg in "$PKG_DIR"/mitranet-*/; do
    [ -d "$deb_pkg/DEBIAN" ] || continue
    PKG_NAME=$(grep "^Package:" "$deb_pkg/DEBIAN/control" | awk '{print $2}')
    OUT_FILE="$OUT_DIR/${PKG_NAME}_${DEB_VERSION}_all.deb"
    echo "  Building: $PKG_NAME..."
    fakeroot dpkg-deb --build "$deb_pkg" "$OUT_FILE"
    if [ -f "$OUT_FILE" ]; then
        SIZE=$(du -sh "$OUT_FILE" | awk '{print $1}')
        echo "  OK: ${PKG_NAME}_${DEB_VERSION}_all.deb (${SIZE})"
        BUILT=$((BUILT + 1))
    else
        echo "  FAILED: $PKG_NAME"
    fi
done

# Optional: Build local APT repo index
if command -v dpkg-scanpackages &>/dev/null; then
    echo ""
    echo "[BONUS] Building local APT repository index..."
    cd "$OUT_DIR"
    dpkg-scanpackages . /dev/null 2>/dev/null > Packages
    gzip -9c Packages > Packages.gz
    echo "  OK: APT Packages index created"
fi

echo ""
echo "==================================================="
echo "  BUILD COMPLETE: $BUILT package(s)"
echo "  Output: $OUT_DIR"
echo ""
echo "  Install with:"
echo "    sudo dpkg -i ${OUT_DIR}/mitranet-core_${DEB_VERSION}_all.deb"
echo "==================================================="
