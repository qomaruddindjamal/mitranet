# Panduan Lengkap Konfigurasi Cloud Speed Booster — MitraNet Rinjani 1.0.2

Dokumen ini adalah panduan operasional dan teknis resmi untuk konfigurasi **MitraNet Cloud Speed Booster Multi-Stream WireGuard**, mencakup konfigurasi sisi **MikroTik CHR (Cloud VPS Gateway)** baik via **WinBox GUI** maupun **Terminal CLI**, serta sisi **MitraNet OS Rinjani** baik via **WebUI** maupun **Terminal Linux CLI**.

---

## 1. Arsitektur & Prinsip Kerja Multi-Stream

```
                                  ┌─── [Stream #1: wgboost1 / Port 51831] ───┐
[Klien LAN / veth0] ──> [MitraNet]                                           ───> [VPS MikroTik CHR] ──> [Internet Publik]
                                  └─── [Stream #2: wgboost2 / Port 51832] ───┘
                                           (ECMP L4 Hash / Table=off)
```

1. **Multi-Tunnel WireGuard**: Membuka 2 hingga 4 stream tunnel paralel ke VPS tujuan.
2. **Bypass Policer Bandwidth ISP**: Banyak penyedia ISP menerapkan *traffic shaping* / *policing* ketat per koneksi tunggal UDP. Dengan agregasi multi-stream paralel pada port berbeda, batas throughput terakumulasi secara berlipat ganda.
3. **ECMP Multipath (Equal-Cost Multi-Path)**: Kernel Linux membagi koneksi secara seimbang menggunakan `fib_multipath_hash_policy = 1` (kombinasi 4-tuple: IP Asal, IP Tujuan, Port Asal, Port Tujuan).
4. **TCP MSS Clamping (1360 Byte)**: Mencegah fragmentasi paket TCP akibat MTU 1420 WireGuard dan overhead enkapsulasi ISP.
5. **Algoritma TCP BBR + FQ**: Mengoptimalkan antrean transmisi kernel Linux agar throughput maksimal dengan latensi serendah mungkin.

---

## 2. Parameter Jaringan & Alamat IP

| Komponen | Parameter | Nilai / Konfigurasi | Keterangan |
| :--- | :--- | :--- | :--- |
| **VPS Gateway** | Host / IP Publik | `103.93.162.168` | IP Publik VPS MikroTik CHR |
| **Stream #1** | Subnet Point-to-Point | `10.250.1.0/30` | VPS: `10.250.1.1`, MitraNet: `10.250.1.2` |
| | Port UDP Listener | `51831` | Terbuka di Firewall VPS |
| | VPS Public Key | `V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8=` | Kunci publik antarmuka `wg-boost1` |
| | MitraNet Public Key | `ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o=` | Kunci publik peer di VPS |
| **Stream #2** | Subnet Point-to-Point | `10.250.2.0/30` | VPS: `10.250.2.1`, MitraNet: `10.250.2.2` |
| | Port UDP Listener | `51832` | Terbuka di Firewall VPS |
| | VPS Public Key | `e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8=` | Kunci publik antarmuka `wg-boost2` |
| | MitraNet Public Key | `jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc=` | Kunci publik peer di VPS |
| **Optimasi** | Algoritma Balancing | `ECMP` | Hash L4 Multipath |
| | DSCP QoS | `AF41 (0x28)` | Prioritas multimedia stream |
| | Clamping MSS | `1360` | Menghindari fragmentasi |

---

## 3. Konfigurasi Sisi VPS MikroTik CHR

### A. Versi GUI (WinBox MikroTik)

1. **Membuat Antarmuka WireGuard**:
   - Buka menu **WireGuard** ➔ Tab **WireGuard** ➔ Klik icon **+ (Add)**:
     - **Stream 1**: Name = `wg-boost1`, Listen Port = `51831` ➔ Klik **Apply** & **OK**.
     - **Stream 2**: Name = `wg-boost2`, Listen Port = `51832` ➔ Klik **Apply** & **OK**.
   - Catat atau salin nilai **Public Key** dari masing-masing antarmuka di atas.

2. **Menambahkan Alamat IP Subnet Tunnel**:
   - Buka menu **IP** ➔ **Addresses** ➔ Klik icon **+ (Add)**:
     - **Stream 1**: Address = `10.250.1.1/30`, Network = `10.250.1.0`, Interface = `wg-boost1` ➔ Klik **OK**.
     - **Stream 2**: Address = `10.250.2.1/30`, Network = `10.250.2.0`, Interface = `wg-boost2` ➔ Klik **OK**.

3. **Mendaftarkan Peer Klien MitraNet**:
   - Buka menu **WireGuard** ➔ Tab **Peers** ➔ Klik icon **+ (Add)**:
     - **Peer Stream 1**:
       - Interface = `wg-boost1`
       - Public Key = `ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o=`
       - Allowed Address = `10.250.1.2/32`
       - Comment = `Booster Peer 1` ➔ Klik **OK**.
     - **Peer Stream 2**:
       - Interface = `wg-boost2`
       - Public Key = `jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc=`
       - Allowed Address = `10.250.2.2/32`
       - Comment = `Booster Peer 2` ➔ Klik **OK**.

4. **Firewall Filter (Membuka Port UDP)**:
   - Buka menu **IP** ➔ **Firewall** ➔ Tab **Filter Rules** ➔ Klik **+ (Add)**:
     - Chain = `input`, Protocol = `udp`, Dst. Port = `51831-51834`, Action = `accept`.
     - Pindahkan urutan rule ini ke baris paling atas (sebelum rule drop paket WAN).

5. **Firewall NAT (Masquerade Internet Keluar)**:
   - Buka menu **IP** ➔ **Firewall** ➔ Tab **NAT** ➔ Klik **+ (Add)**:
     - Rule 1: Chain = `srcnat`, Src. Address = `10.250.1.0/30`, Action = `masquerade` ➔ Klik **OK**.
     - Rule 2: Chain = `srcnat`, Src. Address = `10.250.2.0/30`, Action = `masquerade` ➔ Klik **OK**.

---

### B. Versi CLI (RouterOS v7 Terminal)

Salin dan tempel perintah berikut langsung ke Terminal RouterOS / WinBox CLI:

```routeros
# ====================================================================
# 1. Buka Port UDP Masuk di Firewall
# ====================================================================
/ip firewall filter add chain=input action=accept protocol=udp dst-port=51831-51834 comment="MitraNetBooster Multi-Stream" place-before=[:pick [/ip firewall filter find where chain="input" and action="drop"] 0]

# ====================================================================
# 2. Antarmuka WireGuard & Alamat IP
# ====================================================================
/interface wireguard add name=wg-boost1 listen-port=51831 comment="MitraNet Booster Stream 1"
/ip address add address=10.250.1.1/30 interface=wg-boost1 network=10.250.1.0

/interface wireguard add name=wg-boost2 listen-port=51832 comment="MitraNet Booster Stream 2"
/ip address add address=10.250.2.1/30 interface=wg-boost2 network=10.250.2.0

# ====================================================================
# 3. Peers MitraNet Klien
# ====================================================================
/interface wireguard peers add interface=wg-boost1 public-key="ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o=" allowed-address=10.250.1.2/32 comment="Booster Peer 1"
/interface wireguard peers add interface=wg-boost2 public-key="jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc=" allowed-address=10.250.2.2/32 comment="Booster Peer 2"

# ====================================================================
# 4. Outbound NAT Masquerade
# ====================================================================
/ip firewall nat add chain=srcnat action=masquerade src-address=10.250.1.0/30 comment="NAT Boost1"
/ip firewall nat add chain=srcnat action=masquerade src-address=10.250.2.0/30 comment="NAT Boost2"
```

---

## 4. Konfigurasi Sisi MitraNet OS Rinjani

### A. Versi WebUI (Browser Dasbor)

1. Masuk ke halaman **Cloud Speed Booster**:
   - URL: `http://<IP_MITRANET>:8443/vpn/vpn_booster.php` (atau melalui navigasi sidebar **VPN** ➔ **Cloud Speed Booster**).
2. Di panel **Parameter Client Uplink Booster**:
   - **Alamat VPS / Cloud Gateway IP atau Hostname**: `103.93.162.168`
   - **Jumlah Parallel Streams**: `2 Streams (2x Multi-Link)`
   - **Tipe Tunnel**: `WireGuard Multi-Link`
   - **Balancing Mode**: `ECMP (Equal Cost Multi-Path)`
   - **DSCP Marking (Shaper Bypass)**: `AF41 (0x28 - Multimedia Stream)`
   - **TCP MSS Clamping**: `1360`
   - **Akselerasi Kernel**: Centang `Aktifkan TCP BBR / FQ`
   - **Public Key Server VPS (Peer Public Key)**:
     Tempelkan Public Key VPS (1 baris per stream jika berbeda antarmuka di MikroTik):
     ```text
     V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8=
     e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8=
     ```
3. Klik tombol **Aktifkan Client**.
   - Sistem akan memunculkan dialog konfirmasi SweetAlert2. Klik **Ya, Terapkan Sekarang**.
4. **Verifikasi Tampilan Dasbor**:
   - Status di HUD atas berubah menjadi **AKTIF (MULTI-PATH)** berwarna hijau.
   - Tombol berubah menjadi **Hentikan Client** berwarna merah, dan form parameter terkunci (*readonly*).
   - Di tabel **Status Real-Time Client Multi-Stream**, baris Stream #1 dan Stream #2 berstatus **UP** dengan nilai **Latency RTT** aktif (`~14-16 ms`), serta penghitung trafik RX/TX aktif secara langsung.

---

### B. Versi CLI (Linux Terminal di MitraNet)

Bagi administrator yang ingin mengonfigurasi atau melakukan inspeksi manual via SSH terminal MitraNet:

```bash
# ====================================================================
# 1. Konfigurasi File WireGuard Stream 1 (/etc/wireguard/wgboost1.conf)
# ====================================================================
cat << 'EOF' > /etc/wireguard/wgboost1.conf
[Interface]
Address = 10.250.1.2/30
ListenPort = 51831
PrivateKey = uHqz5T3CEDas8edg8Dm919+Dsdk4ych7JYPEGE/amUg=
Table = off
MTU = 1420

[Peer]
PublicKey = V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8=
Endpoint = 103.93.162.168:51831
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 10
EOF

# ====================================================================
# 2. Konfigurasi File WireGuard Stream 2 (/etc/wireguard/wgboost2.conf)
# ====================================================================
cat << 'EOF' > /etc/wireguard/wgboost2.conf
[Interface]
Address = 10.250.2.2/30
ListenPort = 51832
PrivateKey = GJRoPExo3z7ixI6S8dRj0JSix3CjIa189fjBVw3QaUE=
Table = off
MTU = 1420

[Peer]
PublicKey = e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8=
Endpoint = 103.93.162.168:51832
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 10
EOF

# ====================================================================
# 3. Izin File & Mengaktifkan Interface
# ====================================================================
chmod 600 /etc/wireguard/wgboost*.conf
wg-quick up wgboost1
wg-quick up wgboost2

# ====================================================================
# 4. Rute ECMP Multipath & Aturan TCP MSS
# ====================================================================
ip route replace default nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1
sysctl -w net.ipv4.fib_multipath_hash_policy=1
iptables -t mangle -A POSTROUTING -o wgboost1 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss 1360
iptables -t mangle -A POSTROUTING -o wgboost2 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss 1360

# ====================================================================
# 5. Uji Validasi Koneksi
# ====================================================================
wg show
ping -c 2 -I wgboost1 10.250.1.1
ping -c 2 -I wgboost2 10.250.2.1
```

---

## 5. Pemeriksaan & Diagnostik Operasional

Untuk memastikan status koneksi berjalan sehat kapan saja:
1. **Periksa Handshake WireGuard**:
   - Di MitraNet: `wg show wgboost1` dan `wg show wgboost2` (pastikan *latest handshake* berjalan di bawah 2 menit).
   - Di MikroTik: `/interface wireguard peers print detail where interface~"wg-boost"` (pastikan `last-handshake` terus ter-update).
2. **Periksa Agregasi Bandwidth**:
   - Buka menu **Tools** ➔ **Benchmark & Speedtest** di WebUI MitraNet.
   - Jalankan uji kecepatan ke target IP VPS (`103.93.162.168`).
   - Pantau indikator RX dan TX pada tabel live stream untuk melihat lonjakan akumulasi throughput secara bersamaan.
