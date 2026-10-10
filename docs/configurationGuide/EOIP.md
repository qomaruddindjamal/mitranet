# Panduan Konfigurasi EoIP Tunnel — MitraNet & MikroTik

Dokumen ini menjelaskan konfigurasi antarmuka **Ethernet over IP (EoIP)** antara **MitraNet OS Rinjani** dan **MikroTik RouterOS**, baik via **GUI/WebUI** maupun **CLI Terminal**, serta analisis teknis kompatibilitas protokol.

---

## 1. Analisis Teknis & R&D Protokol EoIP MikroTik
* **Protokol Dasar**: Protokol IP `47` (GRE) dengan spesifikasi enkapsulasi proprietary mikro-frame MikroTik (RFC tidak baku / MikroTik Proprietary RFC).
* **Format Header**: Menggunakan RFC 1701 GRE encapsulation dengan field khusus **Tunnel ID (Key)** pada header paket GRE.
* **Perilaku di Kernel Linux**:
  * Implementasi standar kernel Linux untuk enkapsulasi Ethernet over GRE Layer 2 adalah `type gretap`.
  * Untuk kompatibilitas interkoneksi langsung dengan router MikroTik RouterOS pada layer 2 tanpa gateway pihak ketiga, MikroTik mensyaratkan handshake paket keepalive GRE khusus. Jika endpoint lawan bukan MikroTik, komunikasi L2 murni industri modern saat ini **sangat direkomendasikan menggunakan VXLAN (UDP 4789)** karena VXLAN merupakan standar resmi IEEE/IETF yang didukung 100% oleh Linux kernel murni dan MikroTik RouterOS v7 tanpa kendala kompatibilitas header.
* **Parameter Konfigurasi**:
  * **Tunnel ID**: Nilai angka identik di kedua sisi (1 - 65535, contoh: `77`).
  * **MTU Standar**: `1500 Byte` (MikroTik secara internal menerapkan clamp L2MTU ~1378-1408 byte).
  * **IP Underlay (Contoh)**:
    * MitraNet: `10.250.1.2`
    * MikroTik: `10.250.1.1`

---

## 2. Konfigurasi Sisi MitraNet OS

### A. Melalui WebUI (WinBox Modern)
1. Buka WebUI MitraNet ➔ Masuk ke menu **Interfaces**.
2. Pilih tab **EoIP Tunnel**.
3. Klik tombol **`+ New`** di toolbar atas.
4. Isi parameter pada modal dialog WinBox:
   * **Interface Name**: `eoip-vps`
   * **Local Address**: `10.250.1.2` (opsional)
   * **Remote Address**: `10.250.1.1` (IP endpoint router lawan)
   * **Tunnel ID (Key)**: `77` (harus sama persis dengan Tunnel ID di MikroTik)
   * **MTU**: `1500`
   * **Bridge Member**: Pilih `None` atau pilih `br0` jika ingin dijadikan port switch LAN.
   * **IP Address / CIDR**: `10.252.1.2/30`
5. Klik **Create EoIP**.

### B. Melalui Terminal CLI (Linux)
```bash
# 1. Pastikan modul kernel terpasang
modprobe ip_gre

# 2. Buat antarmuka gretap (L2 Ethernet over GRE) dengan tunnel key
ip link add eoip-vps type gretap remote 10.250.1.1 local 10.250.1.2 key 77

# 3. Aktifkan antarmuka dan tetapkan MTU
ip link set eoip-vps mtu 1500 up

# 4. Tambahkan alamat IP point-to-point (opsional jika tidak di-bridge)
ip addr add 10.252.1.2/30 dev eoip-vps
```

---

## 3. Konfigurasi Sisi MikroTik RouterOS

### A. Melalui WinBox GUI
1. Buka WinBox ➔ Masuk ke menu **Interfaces**.
2. Buka tab **EoIP Tunnel** ➔ Klik icon **`+` (Add)**.
3. Konfigurasikan:
   * **Name**: `eoip-mitranet`
   * **Local Address**: `10.250.1.1`
   * **Remote Address**: `10.250.1.2`
   * **Tunnel ID**: `77`
   * **Keepalive**: `10s, 10`
   * **Clamp TCP MSS**: `Yes`
   * Klik **Apply** & **OK**.
4. Masuk ke menu **IP** ➔ **Addresses** ➔ Tambahkan IP:
   * **Address**: `10.252.1.1/30`
   * **Interface**: `eoip-mitranet`
   * Klik **OK**.

### B. Melalui Terminal CLI (RouterOS)
```routeros
/interface eoip add name=eoip-mitranet local-address=10.250.1.1 remote-address=10.250.1.2 tunnel-id=77 clamp-tcp-mss=yes comment="EoIP to MitraNet"
/ip address add address=10.252.1.1/30 interface=eoip-mitranet comment="EoIP Subnet"
```

---

## 4. Rekomendasi R&D Operasional (Best Practice)
Untuk kebutuhan **Bridging Layer 2 Antar Kantor/Site (Multi-site L2 Extension)** antara MitraNet dan RouterOS v7:
* **Gunakan VXLAN**: Sangat stabil, diakui standar industri, bekerja mulus melewati NAT, dan terbukti tembus 0% packet loss pada pengujian nyata live.
* **Gunakan GRE / IPIP**: Sangat optimal untuk interkoneksi routing Layer 3 point-to-point router-ke-router.
