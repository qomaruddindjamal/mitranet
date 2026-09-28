#!/bin/bash
# MitraNet - Run Test Suite
set -e

echo "=== [MitraNet] Running V2Ray/Xray Test Suite ==="
python3 test/test_v2ray.py
echo "[+] All automated tests passed successfully!"
