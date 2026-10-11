# PHASE A4 — SINGLE SOURCE OF TRUTH PACKAGING (DEDUP DEBIAN & REBUILD .deb)

**Tanggal:** 2026-10-11  
**Workspace:** `C:\mitranet`  
**Branch:** `phase-a-hygiene`  
**Commit:** `8d263f6` (`build(packaging): stage deb from canonical sources, remove drifted copies, single webroot (Phase A4)`)  
**Parent:** `227a378` (Phase A3c)  
**Status Eksekusi:** SELESAI & SUKSES (Single Source of Truth Tercapai, 0 Path Overlap, 0 Var/www, Regresi Staging 100% Pass)

---

## 1. KONTRAK PEMETAAN AKHIR (BUILD CONTRACT)

Semua paket Debian MitraNet dibangun langsung dari pohon kanonikal (`web/`, `core/`, `src/`) tanpa salinan statik redundan di tracking Git.

| Paket | Path Runtime Target | Sumber Kanonikal di Repo | Catatan / Modul |
| :--- | :--- | :--- | :--- |
| **`mitranet-config-engine`** | `/etc/mitranet/*`<br>`.../dist-packages/mitranet/core/config`<br>`.../dist-packages/mitranet/core/transaction` | `packages/debian/mitranet-config-engine/etc/`<br>`core/config/`<br>`core/transaction/` | Schemas, default configs, transaction & validation engine. |
| **`mitranet-network-engine`** | `.../dist-packages/mitranet/core/network` | `core/network/` | Kepemilikan tunggal modul networking (interfaces, vlan, bridge, bond, vrf, routing). |
| **`mitranet-core`** | `/usr/share/mitranet/web`<br>`.../dist-packages/mitranet/core/{version,firewall,migration,services}`<br>`.../dist-packages/mitranet/src`<br>`.../dist-packages/mitranet/api`<br>`/usr/bin/mitranet`<br>`/etc/*`, `/lib/systemd/*` | `web/`<br>`core/version.py`<br>`core/firewall/`<br>`core/migration/`<br>`core/services/`<br>`src/`<br>`src/api/`<br>`packages/debian/mitranet-core/{etc,lib,usr/bin}` | Single webroot, REST API, system CLI, firewall nftables engine, systemd unit `mitranet-webui.service`. |
| **`mitranet-gateway-monitor`**| `/usr/bin/mitranet-gateway-monitor`<br>`/lib/systemd/system/mitranet-gateway-monitor.service` | `packages/debian/mitranet-gateway-monitor/{usr/bin,lib}` | Daemon monitoring latency statik murni. |

- **Aturan Namespace PEP 328**: Paket Python menginjeksikan header `extend_path(__path__, __name__)` pada file `__init__.py` di level `mitranet` dan `mitranet.core` sehingga multi-paket dapat berkontribusi pada namespace Python yang sama secara seamless di Debian.

---

## 2. HASIL LANGKAH 0 — PRE-CHECK AUDIT

### 0a. Status `pfSense.css`
- **Grep Referensi**: Ditemukan 4 referensi aktif di `web/` (`web/system/system.php`, `web/css/mitranet-dark.css`, `web/css/mitranet-beta.css`, `web/css/Compact-RED.css`).
- **Aksi**: File dipulihkan dari salinan paket Debian ke direktori kanonikal `web/css/pfSense.css`.
- **Status**: **RESTORED**.

### 0b. Audit `mitranet-webui.service` & Maintainer Scripts
- `mitranet-webui.service`:
  - `WorkingDirectory=/usr/share/mitranet`
  - `ExecStart=/usr/bin/python3 -m mitranet.src.api.server`
  - Tidak merujuk `/var/www`.
- `DEBIAN/postinst` (mitranet-core):
  - Bagian langkah 2 yang sebelumnya menyalin `cp -a /usr/share/mitranet/web/. /var/www/mitranet/` telah **dihapus**.
  - Digantikan dengan penetapan permission langsung pada direktori kanonikal `/usr/share/mitranet/web` (`chmod 644/755`, `chown www-data:www-data`).
- `DEBIAN/postrm` (mitranet-core):
  - Membersihkan `/var/log/mitranet`, `/var/lib/mitranet`, `/run/mitranet` pada aksi `purge`.

### 0c. Audit `build_packages.sh`
- Skrip lama mengandalkan sinkronisasi dua arah ke `usr/share` dan `var/www`.
- Telah direfaktor penuh untuk membangun staging tree bersih per paket dari direktori kanonikal.

### 0d. Audit Referensi `var/www/mitranet`
- Seluruh referensi pada kode produksi aktif telah dibersihkan.
- `git grep -n "var/www/mitranet" -- ':!docs'` menghasilkan **0 baris** (bersih total).

---

## 3. PENGHAPUSAN SALINAN STATIK DRIFTED (LANGKAH 3)

Sebanyak 500 file salinan statik duplikat yang mengalami drift telah dihapus dari pelacakan Git secara eksplisit:
- `git rm -r packages/debian/mitranet-core/usr/share/mitranet/web`
- `git rm -r packages/debian/mitranet-core/var/www`
- `git rm -r packages/debian/mitranet-core/usr/lib/python3/dist-packages`
- `git rm -r packages/debian/mitranet-config-engine/usr/lib/python3/dist-packages`
- `git rm -r packages/debian/mitranet-network-engine/usr/lib/python3/dist-packages`

*Catatan: Seluruh perbaikan A3c (seperti auth.py dan server.py) ikut dibersihkan dari direktori paket dan kini secara otomatis distage langsung dari kanonikal `src/` dan `core/` saat packaging.*

---

## 4. RE-TRIAGE ALLOWLIST LIVE-LOGIN (LANGKAH 5)

Evaluasi terhadap 7 file pengujian interaksi WebUI:

| File | Klasifikasi | Aksi Dilakukan | Status Allowlist |
| :--- | :--- | :--- | :--- |
| `tests/test_kvm_toggle_flow.py` | **LIVE HTTP** | Dimigrasikan ke `WEBUI_USER` & `WEBUI_PASS` | **Dikeluarkan dari allowlist** |
| `tests/verify_all_system_submenus.py` | **LIVE HTTP** | Form login dimigrasikan ke `WEBUI_USER` & `WEBUI_PASS` | Password change fixture tetap di allowlist |
| `tests/verify_dhcp_tabs.py` | **LIVE REMOTE** | `MitraNetApi::login` dimigrasikan ke `WEBUI_USER` & `WEBUI_PASS` | **Dikeluarkan dari allowlist** |
| `tests/test_aapanel_creds_sync.py` | **MOCK UI DATA** | Assertion teks HTML `value="mitranet"` | Tetap di allowlist (Bukan auth password) |
| `tests/test_manage_aapanel_ui.py` | **MOCK UI DATA** | Assertion teks HTML `value="mitranet"` | Tetap di allowlist (Bukan auth password) |
| `tests/verify_genuine_aapanel_sync.py` | **MOCK API DATA** | Assertion API dict `creds['username'] == 'mitranet'` | Tetap di allowlist (Bukan auth password) |
| `tests/test_webui_api.py` | **UNIT MOCK** | Unit test murni `AuthManager` dengan dictionary terisolasi | Tetap di allowlist (Offline test mock) |

- Jumlah residual literal: berkurang dari 53 menjadi **37 kejadian**.
- Seluruh 37 kejadian terdaftar dalam `scratch/credential_allowlist.json` dengan validasi: **0 REAL-CRED violations**.

---

## 5. BUKTI VERIFIKASI STAGING & PACKAGING (LANGKAH 6)

### 5.1. Pembangunan Staging Tree
Eksekusi staging via `scratch/build_packages.py` dan `build_packages.sh`:
- `build/stage/mitranet-config-engine` (78 KB)
- `build/stage/mitranet-network-engine` (145 KB)
- `build/stage/mitranet-gateway-monitor` (4 KB)
- `build/stage/mitranet-core` (10,266 KB)

### 5.2. Verifikasi Konsistensi Staging
1. `diff -r web/ vs stage webroot`: **0 differences** (identik 100%).
2. `diff -r core/ vs stage core`: **0 differences** pada seluruh modul terpeta (`config`, `transaction`, `network`, `firewall`, `migration`, `services`).
3. Direktori `webroot/wg/` berisi **6 file** WireGuard lengkap (`status_wireguard.php`, `vpn_wg_peers.php`, `vpn_wg_peers_edit.php`, `vpn_wg_settings.php`, `vpn_wg_tunnels.php`, `vpn_wg_tunnels_edit.php`).
4. Direktori `webroot/wifi/` dan berkas `index.php`, `sysstats.php` hadir lengkap.
5. **Var/www Elimination**: **0 direktori `var/www`** di seluruh pohon staging `build/stage/`.
6. **Path Overlap**: **0 path collision** pada file fungsional antar-paket (satu-satunya file bersama adalah file standard namespace PEP 328 `__init__.py`).
7. **Pembersihan Disk**: Berkas `.deb` lama versi `-1` dan `-2` di `packages/pool/` dan `packages/debian/` telah dihapus dari disk. Berkas `fping` tetap dipertahankan utuh.

### 5.3. Hasil Regresi Pytest Staging
Menjalankan pengujian unit dengan `PYTHONPATH` menunjuk langsung ke staging dist-packages:
```powershell
$env:PYTHONPATH="build/stage/mitranet-core/usr/lib/python3/dist-packages;build/stage/mitranet-config-engine/usr/lib/python3/dist-packages;build/stage/mitranet-network-engine/usr/lib/python3/dist-packages;.package_env;."; pytest tests/test_config_model.py tests/test_json_schema.py tests/test_versioning.py tests/test_normalizer.py tests/test_xml_parser.py tests/test_firewall_models.py tests/test_firewall_validator.py
```
**Hasil:**
```
============================= 27 passed in 0.89s ==============================
```
**100% PASS** (27 unit test lulus tanpa error).

### 5.4. Hasil Kompilasi Bytecode (`py_compile`)
Seluruh berkas Python yang disentuh:
- `tests/test_kvm_toggle_flow.py` -> **OK**
- `tests/verify_all_system_submenus.py` -> **OK**
- `tests/verify_dhcp_tabs.py` -> **OK**
- `scratch/build_packages.py` -> **OK**
**Hasil: 0 ERROR**.

---

## 6. LIMITASI TOOLING & STATUS GIT

- **Tooling Limitation**: Utilitas biner Linux natif `dpkg-deb` dan `fakeroot` tidak tersedia di host Windows environment saat ini (WSL hanya memiliki distro `docker-desktop` yang stopped). Sesuai aturan Langkah 0c dan Bagian 6, seluruh pohon **staging per-paket** telah dibangun, divalidasi, dan diuji regresi secara komprehensif pada level struktur direktori Debian (`build/stage/`). Skrip `build_packages.sh` siap mengeksekusi `fakeroot dpkg-deb` secara otomatis saat dijalankan di lingkungan Linux/Debian.
- **Git Diff Stat**:
  - `508 files changed, 226 insertions(+), 106834 deletions(-)`
  - Penurunan drastis sebesar ~106,000 baris duplikasi kode di repositori.
- **Status Akhir Git**:
  - Branch: `phase-a-hygiene`
  - Commit HEAD: `8d263f6`
  - Post-Update Workflow AGENTS.md (sync Mini PC, build ISO, git push, merge) tetap **disuspen penuh**.
