"""
Cross-platform builder and stage orchestrator for MitraNet Debian packages (Phase A4 SSoT).
Executes staging of mitranet-core, mitranet-config-engine, mitranet-network-engine,
and mitranet-gateway-monitor from canonical repo sources (web/, core/, src/).
Supports building .deb via python dpkg-deb equivalent or staging tree verification.
"""

import os
import sys
import shutil
import subprocess
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
PKG_DEBIAN_DIR = PROJECT_ROOT / "packages" / "debian"
STAGE_BASE = PROJECT_ROOT / "build" / "stage"
POOL_DIR = PROJECT_ROOT / "packages" / "pool"

VERSION = "1.0.2"
REVISION = "3~deb13u1"
DEB_VERSION = f"{VERSION}-{REVISION}"

PEP_INIT = """# MitraNet OS Package Namespace
from pkgutil import extend_path
__path__ = extend_path(__path__, __name__)
"""

def clean_copy(src: Path, dst: Path):
    """Copies directory tree ignoring __pycache__, .pyc, and pytest caches."""
    if not src.exists():
        raise FileNotFoundError(f"Source missing: {src}")
    dst.mkdir(parents=True, exist_ok=True)
    for root, dirs, files in os.walk(src):
        # Filter out cache dirs
        dirs[:] = [d for d in dirs if d not in ("__pycache__", ".pytest_cache")]
        rel = os.path.relpath(root, src)
        target_dir = dst / rel if rel != "." else dst
        target_dir.mkdir(parents=True, exist_ok=True)
        for f in files:
            if f.endswith(".pyc"):
                continue
            shutil.copy2(os.path.join(root, f), target_dir / f)

def get_dir_size_kb(path: Path) -> int:
    total = 0
    for root, dirs, files in os.walk(path):
        for f in files:
            fp = os.path.join(root, f)
            total += os.path.getsize(fp)
    return max(1, total // 1024)

def build_stage():
    print(f"===================================================")
    print(f"  MitraNet SSoT Package Stager v{DEB_VERSION}")
    print(f"===================================================")

    for req in (PROJECT_ROOT / "web", PROJECT_ROOT / "core", PROJECT_ROOT / "src"):
        if not req.is_dir():
            print(f"ERROR: Missing canonical directory: {req}", file=sys.stderr)
            sys.exit(1)

    if STAGE_BASE.exists():
        shutil.rmtree(STAGE_BASE)
    STAGE_BASE.mkdir(parents=True, exist_ok=True)
    POOL_DIR.mkdir(parents=True, exist_ok=True)

    # 1. mitranet-config-engine
    print("[1/4] Staging mitranet-config-engine...")
    stage_cfg = STAGE_BASE / "mitranet-config-engine"
    clean_copy(PKG_DEBIAN_DIR / "mitranet-config-engine", stage_cfg)
    py_cfg = stage_cfg / "usr" / "lib" / "python3" / "dist-packages" / "mitranet"
    py_cfg_core = py_cfg / "core"
    py_cfg_core.mkdir(parents=True, exist_ok=True)
    (py_cfg / "__init__.py").write_text(PEP_INIT, encoding="utf-8")
    (py_cfg_core / "__init__.py").write_text(PEP_INIT, encoding="utf-8")
    clean_copy(PROJECT_ROOT / "core" / "config", py_cfg_core / "config")
    clean_copy(PROJECT_ROOT / "core" / "transaction", py_cfg_core / "transaction")
    print("  OK: mitranet-config-engine staged.")

    # 2. mitranet-network-engine
    print("[2/4] Staging mitranet-network-engine...")
    stage_net = STAGE_BASE / "mitranet-network-engine"
    clean_copy(PKG_DEBIAN_DIR / "mitranet-network-engine", stage_net)
    py_net = stage_net / "usr" / "lib" / "python3" / "dist-packages" / "mitranet"
    py_net_core = py_net / "core"
    py_net_core.mkdir(parents=True, exist_ok=True)
    (py_net / "__init__.py").write_text(PEP_INIT, encoding="utf-8")
    (py_net_core / "__init__.py").write_text(PEP_INIT, encoding="utf-8")
    clean_copy(PROJECT_ROOT / "core" / "network", py_net_core / "network")
    print("  OK: mitranet-network-engine staged.")

    # 3. mitranet-gateway-monitor
    print("[3/4] Staging mitranet-gateway-monitor...")
    stage_gwm = STAGE_BASE / "mitranet-gateway-monitor"
    clean_copy(PKG_DEBIAN_DIR / "mitranet-gateway-monitor", stage_gwm)
    print("  OK: mitranet-gateway-monitor staged.")

    # 4. mitranet-core
    print("[4/4] Staging mitranet-core...")
    stage_core = STAGE_BASE / "mitranet-core"
    clean_copy(PKG_DEBIAN_DIR / "mitranet-core", stage_core)
    
    # Ensure NO var/www in stage
    if (stage_core / "var" / "www").exists():
        shutil.rmtree(stage_core / "var" / "www")

    # Canonical Webroot
    web_dst = stage_core / "usr" / "share" / "mitranet" / "web"
    clean_copy(PROJECT_ROOT / "web", web_dst)

    # Canonical Python Core & Src
    py_core = stage_core / "usr" / "lib" / "python3" / "dist-packages" / "mitranet"
    py_core_sub = py_core / "core"
    py_core_sub.mkdir(parents=True, exist_ok=True)
    (py_core / "__init__.py").write_text(PEP_INIT, encoding="utf-8")
    (py_core_sub / "__init__.py").write_text(PEP_INIT, encoding="utf-8")
    shutil.copy2(PROJECT_ROOT / "core" / "version.py", py_core_sub / "version.py")
    clean_copy(PROJECT_ROOT / "core" / "firewall", py_core_sub / "firewall")
    clean_copy(PROJECT_ROOT / "core" / "migration", py_core_sub / "migration")
    clean_copy(PROJECT_ROOT / "core" / "services", py_core_sub / "services")

    clean_copy(PROJECT_ROOT / "src", py_core / "src")
    clean_copy(PROJECT_ROOT / "src" / "api", py_core / "api")
    print("  OK: mitranet-core staged.")

    # Update control files
    print("\nUpdating control versions & calculating sizes...")
    for pkg_item in stage_base_pkgs():
        ctrl = pkg_item / "DEBIAN" / "control"
        if not ctrl.exists():
            continue
        content = ctrl.read_text(encoding="utf-8")
        lines = []
        size_kb = get_dir_size_kb(pkg_item)
        has_size = False
        for l in content.splitlines():
            if l.startswith("Version:"):
                lines.append(f"Version: {DEB_VERSION}")
            elif l.startswith("Installed-Size:"):
                lines.append(f"Installed-Size: {size_kb}")
                has_size = True
            else:
                lines.append(l)
        if not has_size:
            lines.append(f"Installed-Size: {size_kb}")
        ctrl.write_text("\n".join(lines) + "\n", encoding="utf-8")
        print(f"  Configured: {pkg_item.name} (v{DEB_VERSION}, {size_kb} KB)")

    print(f"\nStaging complete in: {STAGE_BASE}")

def stage_base_pkgs():
    return [
        STAGE_BASE / "mitranet-config-engine",
        STAGE_BASE / "mitranet-network-engine",
        STAGE_BASE / "mitranet-gateway-monitor",
        STAGE_BASE / "mitranet-core",
    ]

if __name__ == "__main__":
    build_stage()
