# Dokumentasi Teknis: MitraNet Cloud Speed Booster Multi-Stream WireGuard

Dokumen ini menjelaskan secara menyeluruh konfigurasi dan integrasi **MitraNet Cloud Speed Booster (Dual Stream WireGuard Aggregation)** antara **MikroTik CHR (Cloud VPS Gateway)** dan **MitraNet OS Rinjani (Client/Router)**.

---

## 1. Topologi & Parameter Konfigurasi

| Komponen | Parameter | Nilai / Keterangan |
| :--- | :--- | :--- |
| **VPS Gateway** | IP Publik | `103.93.162.168` |
| **Stream #1** | Subnet Point-to-Point | `10.250.1.0/30` (VPS: `10.250.1.1`, MitraNet: `10.250.1.2`) |
| | Port Listener UDP | `51831` |
| | Public Key VPS Stream 1 | `V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8=` |
| | Public Key MitraNet Stream 1 | `ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o=` |
| **Stream #2** | Subnet Point-to-Point | `10.250.2.0/30` (VPS: `10.250.2.1`, MitraNet: `10.250.2.2`) |
| | Port Listener UDP | `51832` |
| | Public Key VPS Stream 2 | `e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8=` |
| | Public Key MitraNet Stream 2 | `jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc=` |
| **Optimasi Jaringan** | Balancing Algorithm | ECMP (Equal-Cost Multi-Path) |
| | TCP MSS Clamping | `1360` (Mencegah fragmentasi MTU di jaringan ISP) |
| | TCP Congestion Control | `BBR + FQ` (BBR Bottleneck Bandwidth and RTT) |
| | DSCP QoS Marking | `AF41 (0x28)` |

---

## 2. Konfigurasi Sisi VPS MikroTik CHR

### A. Versi CLI (RouterOS v7 Terminal)
Jalankan perintah berikut di Terminal MikroTik CHR Anda:

```routeros
# ====================================================================
# 1. Buka Port UDP Booster di Firewall Filter Input
# ====================================================================
/ip firewall filter add chain=input action=accept protocol=udp dst-port=51831-51834 comment="MitraNetBooster Multi-Stream" place-before=[:pick [/ip firewall filter find where chain="input" and action="drop"] 0]

# ====================================================================
# 2. Antarmuka WireGuard & IP Address (Stream 1 & 2)
# ====================================================================
/interface wireguard add name=wg-boost1 listen-port=51831 comment="MitraNet Booster Stream 1"
/ip address add address=10.250.1.1/30 interface=wg-boost1 network=10.250.1.0

/interface wireguard add name=wg-boost2 listen-port=51832 comment="MitraNet Booster Stream 2"
/ip address add address=10.250.2.1/30 interface=wg-boost2 network=10.250.2.0

# ====================================================================
# 3. Mendaftarkan Peer Client MitraNet
# ====================================================================
/interface wireguard peers add interface=wg-boost1 public-key="ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o=" allowed-address=10.250.1.2/32 comment="Booster Peer 1"
/interface wireguard peers add interface=wg-boost2 public-key="jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc=" allowed-address=10.250.2.2/32 comment="Booster Peer 2"

# ====================================================================
# 4. Outbound NAT Masquerade
# ====================================================================
/ip firewall nat add chain=srcnat action=masquerade src-address=10.250.1.0/30 comment="NAT Boost1"
/ip firewall nat add chain=srcnat action=masquerade src-address=10.250.2.0/30 comment="NAT Boost2"
```

### B. Versi WinBox GUI (MikroTik CHR)
1. **Membuat Antarmuka WireGuard**:
   - Masuk ke menu **WireGuard** -> Tab **WireGuard** -> Klik **+ (Add)**.
   - Stream 1: Name = `wg-boost1`, Listen Port = `51831` -> Klik **OK**.
   - Stream 2: Name = `wg-boost2`, Listen Port = `51832` -> Klik **OK**.
2. **Menambahkan IP Address**:
   - Masuk ke **IP** -> **Addresses** -> Klik **+ (Add)**.
   - Stream 1: Address = `10.250.1.1/30`, Interface = `wg-boost1` -> Klik **OK**.
   - Stream 2: Address = `10.250.2.1/30`, Interface = `wg-boost2` -> Klik **OK**.
3. **Mendaftarkan Peer MitraNet**:
   - Masuk ke **WireGuard** -> Tab **Peers** -> Klik **+ (Add)**.
   - Peer 1: Interface = `wg-boost1`, Public Key = `ajiHxAhDgW0rSsadssTNtwq51u1X5dHnMmaTUGPfA1o=`, Allowed Address = `10.250.1.2/32` -> Klik **OK**.
   - Peer 2: Interface = `wg-boost2`, Public Key = `jtnP093wZuCW7jwYWJEfdqOkTq2NVl9IVdqNpiZLRQc=`, Allowed Address = `10.250.2.2/32` -> Klik **OK**.
4. **Firewall Filter & NAT Masquerade**:
   - Masuk ke **IP** -> **Firewall** -> Tab **Filter Rules** -> Tambahkan Rule `chain=input`, `protocol=udp`, `dst-port=51831-51834`, `action=accept`. Geser rule ke atas sebelum rule Drop WAN.
   - Tab **NAT** -> Tambahkan 2 Rule `chain=srcnat`, `src-address=10.250.1.0/30` dan `10.250.2.0/30`, `action=masquerade`.

---

## 3. Konfigurasi Sisi MitraNet OS Rinjani

### A. Versi WebUI (Dasbor Cloud Speed Booster)
1. Buka browser menuju: `http://<IP-MitraNet>:8443/vpn/vpn_booster.php`.
2. Isi formulir **Parameter Client Uplink Booster**:
   - **Alamat VPS / Cloud Gateway IP**: `103.93.162.168`
   - **Jumlah Parallel Streams**: `2 Streams (2x Multi-Link)`
   - **Tipe Tunnel**: `WireGuard Multi-Link`
   - **Balancing Mode**: `ECMP (Equal Cost Multi-Path)`
   - **DSCP Marking**: `AF41 (0x28 - Multimedia Stream)`
   - **TCP MSS Clamping**: `1360`
   - **Akselerasi Kernel**: Centang `Aktifkan TCP BBR / FQ`
   - **Public Key Server VPS**: Masukkan kunci publik VPS per baris:
     ```text
     V8T7f3Ec13e1imlbU/bfH4q/IEkcoM3YtZtIkSyO6y8=
     e+BxtRvJQvMLdUbTRCCEA5RpTNuyngIK3jQIj8gEPX8=
     ```
3. Klik tombol **Aktifkan Client**.
4. Sistem akan otomatis memunculkan SweetAlert2 dan mengaktifkan koneksi.
5. Tabel **Status Real-Time Client Multi-Stream** akan langsung berstatus **UP** dengan indikator latency live (`~14-16 ms`).

### B. Versi CLI (Linux Shell di MitraNet Mini PC)
Jika ingin mengaktifkan / memeriksa secara manual melalui terminal SSH MitraNet:

```bash
# 1. Konfigurasi Interface Stream 1 (/etc/wireguard/wgboost1.conf)
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

# 2. Konfigurasi Interface Stream 2 (/etc/wireguard/wgboost2.conf)
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

# 3. Jalankan Antarmuka
chmod 600 /etc/wireguard/wgboost*.conf
wg-quick up wgboost1
wg-quick up wgboost2

# 4. Rute Multipath ECMP & Mangle
ip route replace default nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1
sysctl -w net.ipv4.fib_multipath_hash_policy=1
iptables -t mangle -A POSTROUTING -o wgboost1 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss 1360
iptables -t mangle -A POSTROUTING -o wgboost2 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --set-mss 1360

# 5. Cek Status Handshake Live
wg show
ping -c 2 -I wgboost1 10.250.1.1
ping -c 2 -I wgboost2 10.250.2.1
```

---

## 4. Hasil Verifikasi Status Saat Ini

Pengecekan live dari Mini PC (`10.10.66.228`) dan Telemetry API menunjukkan:
- **Stream #1 (`wgboost1`)**: **UP** | Latency: **14.89 ms** | Handshake: Aktif
- **Stream #2 (`wgboost2`)**: **UP** | Latency: **15.03 ms** | Handshake: Aktif
- **Status Agregasi**: **AKTIF (MULTI-PATH)**
- **Ping Loss**: **0%** ke kedua gateway VPS.
