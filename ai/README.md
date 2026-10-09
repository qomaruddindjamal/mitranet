# MitraNet AI Assistant — Internal Automation & Knowledge Engine

AI Assistant internal untuk sistem operasi router **MitraNet Rinjani 1.0.2** (Debian GNU/Linux 13).
Engine ini dirancang untuk beroperasi secara independen tanpa ketergantungan model cloud tertutup, dengan dukungan penuh terhadap:
- **Repository Indexing**: Pemetaan cepat kode sumber, arsitektur, dan relasi modul WebUI / Python API.
- **Debian / Linux Network Operational Standards**: Pengetahuan perintah `systemctl`, `nftables`, `iproute2`, `dnsmasq`, `wireguard`.
- **Golden Rules MitraNet**: Penegakan otomatis workflow 3-tahap (Sync -> Rebuild ISO -> GitHub Commit).
- **Crash-Safe Recovery**: Integrasi penuh dengan `MITRANET_WORK_STATE.md`, `MITRANET_NEXT_ACTION.md`, dan `MITRANET_RESUME.md`.

---

## 1. Mode Operasi

Assistant menyediakan 8 mode kerja:
1. `ASK`: Menjawab pertanyaan seputar arsitektur proyek dan dokumentasi.
2. `ANALYZE`: Menganalisis kondisi repositori, file dirty, dan log kesalahan.
3. `PLAN`: Menyusun rencana tindakan perbaikan berdasarkan temuan aktual.
4. `TEST`: Menjalankan suite pengujian lokal terisolasi (`test_core.py`, `test_webui_api.py`).
5. `REPAIR`: Menerapkan patch perbaikan otomatis terverifikasi.
6. `RECOVER`: Memulihkan konteks kerja dari checkpoint file terbaru.
7. `HANDOVER`: Mengenerate paket ringkasan serah terima pekerjaan ke percakapan / agent baru.
8. `DEPLOY`: Memandu deployment aman ke Mini PC (`10.10.66.228`) dan rebuild ISO.

---

## 2. Cara Menjalankan

```bash
# Menampilkan bantuan dan status
python ai/ai-asistans.py --help

# Memeriksa status dan pemulihan konteks (RECOVER)
python ai/ai-asistans.py --mode RECOVER

# Menjalankan analisis kesehatan sistem (ANALYZE)
python ai/ai-asistans.py --mode ANALYZE

# Menjalankan pengujian otomatis (TEST)
python ai/ai-asistans.py --mode TEST

# Menyiapkan paket serah terima (HANDOVER)
python ai/ai-asistans.py --mode HANDOVER
```
