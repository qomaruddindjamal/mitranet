# MITRANET WORK STATE — PERSISTENT CHECKPOINT

- **Waktu Pembaruan:** 2026-10-10 02:37:00 WIB
- **Tujuan & Ruang Lingkup Aktif:** Penyelesaian Implementasi WireGuard Penuh (Tahap 1 s/d 10): Hook Preservation, WebUI Policy Routing & NAT Masquerade, Dynamic Telemetry, Recovery Stale Interface, Peer MikroTik ROS v7 & Client Config Export, Deployment Mini PC, Git Push, dan ISO Rebuild.

---

### 1. HASIL IMPLEMENTASI & PERBAIKAN WIREGUARD

1. **Tahap 1 — Preservasi Hook `PostUp` & `PostDown`**:
   - Berkas: [src/api/server.py](file:///c:/mitranet/src/api/server.py)
   - Parser `parse_wireguard_conf()` dan generator konfigurasi memisahkan hook auto-generated (`table 100`, `table 101`, `MASQUERADE`, `net.ipv4.ip_forward=1`) dari hook custom pengguna (`custom_postup`, `custom_postdown`).
   - Mencegah duplikasi hook auto-routing/NAT saat tunnel disimpan berulang kali.
   - Validasi ketat nama interface (`^[a-zA-Z0-9_\-]+$`), format IP/CIDR (`^[0-9a-fA-F\.\:\,\s\/]+$`), listen port (`1-65535`), dan MTU (`576-65535`) untuk mencegah command injection.
   - Penulisan berkas konfigurasi menggunakan mekanisme atomik dan mempertahankan peer lama tanpa kehilangan data.

2. **Tahap 2 — WebUI Policy Routing per Interface & NAT Masquerade**:
   - Berkas: [web/wg/vpn_wg_tunnels_edit.php](file:///c:/mitranet/web/wg/vpn_wg_tunnels_edit.php), [web/includes/api.inc](file:///c:/mitranet/web/includes/api.inc), [src/api/server.py](file:///c:/mitranet/src/api/server.py).
   - Form editor tunnel dilengkapi kontrol:
     - `Enable Routing from Local Interface` & Dropdown `route_interface` (otomatis mendeteksi interface lokal seperti `veth0`, `eth0`, dll.).
     - Checkbox toggle `Enable Outbound NAT (Masquerade)`.
     - Input field `DNS` dan `MTU`.
   - Mengalirkan routing table 100 (`ip rule add iif <interface> table 100`, `ip route add default dev <wg> table 100`, iptables forward rules, dan masquerade) secara operasional dan persisten.

3. **Tahap 3 — Telemetri Dinamis**:
   - Berkas: [web/wg/status_wireguard.php](file:///c:/mitranet/web/wg/status_wireguard.php), [src/api/server.py](file:///c:/mitranet/src/api/server.py).
   - Menghapus nilai statis/hardcoded (`tun_wg0`, `WGVPN (opt1)`, `1420`, `23.4 KiB`, `1.2 KiB`).
   - Telemetri tunnel membaca interface aktual (`name`, `interface`, `address`), MTU live dari `/sys/class/net/<dev>/mtu`, serta RX/TX real-time terakumulasi dari seluruh peer aktif.

4. **Tahap 4 — Recovery Stale Interface**:
   - Berkas: [src/api/server.py](file:///c:/mitranet/src/api/server.py) pada endpoint `/api/v1/wireguard/service` dan `/api/v1/wireguard/tunnel/save`.
   - Melakukan deteksi komprehensif bila interface kernel sudah ada namun service systemd tidak sinkron: menjalankan `wg-quick down` dan `ip link delete` sebelum me-restart service untuk mencegah error `Interface already exists`.

5. **Tahap 5 — Tools Peer (MikroTik ROS Command & Client Config)**:
   - Berkas: [web/wg/vpn_wg_peers.php](file:///c:/mitranet/web/wg/vpn_wg_peers.php).
   - Ditambahkan tombol aksi `ROS` yang memunculkan modal SweetAlert2 interaktif berisi command MikroTik RouterOS v7 siap pakai (`/interface wireguard peers add interface=wg0 public-key="..." allowed-address=...`).
   - Ditambahkan tombol aksi `QR` dan `Download .conf` client dengan generator SVG QR Code lokal dan modal konfigurasi siap download.

---

### 2. PENGUJIAN REGRESI & KUALITAS KODE (LOKAL)

- **Unit & Regression Tests:** [tests/test_wireguard_regression.py](file:///c:/mitranet/tests/test_wireguard_regression.py)
  - `test_parse_and_preserve_hooks`: LULUS (100%).
  - `test_ros_command_generation_format`: LULUS (100%).
  - `test_repeated_save_hook_preservation`: LULUS (100%).
  - `test_input_validation`: LULUS (100%).
- **WebUI API Tests:** [tests/test_webui_api.py](file:///c:/mitranet/tests/test_webui_api.py) (10 tests LULUS).
- **Linter PHP & Python:** `py_compile` dan `php -l` lulus tanpa syntax error.

---

### 3. DEPLOYMENT & PIPELINE STATUS

- **Mini PC (`10.10.66.228`):**
  - Berkas disinkronkan ke `/mitranet/web`, `/usr/share/mitranet/web`, `/mitranet/src`, `/mitranet/core`, dan `/usr/lib/python3/dist-packages/mitranet/`.
  - Service `mitranet-webui.service` aktif (`active`).
  - Interface `wg0` aktif dengan tunnel ke MikroTik CHR VPS `103.93.162.168:13231` (Handshake aktif, transfer >340 MiB).
- **Rebuild ISO:**
  - File: `iso/MitraNet-Rinjani-1.0.2-amd64.iso`
  - Ukuran: 1,017,139,200 bytes
  - Status: Exit code 0 (xorriso berhasil membuat hybrid ISO).
- **Git Commit & Push GitHub:**
  - Commit: `9a64b1fb13e7bb8842f1dab76348bfa0418495f0`
  - Pesan: `feat(wireguard): fix postup/postdown hooks preservation, implement policy routing & nat masquerade, dynamic telemetry, and peer tools`
  - Branch: `origin/main` (Synced).

---

### 4. STATUS KESIAPAN
Semua 10 tahap tugas implementasi WireGuard telah tuntas dieksekusi, diuji, disinkronkan ke Mini PC, di-push ke GitHub, dan di-build ke berkas ISO resmi MitraNet.
