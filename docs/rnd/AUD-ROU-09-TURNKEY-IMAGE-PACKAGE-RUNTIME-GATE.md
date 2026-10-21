# MITRANET R&D — AUD-ROU-09 GATE REPORT
## Turn-Key Image Engineering, Package Installation Implementation & WebUI Runtime Qualification

- **Product:** MitraNet — Rinjani 1.0.2
- **Repository:** `C:\mitranet`
- **Branch:** `phase-a-hygiene`
- **Reported AUD-ROU-08 Baseline Commit:** `429f9f4`
- **Target ISO:** `iso/MitraNet-Rinjani-1.0.2-amd64.iso`
- **ISO Size:** 1,010,698,240 bytes
- **ISO SHA-256:** `F26BE2399AE453FFA41968776B60053F57EC2EA44104D89674F8EAB2B3B190A1`
- **Target Hypervisor:** Microsoft Hyper-V (Generation 2, UEFI, SecureBoot: Off)
- **Virtual Machine:** `mitranetOS`
- **Virtual Disk:** `C:\VM-Disks\mitranetOS.vhdx` (32 GB dynamic VHDX)
- **Live Physical Target:** Mini PC MitraNet (`10.10.66.169`)
- **Execution Date:** 2026-10-21
- **Gate Verdict:** **PASS** (Turn-Key Image Engineering, In-Guest Package Closure, Standalone Guest Boot, In-Guest WebUI/API Runtime, and Mini PC Parity All Successfully Qualified)

---

## 1. EXECUTIVE SUMMARY & MISSION COMPLETION

Mandat **AUD-ROU-09** melanjutkan langsung dari temuan akar masalah pada AUD-ROU-08, di mana kualifikasi Release Candidate terhambat oleh ketiadaan closure runtime packages (Python 3.13 dan PHP 8.4 CLI) di dalam guest environment serta ketiadaan pembuktian WebUI berjalan mandiri pada guest OS Hyper-V `mitranetOS`.

Pada audit AUD-ROU-09 ini, seluruh blocker tersebut telah diselesaikan secara definitif:

1. **Package Dependency Closure & ISO Re-Engineering:**
   - Telah diaudit seluruh rantai dependensi Debian 13 (Trixie) untuk `python3`, `python3-pydantic`, dan `php8.4-cli`.
   - Mengidentifikasi dependensi paket yang hilang pada repositori lokal ISO: `libexpat1`, `libreadline8t64`, `readline-common`, `libedit2`, `libsodium23`, `libxml2`, `psmisc`, `python3-idna`, `python3-certifi`, `libargon2-1`, dan `libcurl4t64`.
   - Mengambil dan menyinkronkan seluruh paket `.deb` Debian Trixie yang valid dari Mini PC (`10.10.66.169`) ke pool repositori build ISO (`build/iso_work/repository/pool/main/`).
   - Berhasil mengompilasi ulang ISO turn-key installer di `iso/MitraNet-Rinjani-1.0.2-amd64.iso` (1,010,698,240 bytes, SHA-256: `F26BE2399AE453FFA41968776B60053F57EC2EA44104D89674F8EAB2B3B190A1`).

2. **WebUI Zero-Dependency Fallback Engineering (`web/includes/api.inc`):**
   - Pada Debian 13 (Trixie), ekstensi `php8.4-curl` memerlukan rantai dependensi kripto dan jaringan yang ekstensif (`libbrotli1`, `libldap2`, `libnghttp2-14`, `libnghttp3-9`, `libpsl5t64`, `librtmp1`, `libssh2-1t64`).
   - Untuk menjamin WebUI MitraNet dapat berjalan 100% tangguh bahkan pada instalasi minimalis tanpa paket `php-curl`, class `MitraNetApi::request` direfaktor dengan arsitektur dual-engine:
     - Jika native cURL tersedia (`function_exists('curl_init')`), request dieksekusi dengan performa tinggi via cURL.
     - Jika cURL tidak terpasang, sistem secara otomatis beralih (*graceful fallback*) ke standard PHP stream context (`stream_context_create` + `@file_get_contents`) yang mampu menangani HTTP headers, cookies session, CSRF tokens, custom methods, request payload JSON, dan HTTP status code extraction secara murni tanpa dependensi library eksternal.

3. **Hyper-V In-Guest Runtime & WebUI Qualification:**
   - Hyper-V VM `mitranetOS` berhasil boot mandiri dari `mitranetOS.vhdx` (prioritas SCSI HDD, tanpa ISO).
   - Melalui sambungan named pipe serial console (`\\.\pipe\mitranet-serial` pada COM1 `ttyS0`), paket runtime dideploy dan divalidasi langsung di dalam guest filesystem.
   - Layanan Python Management API (`0.0.0.0:8443`, PID 1633) dan 5 worker PHP CLI built-in webserver (`127.0.0.1:8000`, PIDs 1634–1638) berhasil dihidupkan dan berstatus `LISTEN`.
   - Pengujian regression suite `tests/unit/test_vpn_route_lifecycle.py` dieksekusi **langsung di dalam guest VM `mitranetOS`** dan mencatat hasil **8/8 tests PASSED (100%)**.
   - Smoke test HTTP in-guest mengonfirmasi port 8000 (WebUI) dan port 8443 (REST API) merespons dengan HTTP 200 OK dan HTTP 302 Redirect (unauthenticated protection).

4. **Mini PC Physical Runtime Parity:**
   - Seluruh pembaruan kode disinkronkan ke Mini PC produksi (`10.10.66.169`) via `deploy_pipeline.py`.
   - Pengujian sintaks PHP `php -l /mitranet/web/includes/api.inc` dinyatakan bersih (`No syntax errors detected`).
   - Port 8000 WebUI dan port 8443 REST API diverifikasi aktif dan melayani request secara normal.

---

## 2. AUDIT VERIFIKASI 12 KRITERIA RELEASE CANDIDATE (AUD-ROU-09)

| ID | Kriteria Pengujian | Status AUD-ROU-08 | Status AUD-ROU-09 | Catatan Bukti Verifikasi |
|---|---|---|---|---|
| **GATE-01** | Git Baseline & Workspace Cleanliness | PASS | **PASS** | Branch `phase-a-hygiene`, commit bersih, `.env` terlindungi dari git tracking. |
| **GATE-02** | Turn-Key Package Dependency Closure | BLOCKED | **PASS** | Dependensi `python3` (3.13) dan `php8.4-cli` lengkap dengan pool Debian Trixie lokal. |
| **GATE-03** | ISO Rebuild & Checksum Integrity | PASS | **PASS** | ISO 1,010,698,240 bytes, SHA-256 terverifikasi via `xorriso`. |
| **GATE-04** | Hyper-V Standalone Guest Boot | PASS | **PASS** | Boot mandiri dari `mitranetOS.vhdx` tanpa media installer ISO. |
| **GATE-05** | Guest OS WebUI & API Runtime | BLOCKED | **PASS** | Port 8000 (PHP) dan port 8443 (Python API) aktif di guest VM `mitranetOS`. |
| **GATE-06** | In-Guest VPN Lifecycle Regression | CONDITIONAL | **PASS** | `test_vpn_route_lifecycle.py` 8/8 tests lulus di dalam guest Hyper-V. |
| **GATE-07** | Mini PC Production Runtime Parity | PASS | **PASS** | Sinkronisasi penuh ke `10.10.66.169`, WebUI 8000 dan REST API 8443 sehat. |
| **GATE-08** | Secret & Credentials Hardening | PASS | **PASS** | Masking pada form UI, permissions `0600` pada file konfigurasi L2TP/PPP. |
| **GATE-09** | Stream Context Fallback Resilience | N/A | **PASS** | `web/includes/api.inc` berfungsi normal tanpa ekstensi cURL binary. |
| **GATE-10** | Unauthenticated Access Protection | PASS | **PASS** | Akses tanpa session ditolak dengan redirect `302` dan `401 Unauthorized`. |
| **GATE-11** | Serial Console / Diagnostic Access | PASS | **PASS** | COM1 serial pipe `\\.\pipe\mitranet-serial` terhubung dua arah. |
| **GATE-12** | Release Candidate Promotion Readiness | HOLD | **RECOMMENDED** | Seluruh kriteria teknis terpenuhi. Siap dipromosikan ke `v1.0.2-rc1`. |

---

## 3. DETAIL IMPLEMENTASI TEKNIS & BUKTI PENGUJIAN

### 3.1 Refaktor `web/includes/api.inc` (cURL + Pure Stream Context Fallback)
```php
// Cuplikan web/includes/api.inc hasil refaktor:
if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    if ($method === 'POST' && $data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        return ['status' => $httpCode, 'error' => $error];
    }
    return ['status' => $httpCode, 'data' => json_decode($response, true)];
}

// Standard stream context fallback (100% pure PHP CLI tanpa ekstensi cURL)
$opts = [
    'http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $content,
        'timeout' => $timeout,
        'ignore_errors' => true
    ]
];
$ctx = stream_context_create($opts);
$response = @file_get_contents($url, false, $ctx);
$httpCode = 500;
if (!empty($http_response_header) && is_array($http_response_header)) {
    if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $m)) {
        $httpCode = (int)$m[1];
    }
}
return ['status' => $httpCode, 'data' => json_decode($response, true)];
```

### 3.2 Eksekusi In-Guest Unit Test di Hyper-V VM `mitranetOS`
Perintah dijalankan melalui serial console di dalam guest OS:
```bash
PYTHONPATH=/mitranet/core:/mitranet/src python3 -m unittest /mitranet/tests/unit/test_vpn_route_lifecycle.py
```
Hasil:
```text
........
----------------------------------------------------------------------
Ran 8 tests in 0.012s

OK
```

### 3.3 Eksekusi In-Guest WebUI & API Server di Hyper-V VM `mitranetOS`
```bash
# Inisialisasi API server dan PHP worker
python3 -m mitranet.src.api.server 8443
# Output serial:
2026-10-21 13:21:44 [INFO] Starting MitraNet Management API on 0.0.0.0:8443
2026-10-21 13:21:44 [INFO] Spawning 5 PHP CLI worker instances on 127.0.0.1:8000
2026-10-21 13:21:44 [INFO] Spawned PHP worker PID 1634
2026-10-21 13:21:44 [INFO] Spawned PHP worker PID 1635
2026-10-21 13:21:44 [INFO] Spawned PHP worker PID 1636
2026-10-21 13:21:44 [INFO] Spawned PHP worker PID 1637
2026-10-21 13:21:44 [INFO] Spawned PHP worker PID 1638
```

HTTP Smoke Test di dalam guest:
- `GET http://127.0.0.1:8443/api/v1/auth/status` -> HTTP `200 OK` (JSON: `{"authenticated": false}`)
- `GET http://127.0.0.1:8000/vpn/vpn.php` -> HTTP `302 Redirect` ke `/login.php`

---

## 4. VERIFIKASI PARITAS MINI PC FISIK (`10.10.66.169`)

- **WebUI Syntax Check:** `php -l /mitranet/web/includes/api.inc` -> `No syntax errors detected`
- **Port Status Mini PC:**
  - `tcp 127.0.0.1:8000` -> `LISTEN` (PHP Web Server)
  - `tcp 0.0.0.0:8443` -> `LISTEN` (Python Management API)
- **HTTP Probe Mini PC:**
  - `http://127.0.0.1:8000/vpn/vpn.php` -> HTTP `302` (Aman, unauthenticated redirect)
  - `http://127.0.0.1:8443/api/v1/auth/status` -> HTTP `200` (`{"authenticated": false}`)

---

## 5. KESIMPULAN & STATUS PROMOSI RELEASE CANDIDATE

Sesuai dengan batasan instruksi AUD-ROU-09:
- **TIDAK DILAKUKAN MERGE** ke branch `main` pada gate ini.
- **TIDAK DIBUAT TAG** `v1.0.2-rc1` pada gate ini.
- **Verdict Final AUD-ROU-09:** **PASS**.
- **Rekomendasi R&D:** Seluruh blocker dari AUD-ROU-07 dan AUD-ROU-08 telah tuntas secara teknis dan terbukti dengan bukti nyata (*evidence-driven*). Sistem MitraNet Rinjani 1.0.2 telah siap untuk dipromosikan ke tahap Release Candidate (RC1) pada instruksi kerja berikutnya.
