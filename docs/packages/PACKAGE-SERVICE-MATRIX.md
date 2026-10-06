# MITRANET — PACKAGE SERVICE MATRIX

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Managed Daemon Catalog
| Package Name | Service Name | Configuration File | Health Check Probe | Recovery Action |
|---|---|---|---|---|
| `unbound-*.pkg` | `unbound` | `/etc/unbound/unbound.conf` | Socket response | Restart on failure |
| `dnsmasq-*.pkg` | `dnsmasq` | `/etc/dnsmasq.conf` | DNS query response | Restart on failure |
| `isc-dhcp44-server-*.pkg`| `dhcpd` | `/etc/dhcpd.conf` | Process & PID alive | Restart on failure |
| `nginx-*.pkg` | `nginx` | `/usr/local/etc/nginx/nginx.conf` | HTTP GET /api/status | Restart on failure |
| `openvpn-*.pkg` | `openvpn` | `/etc/openvpn/server.conf` | Interface `tun0` active | Safe degrade |
| `strongswan-*.pkg` | `strongswan` | `/etc/ipsec.conf` | IKE daemon status | Safe degrade |
| `suricata-*.pkg` | `suricata` | `/etc/suricata/suricata.yaml` | Thread status probe | Restart on failure |
| `bsnmp-*.pkg` | `bsnmpd` | `/etc/snmpd.conf` | SNMP poll probe | Log warning |
