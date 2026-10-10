# Panduan Konfigurasi VXLAN Overlay Layer 2 — MitraNet & MikroTik

Dokumen ini menjelaskan konfigurasi antarmuka **Virtual Extensible LAN (VXLAN)** point-to-point / overlay layer 2 antara **MitraNet OS Rinjani** dan **MikroTik RouterOS**, baik via **GUI/WebUI** maupun **CLI Terminal**.

---

## 1. Spesifikasi Teknis & Parameter Uji Nyata
* **Protokol Transport**: UDP Port `4789` (Standar resmi IANA)
* **Tipe Enkapsulasi**: Layer 2 Ethernet over UDP (Overlay L2 Extension)
* **VNI (VXLAN Network Identifier)**: `100` (harus sama persis di kedua sisi)
* **MTU Standar**: `1500 Byte`
* **Underlay Transport**:
  * MitraNet: `10.250.1.2` (interface parent `wgboost1` atau antarmuka fisik WAN)
  * MikroTik: `10.250.1.1`
* **IP Interface VXLAN (Subnet L2)**:
  * MitraNet: `10.251.1.2/30`
  * MikroTik: `10.251.1.1/30`
* **Fungsi Khusus**: Dapat dimasukkan langsung ke port **Bridge LAN (`br0`)** di MitraNet dan MikroTik untuk menyatukan segmen LAN/VLAN antar kantor/site seolah berada dalam 1 switch fisik.

---

## 2. Konfigurasi Sisi MitraNet OS

### A. Melalui WebUI (WinBox Modern)
1. Buka WebUI MitraNet ➔ Masuk ke menu **Interfaces**.
2. Pilih tab **VXLAN**.
3. Klik tombol **`+ New`** di toolbar atas.
4. Isi parameter pada form modal:
   * **Interface Name**: `vxlan100`
   * **VNI**: `100`
   * **UDP Port**: `4789`
   * **Remote VTEP IP**: `10.250.1.1` (IP underlay MikroTik)
   * **Underlay Parent Interface**: Pilih `wgboost1` atau `enp1s0`
   * **Bridge Member**: Pilih `None` (jika standalone L2) atau `br0` (jika ingin bridge ke LAN)
   * **IP Address / CIDR**: `10.251.1.2/30`
5. Klik **Create VXLAN**.

### B. Melalui Terminal CLI (Linux)
```bash
# 1. Pastikan modul kernel terpasang
modprobe vxlan

# 2. Buat antarmuka VXLAN point-to-point VTEP
ip link add vxlan100 type vxlan id 100 remote 10.250.1.1 dstport 4789 dev wgboost1

# 3. Aktifkan antarmuka
ip link set vxlan100 up

# 4. Tambahkan IP address L2 (jika tidak di-bridge)
ip addr add 10.251.1.2/30 dev vxlan100

# Opsional: Jika ingin dimasukkan ke Bridge LAN lokal
# ip link set vxlan100 master br0
```

---

## 3. Konfigurasi Sisi MikroTik RouterOS

### A. Melalui WinBox GUI
1. Buka WinBox ➔ Masuk ke menu **Interfaces**.
2. Buka tab **VXLAN** ➔ Klik icon **`+` (Add)**:
   * **Name**: `vxlan-mitranet`
   * **VNI**: `100`
   * **Port**: `4789`
   * Klik **Apply** & **OK**.
3. Pindah ke sub-tab **VTEPs** di bawah menu VXLAN ➔ Klik **`+` (Add)**:
   * **Interface**: `vxlan-mitranet`
   * **Remote IP**: `10.250.1.2` (IP underlay MitraNet)
   * **Port**: `4789`
   * Klik **OK**.
4. Masuk ke menu **IP** ➔ **Addresses** ➔ Tambahkan IP:
   * **Address**: `10.251.1.1/30`
   * **Interface**: `vxlan-mitranet`
   * Klik **OK**.
5. Pastikan Firewall MikroTik mengizinkan port UDP 4789:
   ```routeros
   /ip firewall filter add chain=input protocol=udp dst-port=4789 action=accept comment="Allow VXLAN"
   ```

### B. Melalui Terminal CLI (RouterOS)
```routeros
/interface vxlan add name=vxlan-mitranet vni=100 port=4789 comment="VXLAN to MitraNet"
/interface vxlan vteps add interface=vxlan-mitranet remote-ip=10.250.1.2 port=4789
/ip address add address=10.251.1.1/30 interface=vxlan-mitranet comment="VXLAN Subnet"
/ip firewall filter add chain=input protocol=udp dst-port=4789 action=accept comment="Allow VXLAN"
```

---

## 4. Verifikasi & Pengujian
Jalankan uji ping dua arah:
* **Dari MitraNet**: `ping -c 4 10.251.1.1` (Hasil: 0% loss, rtt ~15 ms)
* **Dari MikroTik**: `/ping count=4 10.251.1.2` (Hasil: 0% loss, rtt ~14 ms)
* **Karakteristik**: Sebagai antarmuka Layer 2, VXLAN juga mendukung broadcast ARP, DHCP relay/snooping, dan bridging multi-port tanpa batasan proprietary.
