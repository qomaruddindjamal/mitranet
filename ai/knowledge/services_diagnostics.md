# DNS, Firewall, Routing, and Diagnostic Services

## 1. Layanan DNS Server (`dnsmasq`)
- File konfigurasi utama: `/etc/dnsmasq.conf` dan direktori include `/etc/dnsmasq.d/`.
- Port default: 53 UDP/TCP.
- WebUI: `web/services/dns_server.php`.
- API endpoint: `GET /api/v1/services/dns` dan `POST /api/v1/services/dns/save`.
- Fitur: Cache size, forward upstream DNS, Host overrides, Domain overrides, dan restart service atomic.

## 2. Firewall & NAT Engine
- Backend nftables/iptables di `core/firewall/`.
- Rule filtering: Filter, NAT (Masquerade, Port Forward / DNAT, 1:1 NAT).
- Outbound NAT Masquerade per-interface untuk VPN WireGuard dan interface LAN.

## 3. Diagnostik Sistem
- Speedtest & Bandwidth Benchmarking di `web/tools/speedtest.php`.
- Gateway monitoring (DPINGER / fping) untuk deteksi latency & packet loss uplink WAN.
- Live traffic graphing (SVG polling per 1 detik via `/api/v1/system/network-stats`).
