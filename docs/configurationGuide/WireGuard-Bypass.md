# Panduan Konfigurasi WireGuard Shaper & Throttling Bypass — MitraNet Rinjani 1.0.2

Fitur **WireGuard Shaper & Throttling Bypass** di MitraNet Rinjani dirancang untuk menembus batasan kecepatan (*bandwidth throttling*), antrian *Simple Queue/Tree* ISP, serta *DPI (Deep Packet Inspection)* yang sengaja memperlambat atau membatasi lalu lintas VPN.

---

## 1. Arsitektur & Cara Kerja Fitur Bypass

Ada 3 lapisan mekanisme yang digabungkan secara otomatis melalui WebUI:

```
+-----------------------------------------------------------------------------------------+
|                                    MITRANET ROUTER                                      |
|                                                                                         |
|  [ Klien LAN / Hotspot / VM ]                                                           |
|             |                                                                           |
|             v                                                                           |
|  [ TCPMSS Clamping ] ----------> Menyesuaikan ukuran paket TCP SYN agar pas MTU (1420)  |
|             |                   (Mencegah fragmentasi paket UDP WireGuard)              |
|             v                                                                           |
|  [ WireGuard Core ] -----------> Enkripsi ChaCha20-Poly1305                             |
|             |                                                                           |
|             v                                                                           |
|  [ QoS DSCP Priority Mark ] ---> Injeksi tag EF (VoIP) / CS7 (Network Control)          |
|             |                   via `iptables -t mangle -A POSTROUTING`                 |
|             v                                                                           |
|  [ Port Camouflage ] ----------> UDP Port 53 (DNS) atau UDP Port 443 (QUIC / HTTPS)     |
+-------------|---------------------------------------------------------------------------+
              |
              v (Keluar melalui Modem ISP / Uplink)
+-----------------------------------------------------------------------------------------+
|                                 ROUTER / DPI SHAPER ISP                                 |
|                                                                                         |
|  - Melihat paket sebagai: UDP port 53 / 443 ber-tag DSCP EF (High Priority Traffic)    |
|  - Paket dilewatkan tanpa antrian rate limit (Bypass Simple Queue & Best-Effort Drop)   |
|  - Tidak ada fragmentasi paket (Throughput maksimal & jitter stabil)                    |
+-----------------------------------------------------------------------------------------+
```

---

## 2. Lokasi Pengaturan di WebUI MitraNet

Semua pengaturan telah terintegrasi di WebUI tanpa memerlukan terminal/SSH manual:

1. **Konfigurasi Tunnel (DSCP Priority & Anti-Fragmentation MSS Clamping)**:
   - Navigasi: **VPN** > **WireGuard** > Tab **Tunnels** (`/wg/vpn_wg_tunnels.php`)
   - Klik ikon **Edit** pada tunnel (misal: `wg0`) atau klik **Add Tunnel** untuk membuat baru.
   - Bagian **WireGuard Shaper & Throttling Bypass**:
     - **DSCP Priority Mark**: Dropdown pilihan tag QoS.
     - **Anti-Fragmentation MSS Clamping**: Checkbox proteksi fragmentasi paket.
     - **Listen Port & Camouflage**: Tombol dropdown *Camouflage* pada kolom Listen Port.

2. **Konfigurasi Remote Peer (Endpoint Camouflage)**:
   - Navigasi: **VPN** > **WireGuard** > Tab **Peers** (`/wg/vpn_wg_peers.php`)
   - Klik **Edit** atau **Add Peer**.
   - Pada baris **Endpoint Host & Port**, klik tombol **Camouflage** untuk memilih port server penyamaran.

---

## 3. Langkah Konfigurasi Step-by-Step

### Langkah 1: Atur Port Camouflage pada Tunnel
1. Masuk ke **VPN > WireGuard > Tunnels** > Klik **Edit** pada `wg0`.
2. Di samping form **Listen Port**, klik tombol dropdown **Camouflage**.
3. Pilih salah satu preset:
   - **Port 53 (DNS Server Bypass)** *(Sangat Efektif)*: Menyamarkan paket WireGuard sebagai query DNS UDP. Sebagian besar ISP tidak melakukan limit/drop pada port 53 untuk menjaga stabilitas browsing pelanggan.
   - **Port 443 (QUIC/HTTPS Bypass)**: Menyamarkan paket sebagai trafik HTTP/3 QUIC modern (Youtube, Google, Cloudflare).
   - **Port 123 (NTP Time Bypass)**: Menyamarkan paket sebagai sinkronisasi waktu jaringan.

### Langkah 2: Aktifkan QoS DSCP Priority Mark
1. Pada form yang sama di bagian **WireGuard Shaper & Throttling Bypass**:
2. Buka dropdown **DSCP Priority Mark** dan pilih salah satu kelas prioritas:
   - **EF (Expedited Forwarding / Nilai 46)** *(Rekomendasi Utama)*:
     Tag standar untuk VoIP dan trafik interaktif real-time. Router uplink akan memperlakukan paket sebagai antrian tercepat (*Lowest Latency & Highest Scheduling Priority*).
   - **CS6 (Internetwork Control / Nilai 48)**:
     Tag prioritas kontrol routing antar-jaringan.
   - **CS7 (Network Control / Nilai 56)**:
     Tag tingkat VIP tertinggi pada switch/router telekomunikasi.
   - **AF41 (Assured Forwarding High Throughput)**:
     Menjamin throughput bandwidth tinggi tanpa packet drop mendadak.

### Langkah 3: Aktifkan Anti-Fragmentation MSS Clamping
1. Pastikan kotak centang **Anti-Fragmentation MSS Clamping (`TCPMSS --clamp-mss-to-pmtu`)** dalam keadaan tercentang (*Checked*).
2. **Mengapa ini penting?**
   WireGuard menambahkan overhead header (biasanya MTU 1420 byte). Jika klien mengirim paket TCP ukuran standar (1500 byte), paket akan dipecah (fragmentasi). Banyak shaper ISP membatasi atau me-drop paket UDP terfragmentasi. Clamping memaksa ukuran TCP MSS disesuaikan otomatis dengan jalur tunnel sehingga transmisi 100% mulus.

### Langkah 4: Simpan & Terapkan
1. Klik tombol **Update Tunnel** atau **Save Tunnel**.
2. WebUI akan secara otomatis:
   - Menghasilkan aturan `PostUp` dan `PostDown` di `/etc/wireguard/<name>.conf`.
   - Mengaktifkan kernel mangle rule via `iptables`.
   - Me-restart antarmuka `wg-quick@<name>` secara instan tanpa restart mesin.

---

## 4. Verifikasi Aturan di Sistem (Teknis & Diagnostics)

Aturan yang diterapkan oleh WebUI bekerja langsung di level kernel Linux:

### Memeriksa Konfigurasi Tunnel:
```bash
cat /etc/wireguard/wg0.conf
```
Contoh output konfigurasi yang di-generate:
```ini
[Interface]
# Description: WireGuard Server Tunnel
# Mode: server
# DSCP: EF
# ClampMSS: true
Address = 10.10.99.1/24
ListenPort = 53
PrivateKey = <server-private-key>
MTU = 1420

# Shaper Bypass: DSCP Priority & Anti-Fragmentation MSS Clamping
PostUp = iptables -t mangle -A POSTROUTING -p udp --dport 53 -j DSCP --set-dscp-class EF
PostUp = iptables -t mangle -A POSTROUTING -p udp --sport 53 -j DSCP --set-dscp-class EF
PostDown = iptables -t mangle -D POSTROUTING -p udp --dport 53 -j DSCP --set-dscp-class EF 2>/dev/null || true
PostDown = iptables -t mangle -D POSTROUTING -p udp --sport 53 -j DSCP --set-dscp-class EF 2>/dev/null || true
PostUp = iptables -t mangle -A FORWARD -p tcp --tcp-flags SYN,RST SYN -o wg0 -j TCPMSS --clamp-mss-to-pmtu
PostUp = iptables -t mangle -A FORWARD -p tcp --tcp-flags SYN,RST SYN -i wg0 -j TCPMSS --clamp-mss-to-pmtu
PostDown = iptables -t mangle -D FORWARD -p tcp --tcp-flags SYN,RST SYN -o wg0 -j TCPMSS --clamp-mss-to-pmtu 2>/dev/null || true
PostDown = iptables -t mangle -D FORWARD -p tcp --tcp-flags SYN,RST SYN -i wg0 -j TCPMSS --clamp-mss-to-pmtu 2>/dev/null || true
```

### Memeriksa Penghitung Paket Mangle:
Masuk ke menu **Diagnostics > Command Prompt** atau jalankan perintah:
```bash
iptables -t mangle -L POSTROUTING -v -n
```
Anda akan melihat statistik hitungan paket (*packet counters*) bertambah pada baris target `DSCP set EF`.

---

## 5. Tips Tambahan untuk Menembus Shaper Ekstrem

1. **Gunakan Google BBR**:
   Buka menu **System > Advanced > System Tunables & BBR**, aktifkan algoritma **BBR** dan **fq**. BBR akan memompa paket secara agresif dengan mengukur *bottleneck bandwidth* aktual tanpa terpengaruh oleh *random packet drop* dari ISP.
2. **Kombinasi Multi-WAN / VLAN**:
   Jika satu jalur ISP memiliki hard limit bandwidth (misal: FUP atau paket 50 Mbps), buat 2 interface VLAN atau 2 port Ethernet, sambungkan ke 2 tunnel WireGuard berbeda, lalu gabungkan di menu **Firewall > Routing / Load Balancing**.
