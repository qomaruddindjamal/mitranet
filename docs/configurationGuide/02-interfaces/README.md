# Panduan Konfigurasi: 02 - Virtual Interfaces (VLAN, MACVLAN & Bonding)

Modul ini memandu pembuatan dan pengelolaan antarmuka virtual pada MitraNet Rinjani 1.0.2 untuk kebutuhan segmentasi LAN, multi-MAC WAN bypass, serta agregasi link fisik.

---

## 1. 802.1Q VLAN Tagging

### A. Metode WebUI
1. Buka menu **Interfaces** ➔ **VLANs** (`interfaces.php?tab=vlan`).
2. Klik tombol **New**.
3. **Parent Physical Interface**: Pilih interface fisik (contoh: `enp1s0`).
4. **VLAN Tag**: Masukkan VLAN ID (misal: `100` atau `200`).
5. **Description**: Beri keterangan (misal: `VLAN_HOTSPOT`).
6. Klik **Create VLAN**.
7. Buka **Interfaces** ➔ **Interface Assignments** untuk menetapkan IP pada `enp1s0.100`.

### B. Metode CLI
```bash
# Membuat interface VLAN ID 100 di atas enp1s0
ip link add link enp1s0 name enp1s0.100 type vlan id 100
ip link set enp1s0.100 up

# Memberikan IP address ke VLAN
ip addr add 192.168.100.1/24 dev enp1s0.100

# Verifikasi status
ip -d link show enp1s0.100
```

---

## 2. Virtual MAC / MACVLAN (Multi-MAC Splitting)

Berguna untuk meminta beberapa IP publik/DHCP terpisah dari satu port modem ISP.

### A. Metode WebUI
1. Buka menu **Interfaces** ➔ **MACVLANs** (`interfaces.php?tab=macvlan`).
2. Klik tombol **New**.
3. **Interface Name**: Masukkan nama (contoh: `mac0` atau `macvlan0`).
4. **Parent Physical Interface**: Pilih interface fisik (contoh: `enp1s0`).
5. **MACVLAN Mode**: Pilih `bridge` (default).
6. Centang opsi **Minta IP Address otomatis via DHCP (dhcpcd)**.
7. Klik **Create MACVLAN**.

### B. Metode CLI
```bash
# Membuat interface mac0 bertipe macvlan mode bridge
ip link add link enp1s0 name mac0 type macvlan mode bridge
ip link set mac0 up

# Menjalankan DHCP client untuk meminta IP baru dengan virtual MAC unik
dhcpcd -b mac0

# Cek IP dan MAC virtual yang diperoleh
ip addr show mac0
```

---

## 3. Network Bonding / Link Aggregation (LACP / Active-Backup)

### A. Metode WebUI
1. Buka menu **Interfaces** ➔ **Bridges / Bonds** (`interfaces.php?tab=bonding`).
2. Klik **New Bond Interface**.
3. Pilih mode bonding (contoh: `802.3ad LACP` atau `active-backup`).
4. Pilih slave interface fisik (contoh: `enp1s0`, `enp2s0`).
5. Klik **Save & Apply**.

### B. Metode CLI
```bash
# Load modul kernel bonding
modprobe bonding

# Buat master bond0 dengan mode LACP (mode 4)
ip link add name bond0 type bond miimon 100 mode 802.3ad

# Masukkan slave interface
ip link set enp1s0 down
ip link set enp2s0 down
ip link set enp1s0 master bond0
ip link set enp2s0 master bond0

# Naikkan bond0 dan slave
ip link set bond0 up
ip link set enp1s0 up
ip link set enp2s0 up
```
