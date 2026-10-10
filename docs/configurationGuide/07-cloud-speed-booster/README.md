# Panduan Konfigurasi: 07 - Cloud Speed Booster (Multi-Stream ECMP)

Modul ini menjelaskan cara mengonfigurasi fitur unggulan **Cloud Speed Booster** pada MitraNet Rinjani 1.0.2 untuk memecah lalu lintas ke beberapa stream WireGuard paralel secara dinamis.

---

## 1. Metode A: Melalui WebUI (Browser)

1. Buka WebUI di: `http://10.10.66.228:8000` (atau IP router Anda).
2. Di menu sidebar kiri, pilih **VPN** ➔ **Cloud Speed Booster** (`/vpn/vpn_booster.php`).
3. Pada panel form:
   - **Alamat Cloud VPS Gateway**: Masukkan IP VPS Anda (contoh: `103.93.162.168`).
   - **Jumlah Stream Paralel**: Pilih `2 Streams` (bisa 3 atau 4 stream).
   - **Tipe Tunnel Multi-Link**: Pilih `WireGuard Multi-Link (L3/L4)`.
   - **Metode Pembagian Beban**: Pilih `ECMP Multipath (Equal-Cost Multi-Path)`.
   - **DSCP Traffic Class**: Pilih `Expedited Forwarding (EF - 0x2e)` atau `AF41 (0x28)`.
   - **TCP MSS Clamping**: Pilih `1360 Bytes`.
4. Klik tombol **Terapkan & Jalankan Booster**.
5. Buka tab **RouterOS Script** di bawahnya:
   - Salin skrip RouterOS yang telah terisi Public Key unik masing-masing stream.
   - Buka WinBox / Terminal MikroTik VPS, lalu paste perintah tersebut.
6. Periksa indikator **Status HUD**:
   - Stream 1 & 2 berstatus `UP`.
   - RTT latency dan handshake terdeteksi.

---

## 2. Metode B: Melalui Terminal / CLI (Linux Shell)

Berikut langkah lengkap manual jika ingin menyetelnya langsung dari terminal Linux:

### 1. Menyiapkan Kunci & Konfigurasi Interface Stream 1 & Stream 2
```bash
# Generate Private & Public Key Stream 1 & 2
wg genkey | tee /tmp/k1_priv | wg pubkey > /tmp/k1_pub
wg genkey | tee /tmp/k2_priv | wg pubkey > /tmp/k2_pub

# Buat konfigurasi wgboost1.conf
cat << EOF > /etc/wireguard/wgboost1.conf
[Interface]
Address = 10.250.1.2/30
ListenPort = 51831
PrivateKey = $(cat /tmp/k1_priv)
FwMark = 0xca6c
MTU = 1420

[Peer]
PublicKey = <PUBLIC_KEY_VPS_STREAM_1>
Endpoint = 103.93.162.168:51831
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 10
EOF

# Buat konfigurasi wgboost2.conf
cat << EOF > /etc/wireguard/wgboost2.conf
[Interface]
Address = 10.250.2.2/30
ListenPort = 51832
PrivateKey = $(cat /tmp/k2_priv)
FwMark = 0xca6c
MTU = 1420

[Peer]
PublicKey = <PUBLIC_KEY_VPS_STREAM_2>
Endpoint = 103.93.162.168:51832
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 10
EOF

chmod 600 /etc/wireguard/wgboost*.conf
```

### 2. Mengaktifkan Tunnel & Policy Routing
```bash
# Aktifkan interface WireGuard
wg-quick up wgboost1
wg-quick up wgboost2

# Aktifkan L4 Hashing multipath pada kernel
sysctl -w net.ipv4.fib_multipath_hash_policy=1

# Terapkan ECMP default route pada tabel main dan tabel policy 51820
ip route replace default nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1
ip route replace default table 51820 nexthop dev wgboost1 weight 1 nexthop dev wgboost2 weight 1

# Tambahkan aturan Masquerade dan Forwarding
iptables -t nat -A POSTROUTING -o wgboost1 -j MASQUERADE
iptables -t nat -A POSTROUTING -o wgboost2 -j MASQUERADE
iptables -A FORWARD -o wgboost1 -j ACCEPT
iptables -A FORWARD -o wgboost2 -j ACCEPT
```

### 3. Mengonfigurasi VPS RouterOS (MikroTik CLI)
```routeros
# Firewall Port
/ip firewall filter add chain=input action=accept protocol=udp dst-port=51831-51832 place-before=0

# Interface WireGuard & IP
/interface wireguard add name=wg-boost1 listen-port=51831
/ip address add address=10.250.1.1/30 interface=wg-boost1 network=10.250.1.0

/interface wireguard add name=wg-boost2 listen-port=51832
/ip address add address=10.250.2.1/30 interface=wg-boost2 network=10.250.2.0

# Daftarkan Peer Klien (Public Key dari /tmp/k1_pub dan /tmp/k2_pub)
/interface wireguard peers add interface=wg-boost1 public-key="<K1_PUB>" allowed-address=10.250.1.2/32
/interface wireguard peers add interface=wg-boost2 public-key="<K2_PUB>" allowed-address=10.250.2.2/32

# NAT Masquerade
/ip firewall nat add chain=srcnat src-address=10.250.1.0/30 action=masquerade
/ip firewall nat add chain=srcnat src-address=10.250.2.0/30 action=masquerade
```

### 4. Menghentikan Booster / Rollback
```bash
# Turunkan interface
wg-quick down wgboost1
wg-quick down wgboost2

# Kembalikan routing default ke gateway fisik awal
ip route replace default via 10.10.66.254 dev enp1s0
ip route replace default dev wg0 table 51820
```
