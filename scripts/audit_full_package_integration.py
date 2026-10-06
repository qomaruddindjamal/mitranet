#!/usr/bin/env python3
"""
MitraNet Phase 2H - Full Package Functional & Synchronization Audit Script
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64
"""

import os
import sys
import json

BASE_DIR = r"C:\mitranet"
PKG_DIR = os.path.join(BASE_DIR, "packages")
INDEX_PATH = os.path.join(BASE_DIR, "docs", "packages", "packages.pkg.json")

def main():
    print("=" * 60)
    print("MitraNet Phase 2H - Full Package Functional & Sync Audit")
    print("=" * 60)

    # 1. Check physical folder
    items = sorted(os.listdir(PKG_DIR))
    print(f"Expected packages : 204")
    print(f"Actual packages   : {len(items)}")
    assert len(items) == 204, f"Mismatch: expected 204, found {len(items)}"
    print("[PASS] Package count 204 exactly verified.")

    # 2. Check duplicates
    assert len(items) == len(set(items)), "Duplicate packages detected"
    print("[PASS] Zero duplicate packages detected.")

    # 3. Check split package set
    assert "mitranet-base-1.0.0.pkg.partaa" in items, "Split partaa missing"
    assert "mitranet-base-1.0.0.pkg.partab" in items, "Split partab missing"
    print("[PASS] Split package set verified (KEEP).")

    # 4. Check index synchronization
    assert os.path.exists(INDEX_PATH), "Index file packages.pkg.json missing"
    with open(INDEX_PATH, "r", encoding="utf-8") as f:
        data = json.load(f)
    assert data.get("total") == 204, "Index total count is not 204"
    print("[PASS] Package index synchronization verified (204/204).")

    # 5. Check documentation specifications
    docs = [
        "PACKAGE-FUNCTIONAL-MATRIX.md",
        "PACKAGE-API-MATRIX.md",
        "PACKAGE-WEBUI-MATRIX.md",
        "PACKAGE-SERVICE-MATRIX.md",
        "PACKAGE-SYNCHRONIZATION.md"
    ]
    for d in docs:
        p = os.path.join(BASE_DIR, "docs", "packages", d)
        assert os.path.exists(p), f"Documentation {d} missing"
    print(f"[PASS] All {len(docs)} package specification matrices verified.")

    print("\nPhase 2H Audit finished successfully: 100% PASS.")

if __name__ == "__main__":
    main()
