# MITRANET RESUME — PANDUAN PEMULIHAN KONTEKS & PERCAKAPAN BARU

Ketika memulai conversation baru atau memulihkan pekerjaan setelah konteks terpotong:

1. Baca `MITRANET_RESUME.md`.
2. Baca `MITRANET_WORK_STATE.md`.
3. Baca `MITRANET_NEXT_ACTION.md`.
4. Verifikasi kondisi aktual repository dan sistem sebelum melanjutkan.
5. Jangan mengulang audit yang sudah selesai dan terbukti.
6. Lanjutkan dari langkah pertama yang belum selesai.
7. Jika keadaan aktual berbeda dari checkpoint, perbarui checkpoint berdasarkan bukti terbaru.
8. Lakukan perbaikan, tes, sinkronisasi ke Mini PC bila diperlukan, dan verifikasi integrasi.
9. Perbarui checkpoint setelah setiap tahap penting.
10. Jangan berhenti hanya untuk memberikan laporan audit.
11. Jika konteks mulai penuh atau pekerjaan mendekati batas yang aman untuk melanjutkan, simpan checkpoint terbaru sebelum meneruskan pekerjaan besar.
12. Jika tidak ada lagi ruang untuk melanjutkan dengan aman, tinggalkan checkpoint yang lengkap sehingga agent berikutnya dapat meneruskan pekerjaan.

---

### RINGKASAN STRUKTUR SISTEM MITRANET
- **Workspace**: `c:\mitranet` (Windows)
- **Target Hardware**: Mini PC x86_64 Debian GNU/Linux (`10.10.66.228`)
- **WebUI Port**: HTTP `8000` (`/mitranet/web`, dialihkan oleh PHP built-in server)
- **Management API Port**: HTTP `8443` (Python REST API `/mitranet/src/api/server.py`)
- **Aturan Operasional Wajib**: Ikuti batasan dan standar di `AGENTS.md` dan `RecoveryAgents.md`.
- **Status Rilis MitraNet Rinjani 1.0.2**:
  - Baseline commit: `7929a97`
  - Berkas ISO: `iso/MitraNet-Rinjani-1.0.2-amd64.iso` (SHA256: `9E544A245372965D7D7688B0B54370113C7280E8A2C3A40ADECC435FFAEA3A09`, 1,017,139,200 bytes)
  - Validasi multi-stream WireGuard (Stream 1 dan Stream 2) terverifikasi 100% pada VPS RouterOS `103.93.162.168`.
  - Integrasi HUD browser dan API telemetri (`/api/v1/vpn/booster/status`) terverifikasi 100% konsisten dengan kernel runtime WireGuard.
  - Rollback routing default terbukti aman dan mempertahankan akses SSH manajemen.
  - Status VM Boot Test: **NOT TESTED** (RAM host terbatas saat start Hyper-V).
