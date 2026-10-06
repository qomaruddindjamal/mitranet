# pfSense config.xml Migration Engine Architecture

## 1. Migration Overview
The MitraNet Migration Engine parses pfSense `config.xml` files, normalizes BSD-specific attributes (interface naming conventions like `em0`, `igb0`, `vtnet0`, `pf` rules, CARP VIPs), maps them to MitraNet's unified JSON configuration schema, validates integrity and network invariants, and outputs a migration report before applying changes.

```text
  ┌───────────────────────┐
  │   pfSense config.xml  │
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │    XML AST Parser     │
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ Interface Normalizer  │ (e.g., em0 -> eth0, igb0 -> enp1s0)
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ Rule & Service Mapper │ (pf -> nftables, carp -> keepalived)
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ MitraNet Schema Model │
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ Validator & Reporter  │ (Check overlapping subnets, missing gateways)
  └───────────┬───────────┘
              │
              ▼
  ┌───────────────────────┐
  │ Linux Backend Commits │
  └───────────────────────┘
```

## 2. Section-by-Section Schema Mapping

### 2.1 System Setup (`<system>`)
- `<hostname>` & `<domain>` $\rightarrow$ Linux `/etc/hostname` and FQDN.
- `<timeservers>` $\rightarrow$ Chrony / systemd-timesyncd NTP server pools.
- `<user>` $\rightarrow$ MitraNet RBAC user store (`/etc/mitranet/users.json`). Bcrypt password hashes are standard across Linux PAM and pfSense.

### 2.2 Network Interfaces (`<interfaces>`)
- `<wan>`, `<lan>`, `<optX>`
- BSD interface strings mapped through an interactive or declarative interface mapping table:
  ```json
  {
    "em0": "eth0",
    "em1": "eth1",
    "vtnet0": "enp0s3"
  }
  ```
- IPv4/IPv6 static configuration translated to `mitranet.network.interface` IP addresses and CIDR prefixes.
- DHCP client mode translated to systemd-networkd / dhcpcd client bindings.

### 2.3 Firewall Rules (`<filter>`)
- `<rule>` in pfSense:
  - `<type>`: `pass` $\rightarrow$ `accept`, `block`/`reject` $\rightarrow$ `drop`/`reject`.
  - `<interface>`: mapped to Linux interface name (`iifname "eth0"`).
  - `<source>` / `<destination>`: network aliases, direct subnets, or `any`.
  - `<protocol>`: `tcp`, `udp`, `icmp`, `any`.
- Output: Structured MitraNet rule objects ready for compilation into atomic `nftables` tables.

### 2.4 NAT Configuration (`<nat>`)
- `<outbound>`:
  - `automatic` / `hybrid` / `manual` mapped to MitraNet SNAT / Masquerade chain policies.
- `<rule>` (Port Forwarding):
  - Inbound DNAT translation with automated target port and destination rewriting.

### 2.5 DHCP & DNS Services (`<dhcpd>`, `<unbound>`)
- `<dhcpd><lan><range>`: converted to Kea DHCP subnet pool definitions.
- `<staticmap>`: converted to Kea DHCP host reservations (MAC to IP).
- `<unbound>`: converted to `/etc/unbound/unbound.conf.d/mitranet.conf`.

### 2.6 High Availability (`<hasync>`, `<vip>`)
- CARP VIPs translated to Keepalived VRRP instances with VRID and virtual IP bindings.
- pfsync state sync translated to conntrackd replication over multicast or unicast UDP.
