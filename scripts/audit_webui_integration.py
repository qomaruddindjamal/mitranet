#!/usr/bin/env python3
"""
MitraNet Phase 2F - Web UI Integration Audit Script
Project: MitraNet
Code OS: Rinjani
Foundation: MitraOS 1.0.0
Architecture: amd64
"""

import os
import sys
import re

BASE_DIR = r"C:\mitranet"
WEB_DIR = os.path.join(BASE_DIR, "public_html")

def main():
    print("=" * 60)
    print("MitraNet Phase 2F - Web UI Integration Audit")
    print("=" * 60)

    assert os.path.exists(WEB_DIR), "public_html missing"
    
    # 1. Audit dangerous execution in PHP presentation files
    dangerous_calls = []
    for root, dirs, files in os.walk(WEB_DIR):
        for f in files:
            if f.endswith(".php") and f != "guiconfig.php":
                p = os.path.join(root, f)
                with open(p, "r", encoding="utf-8", errors="ignore") as fp:
                    cnt = fp.read()
                for fn in ["shell_exec", "passthru", "system", "proc_open", "popen"]:
                    if re.search(rf"\b{fn}\s*\(", cnt):
                        dangerous_calls.append((f, fn))

    assert len(dangerous_calls) == 0, f"Dangerous PHP functions found in pages: {dangerous_calls}"
    print("[PASS] Presentation PHP pages: Zero raw shell execution detected.")

    # 2. Check client API standardization in common.js
    common_js = os.path.join(WEB_DIR, "includes", "common.js")
    with open(common_js, "r", encoding="utf-8") as fp:
        c_js = fp.read()
    assert "apiFetch" in c_js, "apiFetch missing from common.js"
    assert "escapeHtml" in c_js, "escapeHtml missing from common.js"
    print("[PASS] Client API Client & XSS sanitizer verified in common.js.")

    # 3. Check Web UI module integrity
    modules = ["interfaces", "firewall", "routing", "qos", "vpn", "zones", "diagnostics", "hardware", "system", "terminal"]
    for m in modules:
        m_path = os.path.join(WEB_DIR, m)
        assert os.path.exists(m_path), f"Module {m} missing from public_html"
    print(f"[PASS] All {len(modules)} Web UI modules verified intact.")

    print("\nPhase 2F Web UI Audit finished successfully: 100% PASS.")

if __name__ == "__main__":
    main()
