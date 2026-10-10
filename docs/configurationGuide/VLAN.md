# Panduan Konfigurasi 802.1Q VLAN — MitraNet Rinjani 1.0.2

Fitur **Virtual Local Area Network (802.1Q VLAN)** di MitraNet memungkinkan pemisahan segmen jaringan Layer 2 secara logis di atas satu port fisik Ethernet (contoh: `enp1s0.10`, `enp1s0.100`).

---

## 1. Lokasi Menu WebUI
- **Navigasi Sidebar**: `Interfaces` > `VLANs`
- **Tampilan Antarmuka Terpadu**: `Interfaces` > Tab `VLAN` (URL: `/interfaces/interfaces.php?tab=vlan`)

---

## 2. Mengapa Daftar VLAN Sebelumnya Kosong?
Tabel pada tab VLAN membaca interface kernel Linux yang berjenis `type vlan` atau berformat nama sub-interface dot notation (`enp1s0.<ID>`). Jika belum ada VLAN yang dibuat atau baru selesai di-reset, tabel akan menampilkan keterangan *"No interfaces found"*.

---

## 3. Langkah-Langkah Pembuatan VLAN Baru di WebUI:
1. Masuk ke menu **Interfaces > VLANs** atau buka tab **VLAN**.
2. Klik tombol **New** di pojok kiri atas toolbar.
3. Pada modal form **"New 802.1Q VLAN Interface"**, isi parameter berikut:
   - **Parent Interface**: Pilih port fisik tempat VLAN akan ditumpangkan (contoh: `enp1s0`).
   - **VLAN Tag**: Masukkan ID VLAN antara `1` sampai `4094` (contoh: `100`).
   - **Description**: Keterangan segmen VLAN (contoh: *VLAN Hotspot Kantor*).
   - **Minta IP Address otomatis via DHCP**: Centang opsi ini jika router/switch upstream menyediakan server DHCP pada segmen VLAN ini.
4. Klik **Create VLAN**.
5. Sistem akan:
   - Membuat interface virtual `enp1s0.100` di Linux kernel.
   - Mengaktifkan status link menjadi **UP**.
   - Meminta IP address melalui DHCP (`dhcpcd`) jika opsi dipilih.
   - Menampilkan interface baru pada baris tabel dengan flag running hijau.

---

## 4. Persyaratan pada Router / Switch Upstream (MikroTik / Cisco):
Agar paket ber-VLAN dapat berkomunikasi dengan router upstream:
1. Port pada Switch/Router yang mengarah ke kabel Mini PC **WAJIB** dikonfigurasi sebagai **Trunk Port / Tagged** untuk VLAN ID tersebut:
   ```routeros
   # Contoh pada MikroTik RouterOS:
   /interface vlan add name=vlan100-mitranet vlan-id=100 interface=ether2
   /ip address add address=192.168.100.1/24 interface=vlan100-mitranet
   /ip dhcp-server add interface=vlan100-mitranet address-pool=pool-vlan100 ...
   ```
2. Jika port di router upstream diset sebagai **Access Port murni (Strict Untagged)**, seluruh paket yang memiliki tag 802.1Q akan otomatis di-*drop* oleh hardware switch upstream.
