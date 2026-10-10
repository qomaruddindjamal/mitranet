# MitraNet Architecture & System Knowledge

## 1. Ikhtisar Sistem
MitraNet Rinjani 1.0.2 adalah distribusi sistem operasi router/appliance berbasis Debian GNU/Linux 13 (Trixie) yang dirancang untuk hardware x86_64 Mini PC dan virtual appliance (KVM).

## 2. Struktur Komponen Utama
- **Frontend Presentation Layer (`web/`)**:
  - Dibangun menggunakan PHP 8.4 murni (tanpa framework berat), HTML5, JavaScript (jQuery), Bootstrap UI, FontAwesome, dan SweetAlert2.
  - Menyajikan antarmuka visual bertema MikroTik WinBox (`.mitranet-window`, `.mitranet-toolbar`, `.mitranet-grid`).
  - Dijalankan secara lokal oleh PHP CLI Server internal pada `127.0.0.1:8000` (`PHP_CLI_SERVER_WORKERS=4`).
- **REST API Management Engine (`src/api/server.py`)**:
  - Python HTTP Server (`ThreadingHTTPServer`) pada port `8443`.
  - Berfungsi sebagai reverse proxy untuk WebUI PHP sekaligus penyedia endpoint JSON REST API `/api/v1/*`.
  - Memiliki modul autentikasi sesi (`AuthManager`) berbasis cookie `mitranet_session` dan validasi CSRF token (`X-CSRF-Token`).
- **Networking Core Subsystems (`core/`)**:
  - `core/network/discovery.py`: Deteksi hardware interface Linux melalui `/sys/class/net` dan `iproute2`.
  - `core/network/routing_discovery.py` & `routing_service.py`: Manajemen routing table, static route, default gateway, dan rule policy routing.
  - `core/network/vlan.py`: 802.1Q VLAN sub-interfaces (`ip link add link ... type vlan id ...`).
  - `core/network/bridge.py`: Linux bridge ports dan STP management.
  - `core/network/bonding.py`: Linux link aggregation / bonding modes (`802.3ad`, `balance-rr`, `active-backup`).
  - `core/network/vrf.py`: Virtual Routing and Forwarding (`ip link add type vrf table ...`).
  - `core/firewall/`: Mesin transaksi firewall nftables dan iptables wrapper.
- **Hardware Appliance Target**:
  - Mini PC x86_64 pada IP `10.10.66.228`.
  - Default SSH port 22 (`root:mitranet`).
