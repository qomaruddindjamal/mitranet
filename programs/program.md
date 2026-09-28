# MitraNet Programs & Network Enhancements

Direktori `programs/` berisi paket-paket tambahan dan modul sistem untuk memperkaya fungsionalitas OSNetwork (FreeBSD / Netgate base), dengan fokus utama pada integrasi **V2Ray / Xray (VLESS & VMESS)**.

---

## 1. Arsitektur Komponen V2Ray / Xray

```
+-------------------------------------------------------------------------+
|                              MitraNet OS                                |
+-------------------------------------------------------------------------+
|                                                                         |
|  [ Klien LAN / Router Traffic ]                                         |
|                 |                                                       |
|                 v (Port 53 / 80 / 443)                                  |
|  +-------------------------------------------------------------------+  |
|  |             FreeBSD PF (Packet Filter) Redirection                |  |
|  |                      (pf_xray.conf)                               |  |
|  +-------------------------------------------------------------------+  |
|                 |                                                       |
|                 | (Port 12345 dokodemo-door tproxy)                     |
|                 v                                                       |
|  +-------------------------------------------------------------------+  |
|  |              Xray-core FreeBSD amd64 Daemon                       |  |
|  |               (/usr/local/bin/xray)                               |  |
|  |                                                                   |  |
|  |  +---------------------------+   +-----------------------------+  |  |
|  |  |      Inbound Ports        |   |       Routing Engine        |  |  |
|  |  |  - SOCKS5: 10808          |   |  - Bypass LAN / Private IPs |  |  |
|  |  |  - HTTP Proxy: 10809      |   |  - Direct DNS               |  |  |
|  |  |  - TProxy / Doko: 12345   |   |  - Proxy Outbound           |  |  |
|  |  +---------------------------+   +-----------------------------+  |  |
|  |                 |                                                 |  |
|  |                 v (Encrypted Outbounds)                           |  |
|  |  +-------------------------------------------------------------+  |  |
|  |  | Protocol Outbound:                                          |  |  |
|  |  | - VLESS Reality (XTLS-Vision / TCP)                         |  |  |
|  |  | - VLESS WebSocket + TLS                                     |  |  |
|  |  | - VMESS WebSocket + TLS                                     |  |  |
|  |  | - VMESS TCP / AEAD                                          |  |  |
|  |  +-------------------------------------------------------------+  |  |
|  +-------------------------------------------------------------------+  |
|                 |                                                       |
|                 v (WAN / Internet Tunnel)                               |
|          [ Server Remote / CDN ]                                        |
|                                                                         |
+-------------------------------------------------------------------------+
```

---

## 2. Fitur Protokol yang Didukung

### A. VLESS Reality (Direkomendasikan)
- Menggunakan standar enkripsi modern tanpa sertifikat TLS kustom pada server (meminjam SNI seperti `www.cloudflare.com` atau `www.apple.com`).
- Dilengkapi flow `xtls-rprx-vision` untuk kinerja transmisi tertinggi dan resistensi terhadap Deep Packet Inspection (DPI).
- File konfigurasi template: `programs/xray/config/config_vless_reality.json`.

### B. VLESS WebSocket + TLS
- Sangat cocok untuk melewati Cloudflare CDN atau reverse proxy.
- File konfigurasi template: `programs/xray/config/config_vless_ws.json`.

### C. VMESS WebSocket + TLS / TCP
- Protokol klasik VMESS dengan otentikasi User ID (UUID) dan enkripsi AEAD.
- File konfigurasi template: `programs/xray/config/config_vmess_ws.json`.

---

## 3. Manajemen CLI (`mitranet-cli` / `mitra-v2ray`)

Perintah CLI interaktif yang disediakan:

```bash
# Cek status daemon Xray, node aktif, dan transparent proxy
mitranet-cli status

# Menjalankan, mematikan, atau me-restart daemon
mitranet-cli start
mitranet-cli stop
mitranet-cli restart

# Mengimpor node dari link vless:// atau vmess://
mitranet-cli import-link "vless://uuid@host:443?security=reality&sni=example.com&pbk=...#MyNode"

# Melihat daftar node yang sudah diimpor
mitranet-cli list-nodes

# Memilih dan mengaktifkan node tertentu
mitranet-cli use-node MyNode

# Menguji latensi & koneksi proxy ke internet
mitranet-cli ping-node

# Mengaktifkan/menonaktifkan transparent proxy (redirection firewall pf)
mitranet-cli tproxy enable
mitranet-cli tproxy disable

# Melihat log akses/error
mitranet-cli logs
```

---

## 4. Web API Daemon (`mitranet_api.py`)

Daemon API ringan berjalan pada port `8080` (dan dapat diakses melalui Nginx pada `/api/mitranet`):

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/api/mitranet/status` | Mengembalikan status layanan, PID, port proxy, dan node aktif |
| `GET` | `/api/mitranet/nodes` | Mengembalikan daftar node yang tersimpan |
| `POST`| `/api/mitranet/start` | Menyalakan daemon Xray |
| `POST`| `/api/mitranet/stop` | Mematikan daemon Xray |
| `POST`| `/api/mitranet/switch` | Mengganti node aktif (`{"node": "<nama>"}`) |
| `POST`| `/api/mitranet/import` | Mengimpor link VLESS/VMESS (`{"link": "<url>"}`) |

---

## 5. Startup FreeBSD Service (`/usr/local/etc/rc.d/xray`)

Daemon diatur menggunakan standar FreeBSD `rc.subr`:
- Menambahkan `xray_enable="YES"` di `/etc/rc.conf` membuat Xray otomatis menyala saat mesin booting.
- Skrip memvalidasi sintaks konfigurasi (`xray test -c ...`) sebelum proses dijalankan untuk mencegah kegagalan runtime.
