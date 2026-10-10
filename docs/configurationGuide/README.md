# Direktori Panduan Konfigurasi MitraNet OS Rinjani 1.0.2

Kumpulan dokumentasi teknis ini disusun secara modular per topik, mencakup panduan langkah demi langkah menggunakan **2 Metode**:
1. **Melalui WebUI (Browser / Graphical Interface)**
2. **Melalui Terminal / CLI (Linux Shell & MikroTik CLI)**

---

## Daftar Modul Konfigurasi

| No | Modul / Folder | Cakupan Topik | Tautan Dokumen |
| :---: | :--- | :--- | :---: |
| **01** | [01-basic-network/](01-basic-network/README.md) | Konfigurasi IP WAN/LAN, DHCP Client, Gateway, Hostname & DNS | [Baca Panduan](01-basic-network/README.md) |
| **02** | [02-interfaces/](02-interfaces/README.md) | 802.1Q VLAN Tagging, Virtual MAC (MACVLAN), dan Bonding / LACP | [Baca Panduan](02-interfaces/README.md) |
| **03** | [03-routing-loadbalance/](03-routing-loadbalance/README.md) | Rute Statis, Policy Routing (PBR / Table 100), dan Multi-WAN ECMP | [Baca Panduan](03-routing-loadbalance/README.md) |
| **04** | [04-services-dhcp-dns/](04-services-dhcp-dns/README.md) | DHCP Server Klien LAN (dnsmasq) & Encrypted DNS (Unbound / DoT) | [Baca Panduan](04-services-dhcp-dns/README.md) |
| **05** | [05-firewall-nat/](05-firewall-nat/README.md) | Outbound Masquerade, Port Forward (DST-NAT), MSS Clamping & DSCP | [Baca Panduan](05-firewall-nat/README.md) |
| **06** | [06-vpn-tunnels/](06-vpn-tunnels/README.md) | WireGuard Tunnel (wg0), GRE Tunnel, EOIP L2 Bridge, & Xray-core | [Baca Panduan](06-vpn-tunnels/README.md) |
| **07** | [07-cloud-speed-booster/](07-cloud-speed-booster/README.md) | Multi-Stream WireGuard, ECMP L4 Hash, FwMark 0xca6c, Skrip VPS | [Baca Panduan](07-cloud-speed-booster/README.md) |
| **08** | [08-advanced-system-tunables/](08-advanced-system-tunables/README.md) | Optimasi Google BBR, FQ qdisc, TCP Window Buffer, sysctl tuning | [Baca Panduan](08-advanced-system-tunables/README.md) |

---

### Cara Menggunakan Dokumen
Setiap dokumen di atas memiliki struktur yang konsisten:
- **Prinsip Kerja & Arsitektur**
- **Metode A: Melalui WebUI** (Path menu, input field, dan langkah klik tombol)
- **Metode B: Melalui Terminal / CLI** (Sintaks perintah Linux terminal, konfigurasi file teks, dan skrip RouterOS)
- **Verifikasi Status** (Perintah pengujian untuk memastikan konfigurasi berjalan sukses)
