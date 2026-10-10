# Panduan Konfigurasi Cloud Speed Booster — MitraNet Rinjani 1.0.2

Fitur **Cloud Speed Booster** di MitraNet Rinjani dirancang untuk meningkatkan stabilitas, kapasitas throughput, dan agregasi koneksi internet dengan memecah lalu lintas ke beberapa stream terenkripsi WireGuard secara paralel menuju Cloud Gateway (VPS MikroTik RouterOS atau Linux Server).

---

## 1. Arsitektur & Prinsip Kerja

```
                                  ┌─── [Stream 1: wgboost1 / Port 51831] ───┐
[Klien LAN / veth0] ──> [MitraNet]                                           ───> [VPS Cloud Gateway] ──> [Internet Publik]
                                  └─── [Stream 2: wgboost2 / Port 51832] ───┘
                                           (ECMP L4 Hash / FwMark 0xca6c)
```

1. **Multi-Tunnel WireGuard**: Membuka 2, 3, atau 4 tunnel independen (`wgboost1`, `wgboost2`, dst.) ke VPS.
2. **ECMP Multipath (Layer 4 Hashing)**: Kernel Linux membagi koneksi secara dinamis menggunakan `fib_multipath_hash_policy = 1` (berdasarkan kombinasi IP asal, IP tujuan, Port asal, dan Port tujuan).
3. **FwMark Isolation (0xca6c)**: Menjaga agar paket enkapsulasi UDP WireGuard keluar langsung melalui interface WAN fisik tanpa terjebak dalam perulangan policy routing tabel `51820`.
4. **QoS DSCP & TCP MSS Clamping**: Penandaan prioritas paket (EF/AF41) serta pencegahan fragmentasi paket MTU 1420 dengan MSS Clamping 1360 byte.

---

## 2. Persyaratan & Parameter Jaringan

| Parameter | Kebutuhan / Standar | Keterangan |
| :--- | :--- | :--- |
| **IP VPS / Cloud Gateway** | IP Publik Statis | Alamat VPS RouterOS atau Linux |
| **Port UDP Masuk di VPS** | `51831` s/d `51834` | Harus dibuka di firewall VPS |
| **Alamat Subnet Tunnel** | `10.250.X.0/30` | Stream 1: `10.250.1.0/30`, Stream 2: `10.250.2.0/30`, dst. |
| **AllowedIPs di Klien** | `0.0.0.0/0` | Untuk merutekan trafik internet keluar |
| **Allowed-Address di VPS** | `10.250.X.2/32` | Sesuai IP klien tiap stream |
| **MTU Tunnel** | `1420` | Standar aman WireGuard |
| **TCP MSS Clamping** | `1360` | Mencegah TCP packet fragmentation |

---

## 3. Langkah Konfigurasi Melalui WebUI MitraNet

1. Buka WebUI MitraNet di browser: `http://10.10.66.228:8000` (atau IP manajemen router Anda).
2. Di menu sidebar kiri, buka menu **VPN** ➔ **Cloud Speed Booster** (`/vpn/vpn_booster.php`).
3. Pada panel **Konfigurasi Cloud Booster**:
   - **Alamat Cloud VPS Gateway**: Masukkan IP publik VPS Anda (contoh: `103.93.162.168`).
   - **Jumlah Stream Paralel**: Pilih `2 Streams`, `3 Streams`, atau `4 Streams`.
   - **Tipe Tunnel Multi-Link**: Pilih `WireGuard Multi-Link (L3/L4)`.
   - **Metode Pembagian Beban**: Pilih `ECMP Multipath (Equal-Cost Multi-Path)`.
   - **DSCP Traffic Class**: Pilih `Expedited Forwarding (EF - 0x2e)` atau `AF41 (0x28)`.
   - **TCP MSS Clamping**: Pilih `1360 Bytes`.
4. Klik tombol **Terapkan & Jalankan Booster**.
5. Sistem akan:
   - Membuat pasangan kunci kriptografi (Private/Public Key) untuk tiap stream.
   - Menyiapkan interface `wgboost1` s/d `wgboostN`.
   - Mengonfigurasi tabel routing `51820` dan tabel `main`.
   - Menghasilkan skrip konfigurasi otomatis untuk VPS Anda.

---

## 4. Konfigurasi Sisi VPS (Server Gateway)

### Opsi A: VPS MikroTik RouterOS (CHR / Cloud Hosted Router)
Salin skrip yang muncul di tab **RouterOS Script** pada WebUI, lalu tempel (*paste*) di Terminal RouterOS / WinBox:

```routeros
# 1. Buka Firewall Port UDP
/ip firewall filter add chain=input action=accept protocol=udp dst-port=51831-51834 comment="Accept WireGuard Booster Streams" place-before=0

# 2. Tambah Interface WireGuard & IP Address
/interface wireguard add name=wg-boost1 listen-port=51831 comment="MitraNet Stream 1"
/ip address add address=10.250.1.1/30 interface=wg-boost1 network=10.250.1.0

/interface wireguard add name=wg-boost2 listen-port=51832 comment="MitraNet Stream 2"
/ip address add address=10.250.2.1/30 interface=wg-boost2 network=10.250.2.0

# 3. Daftarkan Peer Klien (Ganti Public Key Klien sesuai yang digenerate WebUI)
/interface wireguard peers add interface=wg-boost1 public-key="<CLIENT_PUBKEY_STREAM_1>" allowed-address=10.250.1.2/32 comment="MitraNet Node Stream 1"
/interface wireguard peers add interface=wg-boost2 public-key="<CLIENT_PUBKEY_STREAM_2>" allowed-address=10.250.2.2/32 comment="MitraNet Node Stream 2"

# 4. Outbound NAT Masquerade
/ip firewall nat add chain=srcnat src-address=10.250.1.0/30 action=masquerade comment="Booster NAT Stream 1"
/ip firewall nat add chain=srcnat src-address=10.250.2.0/30 action=masquerade comment="Booster NAT Stream 2"

# 5. TCP MSS Clamping di VPS
/ip firewall mangle add chain=forward action=change-mss new-mss=1360 passthrough=yes tcp-flags=syn protocol=tcp tcp-mss=1361-65535 comment="Clamp MSS for Booster"
```

### Opsi B: VPS Linux (Ubuntu / Debian)
Jalankan perintah berikut di VPS Linux Anda:

```bash
# Aktifkan IP Forwarding & Algoritma BBR
sysctl -w net.ipv4.ip_forward=1
sysctl -w net.core.default_qdisc=fq
sysctl -w net.ipv4.tcp_congestion_control=bbr

# Pastikan iptables Masquerade aktif
iptables -t nat -A POSTROUTING -s 10.250.0.0/16 -o eth0 -j MASQUERADE
iptables -A FORWARD -i wgboost+ -j ACCEPT
iptables -A FORWARD -o wgboost+ -m state --state ESTABLISHED,RELATED -j ACCEPT
```

---

## 5. Pemantauan & Verifikasi di WebUI (Status HUD)

Pada halaman **Cloud Speed Booster** (`/vpn/vpn_booster.php`):
- **HUD Stream Status**: Menampilkan status real-time tiap tunnel (`UP` / `DOWN`), latensi RTT (ms), status handshake, serta transfer RX/TX.
- **HUD Agregasi**: Menampilkan total throughput gabungan yang sedang aktif melewati seluruh stream.
- **Tombol Hentikan Booster**: Menghapus interface `wgboost*` dan mengembalikan routing default secara instan dan bersih ke gateway awal.

---

## 6. Tips Mengoptimalkan Throughput Agregat

1. **Multi-WAN Uplink**:
   - Jika Mini PC terhubung ke lebih dari 1 ISP fisik (misalnya ISP 1 melalui `enp1s0` dan ISP 2 melalui `mac0`/VLAN), pisahkan endpoint UDP tunnel menggunakan policy routing per-WAN. Ini akan melipatgandakan kecepatan fisik secara nyata.
2. **Koneksi Paralel (Multi-Flow)**:
   - Karena ECMP membagi beban per sesi/koneksi (bukan per paket tunggal), agregasi maksimal terlihat ketika jaringan melayani banyak koneksi bersamaan (multi-user, download manager multi-thread, streaming, atau web browsing).
