(SweetAlert2 & WinBox dipertahankan utuh, Bagian 1 diperbarui, Bagian 3 ditambah)

MitraNet Rinjani 1.0.2 — AI Agent Rules & Operational Standards

1. MANDATORY Post-Update Workflow (Golden Rule)
Setiap kali ada pembaruan kode program (baik di web/, core/, src/, atau konfigurasi sistem):Anda WAJIB menjalankan rangkaian verifikasi & deployment berikut:

Sinkronisasi ke Mini PC:
Host, user, dan password wajib dibaca dari mitranet_secrets.py + .env(DILARANG hardcode IP/password di kode maupun dokumentasi).
Jalankan python deploy_pipeline.py "<pesan commit>" — pipeline sudah hardening(guard .env gitignored, staging git add -u).
Pastikan service WebUI (php -S 127.0.0.1:8000) dan REST API berjalan baik;MITRANET_API_SECRET wajib ter-set di environment service Mini PC.
Rebuild Paket & ISO:
Build 4 paket .deb dari sumber kanonikal via build_packages.sh(DILARANG menyimpan salinan source di packages/debian/).
Build ISO via tools/build/build_iso.py (xorriso); pastikaniso/MitraNet-Rinjani-1.0.2-amd64.iso ter-generate: pool berisi .debrevisi terbaru + fping, tanpa path var/www.
Commit & Push ke GitHub:
Stage HANYA dengan git add -u atau git add <file> eksplisit.DILARANG git add . dan DILARANG git commit --amend.
Commit dengan pesan deskriptif; push ke branch kerja aktif;merge ke main hanya setelah lulus gerbang verifikasi.
Utility Pintas: python c:\mitranet\deploy_pipeline.py "<pesan commit>"mengeksekusi ketiga langkah di atas secara terpadu (sinkron → build → commit/push).

2. Standar Desain & Interaksi WebUI
SweetAlert2 untuk Semua Aksi CRUD:
Konfirmasi Hapus (MitraNet.confirmDelete)
Input Cepat / Komentar / Rename (MitraNet.promptInput)
Notifikasi Toast Pojok Kanan Atas (MitraNet.toast)
Konfirmasi Aksi Sistem / Restart / Shutdown (MitraNet.rebootSystem, MitraNet.shutdownSystem)
Estetika WinBox:
Pertahankan styling .mitranet-window, .mitranet-grid, dan modal WinBox dua kolom untuk form konfigurasi yang kompleks.
Konsistensi Struktur Direktori:
Jangan mengubah struktur modul yang sudah dikelompokkan pengguna (services/, firewall/, system/, vpn/, status/, diagnostics/, packages/, terminal/, tools/, xray/, qos/, vOlt/, wifi/, interfaces/).
Selalu pastikan path file pendukung menggunakan require_once(__DIR__ . '/../includes/head.inc') dan foot.inc.
3. Standar Engineering (Phase A — WAJIB)
Kredensial: Semua script ops/test wajib membaca kredensial darimitranet_secrets.py + .env (gitignored). DILARANG hardcode password/IPprivat. MITRANET_API_SECRET wajib diset di environment service Mini PC.
Git: Dilarang commit secret & artefak biner (*.deb, *.iso, cookies).Dilarang git commit --amend. Staging hanya git add -u atau file eksplisit.
Struktur: tests/{unit,integration,audit,fixtures}, tools/{ops,oneoff,build}.pytest polos = unit only (pytest.ini); integration/audit dijalankan eksplisit.
Packaging: Single webroot /usr/share/mitranet/web. .deb dibangun darisumber kanonikal (web/, core/, src/) via build_packages.sh. Dilarangmenyimpan salinan source di packages/debian/.
Laporan: Tabel panjang → docs/verification/. Output chat agent ≤30 baris.Nilai secret wajib di-mask (<REDACTED>).
⚠️ Ingat: ini menggantikan Bagian 1 lama — jadi instruksi git add . dan IP hardcoded resmi hilang dari rulebook agent.