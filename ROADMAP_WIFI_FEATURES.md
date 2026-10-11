# ROADMAP PENGEMBANGAN FITUR & PERBAIKAN MITRANET RINJANI (WIFI & CORE)

Berdasarkan hasil audit sistem pada unit live Mini PC (`10.10.66.228`) dan repositori MitraNet, berikut adalah rencana strategis bertahap (*roadmap*) untuk menghadirkan kapabilitas nirkabel setara MikroTik RouterOS (Access Point, Station/Client, dan Virtual AP/Repeater) serta menyelesaikan perbaikan operasional sistem.

---

## Tahap 1: Fondasi Sistem & Perbaikan Cepat (Quick-Wins & Fixes)

### 1.1 Perbaikan Layanan DHCP Server (`dnsmasq`)
- **Masalah:** Layanan `dnsmasq.service` saat ini berstatus *failed* karena konfigurasi `/etc/dnsmasq.d/dhcp_wlan0.conf` mewajibkan interface `wlan0` aktif saat boot (`bind-interfaces`).
- **Solusi:**
  - Ganti opsi `bind-interfaces` dengan `bind-dynamic` pada konfigurasi template dnsmasq, atau kelola start/stop file konfigurasi per-interface secara dinamis hanya saat interface tersebut berstatus *UP*.
  - Tambahkan guard check di modul DHCP backend MitraNet.

### 1.2 Sinkronisasi Manifest Rilis & Checksum ISO
- **Masalah:** Checksum pada `RELEASE_MANIFEST.md` dan `MITRANET_WORK_STATE.md` masih mencatat build SHA-256 lama (`C0F69F83...`), sedangkan file ISO hasil build 11 Oktober memiliki hash `EA4DB6DE...`.
- **Solusi:**
  - Perbarui hash ISO resmi di dokumen rilis agar verifikasi integritas pengguna tepat sasaran.

---

## Tahap 2: Implementasi Mesin Wireless Multi-Mode (MikroTik Parity)

### 2.1 Pemasangan & Orkestrasi Daemon Inti
- **Komponen:**
  - Paket `hostapd` (`2:2.10-24` Debian 13) untuk fungsi Access Point (AP).
  - Integrasi lanjutan `wpa_supplicant` untuk fungsi Client/Station.
- **Implementasi:**
  - Buat folder konfigurasi persistensi di `/etc/mitranet/wifi/`.
  - Buat template konfigurasi otomatis:
    - `/etc/mitranet/wifi/hostapd.conf.template`
    - `/etc/mitranet/wifi/wpa_supplicant.conf.template`
  - Buat skrip kontrol systemd / helper: `mitranet-wifi-controller` untuk transisi mode tanpa restart OS.

### 2.2 Mode 1: Access Point (AP-Bridge)
- **Alur Kerja:**
  1. Tetapkan IP statis Gateway ke `wlan0` (default: `10.10.1.254/24`).
  2. Generate konfigurasi `hostapd` dengan parameter:
     - Band: `2.4 GHz (802.11g/n)` atau `5 GHz (802.11a/n/ac)`.
     - Channel: Pilihan manual atau *ACS (Automatic Channel Selection)*.
     - Security: `WPA2-PSK (AES/CCMP)` atau `WPA3-SAE`.
  3. Aktifkan DHCP Server dinamis (`dnsmasq`) pada pool `10.10.1.10 - 10.10.1.200`.
  4. Tambahkan rule firewall NAT Masquerade otomatis dari subnet `wlan0` ke default gateway uplink.

### 2.3 Mode 2: Client / Station (Wireless Uplink / WA Radio)
- **Alur Kerja:**
  1. Pengguna memilih SSID dari hasil scan tabel di WebUI.
  2. Input passphrase/kunci keamanan.
  3. Tulis konfigurasi ke `wpa_supplicant`.
  4. Jalankan DHCP Client (`dhcpcd wlan0` atau internal DHCP client).
  5. Masukkan `wlan0` ke dalam interface list `WAN` untuk failover atau load balancing / Cloud Booster.

### 2.4 Mode 3: Dual-Role / Repeater (Station + Virtual AP)
- **Alur Kerja:**
  1. Sambungkan interface fisik `wlan0` sebagai Client ke Wi-Fi sumber.
  2. Baca frekuensi/channel dari koneksi yang terhubung (misal: Channel 6 / 2437 MHz).
  3. Buat antarmuka virtual: `iw dev wlan0 interface add wlan0-ap type __ap`.
  4. Jalankan `hostapd` pada `wlan0-ap` dengan channel yang sama persis.
  5. Bridging atau routing NAT dari `wlan0-ap` (LAN) melalui `wlan0` (WAN).

---

## Tahap 3: Backend REST API (`src/api/wifi.py`)

Tambahkan modul API khusus nirkabel untuk menangani permintaan dari WebUI:

| Endpoint | Method | Fungsi |
|---|---|---|
| `/api/v1/wifi/interfaces` | `GET` | Mendapatkan daftar interface fisik (`wlan0`) dan virtual (`wlan0-ap`) beserta status real-time. |
| `/api/v1/wifi/scan` | `GET` | Melakukan pemindaian sinyal RF riil dan mengembalikan daftar BSSID, SSID, Signal dBm, dan enkripsi. |
| `/api/v1/wifi/mode` | `POST` | Mengubah mode kerja radio: `ap`, `station`, atau `repeater`. |
| `/api/v1/wifi/ap/config` | `POST` | Mengatur parameter AP (SSID, Band, Channel, Tx Power, Hide SSID, WPA2/WPA3 Passphrase). |
| `/api/v1/wifi/client/connect`| `POST` | Menyambungkan radio ke target SSID luar. |
| `/api/v1/wifi/clients` | `GET` | Menampilkan tabel stasiun/klien yang sedang terhubung ke AP (MAC, IP, RSSI, Tx/Rx Rate). |

---

## Tahap 4: Modernisasi Antarmuka WebUI (`web/wifi/wifi.php`)

### 4.1 Fungsionalitas Penuh 14 Tab-Menu
Saat ini ke-14 tab sudah memiliki rute dan tampilan aktif di navigasi. Tahap ini mengisi fungsionalitas spesifik untuk setiap tab:
- **Tab `WiFi`**: Toolbar Action aktif (Enable/Disable Radio, Edit Modal, Scan Networks).
- **Tab `Network` / `Configuration`**: Pengaturan profil jaringan (SSID profile, Country Code regulasi wireless).
- **Tab `Channel`**: Pemilihan frekuensi channel (Channel 1-14 pada 2.4 GHz, Channel 36-165 pada 5 GHz) serta visualisasi spektrum channel.
- **Tab `Security`**: Security Profiles (WPA2-PSK, WPA3, Enterprise 802.1X, Open).
- **Tab `Registration` / `Access List`**:
  - Menampilkan daftar perangkat HP/Laptop klien yang sedang terhubung ke Hotspot (MAC, sinyal, durasi).
  - Fitur MAC Filter (Whitelist / Blacklist Klien).

### 4.2 Tombol Quick-Action di Modal Edit WinBox
- **Scan Button:** Membuka pop-up scanner dan menyediakan tombol satu-klik **"Connect to this network"**.
- **Mode Dropdown:** Dropdown mode yang langsung menyesuaikan kolom isian yang relevan (jika memilih AP, muncul kolom SSID & Password Hotspot; jika memilih Client, muncul form koneksi).

---

## Tahap 5: Pengujian, QA, & Validasi Rilis

1. **Unit & API Testing:**
   - Menambahkan file pengujian baru: `tests/test_wifi_api.py` untuk menguji parser `iw`, generator `hostapd.conf`, dan validasi parameter keamanan.
2. **Hardware Live Test:**
   - Validasi pemancaran SSID di spektrum radio 2.4 GHz dan 5 GHz Mini PC menggunakan perangkat HP/Laptop uji.
   - Uji koneksi data internet (Throughput test dari klien yang terhubung ke AP Mini PC).
   - Uji stabilitas mode Repeater (Station + AP).
3. **Pembaruan Artefak Rilis Debian (`.deb`) & ISO:**
   - Masukkan paket `hostapd` ke dalam manifest ketergantungan ISO installer `iso/MitraNet-Rinjani-1.0.2-amd64.iso`.
