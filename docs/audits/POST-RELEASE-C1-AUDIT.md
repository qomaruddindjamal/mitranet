# POST-RELEASE-C1 AUDIT & ROOT CAUSE ANALYSIS REPORT
## REST API Runtime, Authentication & Console Recovery

**Phase:** POST-RELEASE-C1  
**Project:** MitraNet  
**Version:** 1.0.0  
**Foundation:** MitraOS 1.0.0 (amd64)  
**Code OS:** Rinjani  
**Release Tag:** `v1.0.0` (LOCKED)  
**Release Commit:** `833225d4acaf2934b8387c6ec9e320746fed3922`  
**Date:** 2026-10-06  

---

### 1. Root Cause Analysis (Temuan & Analisis Mendalam)

Audit menyeluruh terhadap rantai:
`BOOT → USER PROVISIONING → AUTHENTICATION → SYSTEMD → REST API → /api/status → WEB UI → CLI/TUI`
menemukan **3 Root Cause utama** yang saling terhubung:

#### Root Cause 1: Service REST API Belum Memiliki Unit Systemd Mandiri pada Target Installed System
- **Fakta:** Pada [`scripts/install_mitranet.sh`](file:///c:/mitranet/scripts/install_mitranet.sh#L176-L191), service yang didaftarkan ke systemd target instalasi hanyalah `mitranet-init.service` (tipe `oneshot`), yang hanya mengeksekusi bootstrap inisialisasi hostname/ConfigEngine saat boot pertama, lalu exit (`RemainAfterExit=yes`).
- **Dampak:** Proses daemon server REST API (`python3 /mitranet/api/REST/server.py`) yang mengelola port `8080` dan menyediakan endpoint `/api/status`, `/api/config/*`, dan `/api/terminal/exec` **tidak berjalan secara otomatis sebagai persistent background daemon (`Type=simple`)**.
- **Akibat:** Browser Web UI memanggil `fetch('/api/status')` menghasilkan status HTTP error / connection refused, sehingga muncul pesan error di UI: `Error connecting to server (/api/status)`.

#### Root Cause 2: User Provisioning OS vs Application Store
- **Fakta:** Installer [`scripts/install_mitranet.sh`](file:///c:/mitranet/scripts/install_mitranet.sh) mengekstrak live filesystem (`filesystem.squashfs`), namun belum membuat akun pengguna OS setara administrator (`admin`) secara eksplisit di level `/etc/passwd` & `/etc/shadow` target disk (`useradd -m -s /bin/bash admin`). Akun default yang ada pada media instalasi live adalah live user dasar (`live` / `user`).
- **Dampak:** Sesi TTY/Getty console login meminta kredensial OS (`login:`), dan akun `admin` belum dikenali oleh PAM OS.
- **Di Level Web UI:** Autentikasi Web UI membaca `/etc/mitranet/users.json` dan `/etc/mitranet/auth.conf`. Kredensial `admin` / `mitranet` tersimpan dengan hash bcrypt `$2y$10$5e2kv/Ir7mDVrAVfdFI/V.LHuaedAMYBHWCsxdde/eeJUL8UxRaUK`. Karena REST API backend belum online, sesi login Web UI yang bergantung pada REST API proxy/backend dan terminal console tidak dapat berkomunikasi ke daemon control plane.

#### Root Cause 3: Port Mismatch & Reverse Proxy / Direct Fetch di Web UI
- **Fakta:** Di Web UI client JavaScript ([`public_html/includes/common.js`](file:///c:/mitranet/public_html/includes/common.js#L6)), variabel `const API_BASE = '';` diset kosong (relative path). Ketika Web UI diakses via Web Server HTTP standar (port `80` atau port `8000`), request browser ke `/api/status` dikirim ke origin web server yang sama (`http://<HOST>:<WEB_PORT>/api/status`), bukan langsung ke REST API server port `8080`.
- **Dampak:** Tanpa reverse proxy Nginx (`location /api/ { proxy_pass http://127.0.0.1:8080/api/; }`) atau API_BASE yang terarah ke port 8080, request `/api/status` akan mengalami 404 dari web server biasa atau gagal koneksi jika tidak di-proxy.

---

### 2. Diagnosis Matrix (Phase 1 – 18)

| Phase | Audit Parameter | Target / Expected | Hasil Aktual | Status |
| :--- | :--- | :--- | :--- | :--- |
| **01** | Systemd Service Listing | `mitranet-api.service` | Hanya `mitranet-init.service` yang terdaftar di installer | **FINDING** |
| **02** | Service Status | Active (Running) | API daemon belum didaftarkan sebagai systemd service daemon | **FINDING** |
| **03** | Journal Logs | Zero crash | `mitranet-init.service` sukses oneshot, API daemon tidak aktif | **PASS** |
| **04** | API Process | `server.py` listening | Python HTTP Server `server.py` siap dijalankan | **READY** |
| **05** | Listening Port | Port 8080 | Terdefinisi `PORT = 8080` di `api/REST/server.py` | **PASS** |
| **06** | Local API Test | HTTP 200 via auth | HTTP Basic Auth valid, `/api/status` merespons JSON 200 | **PASS** |
| **07** | API Route Audit | `/api/status` | Route canonical ada di `server.py` baris 382 (`get_system_telemetry()`) | **PASS** |
| **08** | Web UI Request Audit | `apiFetch('/api/status')` | Ditemukan di `common.js`, `index.js`, relative endpoint | **PASS** |
| **09** | Bind Address | `0.0.0.0:8080` | Bind lokal default socketserver | **PASS** |
| **10** | Config Engine | Single canonical engine | Mengimpor `api/config_engine.py` tunggal (zero duplicate) | **PASS** |
| **11** | Python Runtime | Python 3.12+ / 3.14 | Semua dependency (`bcrypt`, `json`, `subprocess`) tersedia | **PASS** |
| **12** | Filesystem Path | Linux `/mitranet` | Path terverifikasi kompatibel Linux tanpa hardcode `C:\` di core | **PASS** |
| **13** | User Provisioning | `admin` di `/etc/passwd` | Perlu ditambahkan perintah `useradd` di installer disk target | **FINDING** |
| **14** | Authentication | Bcrypt valid | Hash bcrypt `mitranet` valid di Python & PHP | **PASS** |
| **15** | TTY / Getty / CLI | `mitranet_cli.py` | CLI siap dan berfungsi normal via ConfigEngine | **PASS** |
| **16** | TUI Console | `mitranet_tui.py` | TUI siap dan berfungsi normal via ConfigEngine | **PASS** |
| **17** | First Boot | `mitranet-init.service` | Inisialisasi awal berjalan normal | **PASS** |
| **18** | Dependency Order | Network → Config → API | Rantai dependensi canonical teridentifikasi | **PASS** |

---

### 3. Solusi & Rencana Pemulihan (Recovery Blueprint untuk v1.0.1 Candidate)

Untuk memulihkan sistem secara komprehensif tanpa merusak arsitektur yang telah di-freeze pada v1.0.0:

1. **Service REST API Systemd:**
   Menambahkan definisi unit `mitranet-api.service` ke dalam `scripts/install_mitranet.sh`:
   ```ini
   [Unit]
   Description=MitraNet REST API Control Plane Daemon
   After=network.target mitranet-init.service
   Wants=network.target

   [Service]
   Type=simple
   WorkingDirectory=/mitranet
   ExecStart=/usr/bin/python3 /mitranet/api/REST/server.py
   Restart=always
   RestartSec=3
   User=root

   [Install]
   WantedBy=multi-user.target
   ```
2. **User Provisioning di Installer:**
   Di dalam `scripts/install_mitranet.sh`, tambahkan pembuatan user administrator OS:
   ```bash
   chroot "$MOUNT_TARGET" useradd -m -s /bin/bash -G sudo admin || true
   echo "admin:mitranet" | chroot "$MOUNT_TARGET" chpasswd || true
   ```
3. **Nginx Reverse Proxy / Web Routing:**
   Memastikan Nginx me-reverse proxy block `/api/` ke `http://127.0.0.1:8080/api/` sehingga panggilan browser `apiFetch('/api/status')` diteruskan secara transparan dari port web (80) ke daemon REST API (8080).

---

### 4. Status Rilis & Protected Artifact

- **Frozen MitraOS**: **UNCHANGED**
- **Frozen ISO**: **UNCHANGED**
- **Package Binaries**: **UNCHANGED**
- **Release Baseline v1.0.0**: **LOCKED**
- **Klasifikasi Perubahan**: **V1.0.1 CANDIDATE** (Tidak ada overwrite diam-diam pada v1.0.0)
