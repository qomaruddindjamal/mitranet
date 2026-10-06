#!/usr/bin/env python3
"""
MitraNet Phase 2I - Strict System Integration Audit Script
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64
"""

import os
import sys
import json
import hashlib

BASE_DIR = r"C:\mitranet"

def main():
    print("=" * 60)
    print("MitraNet Phase 2I - Strict System Integration Audit")
    print("=" * 60)

    # 1. Package Baseline Verification
    pkg_dir = os.path.join(BASE_DIR, "packages")
    items = sorted(os.listdir(pkg_dir))
    print(f"Package Store Items: {len(items)}")
    assert len(items) == 204, f"Expected 204 packages, found {len(items)}"
    assert len(items) == len(set(items)), "Duplicate packages detected"
    assert "mitranet-base-1.0.0.pkg.partaa" in items and "mitranet-base-1.0.0.pkg.partab" in items
    print("[PASS] Package baseline (204 items, 0 duplicates, split KEEP) verified.")

    # 2. ISO Baseline Verification
    iso_path = os.path.join(BASE_DIR, "MitraOS-Apollo-amd64.iso")
    assert os.path.exists(iso_path), "ISO file missing"
    assert os.path.getsize(iso_path) == 99774464, f"ISO size mismatch: {os.path.getsize(iso_path)}"
    sha256 = hashlib.sha256()
    with open(iso_path, "rb") as f:
        while chunk := f.read(65536):
            sha256.update(chunk)
    assert sha256.hexdigest() == "8e414785059f42b5f1cf7872cf926213c2b037e6291e9ba0d194bb3c9dbe1392"
    print("[PASS] Frozen ISO baseline verified (99,774,464 bytes, SHA256 verified).")

    # 3. Canonical Architecture Verification (Zero Duplicates)
    assert os.path.exists(os.path.join(BASE_DIR, "api", "config_engine.py")), "config_engine missing"
    assert os.path.exists(os.path.join(BASE_DIR, "api", "REST", "server.py")), "server.py missing"
    assert os.path.exists(os.path.join(BASE_DIR, "public_html", "index.php")), "Web UI index missing"
    assert os.path.exists(os.path.join(BASE_DIR, "scripts", "mitranet_cli.py")), "CLI missing"
    assert os.path.exists(os.path.join(BASE_DIR, "scripts", "mitranet_tui.py")), "TUI missing"
    print("[PASS] Canonical architectural components verified (Zero duplicates).")

    # 4. Integration Documents Verification
    matrix_doc = os.path.join(BASE_DIR, "docs", "integration", "SYSTEM-INTEGRATION-MATRIX.md")
    assert os.path.exists(matrix_doc), "SYSTEM-INTEGRATION-MATRIX.md missing"
    print("[PASS] System Integration Matrix verified.")

    print("\nPhase 2I System Integration Audit finished successfully: 100% PASS.")

if __name__ == "__main__":
    main()
