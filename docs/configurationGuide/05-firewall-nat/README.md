# Panduan Konfigurasi: 05 - Firewall & NAT (Filtering, Port Forward & Mangle)

Modul ini menjelaskan cara mengatur firewall filter, outbound NAT masquerade, port forwarding (DST-NAT), serta TCP MSS clamping pada MitraNet Rinjani 1.0.2.

---

## 1. Outbound NAT Masquerade

Memungkinkan seluruh klien LAN mengakses internet publik melalui interface WAN/VPN.

### A. Metode WebUI
1. Buka menu **Firewall** ➔ **NAT** ➔ **Outbound** (`/firewall/firewall_nat_out.php`).
2. Pilih mode **Automatic** atau **Hybrid Outbound NAT**.
3. Jika mode Hybrid, tambahkan mapping rule:
   - **Interface**: Pilih `WAN (enp1s0)` atau tunnel VPN (`wg0` / `wgboost1`).
   - **Source**: Subnet LAN (contoh: `10.10.1.0/24`).
   - **Translation Target**: `Interface Address (MASQUERADE)`.
4. Klik **Save** ➔ **Apply Changes**.

### B. Metode CLI
```bash
# Aktifkan IP forwarding di kernel
sysctl -w net.ipv4.ip_forward=1

# Aturan Masquerade untuk LAN keluar lewat WAN fisik
iptables -t nat -A POSTROUTING -s 10.10.1.0/24 -o enp1s0 -j MASQUERADE

# Aturan Masquerade jika keluar lewat WireGuard
iptables -t nat -A POSTROUTING -o wg0 -j MASQUERADE
iptables -t nat -A POSTROUTING -o wgboost1 -j MASQUERADE

# Periksa tabel NAT POSTROUTING
iptables -t nat -L POSTROUTING -n -v
```

---

## 2. Port Forwarding (Destination NAT / DST-NAT)

Meneruskan akses port publik (misal web server atau CCTV) ke server internal LAN.

### A. Metode WebUI
1. Buka menu **Firewall** ➔ **NAT** ➔ **Port Forward** (`/firewall/firewall_nat.php`).
2. Klik **Add Rule**:
   - **Interface**: `WAN`.
   - **Protocol**: `TCP`.
   - **Destination Port**: `8080`.
   - **Redirect Target IP**: `10.10.1.10`.
   - **Redirect Target Port**: `80`.
3. Klik **Save & Apply**.

### B. Metode CLI
```bash
# Meneruskan port 8080 di WAN ke IP internal 10.10.1.10 port 80
iptables -t nat -A PREROUTING -i enp1s0 -p tcp --dport 8080 -j DNAT --to-destination 10.10.1.10:80
iptables -A FORWARD -p tcp -d 10.10.1.10 --dport 80 -m state --state NEW,ESTABLISHED,RELATED -j ACCEPT
```

---

## 3. TCP MSS Clamping & QoS DSCP Marking

Mencegah fragmentasi paket pada link terenkripsi WireGuard dan menandai prioritas paket antrean.

### A. Metode WebUI
1. Buka menu **Firewall** ➔ **Rules** atau menu **Cloud Speed Booster**.
2. Pilih pengaturan MSS: `Clamp to PMTU` atau nilai spesifik `1360`.
3. Pilih DSCP class: `Expedited Forwarding (EF - 0x2e)` atau `AF41 (0x28)`.
4. Klik **Apply**.

### B. Metode CLI
```bash
# Clamp MSS otomatis ke PMTU pada interface VPN
iptables -t mangle -A FORWARD -o wg0 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --clamp-mss-to-pmtu
iptables -t mangle -A FORWARD -i wg0 -p tcp --tcp-flags SYN,RST SYN -j TCPMSS --clamp-mss-to-pmtu

# Set DSCP Expedited Forwarding (0x2e) pada paket WireGuard
iptables -t mangle -A POSTROUTING -p udp --dport 13231 -j DSCP --set-dscp 0x2e
```
