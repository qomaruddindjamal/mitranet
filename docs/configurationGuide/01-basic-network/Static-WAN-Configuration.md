# Panduan Lengkap: Konfigurasi WAN IP Statis & Masquerade LAN — MitraNet Rinjani 1.0.2

Panduan ini ditujukan bagi teknisi atau pengguna yang mendapatkan koneksi internet dari modem ISP / router upstream dengan **IP Statis (Manual)**, bukan DHCP otomatis. Panduan ini mencakup langkah agar MitraNet mendapatkan internet dari WAN, lalu membagikannya ke seluruh klien di jaringan LAN.

---

## 1. Topologi & Contoh Alokasi IP

```
[Modem ISP / Router Upstream] 
     │ IP Gateway: 192.168.1.1/24 (atau 10.10.66.254/24)
     ▼
[Port WAN: enp1s0] (MitraNet OS) ──> Diberi IP Statis: 192.168.1.2/24
                                 ──> Default Gateway: 192.168.1.1
                                 ──> DNS: 1.1.1.1, 8.8.8.8
[Port LAN: veth0 / enp2s0]       ──> Diberi IP Lokal: 10.10.1.254/24
     │                               DHCP Server: 10.10.1.100 - 10.10.1.200
     ▼                               NAT Masquerade: Aktif
[Switch / Klien Laptop / HP]     ──> Menerima IP otomatis: 10.10.1.x
```

---

## 2. METODE A: Konfigurasi Melalui WebUI (Browser)

Akses WebUI di `http://<IP-MitraNet>:8000` menggunakan username `admin` atau `root`.

### Langkah 1: Setel IP Statis pada Interface WAN
1. Buka menu **Interfaces** ➔ **Interface Assignments** (`/interfaces/interfaces_assign.php`).
2. Klik interface **WAN** (misalnya `enp1s0`).
3. Pada opsi **General Configuration**:
   - Centang **Enable Interface**.
   - **IPv4 Configuration Type**: Pilih **Static IPv4**.
4. Pada bagian **Static IPv4 Configuration**:
   - **IPv4 Address**: Masukkan `192.168.1.2` dan pilih prefix subnet `/24` (255.255.255.0).
   - **IPv4 Upstream Gateway**: Klik **Add a new gateway**, beri nama `WAN_GW`, lalu masukkan IP modem upstream `192.168.1.1`.
5. Klik **Save** di bagian bawah, lalu klik **Apply Changes**.

### Langkah 2: Setel DNS Server & Upstream Resolver (Terpusat)
1. Buka menu **Services** ➔ **DNS Server** (`/services/dns_server.php`).
2. Centang **Enable** (*Aktifkan DNS Server dnsmasq pada sistem ini*).
3. Pada kolom **Upstream DNS Servers**, masukkan:
   ```text
   1.1.1.1
   8.8.8.8
   ```
4. Klik tombol **Save** di bagian bawah.
5. **Verifikasi Uji Internet di WebUI**:
   - Buka menu **Diagnostics** ➔ **Ping**.
   - Ping ke `8.8.8.8` dan `google.com`. Jika status sukses (0% packet loss), MitraNet sudah terhubung internet dengan benar.

### Langkah 3: Beri IP pada Interface LAN & Aktifkan DHCP Server
1. Buka menu **Interfaces** ➔ klik interface **LAN** (`veth0` atau `enp2s0`).
2. Set **IPv4 Configuration Type** ke **Static IPv4**:
   - **IPv4 Address**: `10.10.1.254` dengan subnet `/24`.
   - **IPv4 Upstream Gateway**: Biarkan kosong (*None*).
   - Klik **Save** ➔ **Apply Changes**.
3. Buka menu **Services** ➔ **DHCP Server** (`/services/services_dhcp.php`):
   - Centang **Enable DHCP server on LAN interface**.
   - **Range From**: `10.10.1.100` — **To**: `10.10.1.200`.
   - **DNS Servers**: Masukkan `10.10.1.254` (atau `1.1.1.1`).
   - Klik **Save & Apply**.

### Langkah 4: Aktifkan Outbound NAT Masquerade (Agar Klien LAN Bisa Internetan)
1. Buka menu **Firewall** ➔ **NAT** ➔ tab **Outbound** (`/firewall/firewall_nat_out.php`).
2. Pilih mode **Automatic Outbound NAT rule generation** (atau **Hybrid**).
3. Jika mode Hybrid, buat rule baru:
   - **Interface**: `WAN (enp1s0)`.
   - **Source**: Subnet LAN `10.10.1.0/24`.
   - **Translation**: `Interface Address (MASQUERADE)`.
4. Klik **Save & Apply Changes**.
5. Klien laptop/HP yang dicolokkan ke port LAN sekarang sudah bisa mendapatkan internet.

---

## 3. METODE B: Konfigurasi Melalui Terminal / CLI (Linux Shell)

Bagi teknisi yang langsung berada di depan monitor PC Router atau melalui terminal SSH (`ssh root@<IP>`):

### Langkah 1: Hapus Pengaturan Lama & Pasang IP Statis WAN
```bash
# 1. Bersihkan IP lama di interface WAN (enp1s0)
ip addr flush dev enp1s0

# 2. Pasang IP Statis WAN
ip addr add 192.168.1.2/24 dev enp1s0
ip link set enp1s0 up

# 3. Pasang Default Gateway ke Modem ISP
ip route replace default via 192.168.1.1 dev enp1s0 proto static metric 100
```

### Langkah 2: Konfigurasi DNS Resolver
```bash
cat << 'EOF' > /etc/resolv.conf
nameserver 1.1.1.1
nameserver 8.8.8.8
EOF

# Uji koneksi internet dari router:
ping -c 3 8.8.8.8
ping -c 3 google.com
```

### Langkah 3: Konfigurasi Interface LAN & DHCP Server
```bash
# 1. Berikan IP Statis pada Interface LAN (veth0 atau interface fisik LAN)
ip addr flush dev veth0
ip addr add 10.10.1.254/24 dev veth0
ip link set veth0 up

# 2. Konfigurasi DHCP Server LAN (dnsmasq)
cat << 'EOF' > /etc/dnsmasq.d/lan-dhcp.conf
interface=veth0
dhcp-range=10.10.1.100,10.10.1.200,255.255.255.0,12h
dhcp-option=3,10.10.1.254         # Gateway untuk klien
dhcp-option=6,10.10.1.254,1.1.1.1  # DNS untuk klien
EOF

systemctl restart dnsmasq
```

### Langkah 4: Aktifkan IP Forwarding & NAT Masquerade
```bash
# 1. Aktifkan routing paket di kernel
sysctl -w net.ipv4.ip_forward=1

# 2. Tambahkan aturan Masquerade agar trafik LAN bisa keluar via WAN
iptables -t nat -A POSTROUTING -s 10.10.1.0/24 -o enp1s0 -j MASQUERADE

# 3. Izinkan paket forward antar interface
iptables -A FORWARD -i veth0 -o enp1s0 -j ACCEPT
iptables -A FORWARD -i enp1s0 -o veth0 -m state --state RELATED,ESTABLISHED -j ACCEPT
```

### Langkah 5: Simpan Permanen (Persisten Setelah Reboot)
Agar konfigurasi tidak hilang saat router mati/reboot:
```bash
# Simpan sysctl
echo "net.ipv4.ip_forward = 1" > /etc/sysctl.d/99-ipforward.conf

# Simpan iptables
iptables-save > /etc/iptables/rules.v4 2>/dev/null || iptables-save > /etc/mitranet/firewall/rules.v4
```

---

## 4. Checklist Pengujian (Troubleshooting)

| No | Pengujian | Perintah / Indikator | Hasil Normal |
| :---: | :--- | :--- | :--- |
| **1** | Cek IP WAN & LAN | `ip -br addr show` | `enp1s0: 192.168.1.2/24`, `veth0: 10.10.1.254/24` |
| **2** | Cek Rute Gateway | `ip route show default` | `default via 192.168.1.1 dev enp1s0` |
| **3** | Uji Ping Gateway Modem | `ping -c 3 192.168.1.1` | `0% packet loss` |
| **4** | Uji Ping Internet Publik | `ping -c 3 1.1.1.1` | `0% packet loss` (RTO = periksa kabel WAN) |
| **5** | Uji Resolusi Nama DNS | `ping -c 3 google.com` | `0% packet loss` (RTO = periksa `/etc/resolv.conf`) |
| **6** | Cek Klien LAN | Klien colok kabel LAN | Klien dapat IP `10.10.1.x` & bisa browsing Google |
