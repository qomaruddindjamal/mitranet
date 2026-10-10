# Panduan Konfigurasi: 03 - Routing & Load Balancing (ECMP / PCC)

Modul ini menjelaskan cara mengatur rute statis, policy routing berbasis tabel, serta load balancing multi-gateway pada MitraNet Rinjani 1.0.2.

---

## 1. Rute Statis & Policy Routing (PBR)

### A. Metode WebUI
1. Buka menu **System** ➔ **Routing** ➔ **Static Routes** (`/system/system_routes.php`).
2. Klik tombol **Add Route**:
   - **Destination Network**: Masukkan subnet target (contoh: `192.168.10.0/24` atau `0.0.0.0/0`).
   - **Gateway**: Pilih interface gateway atau masukkan IP hop berikutnya.
   - **Table ID**: Pilih `main` atau tabel custom (misal: `100` untuk klien lokal).
3. Klik **Save** ➔ **Apply Changes**.

### B. Metode CLI
```bash
# Tambah rute statis ke subnet spesifik lewat gateway tertentu
ip route add 192.168.10.0/24 via 10.10.66.1 dev enp1s0

# Menyiapkan Policy Based Routing (PBR) untuk trafik klien LAN (veth0)
# Buat aturan ip rule
ip rule add iif veth0 lookup 100 pref 32763

# Arahkan tabel 100 ke gateway VPN / WAN
ip route add default dev wg0 table 100

# Periksa tabel rute dan rules
ip rule show
ip route show table 100
```

---

## 2. Multi-WAN Load Balancing (ECMP Multipath)

Membagi koneksi keluar ke beberapa ISP atau beberapa gateway sekaligus.

### A. Metode WebUI
1. Buka menu **System** ➔ **Routing** ➔ **Gateway Groups** (`/system/system_gateways.php`).
2. Masukkan nama group (misal: `LOADBALANCE_WAN`).
3. Tetapkan tier yang sama untuk gateway yang seimbang (Tier 1 untuk WAN1 dan WAN2 = Load Balance; Tier 1 dan Tier 2 = Failover).
4. Klik **Save**.
5. Di menu **Firewall** ➔ **Rules** LAN, pilih Gateway Group tersebut.

### B. Metode CLI
```bash
# 1. Aktifkan L4 Hashing (IP + Port) di kernel Linux
sysctl -w net.ipv4.fib_multipath_hash_policy=1

# 2. Buat default route multipath berbobot seimbang
ip route replace default \
  nexthop via 10.10.66.254 dev enp1s0 weight 1 \
  nexthop via 10.10.66.254 dev mac0 weight 1

# 3. Verifikasi rute aktif
ip route show default
```
