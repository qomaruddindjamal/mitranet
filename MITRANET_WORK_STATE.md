# MITRANET WORK STATE — PERSISTENT CHECKPOINT

- **Waktu Pembaruan:** 2026-10-10 15:10:00 WIB
- **Tujuan & Ruang Lingkup Aktif:** MitraNet Rinjani 1.0.2 Release Candidate Finalization (Commit `5b6f091`), Audit Semantik API Booster, Verifikasi Checksum ISO & Manifest Rilis, Validasi Keamanan Routing & Rollback.

---

### 1. AUDIT SEMANTIK API BOOSTER & STATUS LAYER
1. **Audit Semantik Field Telemetri (`GET /api/v1/vpn/booster/status`)**:
   - `enabled` (Boolean): Merefleksikan **status konfigurasi persistensi** pengguna di `/etc/mitranet/secrets/booster_config.json` (`true` jika konfigurasi disimpan/diterapkan, `false` jika booster distop).
   - `active` (Boolean): Merefleksikan **status runtime kernel nyata** (`true` jika minimal satu interface `wgboost*` terdeteksi ada di `/sys/class/net/` dan memiliki handshake aktif <180s atau operstate up).
   - `streams[i].status` ("UP" / "DOWN"): Menandakan bahwa interface kernel ada, handshaking aktif dengan VPS, dan membalas probe ping gateway `10.250.{i}.1`.
   - `latest_handshake` (Epoch): Timestamp detik riil dari jabat tangan terakhir peer yang diambil langsung dari `wg show <dev> latest-handshakes`.
   - **Pembedaan Konfigurasi, Runtime, dan Forwarding**:
     - *Konfigurasi*: Berada di JSON secrets (`booster_config.json`).
     - *Runtime*: Berada di kernel WireGuard interface & peer state.
     - *Forwarding*: Berada di tabel routing ECMP kernel (`ip route replace default nexthop...`) dan mangle iptables (DSCP `0x28` dan TCP MSS `1360`).

2. **Perbaikan Preservasi & Rollback Multi-Default Route**:
   - Berkas: [src/api/server.py](file:///c:/mitranet/src/api/server.py).
   - Deteksi otomatis multiple default route (misalnya via `enp1s0` dan `mac0`) saat booster diterapkan: disimpan seluruhnya ke array `orig_defaults` di `/etc/mitranet/secrets/original_gateway.json`.
   - Saat booster distop (`POST /api/v1/vpn/booster/stop`), seluruh rute default asli dipulihkan secara line-by-line (`ip route replace`), mencegah hilangnya rute sekunder.

---

### 2. AUDIT THROUGHPUT & BENCHMARK MULTI-STREAM
1. **Baseline**: `wg0` ke VPS port `13231` = **121.26 Mbps (15.15 MB/s)**, RTT: **14.62 ms**, loss: **0%**.
2. **Single-Stream Booster**: `wgboost1` ke VPS port `51831` = **90.87 Mbps (11.35 MB/s)**, RTT: **15.05 ms**, loss: **0%**.
3. **Dual-Stream Concurrent Flows**: Pembangkitan aliran paralel simultan pada Stream 1 dan Stream 2 membuktikan counter paket bertambah bersamaan pada kedua tunnel (`rx=1.81 KiB / tx=5.6 KiB`). RTT stabil: **14.96 - 16.90 ms**.
   - *Prinsip ECMP*: ECMP membagi flow paralel berdasarkan hash 5-tuple, bukan membonding paket TCP tunggal menjadi satu stream.

---

### 3. ARTEFAK RILIS ISO & MANIFEST
- **Berkas ISO**: [iso/MitraNet-Rinjani-1.0.2-amd64.iso](file:///c:/mitranet/iso/MitraNet-Rinjani-1.0.2-amd64.iso)
- **Ukuran File**: `1,017,139,200 bytes` (969.95 MB)
- **SHA-256 Checksum**: `C0F69F83CB03AA0A37EE496C4A55CF7B2270BDF58281A7B41E63D56F943E9097`
- **Manifest Rilis**: [RELEASE_MANIFEST.md](file:///c:/mitranet/RELEASE_MANIFEST.md)
- **Commit Baseline**: `5b6f091`
- **Status VM Boot Test**: **NOT TESTED** (Hypervisor Hyper-V pada host Windows kekurangan RAM fisik saat alokasi; boot test fisik/eksternal wajib sebelum produksi).

---

### 4. STATUS TEST SUITE & DEPLOYMENT
- `tests/test_core.py`: **PASS**
- `tests/test_webui_api.py`: **PASS**
- `tests/test_booster_regression.py`: **PASS** (5 tests OK)
- `ai/tests/test_ai_assistant.py`: **PASS** (7 tests OK)
- Deployment Mini PC (`10.10.66.228`): **PASS** (`mitranet-webui.service` aktif).
