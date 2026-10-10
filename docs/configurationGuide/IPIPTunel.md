# Panduan Konfigurasi IPIP Tunnel — MitraNet & MikroTik

Dokumen ini menjelaskan konfigurasi antarmuka **IP-over-IP (IPIP Tunnel)** point-to-point antara **MitraNet OS Rinjani** dan **MikroTik RouterOS**, baik via **GUI/WebUI** maupun **CLI Terminal**.

---

## 1. Spesifikasi Teknis & Parameter Uji Nyata
* **Protokol IP**: `4` (IPv4 encapsulation / `ipencap`)
* **Tipe Enkapsulasi**: Layer 3 Point-to-Point Tunnel ringan (overhead terendah, hanya 20 byte)
* **MTU Standar**: `1480 Byte` (1500 - 20 byte header IP)
* **IP Underlay (Contoh)**:
  * MitraNet: `10.250.1.2`
  * MikroTik: `10.250.1.1`
* **IP Tunnel Point-to-Point (Subnet /30)**:
  * MitraNet: `10.253.1.2/30`
  * MikroTik: `10.253.1.1/30`

---

## 2. Konfigurasi Sisi MitraNet OS

### A. Melalui WebUI (WinBox Modern)
1. Buka WebUI MitraNet ➔ Masuk ke menu **Interfaces**.
2. Pilih tab **IP Tunnel** (IPIP).
3. Klik tombol **`+ New`** di toolbar atas.
4. Isi parameter pada modal dialog:
   * **Interface Name**: `ipip-vps`
   * **Local Address**: `10.250.1.2`
   * **Remote Address**: `10.250.1.1`
   * **TTL**: `64`
   * **MTU**: `1480`
   * **IP Address / CIDR**: `10.253.1.2/30`
5. Klik **Create IPIP**.

### B. Melalui Terminal CLI (Linux)
```bash
# 1. Pastikan modul kernel terpasang
modprobe ipip

# 2. Buat antarmuka tunnel
ip tunnel add ipip-vps mode ipip remote 10.250.1.1 local 10.250.1.2 ttl 64

# 3. Atur MTU dan aktifkan
ip link set ipip-vps mtu 1480 up

# 4. Tambahkan IP address
ip addr add 10.253.1.2/30 dev ipip-vps
```

---

## 3. Konfigurasi Sisi MikroTik RouterOS

### A. Melalui WinBox GUI
1. Buka WinBox ➔ Masuk ke menu **Interfaces**.
2. Buka tab **IP Tunnel** ➔ Klik icon **`+` (Add)**.
3. Konfigurasikan:
   * **Name**: `ipip-mitranet`
   * **Local Address**: `10.250.1.1`
   * **Remote Address**: `10.250.1.2`
   * **Clamp TCP MSS**: `Yes`
   * Klik **Apply** & **OK**.
4. Masuk ke menu **IP** ➔ **Addresses** ➔ Klik **`+` (Add)**:
   * **Address**: `10.253.1.1/30`
   * **Interface**: `ipip-mitranet`
   * Klik **OK**.
5. Pastikan Firewall Filter di MikroTik mengizinkan protokol IP `4` (`ipencap`):
   ```routeros
   /ip firewall filter add chain=input protocol=ipencap action=accept comment="Allow IPIP"
   ```

### B. Melalui Terminal CLI (RouterOS)
```routeros
/interface ipip add name=ipip-mitranet local-address=10.250.1.1 remote-address=10.250.1.2 clamp-tcp-mss=yes comment="IPIP to MitraNet"
/ip address add address=10.253.1.1/30 interface=ipip-mitranet comment="IPIP Subnet"
/ip firewall filter add chain=input protocol=ipencap action=accept comment="Allow IPIP"
```

---

## 4. Verifikasi & Pengujian
Jalankan uji ping dua arah:
* **Dari MitraNet**: `ping -c 4 10.253.1.1`
* **Dari MikroTik**: `/ping count=4 10.253.1.2`
Hasil uji nyata: Latensi stabil 14 ms, throughput optimal tanpa beban enkripsi CPU berat.
