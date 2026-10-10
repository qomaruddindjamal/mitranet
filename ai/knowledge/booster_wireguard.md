# Cloud Speed Booster & WireGuard Tunneling Knowledge

## 1. Arsitektur Cloud Speed Booster
Cloud Speed Booster dirancang untuk mengatasi pembatasan bandwidth per-koneksi/per-flow (misalnya 5 Mbps) yang diterapkan oleh ISP pada port tertentu.

## 2. Mekanisme Multi-Stream WireGuard
- **Stream Allocation**:
  - Stream 1: Interface `wgboost1`, IP lokal `10.250.1.2/30`, Gateway VPS `10.250.1.1/30`, Port `51831`.
  - Stream 2: Interface `wgboost2`, IP lokal `10.250.2.2/30`, Gateway VPS `10.250.2.1/30`, Port `51832`.
  - Stream 3: Interface `wgboost3`, IP lokal `10.250.3.2/30`, Gateway VPS `10.250.3.1/30`, Port `51833`.
  - Stream 4: Interface `wgboost4`, IP lokal `10.250.4.2/30`, Gateway VPS `10.250.4.1/30`, Port `51834`.
- **ECMP Multipath Routing**:
  - `ip route replace default nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1 ...`
  - Lalu lintas antar koneksi baru (per-flow 5-tuple: src IP, dst IP, src port, dst port, proto) didistribusikan merata ke seluruh interface tunnel aktif.
- **DSCP Marking (Shaper Bypass)**:
  - Nilai rekomendasi: `AF41` (`0x28` - Assured Forwarding) atau `CS6` (`0x30` - Network Control).
  - Diinjeksi pada tabel mangle: `iptables -t mangle -A POSTROUTING -o <dev> -j DSCP --set-dscp <val>`.
- **TCP MSS Clamping (1360)**:
  - Mencegah fragmentasi paket TCP dan MTU black hole akibat overhead header enkripsi WireGuard (32 byte UDP/WG overhead + ISP PPPoE encapsulation).
- **TCP BBR (Bottleneck Bandwidth and RTT)**:
  - Menggantikan algoritma CUBIC berbasis packet loss dengan estimasi model throughput dan round-trip time (`sysctl -w net.ipv4.tcp_congestion_control=bbr`).

## 3. Keselamatan Jalur Administrasi (SSH Protection)
- Jalur SSH manajemen Mini PC (`10.10.66.228`) tidak boleh terputus saat aktivasi Booster.
- Sistem wajib mencadangkan default route awal di `/etc/mitranet/secrets/original_gateway.json` dan memulihkannya saat Booster dihentikan (`POST /api/v1/vpn/booster/stop`).

## 4. Pola Diagnostik Live & Troubleshooting Multi-Stream
- **Status Handshake WireGuard**:
  - Handshake dianggap sehat jika timestamp terjadi dalam 180 detik terakhir (<180s).
  - Jika timestamp handshake > 180 detik atau 0, peer dinyatakan STALE atau NO_HANDSHAKE.
  - Periksa port UDP VPS RouterOS (51831–51834) dan pastikan rule filter accept input aktif sebelum drop rule.
- **Deteksi Zero-Counter (Trafik Nol)**:
  - Jika interface tunnel UP tetapi byte Rx/Tx bernilai 0, hal ini menandakan mismatch AllowedIPs, firewall drop pada server, atau routing belum dilewatkan ke gateway peer.
- **Perbedaan Status Konfigurasi vs Runtime**:
  - Konfigurasi `enabled: true` di JSON hanya mencerminkan niat konfigurasi (intent).
  - Status runtime aktual wajib divalidasi melalui interface kernel operstate, epoch handshake, dan byte transfer aktif.
- **Prinsip Keselamatan AI**:
  - AI Assistant beroperasi secara READ-ONLY dan dilarang mengubah routing tabel atau firewall secara otomatis tanpa intervensi pengguna.
