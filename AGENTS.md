# MitraNet Rinjani 1.0.2 — AI Agent Rules & Operational Standards

## 1. MANDATORY Post-Update Workflow (Golden Rule)
Setiap kali ada pembaruan kode program (baik di `web/`, `core/`, `src/`, atau konfigurasi sistem):
Anda **WAJIB** secara otomatis menjalankan rangkaian 3 langkah verifikasi & deployment:
1. **Sinkronisasi ke Mini PC (`10.10.66.228`)**:
   - Upload file yang diperbarui ke `/mitranet/web`, `/usr/share/mitranet/web`, `/mitranet/core`, dan `/mitranet/src`.
   - Pastikan service WebUI (`php -S 127.0.0.1:8000`) dan REST API berjalan baik di Mini PC.
2. **Rebuild File ISO**:
   - Jalankan proses build ISO menggunakan `build/build_iso.py` (`xorriso`).
   - Pastikan ISO di `iso/MitraNet-Rinjani-1.0.2-amd64.iso` berhasil ter-generate tanpa error.
3. **Commit & Push ke GitHub**:
   - Stage perubahan (`git add .`).
   - Commit dengan pesan deskriptif yang relevan.
   - Lakukan `git push origin main` ke repositori `https://github.com/qomaruddindjamal/mitranet.git`.

> **Utility Pintas**: Anda dapat menjalankan `python c:\mitranet\deploy_pipeline.py "<pesan commit>"` untuk mengeksekusi ketiga langkah di atas secara terpadu dan otomatis.

---

## 2. Standar Desain & Interaksi WebUI
- **SweetAlert2 untuk Semua Aksi CRUD**:
  - Konfirmasi Hapus (`MitraNet.confirmDelete`)
  - Input Cepat / Komentar / Rename (`MitraNet.promptInput`)
  - Notifikasi Toast Pojok Kanan Atas (`MitraNet.toast`)
  - Konfirmasi Aksi Sistem / Restart / Shutdown (`MitraNet.rebootSystem`, `MitraNet.shutdownSystem`)
- **Estetika WinBox**:
  - Pertahankan styling `.mitranet-window`, `.mitranet-grid`, dan modal WinBox dua kolom untuk form konfigurasi yang kompleks.
- **Konsistensi Struktur Direktori**:
  - Jangan mengubah struktur modul yang sudah dikelompokkan pengguna (`services/`, `firewall/`, `system/`, `vpn/`, `status/`, `diagnostics/`, `packages/`, `terminal/`, `tools/`, `xray/`, `qos/`, `vOlt/`, `wifi/`, `interfaces/`).
  - Selalu pastikan path file pendukung menggunakan `require_once(__DIR__ . '/../includes/head.inc')` dan `foot.inc`.
