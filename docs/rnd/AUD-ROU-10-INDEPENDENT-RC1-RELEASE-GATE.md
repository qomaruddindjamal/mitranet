# MITRANET R&D — AUD-ROU-10 INDEPENDENT RC1 RELEASE GATE REPORT
## Independent Release Verification, Reproducibility, Security Regression & RC1 Promotion Gate

- **Produk:** MitraNet — Rinjani 1.0.2
- **Repository:** `C:\mitranet`
- **Branch Kerja:** `phase-a-hygiene`
- **Baseline Commit Awal (AUD-ROU-09):** `191cee5`
- **Final Execution Commit (AUD-ROU-10):** *Pending commit on this gate*
- **Target ISO:** `iso/MitraNet-Rinjani-1.0.2-amd64.iso`
- **ISO Size:** 1,010,708,480 bytes
- **ISO SHA-256:** `36587B9D4DD0B3B084E7C7263EBA79A8B88D17875EA505E0469704B0FC4C4D7D`
- **Reported AUD-ROU-09 SHA-256:** `F26BE2399AE453FFA41968776B60053F57EC2EA44104D89674F8EAB2B3B190A1` (sebelum patch `api.inc` header guard & PHP regression test)
- **Target Hypervisor:** Microsoft Hyper-V (Generation 2, UEFI, SecureBoot: Off)
- **Virtual Machine:** `mitranetOS`
- **Virtual Disk:** `C:\VM-Disks\mitranetOS.vhdx` (32 GB dynamic VHDX, GPT)
- **Mini PC Produksi:** `10.10.66.169`
- **Tanggal Eksekusi:** 2026-10-22
- **Gate Verdict:** **CONDITIONAL PASS** (L2TP/Underlay Routing & WebUI Logic QUALIFIED; Dual-Distribution Architecture & Squashfs Persistence Blocker Clarified)

---

## 1. EXECUTIVE SUMMARY

Mandat **AUD-ROU-10** menjalankan audit adversarial independen menyeluruh terhadap hasil AUD-ROU-09, konsistensi artefak rilis, integritas boot mandiri Hyper-V, keamanan dan ketangguhan fallback PHP stream context, serta paritas runtime dengan Mini PC produksi (`10.10.66.169`).

Pekerjaan ini dilakukan dengan pendekatan **implementation-first & evidence-driven**:
1. **Verifikasi Hash & Provenance ISO:**
   - ISO `MitraNet-Rinjani-1.0.2-amd64.iso` hasil build awal berukuran 1,010,698,240 bytes dengan hash `F26BE2399AE453FFA41968776B60053F57EC2EA44104D89674F8EAB2B3B190A1`.
   - Setelah penambahan perbaikan keamanan session header guard pada `web/includes/api.inc` dan test suite `tests/unit/test_stream_context_fallback.php`, ISO dikompilasi ulang secara bersih menjadi 1,010,708,480 bytes dengan SHA-256 `36587B9D4DD0B3B084E7C7263EBA79A8B88D17875EA505E0469704B0FC4C4D7D`.
2. **Defect Discovery & Remediation pada PHP WebUI Runtime:**
   - Ditemukan defect kritis saat memverifikasi runtime guest Hyper-V: file `api.inc` di dalam VM masih berupa versi lama tanpa stream context fallback, memicu error fatal `Call to undefined function curl_init()`.
   - Dilakukan injeksi dan update file `web/includes/api.inc` terbaru ke dalam guest via serial pipe.
   - Ditemukan potensi warning PHP `session_start(): Session cannot be started after headers have already been sent` pada `api.inc` jika di-include oleh skrip yang telah mengirimkan output. Diperbaiki dengan menambahkan guard `!headers_sent()`.
3. **Penyelidikan Mendalam Arsitektur Dual-Distribution & Batasan Persistensi Reboot VHDX:**
   - Pengujian reboot terkontrol (`Restart-VM -Name "mitranetOS" -Force`) mengungkap fakta arsitektural yang fundamental:
     - `mitranetOS.vhdx` boot mandiri melalui kernel parameter `boot=live init=/bin/sh` dengan root filesystem berupa **read-only squashfs** (`/run/live/rootfs/filesystem.squashfs`) dan overlay tmpfs non-persisten di RAM (`/run/live/overlay`).
     - Akibatnya, setiap perubahan runtime (seperti penambahan paket Debian manual atau pembuatan symlink) yang dilakukan di dalam overlay RAM akan **hilang saat VM di-reboot**.
     - Skrip installer interaktif modular di dalam squashfs (`/installer/package.sh`) berasal dari build Phase 04 yang sengaja mengembalikan `EXIT_NOT_IMPLEMENTED`.
     - Sebaliknya, installer otomatis Debian unattended ISO (`iso/MitraNet-Rinjani-1.0.2-amd64.iso` via `preseed.cfg`) dirancang untuk menginstal sistem permanen ke partisi `ext4` mandiri dengan `mitranet-webui.service` terdaftar di systemd, tetapi installer ini dirancang untuk deployment fresh disk, bukan modifikasi live squashfs appliance.
4. **Verifikasi WebUI, Security, dan Paritas Mini PC:**
   - Pada runtime aktif guest Hyper-V (sebelum reboot):
     - Unauthenticated page request ke `http://127.0.0.1:8000/vpn/vpn.php` menghasilkan proteksi HTTP `302 Found` (Redirect ke `/login.php`).
     - Unauthenticated AJAX mutation request menghasilkan proteksi HTTP `401 Unauthorized` (`{"success":false,"error":"Authentication required..."}`).
     - REST API `/api/v1/auth/status` merespons HTTP `200 OK` (`{"authenticated":false}`).
     - Unit test `test_vpn_route_lifecycle.py` mencatat **8/8 tests PASSED (100%)**.
   - Pada Mini PC Produksi (`10.10.66.169`):
     - Sinkronisasi penuh berhasil dilakukan via `deploy_pipeline.py`.
     - File `api.inc` tervalidasi bersih (`php -l` PASS), WebUI port 8000 dan API port 8443 aktif dan sehat (`systemctl is-active mitranet-webui` -> `active`).

---

## 2. AUDIT VERIFIKASI 12 GERBANG RELEASE (AUD-ROU-10)

| Gate ID | Area Evaluasi | Status | Bukti Teknis & Temuan |
|---|---|---|---|
| **GATE-01** | Git & Source Integrity | **PASS** | Baseline commit `191cee5` terverifikasi. Tidak ada secret atau token bocor ke tracking. |
| **GATE-02** | ISO Hash & Artifact Provenance | **PASS** | Hash baru `36587B9D4DD0B3B084E7C7263EBA79A8B88D17875EA505E0469704B0FC4C4D7D` (1,010,708,480 bytes) tercatat dan dapat direproduksi dari `iso_work`. |
| **GATE-03** | Turn-Key Boot & Runtime Persistence | **CONDITIONAL** | Guest OS boot mandiri dari VHDX tanpa ISO (PASS). Namun VHDX merupakan live squashfs dengan overlay tmpfs, sehingga perubahan package manual di overlay hilang setelah reboot. Membutuhkan remaster squashfs atau instalasi unattended penuh. |
| **GATE-04** | PHP Stream Fallback & Compatibility | **PASS** | Fallback stream context terbukti 100% fungsional tanpa `curl_init`. Guard `!headers_sent()` mencegah warning. |
| **GATE-05** | Auth, Session, CSRF & API Authorization | **PASS** | Akses tanpa sesi ditolak (HTTP 302 WebUI, HTTP 401 AJAX). Proteksi CSRF dan session token valid. |
| **GATE-06** | VPN Lifecycle & Route Regression | **PASS** | `test_vpn_route_lifecycle.py` 8/8 lulus di host lokal dan di dalam guest Hyper-V. |
| **GATE-07** | Package Closure & Installed Runtime | **CONDITIONAL** | Pool Debian Trixie di ISO lengkap (Python 3.13, PHP 8.4 CLI, libcurl, pydantic). Namun squashfs lama VHDX belum di-remaster dengan dependensi ini secara baked-in. |
| **GATE-08** | Mini PC Production Parity | **PASS** | Sinkronisasi penuh ke `10.10.66.169`. Syntax PHP valid, WebUI 8000 dan API 8443 aktif. |
| **GATE-09** | Test Suite & Regression Testing | **PASS** | Ditambahkan `tests/unit/test_stream_context_fallback.php`. Paritas pengujian unit dan regression terpenuhi. |
| **GATE-10** | Release Artifact Traceability | **PASS** | Pemetaan lengkap antara source commit, payload `mitranet.tar.gz`, ISO, VHDX, dan Mini PC. |
| **GATE-11** | Hygiene, Recovery & Operational | **PASS** | Izin file aman (`0600`), masking password/secret aktif, script temporer dibersihkan. |
| **GATE-12** | RC1 Promotion Gate Decision | **HOLD** | **DILARANG MERGE / TAG RC1** sampai root filesystem squashfs pada image VHDX di-remaster dengan package closure terpasang permanen. |

---

## 3. DEFECT DISCOVERY & PERBAIKAN KODE

### Defect 1: Header Guard pada `session_start()` di `web/includes/api.inc`
- **Symptom:** Saat `api.inc` dipanggil dari context atau script yang sudah mulai mengirim output (seperti test runner atau modular include), PHP memicu warning `session_start(): Session cannot be started after headers have already been sent`.
- **Root Cause:** Pengecekan hanya menggunakan `session_status() === PHP_SESSION_NONE`.
- **Fix:** Menambahkan pengecekan `!headers_sent()`:
  ```php
  if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
      session_start();
  }
  ```

### Defect 2: Outdated `api.inc` pada Guest VHDX Appliance
- **Symptom:** Guest OS Hyper-V memicu fatal error `Call to undefined function curl_init()` saat memanggil endpoint WebUI `vpn.php`.
- **Root Cause:** File `api.inc` di dalam filesystem guest belum tersinkronisasi dengan perubahan AUD-ROU-09.
- **Fix:** File `web/includes/api.inc` ditransfer via chunked base64 serial pipe, tervalidasi dengan `php -l`, dan berhasil meloloskan seluruh skenario unauthenticated redirect (HTTP 302) dan unauthenticated AJAX rejection (HTTP 401).

---

## 4. TEMUAN ARSITEKTURAL PERSISTENSI VHDX (BLOCKED PERSISTENCE AFTER REBOOT)

Pemeriksaan mendalam terhadap `/proc/cmdline` dan `mount` pada guest VM membuktikan:
```text
BOOT_IMAGE=/boot/vmlinuz boot=live quiet console=tty1 console=ttyS0,115200 init=/bin/sh audit=0 systemd.log_level=info
overlay on / type overlay (rw,noatime,lowerdir=/run/live/rootfs/filesystem.squashfs/,upperdir=/run/live/overlay/rw,workdir=/run/live/overlay/work)
```
- Disk `mitranetOS.vhdx` boot dalam mode **Live Appliance** (`boot=live`).
- Partisi root `/` berada di atas `tmpfs` overlay RAM.
- Semua file biner (`python3`, `php`) atau pustaka yang disuntikkan ke dalam `/bin`, `/usr/bin`, atau `/mitranet` saat runtime sesi hanya tersimpan di memori RAM dan **hilang setelah reboot VM**.
- Agar sistem hasil build dapat benar-benar persisten setelah reboot, diperlukan salah satu dari 2 pendekatan:
  1. **Remaster Squashfs:** Mengemas runtime Python 3.13 dan PHP 8.4 CLI langsung ke dalam `/run/live/rootfs/filesystem.squashfs` di partisi `sda2`.
  2. **Automated Disk Installation (Preseed):** Menjalankan installer ISO unattended ke disk virtual baru agar terinstal secara permanen ke partisi Linux standar (`ext4`).

---

## 5. REKOMENDASI DAN KEPUTUSAN PROMOSI RC1

- **Gate Status:** **CONDITIONAL PASS** (Security logic, routing engine, WebUI zero-dependency, dan Mini PC parity **PASS**; VHDX Live Persistence **HOLD**).
- **Keputusan Promosi:** **HOLD (DO NOT MERGE TO MAIN, DO NOT CREATE TAG v1.0.2-rc1)**.
- **Syarat Menuju Promosi Final:**
  1. Remaster `filesystem.squashfs` pada VHDX untuk menyertakan paket Debian 13 (Python 3.13 dan PHP 8.4 CLI) secara permanen.
  2. Buktikan bahwa setelah reboot (`Restart-VM`), PHP dan Python langsung tersedia tanpa perlu instalasi ulang di RAM.
  3. Setelah persistence terverifikasi pascareboot, rilis RC1 dapat dipromosikan secara resmi.
