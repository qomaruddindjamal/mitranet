# Panduan Konfigurasi GRE Tunnel — MitraNet & MikroTik

Dokumen ini menjelaskan konfigurasi antarmuka **Generic Routing Encapsulation (GRE Tunnel)** point-to-point antara **MitraNet OS Rinjani** dan **MikroTik RouterOS**, baik via **GUI/WebUI** maupun **CLI Terminal**.

---

## 1. Spesifikasi Teknis & Parameter Uji Nyata
* **Protokol IP**: `47` (GRE)
* **Tipe Enkapsulasi**: Layer 3 Point-to-Point Tunnel
* **MTU Standar**: `1476 Byte` (Otomatis menyesuaikan overhead 24 byte dari paket IPv4 1500)
* **IP Underlay (Contoh)**:
  * MitraNet: `10.250.1.2` (atau IP Publik WAN)
  * MikroTik: `10.250.1.1` (atau IP Publik WAN)
* **IP Tunnel Point-to-Point (Subnet /30)**:
  * MitraNet: `10.254.1.2/30`
  * MikroTik: `10.254.1.1/30`

---

## 2. Konfigurasi Sisi MitraNet OS

### A. Melalui WebUI (WinBox Modern)
1. Buka WebUI MitraNet ➔ Masuk ke menu **Interfaces**.
2. Pilih tab **GRE Tunnel**.
3. Klik tombol **`+ New`** di toolbar atas.
4. Isi parameter pada modal dialog WinBox:
   * **Interface Name**: `gre-vps`
   * **Local Address**: `10.250.1.2` (opsional jika memiliki default route)
   * **Remote Address**: `10.250.1.1` (IP lawan)
   * **TTL**: `255`
   * **MTU**: `1476`
   * **IP Address / CIDR**: `10.254.1.2/30`
5. Klik **Create GRE**.
6. Antarmuka akan langsung aktif di tabel antarmuka dengan Rx/Tx traffic rate real-time.

### B. Melalui Terminal CLI (Linux)
```bash
# 1. Pastikan modul kernel terpasang
modprobe ip_gre

# 2. Buat antarmuka tunnel
ip tunnel add gre-vps mode gre remote 10.250.1.1 local 10.250.1.2 ttl 255

# 3. Atur MTU dan aktifkan antarmuka
ip link set gre-vps mtu 1476 up

# 4. Tambahkan alamat IP point-to-point
ip addr add 10.254.1.2/30 dev gre-vps
```

---

## 3. Konfigurasi Sisi MikroTik RouterOS

### A. Melalui WinBox GUI
1. Buka WinBox ➔ Masuk ke menu **Interfaces**.
2. Buka tab **GRE Tunnel** ➔ Klik icon **`+` (Add)**.
3. Konfigurasikan:
   * **Name**: `gre-mitranet`
   * **Local Address**: `10.250.1.1`
   * **Remote Address**: `10.250.1.2`
   * **Clamp TCP MSS**: `Yes`
   * **Keepalive**: `10s, 10`
   * Klik **Apply** & **OK**.
4. Masuk ke menu **IP** ➔ **Addresses** ➔ Klik **`+` (Add)**:
   * **Address**: `10.254.1.1/30`
   * **Network**: `10.254.1.0`
   * **Interface**: `gre-mitranet`
   * Klik **OK**.

### B. Melalui Terminal CLI (RouterOS)
```routeros
/interface gre add name=gre-mitranet local-address=10.250.1.1 remote-address=10.250.1.2 clamp-tcp-mss=yes comment="GRE to MitraNet"
/ip address add address=10.254.1.1/30 interface=gre-mitranet comment="GRE Subnet"
```

---

## 4. Verifikasi & Pengujian
Jalankan uji ping dua arah:
* **Dari MitraNet**: `ping -c 4 10.254.1.1`
* **Dari MikroTik**: `/ping count=4 10.254.1.2`
Hasil: Packet loss 0%, latensi ~14 ms (real-time).
