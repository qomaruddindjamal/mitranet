# LANGKAH BERIKUTNYA MITRANET

LANGKAH BERIKUTNYA: Eksekusi Automated Deployment Pipeline (Sync Mini PC, Rebuild ISO, Git Push) untuk modul VPN WinBox UI
ALASAN: Modul VPN (`web/vpn/vpn.php`) telah selesai diimplementasikan dengan badge "VPN", 8 tab PPP/VPN MikroTik, tabel kolom identik, dan sidebar menu VPN telah diubah menjadi direct link tanpa drop-right. Terverifikasi 100% live di Mini PC.
FILE ATAU SERVICE TERKAIT:
- `web/vpn/vpn.php`
- `web/includes/head.inc`
- `deploy_pipeline.py`
PRASYARAT:
- Sintaks `vpn.php` dan `head.inc` bebas error (STATUS: PASS)
- Render WebUI di Mini PC terkonfirmasi sukses memuat badge VPN dan sidebar direct link (STATUS: PASS)
PERINTAH ATAU TINDAKAN YANG DIRENCANAKAN:
1. Jalankan `python c:\mitranet\deploy_pipeline.py "feat(vpn): transform VPN to MikroTik WinBox PPP style with direct sidebar link and VPN title"`
2. Pastikan ISO `MitraNet-Rinjani-1.0.2-amd64.iso` ter-rebuild dengan exit code 0
3. Pastikan git push ke origin/main sukses
STATUS: READY_TO_EXECUTE
