# Panduan Konfigurasi WireGuard VPN — MitraNet Rinjani 1.0.2

WireGuard adalah protokol Virtual Private Network (VPN) modern berbasis kriptografi state-of-the-art (ChaCha20, Poly1305, Curve25519, BLAKE2s) yang berjalan langsung di dalam kernel Linux. Di MitraNet Rinjani, WireGuard terintegrasi penuh dengan WebUI untuk mendukung mode Server, Client Uplink, serta teknik penembus batas bandwidth ISP.

---

## 1. Navigasi Menu WebUI WireGuard
- **Tunnels** (`/wg/vpn_wg_tunnels.php`): Membuat dan mengelola antarmuka tunnel WireGuard (`wg0`, `wg1`, dst.), port listening, kunci kriptografi, policy routing antarmuka lokal, serta fitur **Shaper Bypass**.
- **Peers** (`/wg/vpn_wg_peers.php`): Mendaftarkan perangkat klien remote (Smartphone Android/iOS, Laptop Windows/macOS/Linux, Router MikroTik CHR).
- **Settings** (`/wg/vpn_wg_settings.php`): Pengaturan umum modul VPN WireGuard.
- **Status** (`/wg/status_wireguard.php`): Pemantauan *real-time* status koneksi, waktu handshake terakhir (*latest handshake*), transfer rate RX/TX, serta pembuat file konfigurasi `.conf` dan QR Code untuk klien mobile.

---

## 2. Fitur Lanjutan & Penembus Shaper ISP
Untuk panduan mendalam mengenai cara melewati limitasi kecepatan ISP, teknik penyamaran port, serta injeksi QoS DSCP tagging:
- Silakan merujuk ke dokumen: [Panduan WireGuard Shaper & Throttling Bypass](WireGuard-Bypass.md).
