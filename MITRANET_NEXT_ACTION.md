# LANGKAH BERIKUTNYA MITRANET

LANGKAH BERIKUTNYA: Audit konsistensi modul WebUI (`web/`) antara lokal Windows dan runtime Mini PC (`/mitranet/web` & `/usr/share/mitranet/web`)
ALASAN: Memastikan tidak ada perbedaan file frontend, template PHP, atau aset JavaScript yang tertinggal sebelum memulai pengembangan fitur baru berikutnya.
FILE ATAU SERVICE TERKAIT:
- `c:\mitranet\web` -> `/mitranet/web` dan `/usr/share/mitranet/web`
- PHP WebUI Server (`php -S 127.0.0.1:8000`)
PRASYARAT:
- Siklus deployment sebelumnya (GitHub, Mini PC, ISO) telah diverifikasi tuntas 100%
- Koneksi SSH ke Mini PC tersedia
PERINTAH ATAU TINDAKAN YANG DIRENCANAKAN:
1. Periksa keselarasan file PHP dan aset di direktori `web/`
2. Jalankan smoke test navigasi menu utama WebUI
3. Laporkan temuan audit sebelum perbaikan dimulai
TES KEBERHASILAN:
- Seluruh modul WebUI tersinkronisasi dan dapat dibuka tanpa error 500 / warning PHP
PROSEDUR ROLLBACK:
- Manfaatkan riwayat Git atau backup remote jika ada modifikasi
STATUS: NOT STARTED
