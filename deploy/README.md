# MitraNet OS - 1-Line VPS Auto-Reinstaller Guide
*(Seperti Cara Mengganti OS Linux ke MikroTik CHR)*

Panduan lengkap untuk menginstal atau mengubah sistem operasi VPS Linux (**Ubuntu, Debian, CentOS, AlmaLinux, Rocky Linux**) menjadi **MitraNet OS / Netgate Network Router** secara langsung melalui satu baris perintah via SSH.

---

## 1. Perintah 1 Baris (One-Line Command)

Cukup login ke SSH VPS Anda sebagai `root`, lalu salin dan jalankan perintah berikut:

```bash
curl -sSL https://raw.githubusercontent.com/qomaruddindjamal/mitranet/main/install.sh | bash
```

Atau jika menggunakan `wget`:

```bash
wget -qO- https://raw.githubusercontent.com/qomaruddindjamal/mitranet/main/install.sh | bash
```

### Mode Otomatis Tanpa Konfirmasi (Unattended / Force Mode):
Cocok untuk deployment otomatis atau cloud-init:

```bash
curl -sSL https://raw.githubusercontent.com/qomaruddindjamal/mitranet/main/install.sh | bash -s -- -y
```

### Mengatur Password WebGUI Kustom:
```bash
curl -sSL https://raw.githubusercontent.com/qomaruddindjamal/mitranet/main/install.sh | bash -s -- --password "Rahasia123!"
```

---

## 2. Cara Kerja Skrip (Under the Hood)

Skrip ini bekerja dengan mekanisme yang sama persis seperti script reinstall MikroTik CHR:

1. **Auto-Detect Jaringan:**
   - Mendeteksi interface utama (misal: `eth0`, `ens3`, `enp1s0`).
   - Merekam alamat IPv4, Subnet Mask, Default Gateway, dan DNS VPS Anda.
   - Memetakan interface Linux ke driver FreeBSD/Netgate (`vtnet0` untuk KVM, `vmx0` untuk VMware, `xn0` untuk Xen/AWS).
2. **Auto-Detect Hard Drive:**
   - Menemukan disk utama (`/dev/vda`, `/dev/sda`, atau `/dev/nvme0n1`).
3. **RAM Disk Execution:**
   - Memuat lingkungan installer ke dalam memori RAM (`tmpfs`) agar penulisan disk tidak terkunci oleh sistem Linux yang sedang berjalan.
4. **Flashing Raw Image:**
   - Mengunduh dan menulis image disk `MitraNet-OS-amd64.raw.gz` langsung ke hard drive utama secara streaming (`curl | gzip -dc | dd of=/dev/vda`).
5. **Injeksi Konfigurasi Jaringan (Network Injection):**
   - Menginjeksi IP, Gateway, dan DNS VPS yang tadi direkam ke dalam konfigurasi `/etc/rc.conf` atau `/cf/conf/config.xml` MitraNet OS.
   - **Hasil:** Saat VPS restart, koneksi internet dan IP address tidak akan hilang!
6. **Hard Reboot Otomatis:**
   - Mengirim sinyal reboot melalui kernel SysRq (`/proc/sysrq-trigger`) untuk langsung booting ke MitraNet OS.

---

## 3. Provider VPS yang Didukung & Teruji

Skrip ini kompatibel dengan hampir semua provider cloud VPS:
- **Internasional:** DigitalOcean, Vultr, Linode / Akamai, AWS Lightsail & EC2, Hetzner Cloud, OVHcloud, Contabo.
- **Lokal (Indonesia):** IDCloudHost, Biznet Gio, Rumahweb, DomaiNesia, KilatVM, CloudHost.

---

## 4. Mengakses Router Setelah Reboot

Tunggu sekitar 1 - 2 menit setelah proses instalasi selesai, lalu buka:

- **Web Dashboard (HTTPS):**
  ```text
  https://<IP-VPS-ANDA>
  ```
  *(Abaikan peringatan self-signed SSL certificate pada browser Anda)*

- **Akses SSH Terminal:**
  ```bash
  ssh admin@<IP-VPS-ANDA>
  ```

- **MitraNet Xray API Daemon:**
  ```text
  http://<IP-VPS-ANDA>:8080/api/mitranet/status
  ```

- **Kredensial Default:**
  - **Username:** `admin`
  - **Password:** `MitraNet@2026!` *(atau password yang Anda tentukan pada parameter `--password`)*

---

## 5. Menjalankan & Mengelola Xray (VLESS / VMESS) di VPS

Setelah masuk via SSH ke MitraNet OS:

```bash
# Cek status Xray
mitranet-cli status

# Impor link VLESS atau VMESS
mitranet-cli import-link "vless://uuid@host:443?security=reality&sni=www.apple.com&pbk=...#MyNode"

# Aktifkan transparent proxy router
mitranet-cli tproxy enable
```
