# MitraNet - Testing & Verification Guide

Panduan pengujian dan verifikasi untuk fitur-fitur **MitraNet OS** serta integrasi protokol **V2Ray / Xray (VLESS & VMESS)**.

---

## 1. Menjalankan Automated Test Suite

Untuk memastikan seluruh template konfigurasi JSON valid dan modul parsing link `vless://` / `vmess://` berfungsi normal:

### Di Linux / Codespace:
```bash
bash test/test_v2ray.sh
```

### Di Windows:
```powershell
python test\test_v2ray.py
```

### Hasil yang Diharapkan:
```text
Ran 5 tests in 0.005s

OK
[OK] Full Config generation verified successfully.
[OK] Configuration template verified: config_transparent_proxy.json
[OK] Configuration template verified: config_vless_reality.json
[OK] Configuration template verified: config_vless_ws.json
[OK] Configuration template verified: config_vmess_ws.json
[OK] VLESS Reality Link Parser verified successfully.
[OK] VLESS WebSocket Link Parser verified successfully.
[OK] VMESS Link Parser verified successfully.
```

---

## 2. Pengujian Emulasi Booting Sistem dengan QEMU

Pengujian image ISO dilakukan menggunakan virtual machine QEMU:

### A. Pengujian Headless (Serial Console Terminal)
```bash
bash test/test_qemu.sh --headless
```
*Gunakan pintasan `Ctrl + A` lalu tekan `X` untuk keluar dari QEMU.*

### B. Pengujian dengan Tampilan Grafis (VGA / GUI)
```bash
bash test/test_qemu.sh --gui
```

### C. Pemetaan Port Jaringan Virtual:
- Host `https://localhost:8443` -> Guest `443` (Web Installer & GUI)
- Host `http://localhost:8080` -> Guest `8080` (MitraNet Web API)
- Host `localhost:10808` -> Guest `10808` (SOCKS5 Proxy)

---

## 3. Matriks Pengujian Fitur

| Komponen | Skenario Uji | Status |
| :--- | :--- | :--- |
| **VLESS Reality** | Parse link, validasi `xtls-rprx-vision`, SNI `www.apple.com`, Reality Public Key | PASSED |
| **VLESS WS** | Parse link, validasi WebSocket stream, Path `/ws-tunnel`, TLS handshake | PASSED |
| **VMESS WS** | Decode base64 JSON, validasi AEAD AlterId 0, UUID otentikasi | PASSED |
| **Full Config Builder** | Inbound SOCKS (10808), HTTP (10809), Dokodemo TProxy (12345) & Private IP bypass | PASSED |
| **pf Firewall Redirection** | Syntax check `pf_xray.conf`, redirection port 53 & 12345 | PASSED |
| **Web API Daemon** | Status check endpoint, list nodes, node switching | PASSED |
