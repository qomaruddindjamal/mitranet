# PHASE A3c — LAPORAN AUDIT INTEGRITAS DIFF A3b & PENUTUPAN RESIDUAL KREDENSIAL

**Tanggal:** 2026-10-11  
**Workspace:** `C:\mitranet`  
**Branch:** `phase-a-hygiene`  
**Commit:** `6569651` (`fix(secrets): reconcile A3b diff integrity, close residual credentials, safe product defaults (Phase A3c)`)  
**Parent:** `a0b3937` (Commit Phase A3b)  
**Status Eksekusi:** SELESAI & SUKSES (Gate Integritas Lolos, Residual REAL-CRED = 0)

---

## BAGIAN 1 — AUDIT INTEGRITAS DIFF A3b (GATE)

### 1. Rekonsiliasi Jumlah File (139 vs 98)
Pada laporan Phase A3b, dilaporkan bahwa codemod memproses **98 file** kode Python. Namun, `git diff --stat e407ece..a0b3937` mencatat **139 files changed (+1108/-6068)**.

Hasil audit mendalam atas index Git pada commit `a0b3937`:
- **101 file berstatus Modified (`M`)**:
  - 98 file Python di direktori `tests/` yang dimigrasikan oleh codemod.
  - 3 file non-test fondasi: `.env.example`, `.gitignore`, dan `mitranet_secrets.py`.
- **38 file berstatus Deleted (`D`)**:
  - Seluruh 38 file ini adalah berkas yang telah dihapus atau direlokasi secara historis dari direktori root pada fase sebelumnya (Phase 5.1/5.2), tetapi status deletion-nya masih berupa unstaged changes di working tree.
  - Ketika Phase A3b menjalankan `git add -u`, Git secara otomatis menyerap (stage) seluruh 38 unstaged deletion yang tercatat di tracking index tersebut ke dalam commit `a0b3937`.
- **Rincian Peran 38 File yang Dihapus dari Tracking Root**:
  - **10 berkas Markdown (`.md`)**: Berkas roadmap dan dokumentasi sesi historis (mis. `MITRANET_*.md`, `ROADMAP_WIFI_FEATURES.md`, `ai/knowledge/*.md`). Berkas-berkas ini telah direlokasi ke `docs/` dan `docs/roadmaps/`.
  - **19 berkas Python (`.py`)**: Skrip analisis ad-hoc dan submodul AI lama di root (`ai/ai-asistans.py`, `ai/tools/diagnostics.py`, `test_php_wifi.py`, `test_wifi_detect.py`, `audit_*.py`, dsb.). Semuanya telah direlokasi ke `src/ai/`, `tests/`, atau `tools/`.
  - **7 berkas JSON (`.json`)**: Dump analisis intermediat relasi menu dan matrix pfsense lama (`package_relations_complete.json`, `menu_audit_pass_missing.json`, `pfsense_to_debian_matrix_complete.json`, dsb.). Berkas ini obsolete dan tidak lagi dipakai di runtime.
  - **1 berkas HTML (`.html`)**: `bridge_tab.html` (markup uji coba sementara).
  - **1 berkas CSS (`.css`)**: `web/css/pfSense.css` (aset CSS pfsense asli, di mana salinan kanonikalnya tersimpan utuh di `packages/debian/mitranet-core/usr/share/mitranet/web/css/pfSense.css`).

### 2. Tabel 10 File Deletions Terbesar & Klasifikasi Hunk
Klasifikasi hunk:
- **(a)** Penggantian literal kredensial (nilai → simbol)
- **(b)** Penambahan blok import/bootstrap `mitranet_secrets`
- **(c)** Deletion murni / relokasi

| File | Deletions (-) | (a) | (b) | (c) | Status & Keterangan |
| :--- | :---: | :---: | :---: | :---: | :--- |
| `web/css/pfSense.css` | 1075 | 0 | 0 | 1075 | **Tersedia di paket** (`packages/debian/mitranet-core/.../pfSense.css`) |
| `package_relations_complete.json` | 1044 | 0 | 0 | 1044 | **Obsolete analysis** (dump matriks dependensi lama) |
| `menu_audit_pass_missing.json` | 635 | 0 | 0 | 635 | **Obsolete analysis** (dump audit menu lama) |
| `pfsense_to_debian_matrix_complete.json` | 562 | 0 | 0 | 562 | **Obsolete analysis** (dump pemetaan pfSense ke Debian) |
| `pfsense_sidebar_complete_audit.json` | 432 | 0 | 0 | 432 | **Obsolete analysis** (dump audit sidebar lama) |
| `missing_targets.json` | 404 | 0 | 0 | 404 | **Obsolete analysis** (dump audit rute endpoint lama) |
| `ai/ai-asistans.py` | 301 | 0 | 0 | 301 | **Direlokasi** identik 100% ke `src/ai/ai-asistans.py` |
| `ai/tools/diagnostics.py` | 134 | 0 | 0 | 134 | **Direlokasi** identik 100% ke `src/ai/tools/diagnostics.py` |
| `test_php_wifi.py` | 118 | 0 | 0 | 118 | **Direlokasi** identik 100% ke `tests/test_php_wifi.py` |
| `test_wifi_detect.py` | 117 | 0 | 0 | 117 | **Direlokasi** identik 100% ke `tests/test_wifi_detect.py` |

### 3. Keputusan Gate
- **Hasil Verifikasi**: Tidak ada kehilangan kode fungsional riil (*no real functional code loss*). Seluruh kode produk aktif (`ai-asistans.py`, skrip uji wifi, aset CSS) berada di lokasi target kanonikalnya (`src/ai/`, `tests/`, `packages/debian/`). Berkas JSON yang terhapus murni laporan sementara fase terdahulu.
- **Keputusan**: **GATE LOLOS (PASS)**. Tidak diperlukan `git restore` yang membatalkan restrukturisasi. Melanjutkan ke BAGIAN 2.

---

## BAGIAN 2 — RESIDUAL ENUMERASI & TRIAGE

Script `scratch/codemod_credentials.py` telah diperluas dengan opsi `--residual` dan verifikasi allowlist `--verify`.
Dari total 66 kemunculan awal pada A3b, telah dilakukan triase penuh:

### 1. Ringkasan Triase 66 Kejadian
- **REAL-CRED (13 kejadian)**:
  - Dimigrasikan seluruhnya ke helper `mitranet_secrets`:
    - `src/api/auth.py` & Debian package mirror: refactor ke `os.environ.get("MITRANET_API_SECRET")` / ephemeral token (2 kejadian).
    - `tests/audit_throughput_acceptance.py`: `MINI_HOST`, `MINI_USER`, `MINI_PASS` → `MINIPC_*` (1 kejadian).
    - `tests/run_acceptance_throughput.py`: `MINI_HOST`, `MINI_USER`, `MINI_PASS` → `MINIPC_*` (1 kejadian).
    - `tests/validate_multistream_live.py`: `MINI_HOST`, `MINI_USER`, `MINI_PASS` → `MINIPC_*` (1 kejadian).
    - `tests/validate_stream1_live.py`: `MINI_HOST`, `MINI_USER`, `MINI_PASS` → `MINIPC_*` (1 kejadian).
    - `tests/check_aapanel_creds.py`: `sshpass -p '<REDACTED>'` → `VM_PASS` (2 kejadian).
    - `tests/setup_host_guest_ssh.py`: `sshpass -p '<REDACTED>'` → `VM_PASS` (2 kejadian).
    - `tests/test_router_utama.py`: `passwords = [...]` → `VM_PASS`, `MINIPC_PASS` (3 kejadian).
  - **Sisa REAL-CRED aktif: 0**.
- **PRODUCT-DEFAULT & DUMMY/MOCK (53 kejadian tersisa)**:
  - Didokumentasikan dan dimasukkan ke `scratch/credential_allowlist.json`.
  - Berisi string identifier produk non-kredensial yang kebetulan memiliki string literal `"mitranet"` (seperti nama tabel nftables atau hostname default sistem).

### 2. Rincian Tabel 53 Allowlisted Residuals

| File | Baris | Jenis | Nilai MASKED | Kelas | Justifikasi |
| :--- | :---: | :---: | :---: | :---: | :--- |
| `core/config/model.py` | 12 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Default NOS hostname `mitranet` |
| `core/firewall/backend.py` | 88, 104, 113, 121 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table identifier `mitranet` |
| `core/firewall/models.py` | 179, 197 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table default name `mitranet` |
| `core/firewall/snapshot.py` | 44, 100 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table identifier `mitranet` |
| `packages/debian/.../core/config/model.py` | 12 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Default NOS hostname `mitranet` (pkg) |
| `packages/debian/.../core/firewall/backend.py` | 88, 104, 113, 121 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table identifier `mitranet` (pkg) |
| `packages/debian/.../core/firewall/models.py` | 179, 197 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table default name `mitranet` (pkg) |
| `packages/debian/.../core/firewall/snapshot.py` | 44, 100 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table identifier `mitranet` (pkg) |
| `packages/debian/.../src/api/server.py` | 1837, 1998, 2087 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Loopback filter, aaPanel user fallback, pkg keyword |
| `packages/debian/.../src/config/models.py` | 11 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Default NOS hostname `mitranet` (pkg) |
| `src/api/server.py` | 1837, 1998, 2087 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Loopback filter, aaPanel user fallback, pkg keyword |
| `src/config/models.py` | 11 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Default NOS hostname `mitranet` |
| `tests/test_aapanel_creds_sync.py` | 10, 74 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | aaPanel username UI HTML assertion |
| `tests/test_config_model.py` | 22 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | Unit test assertion hostname `mitranet` |
| `tests/test_core.py` | 27 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | Unit test assertion hostname `mitranet` |
| `tests/test_firewall_compiler.py` | 26 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table identifier fixture |
| `tests/test_firewall_models.py` | 84, 88, 91 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table name serialization assertion |
| `tests/test_firewall_validator.py` | 28 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | PRODUCT-DEFAULT | Nftables table name validator fixture |
| `tests/test_kvm_toggle_flow.py` | 10 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | Local form login test payload |
| `tests/test_linux_firewall_integration.py` | 57, 73, 80, 102, 111, 117, 146, 220 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | Integration test nftables table identifier |
| `tests/test_manage_aapanel_ui.py` | 33 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | aaPanel username assertion |
| `tests/test_webui_api.py` | 50, 52 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | WebUI auth mock verification unit test |
| `tests/verify_all_system_submenus.py` | 15, 57, 69, 70 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | WebUI form login and mock password change payload |
| `tests/verify_dhcp_tabs.py` | 19 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | Mock PHP WebUI test login payload |
| `tests/verify_genuine_aapanel_sync.py` | 85 | MINIPC_PASS | `<REDACTED:MINIPC_PASS>` | DUMMY/MOCK | aaPanel username API response assertion |

---

## BAGIAN 2c — REFACTOR `src/api/auth.py`

### Diff Ringkas Perilaku (Sebelum vs Sesudah)
```diff
--- a/src/api/auth.py
+++ b/src/api/auth.py
@@ -14,4 +14,14 @@
+import logging
+
+logger = logging.getLogger("mitranet.auth")
+
 AUTH_FILE = "/etc/mitranet/secrets/webui_users.json"
 DEFAULT_USER = "admin"
-DEFAULT_PASS = "<REDACTED>"
+
+_env_secret = os.environ.get("MITRANET_API_SECRET")
+if _env_secret:
+    DEFAULT_PASS = _env_secret
+else:
+    DEFAULT_PASS = secrets.token_hex(32)
+    logger.warning("MITRANET_API_SECRET not set - API tokens are ephemeral")
```
- Perubahan yang sama diterapkan secara sinkron pada berkas kembarannya di `packages/debian/mitranet-core/usr/lib/python3/dist-packages/mitranet/src/api/auth.py`.
- Ditambahkan entri `MITRANET_API_SECRET=` pada `.env.example` dan `.env`.
- Modul runtime produk ini tetap murni menggunakan stdlib `secrets`, `os`, dan `logging` tanpa dependensi eksternal.

---

## BAGIAN 3 — STATUS CONFIG EXAMPLE (KEDUA LOKASI)

1. `ai/config.example.json` (ROOT):
   - **Status**: Berhasil dihapus dari Git tracking (tercatat di commit `a0b3937` sebagai status `D`). File tidak lagi berada di root.
2. `src/ai/config.example.json`:
   - **Status**: Valid dan bersih dari IP/kredensial nyata.
   - Nilai field menggunakan placeholder sanitasi kanonikal:
     - `"minipc_ip": "REPLACE_WITH_TARGET_HOST"`
     - `"minipc_user": "REPLACE_WITH_USER"`
   - Tidak ada password atau IP privat yang bocor.

---

## BAGIAN 4 — REGRESI RINGAN (LOKAL SAJA, TANPA NETWORK)

### 1. Hasil `py_compile`
Kompilasi bytecode dilakukan terhadap seluruh 10 file Python yang disentuh pada tugas ini:
- `src/api/auth.py` -> **OK**
- `packages/debian/.../src/api/auth.py` -> **OK**
- `tests/audit_throughput_acceptance.py` -> **OK**
- `tests/check_aapanel_creds.py` -> **OK**
- `tests/run_acceptance_throughput.py` -> **OK**
- `tests/setup_host_guest_ssh.py` -> **OK**
- `tests/test_router_utama.py` -> **OK**
- `tests/validate_multistream_live.py` -> **OK**
- `tests/validate_stream1_live.py` -> **OK**
- `scratch/codemod_credentials.py` -> **OK**
**Hasil: 0 ERROR**.

### 2. Hasil Pytest Terbatas
Perintah yang dijalankan:
```powershell
$env:PYTHONPATH="packages/debian/mitranet-core/usr/lib/python3/dist-packages;packages/debian/mitranet-config-engine/usr/lib/python3/dist-packages;.package_env;."; pytest tests/test_config_model.py tests/test_json_schema.py tests/test_versioning.py tests/test_normalizer.py tests/test_xml_parser.py tests/test_firewall_models.py tests/test_firewall_validator.py
```
**Hasil Eksekusi**:
```
tests/test_config_model.py ...                                           [ 11%]
tests/test_json_schema.py ..                                             [ 18%]
tests/test_versioning.py ....                                            [ 33%]
tests/test_normalizer.py ...                                             [ 44%]
tests/test_xml_parser.py ..                                              [ 51%]
tests/test_firewall_models.py .......                                    [ 77%]
tests/test_firewall_validator.py ......                                  [100%]

============================= 27 passed in 0.84s ==============================
```
**Semua 27 test murni lulus (100% PASS)** tanpa regresi logika model atau skema.

---

## BAGIAN 5 — ENVIRONMENT & ASUMSI

- **Key Environment Baru**:
  - `MITRANET_API_SECRET` (ditambahkan ke `.env.example` dan `.env`, default kosong / ephemeral fallback).
- **Asumsi**:
  - String literal `"mitranet"` yang dipakai sebagai identitas tabel nftables dan hostname sistem bukan merupakan rahasia autentikasi operasional, melainkan konstanta nama produk. Seluruhnya dipertahankan dan dilindungi melalui `scratch/credential_allowlist.json`.
- **Status Akhir Git**:
  - Branch: `phase-a-hygiene`
  - HEAD: `6569651`
  - Post-Update Workflow AGENTS.md (sync Mini PC, build ISO, git push) tetap di-suspen penuh sesuai aturan tugas.
