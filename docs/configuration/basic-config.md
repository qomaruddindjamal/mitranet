# Panduan Konfigurasi Dasar & Fitur Lanjutan MitraNet Rinjani 1.0.2

Dokumen ini menjelaskan alur konfigurasi fitur antarmuka jaringan, VPN Camouflage, dan optimasi kernel yang tersedia di WebUI MitraNet.

---

## 1. Virtual MAC / MACVLAN (Multi-MAC Splitting)
Fitur untuk melahirkan beberapa identitas MAC address virtual mandiri di atas satu antarmuka fisik Ethernet.
- **Lokasi Menu WebUI**: `Interfaces` > `MACVLANs` atau URL `interfaces.php?tab=macvlan`.
- **Langkah Pembuatan**:
  1. Klik tombol **New** di pojok kiri atas tabel.
  2. Masukkan **Interface Name** (contoh: `mac0` atau `macvlan0`).
  3. Pilih **Parent Physical Interface** (contoh: `enp1s0`).
  4. Pilih **MACVLAN Mode**:
     - `bridge`: Mode standar untuk komunikasi langsung antar-virtual MAC dan jaringan luar.
     - `vepa` / `private` / `passthru`: Sesuai kebutuhan isolasi keamanan jaringan.
  5. Masukkan **Custom MAC Address** (opsional, kosongkan jika ingin di-generate otomatis oleh kernel).
  6. Centang **Minta IP Address otomatis via DHCP (dhcpcd)**.
  7. Klik **Create MACVLAN**.

---

## 2. 802.1Q VLAN Tagging & Sub-interfaces
Membagi segmen jaringan Layer 2 menggunakan tag 802.1Q.
- **Lokasi Menu WebUI**: `Interfaces` > `VLANs` atau URL `interfaces.php?tab=vlan`.
- **Langkah Pembuatan**:
  1. Klik tombol **New** > Pilih **VLAN Interface**.
  2. Pilih **Parent Interface** fisik (misal: `enp1s0`).
  3. Tentukan **VLAN Tag** (1 - 4094).
  4. Centang opsi **Minta IP Address otomatis via DHCP** jika segmen VLAN tersebut memiliki server DHCP di router upstream.
  5. Klik **Create VLAN**.
  > *Catatan Trunking:* Pastikan port switch pada router upstream dikonfigurasi sebagai *Trunk Port / Tagged* untuk VLAN ID terkait.

---

## 3. WireGuard Port Camouflage (DNS Port 53 / QUIC Port 443)
Menyamarkan seluruh paket terenkripsi VPN WireGuard menjadi port layanan umum untuk mem-bypass antrian firewall shaper / Simple Queue pada router ISP/upstream.
- **Lokasi Menu WebUI**: `VPN` > `WireGuard` > `Peers` (`/wg/vpn_wg_peers.php` atau edit peer di `vpn_wg_peers_edit.php`).
- **Langkah Konfigurasi**:
  1. Buka atau edit entri Peer WireGuard.
  2. Pada baris **Endpoint Host & Port**, klik tombol dropdown **Camouflage**.
  3. Pilih preset penyamaran yang diinginkan:
     - **Port 53 (DNS Bypass)**: Menyamarkan handshake dan data sebagai query DNS.
     - **Port 443 (QUIC/HTTPS Bypass)**: Menyamarkan paket UDP sebagai protokol HTTP/3 QUIC.
     - **Port 123 (NTP Time)**: Menyamarkan paket sebagai sinkronisasi waktu jaringan.
  4. Simpan konfigurasi peer (*Save Peer* / *Update Peer*).

---

## 4. Kernel Performance Tunables & TCP BBR
Mengoptimalkan kapasitas pengiriman paket Linux kernel menggunakan algoritma pengontrol kemacetan (Congestion Control) **Google BBR** guna mencegah *bufferbloat* dan degradasi throughput.
- **Lokasi Menu WebUI**: `System` > `Advanced` > Tab **System Tunables & BBR** (`/system/advanced_sysctl.php`).
- **Parameter yang Dapat Dikonfigurasi**:
  1. **TCP Congestion Control**:
     - `BBR (Bottleneck Bandwidth and RTT)`: Direkomendasikan untuk memaksimalkan throughput pada saluran terbatas.
     - `CUBIC`: Algoritma default standar Linux.
  2. **Packet Scheduling (Qdisc)**:
     - `fq_codel`: Fair Queuing Controlled Delay (standar modern).
     - `fq`: Fair Queuing Packet Pacing (sangat direkomendasikan bersama BBR).
  3. **TCP Fast Open (TFO)**: Mempercepat waktu koneksi TCP.
  4. **Max Receive / Transmit Buffer**: Mengatur ukuran buffer socket socket window (hingga 16 MB).
  5. Klik **Simpan & Terapkan Tuning** (perubahan langsung aktif dan disimpan permanen di `/etc/sysctl.d/99-mitranet-tuning.conf`).
