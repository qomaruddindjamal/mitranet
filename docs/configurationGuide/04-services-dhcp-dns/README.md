# Panduan Konfigurasi: 04 - Core Services (DHCP Server & DNS Resolver)

Modul ini menjelaskan cara mengatur DHCP Server (Kea/dnsmasq) dan DNS Resolver terenkripsi (Unbound / DoH / Camouflage) pada MitraNet Rinjani 1.0.2.

---

## 1. DHCP Server untuk Klien LAN

### A. Metode WebUI
1. Buka menu **Services** ➔ **DHCP Server** (`/services/services_dhcp.php`).
2. Pilih tab interface LAN (contoh: `LAN (veth0)`).
3. Centang **Enable DHCP Server on LAN interface**.
4. **Range**: Masukkan rentang IP (contoh: `10.10.1.100` s/d `10.10.1.200`).
5. **DNS Servers**: Masukkan IP gateway MitraNet (`10.10.1.254`) atau upstream DNS (`1.1.1.1`).
6. **Default Lease Time**: Masukkan durasi sewa (contoh: `7200` detik).
7. Klik **Save & Apply**.

### B. Metode CLI
```bash
# Memeriksa status service dnsmasq / kea-dhcp4
systemctl status dnsmasq --no-pager

# Mengonfigurasi DHCP pool pada /etc/dnsmasq.d/lan-dhcp.conf
cat << 'EOF' > /etc/dnsmasq.d/lan-dhcp.conf
interface=veth0
dhcp-range=10.10.1.100,10.10.1.200,255.255.255.0,12h
dhcp-option=3,10.10.1.254        # Default Gateway
dhcp-option=6,10.10.1.254,1.1.1.1 # DNS Server
EOF

# Restart service
systemctl restart dnsmasq
```

---

## 2. DNS Resolver & DNS Camouflage

### A. Metode WebUI
1. Buka menu **Services** ➔ **DNS Resolver** (`/services/services_unbound.php`).
2. Centang **Enable DNS Resolver**.
3. **DNS Query Forwarding**:
   - Centang **Enable Forwarding Mode** untuk meneruskan query ke Cloudflare/Google DNS.
   - Aktifkan **DNS over TLS (DoT)** untuk enkripsi query dari pemblokiran ISP.
4. Klik **Save & Apply**.

### B. Metode CLI
```bash
# Konfigurasi upstream resolver Unbound di /etc/unbound/unbound.conf.d/forward.conf
cat << 'EOF' > /etc/unbound/unbound.conf.d/forward.conf
server:
    interface: 0.0.0.0
    access-control: 10.10.0.0/16 allow
    access-control: 127.0.0.0/8 allow

forward-zone:
    name: "."
    forward-addr: 1.1.1.1@853#cloudflare-dns.com
    forward-addr: 8.8.8.8@853#dns.google
    forward-tls-upstream: yes
EOF

systemctl restart unbound
```
