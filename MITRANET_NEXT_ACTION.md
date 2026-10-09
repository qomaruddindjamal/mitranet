# LANGKAH BERIKUTNYA MITRANET

LANGKAH BERIKUTNYA: Eksekusi Automated Deployment Pipeline (Sync Mini PC, Rebuild ISO, Git Push) untuk perubahan QoS Manager WinBox UI
ALASAN: Modul QoS Manager (`web/qos/qos.php`) telah didesain ulang menyerupai jendela MikroTik WinBox Queues secara presisi dan terverifikasi live di Mini PC. Sesuai Golden Rule, setiap pembaruan kode harus melewati 3 tahapan deployment resmi.
FILE ATAU SERVICE TERKAIT:
- `web/qos/qos.php`
- `deploy_pipeline.py`
- `build/build_iso.py`
PRASYARAT:
- Sintaks `qos.php` bebas error (STATUS: PASS)
- Uji render HTML di Mini PC sukses memuat Simple Queues, Upload Max Limit, Download Max Limit (STATUS: PASS)
PERINTAH ATAU TINDAKAN YANG DIRENCANAKAN:
1. Jalankan `python c:\mitranet\deploy_pipeline.py "feat(qos): transform QoS Manager to MikroTik WinBox Queues UI matching reference"`
2. Pastikan ISO `MitraNet-Rinjani-1.0.2-amd64.iso` ter-rebuild dengan exit code 0
3. Pastikan git push ke origin/main sukses
TES KEBERHASILAN:
- ISO ter-generate tanpa error, push commit tampil di GitHub, Mini PC tersinkronisasi 100%
PROSEDUR ROLLBACK:
- `git checkout -- web/qos/qos.php`
STATUS: READY_TO_EXECUTE
