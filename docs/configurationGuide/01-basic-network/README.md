# Panduan Konfigurasi: 01 - Basic Network (IP, Gateway & Hostname)

Modul ini menjelaskan konfigurasi dasar jaringan pada MitraNet Rinjani 1.0.2, mencakup pemberian IP statis, DHCP client, gateway default, serta pengaturan hostname.

---

## 1. Metode A: Melalui WebUI (Browser)

1. **Pengaturan IP Address & Interface Fisik**:
   - Buka menu **Interfaces** ➔ **Interface Assignments** (`/interfaces/interfaces_assign.php`).
   - Pilih interface yang akan dikonfigurasi (misal: `WAN (enp1s0)` atau `LAN (veth0)`).
   - Pada opsi **IPv4 Configuration Type**:
     - Pilih **DHCP** jika ingin memperoleh IP otomatis dari modem/router ISP.
     - Pilih **Static IPv4** jika ingin menetapkan IP manual:
       - **IPv4 Address**: Masukkan IP dan subnet (contoh: `10.10.66.208 / 24`).
       - **IPv4 Upstream Gateway**: Pilih gateway atau masukkan IP router upstream (contoh: `10.10.66.254`).
   - Klik **Save** ➔ **Apply Changes**.

2. **Pengaturan Hostname & Domain**:
   - Buka menu **System** ➔ **General Setup** (`/system/system.php`).
   - Isi field **Hostname** (contoh: `mitranet`) dan **Domain** (contoh: `lan` / `local`).
   - Klik **Save**.

3. **Pengaturan DNS Server & Resolver (Terpusat)**:
   - Buka menu **Services** ➔ **DNS Server** (`/services/dns_server.php`).
   - Centang **Enable** (Aktifkan DNS Server dnsmasq).
   - Pada kolom **Upstream DNS Servers**, masukkan IP DNS publik (contoh: `1.1.1.1` dan `8.8.8.8` - satu IP per baris).
   - Klik **Save** di bagian bawah untuk menerapkan ke sistem dan klien.

---

## 2. Metode B: Melalui Terminal / CLI (Linux Shell)

1. **Mengecek Status Interface & IP Saat Ini**:
   ```bash
   ip -br addr show
   ip route show
   ```

2. **Konfigurasi IP Otomatis (DHCP Client)**:
   ```bash
   dhcpcd -b enp1s0
   ```

3. **Konfigurasi IP Statis Manual**:
   ```bash
   # Hapus IP lama jika perlu
   ip addr flush dev enp1s0

   # Tambah IP statis
   ip addr add 10.10.66.208/24 dev enp1s0
   ip link set enp1s0 up

   # Tambah default gateway
   ip route replace default via 10.10.66.254 dev enp1s0 proto static metric 100
   ```

4. **Konfigurasi Hostname & DNS Resolver**:
   ```bash
   # Ubah hostname
   hostnamectl set-hostname mitranet

   # Konfigurasi DNS di /etc/resolv.conf
   cat << 'EOF' > /etc/resolv.conf
   nameserver 1.1.1.1
   nameserver 8.8.8.8
   EOF
   ```

5. **Uji Konektivitas**:
   ```bash
   ping -c 3 8.8.8.8
   ping -c 3 google.com
   ```
