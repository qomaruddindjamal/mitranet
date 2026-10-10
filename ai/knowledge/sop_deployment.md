# MitraNet Recovery, Safety, and Deployment SOP

## 1. Golden Rules Deployment
Setiap pembaruan kode sistem wajib mengikuti pipeline 3-langkah otomatis (`python c:\mitranet\deploy_pipeline.py "<commit_message>"`):
1. **Sinkronisasi ke Mini PC (`10.10.66.228`)**:
   - Berkas disinkronkan ke `/mitranet/web`, `/usr/share/mitranet/web`, `/mitranet/core`, `/mitranet/src`, dan `/usr/lib/python3/dist-packages/mitranet/`.
   - Layanan `mitranet-webui.service` di-restart dan diperiksa kestabilannya.
2. **Rebuild File ISO**:
   - Menjalankan `build/build_iso.py` (`xorriso`).
   - Memastikan file ISO kanonikal di `iso/MitraNet-Rinjani-1.0.2-amd64.iso` berhasil terbuat tanpa error (exit code 0).
3. **Commit & Push GitHub**:
   - Stage perubahan (`git add .`), commit dengan pesan deskriptif, dan push ke branch `main` repositori `https://github.com/qomaruddindjamal/mitranet.git`.

## 2. Standar Pemulihan Lintas Model (Recovery Protocol)
- Saat instruksi "lanjutkan" diterima, periksa checkpoint aktif (`MITRANET_WORK_STATE.md`, `MITRANET_NEXT_ACTION.md`, `MITRANET_RESUME.md`).
- Jangan pernah menjalankan `git reset --hard` atau `git clean` yang menghapus modifikasi pengguna.
- Klasifikasikan status setiap fitur secara transparan: VERIFIED, DOCUMENTED, INFERRED, UNKNOWN.
- Jangan mengklaim pengujian berhasil jika dependensi atau endpoint VPS belum tersedia.
