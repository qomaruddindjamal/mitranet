# Panduan Konfigurasi: 06 - VPN Tunnels (WireGuard, GRE, EOIP & Xray)

Modul ini menjelaskan cara mengatur tunnel VPN Layer 3 (WireGuard), tunnel Layer 2 (GRE/EOIP), serta proksi stealth Xray-core pada MitraNet Rinjani 1.0.2.

---

## 1. WireGuard VPN Tunnel

### A. Metode WebUI
1. Buka menu **VPN** ➔ **WireGuard** ➔ **Tunnels** (`/wg/vpn_wg_tunnels.php`).
2. Klik tombol **Add Tunnel**:
   - **Interface**: `wg0`.
   - **Listen Port**: `51820`.
   - Klik tombol **Generate Keys** untuk membuat Private/Public key.
   - **Interface IP**: Masukkan IP tunnel (contoh: `10.10.77.3/24`).
3. Buka tab **Peers** ➔ Klik **Add Peer**:
   - **Endpoint**: Masukkan IP dan port VPS (contoh: `103.93.162.168:13231`).
   - **Public Key**: Masukkan public key dari server VPS.
   - **Allowed IPs**: Masukkan `0.0.0.0/0` (untuk internet penuh) atau subnet internal (contoh: `10.10.77.0/24`).
   - **Persistent Keepalive**: Masukkan `25` detik.
4. Klik **Save & Apply Changes**.

### B. Metode CLI
```bash
# 1. Generate keypair jika belum ada
wg genkey | tee /etc/wireguard/privatekey | wg pubkey > /etc/wireguard/publickey
chmod 600 /etc/wireguard/privatekey

# 2. Buat konfigurasi /etc/wireguard/wg0.conf
cat << 'EOF' > /etc/wireguard/wg0.conf
[Interface]
Address = 10.10.77.3/24
ListenPort = 51820
PrivateKey = <MASUKKAN_PRIVATE_KEY_LOKAL>
FwMark = 0xca6c

[Peer]
PublicKey = <MASUKKAN_PUBLIC_KEY_VPS>
Endpoint = 103.93.162.168:13231
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 25
EOF

# 3. Aktifkan tunnel
wg-quick up wg0
systemctl enable wg-quick@wg0

# 4. Verifikasi status handshake & counter
wg show wg0
```

---

## 2. GRE Tunnel & EOIP Layer 2 Tunnel

Untuk melewatkan traffic VLAN Layer 2 atau bridging langsung ke router MikroTik remote.

### A. Metode WebUI
1. Buka menu **Interfaces** ➔ **GRE / EOIP** (`/interfaces/interfaces_gre.php`).
2. Klik **New Tunnel**:
   - **Local IP**: IP WAN MitraNet (atau IP tunnel WireGuard).
   - **Remote IP**: IP WAN router MikroTik remote.
   - **Tunnel ID (EOIP)**: ID unik yang sama di kedua ujung (contoh: `10`).
3. Klik **Save & Apply**.

### B. Metode CLI
```bash
# Membuat GRE Tunnel
ip tunnel add gre1 mode gre remote 103.93.162.168 local 10.10.66.208 ttl 255
ip link set gre1 up
ip addr add 172.16.1.2/30 dev gre1

# Menghubungkan EOIP Tunnel (membutuhkan modul kernel eoip jika terpasang)
ip link add eoip1 type eoip remote 103.93.162.168 local 10.10.66.208 id 10
ip link set eoip1 up
```

---

## 3. Xray-core / V2Ray Stealth Proxy

Untuk penembus isolasi deep packet inspection (DPI) menggunakan protokol VLESS/XTLS/Vmess.

### A. Metode WebUI
1. Buka menu **Services** ➔ **Xray Core** (`/services/services_xray.php`).
2. Masukkan konfigurasi JSON outbound server VPS.
3. Klik **Enable Transparent Proxy (TPROXY)**.
4. Klik **Start Service**.

### B. Metode CLI
```bash
# Cek versi & konfigurasi
xray version
xray -test -config /etc/xray/config.json

# Restart service
systemctl restart xray
systemctl status xray --no-pager
```
