#!/bin/bash
# ============================================================
# MitraNet Package Build & Staging Script (Phase A4 SSoT)
# Usage: bash build_packages.sh [version] [revision]
# Example: bash build_packages.sh 1.0.2 3~deb13u1
# Requires: bash, rsync/cp (Linux/WSL) or Python fallback for staging
# Builds from CANONICAL sources (web/, core/, src/) into staging trees.
# Outputs .deb packages to packages/pool/ if dpkg-deb is available.
# ============================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR"
PKG_DEBIAN_DIR="$PROJECT_ROOT/packages/debian"
STAGE_BASE="$PROJECT_ROOT/build/stage"
POOL_DIR="$PROJECT_ROOT/packages/pool"

VERSION="${1:-1.0.2}"
REVISION="${2:-3~deb13u1}"
DEB_VERSION="${VERSION}-${REVISION}"

echo "==================================================="
echo "  MitraNet SSoT Package Builder v${DEB_VERSION}"
echo "==================================================="

# Verify canonical sources exist
for req in "$PROJECT_ROOT/web" "$PROJECT_ROOT/core" "$PROJECT_ROOT/src"; do
    if [ ! -d "$req" ]; then
        echo "ERROR: Canonical source directory missing: $req" >&2
        exit 1
    fi
done

# Prepare staging directories
rm -rf "$STAGE_BASE"
mkdir -p "$STAGE_BASE"
mkdir -p "$POOL_DIR"

# Common helper function to copy tree without cache/temporary artifacts
copy_clean() {
    local src="$1"
    local dst="$2"
    mkdir -p "$dst"
    if command -v rsync >/dev/null 2>&1; then
        rsync -a --exclude='__pycache__' --exclude='*.pyc' --exclude='.pytest_cache' "$src/" "$dst/"
    else
        cp -a "$src/." "$dst/"
        find "$dst" -name '__pycache__' -exec rm -rf {} + 2>/dev/null || true
        find "$dst" -name '*.pyc' -delete 2>/dev/null || true
    fi
}

# Namespace bootstrap __init__.py content
PEP_INIT='# MitraNet OS Package Namespace
from pkgutil import extend_path
__path__ = extend_path(__path__, __name__)
'

# ==============================================================================
# 1. Package: mitranet-config-engine
# Contract:
#   Static: packages/debian/mitranet-config-engine/* (DEBIAN/, etc/)
#   Python:
#     .../dist-packages/mitranet/__init__.py
#     .../dist-packages/mitranet/core/__init__.py
#     .../dist-packages/mitranet/core/config/** (from core/config)
#     .../dist-packages/mitranet/core/transaction/** (from core/transaction)
# ==============================================================================
echo "[1/4] Staging mitranet-config-engine..."
STAGE_CFG="$STAGE_BASE/mitranet-config-engine"
copy_clean "$PKG_DEBIAN_DIR/mitranet-config-engine" "$STAGE_CFG"

PY_CFG="$STAGE_CFG/usr/lib/python3/dist-packages/mitranet"
mkdir -p "$PY_CFG/core"
echo "$PEP_INIT" > "$PY_CFG/__init__.py"
echo "$PEP_INIT" > "$PY_CFG/core/__init__.py"

copy_clean "$PROJECT_ROOT/core/config" "$PY_CFG/core/config"
copy_clean "$PROJECT_ROOT/core/transaction" "$PY_CFG/core/transaction"
echo "  OK: mitranet-config-engine staged."

# ==============================================================================
# 2. Package: mitranet-network-engine
# Contract:
#   Static: packages/debian/mitranet-network-engine/* (DEBIAN/)
#   Python:
#     .../dist-packages/mitranet/__init__.py
#     .../dist-packages/mitranet/core/__init__.py
#     .../dist-packages/mitranet/core/network/** (from core/network)
# ==============================================================================
echo "[2/4] Staging mitranet-network-engine..."
STAGE_NET="$STAGE_BASE/mitranet-network-engine"
copy_clean "$PKG_DEBIAN_DIR/mitranet-network-engine" "$STAGE_NET"

PY_NET="$STAGE_NET/usr/lib/python3/dist-packages/mitranet"
mkdir -p "$PY_NET/core"
echo "$PEP_INIT" > "$PY_NET/__init__.py"
echo "$PEP_INIT" > "$PY_NET/core/__init__.py"

copy_clean "$PROJECT_ROOT/core/network" "$PY_NET/core/network"
echo "  OK: mitranet-network-engine staged."

# ==============================================================================
# 3. Package: mitranet-gateway-monitor
# Contract:
#   Static: packages/debian/mitranet-gateway-monitor/* (DEBIAN/, lib/, usr/bin/)
# ==============================================================================
echo "[3/4] Staging mitranet-gateway-monitor..."
STAGE_GWM="$STAGE_BASE/mitranet-gateway-monitor"
copy_clean "$PKG_DEBIAN_DIR/mitranet-gateway-monitor" "$STAGE_GWM"
echo "  OK: mitranet-gateway-monitor staged."

# ==============================================================================
# 4. Package: mitranet-core
# Contract:
#   Static: packages/debian/mitranet-core/* (DEBIAN/, etc/, lib/, usr/bin/)
#   Webroot: /usr/share/mitranet/web (from web/)
#   Python core:
#     .../dist-packages/mitranet/__init__.py
#     .../dist-packages/mitranet/core/__init__.py
#     .../dist-packages/mitranet/core/version.py (from core/version.py)
#     .../dist-packages/mitranet/core/firewall/** (from core/firewall)
#     .../dist-packages/mitranet/core/migration/** (from core/migration)
#     .../dist-packages/mitranet/core/services/** (from core/services)
#   Python src:
#     .../dist-packages/mitranet/src/** (from src/)
#     .../dist-packages/mitranet/api/** (from src/api)
# ==============================================================================
echo "[4/4] Staging mitranet-core..."
STAGE_CORE="$STAGE_BASE/mitranet-core"
copy_clean "$PKG_DEBIAN_DIR/mitranet-core" "$STAGE_CORE"

# Ensure NO var/www in stage
rm -rf "$STAGE_CORE/var/www"

# Webroot
mkdir -p "$STAGE_CORE/usr/share/mitranet"
copy_clean "$PROJECT_ROOT/web" "$STAGE_CORE/usr/share/mitranet/web"

# Python modules
PY_CORE="$STAGE_CORE/usr/lib/python3/dist-packages/mitranet"
mkdir -p "$PY_CORE/core"
echo "$PEP_INIT" > "$PY_CORE/__init__.py"
echo "$PEP_INIT" > "$PY_CORE/core/__init__.py"

cp "$PROJECT_ROOT/core/version.py" "$PY_CORE/core/version.py"
copy_clean "$PROJECT_ROOT/core/firewall" "$PY_CORE/core/firewall"
copy_clean "$PROJECT_ROOT/core/migration" "$PY_CORE/core/migration"
copy_clean "$PROJECT_ROOT/core/services" "$PY_CORE/core/services"

copy_clean "$PROJECT_ROOT/src" "$PY_CORE/src"
copy_clean "$PROJECT_ROOT/src/api" "$PY_CORE/api"
echo "  OK: mitranet-core staged."

# ==============================================================================
# Update Control Files, Versions & Calculate Installed-Size
# ==============================================================================
echo ""
echo "Updating control versions and sizes..."
for pkg_path in "$STAGE_BASE"/*; do
    [ -d "$pkg_path/DEBIAN" ] || continue
    ctrl="$pkg_path/DEBIAN/control"
    sed -i "s/^Version:.*/Version: ${DEB_VERSION}/" "$ctrl"
    PKG_NAME=$(grep "^Package:" "$ctrl" | awk '{print $2}')
    SIZE=$(du -sk "$pkg_path" | awk '{print $1}')
    if grep -q "^Installed-Size:" "$ctrl"; then
        sed -i "s/^Installed-Size:.*/Installed-Size: ${SIZE}/" "$ctrl"
    else
        echo "Installed-Size: ${SIZE}" >> "$ctrl"
    fi
    chmod 755 "$pkg_path/DEBIAN/postinst" 2>/dev/null || true
    chmod 755 "$pkg_path/DEBIAN/postrm" 2>/dev/null || true
    chmod 755 "$pkg_path/DEBIAN/prerm" 2>/dev/null || true
    chmod 644 "$ctrl"
    echo "  Configured: $PKG_NAME (v${DEB_VERSION}, ${SIZE} KB)"
done

# ==============================================================================
# Build .deb Packages (if dpkg-deb and fakeroot are available)
# ==============================================================================
echo ""
if command -v dpkg-deb >/dev/null 2>&1 && command -v fakeroot >/dev/null 2>&1; then
    echo "Building .deb packages using fakeroot dpkg-deb..."
    for pkg_path in "$STAGE_BASE"/*; do
        [ -d "$pkg_path/DEBIAN" ] || continue
        PKG_NAME=$(grep "^Package:" "$pkg_path/DEBIAN/control" | awk '{print $2}')
        OUT_DEB="$POOL_DIR/${PKG_NAME}_${DEB_VERSION}_all.deb"
        fakeroot dpkg-deb --build "$pkg_path" "$OUT_DEB"
        echo "  Built: $OUT_DEB"
    done
else
    echo "NOTICE: dpkg-deb/fakeroot not found in PATH."
    echo "Staging trees generated successfully in: $STAGE_BASE"
    echo "(Verification will evaluate canonical staging directory tree)"
fi

echo ""
echo "==================================================="
echo "  STAGE & BUILD PROCESS COMPLETE"
echo "==================================================="
