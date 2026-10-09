# LANGKAH BERIKUTNYA MITRANET

LANGKAH BERIKUTNYA: Verifikasi integritas & audit sinkronisasi file WebUI (`web/`) lokal versus Mini PC (`/mitranet/web`)
ALASAN: Memastikan tidak ada perbedaan aset CSS, skrip JS, atau file modul PHP antara workspace pengembangan lokal Windows dan direktori runtime di Mini PC setelah perbaikan API selesai.
FILE ATAU SERVICE TERKAIT:
- `c:\mitranet\web` -> `/mitranet/web` & `/usr/share/mitranet/web`
- Service WebUI: `php -S 127.0.0.1:8000` (PID di bawah `mitranet-webui.service`)
PRASYARAT:
- Layanan `mitranet-webui.service` aktif dan healthy (terverifikasi: ACTIVE)
- SSH & SFTP ke Mini PC terverifikasi
PERINTAH ATAU TINDAKAN YANG DIRENCANAKAN:
1. Hitung checksum / periksa daftar file `web/` lokal vs remote untuk mendeteksi file yang usang atau belum tersinkron.
2. Jika ada divergensi yang perlu diperbarui, sinkronkan ke `/mitranet/web` dan `/usr/share/mitranet/web`.
3. Verifikasi HTTP GET pada modul WebUI utama.
TES KEBERHASILAN:
- Seluruh file `web/` sinkron 100%
- WebUI merespons tanpa error PHP / 500
PROSEDUR ROLLBACK:
- Manfaatkan git working tree lokal atau backup remote jika ada berkas yang perlu dipulihkan.
STATUS: NOT STARTED
