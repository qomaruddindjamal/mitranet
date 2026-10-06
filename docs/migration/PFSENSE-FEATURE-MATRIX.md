# pfSense Complete Feature Inventory & Discovery

*Comparative Source: pfSense 2.9.0 ISO inspection (`C:\mitranet\pfsense-offline-installer.iso`) vs Netgate Official Documentation (`https://docs.netgate.com/pfsense/en/latest/`)*

## Verification Legend
- **VERIFIED**: Package, script, binary, or configuration schema element directly verified in the inspected ISO.
- **DOCUMENTED (EXTERNAL)**: Documented in official Netgate manuals, available via online package repositories or enterprise subscription add-ons.

---

## 1. System & Platform Management

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **User & Group RBAC** | Local user/group database, privilege pages, GID/UID assignments | VERIFIED | `/etc/inc/auth.inc`, `/etc/inc/priv.defs.inc` |
| **Authentication Servers** | Local Database, Remote LDAP, Remote RADIUS | VERIFIED | `openldap26-client`, `pam_ldap`, `php85-pecl-radius` |
| **Certificate Authority (CA)** | Internal CA creation, intermediate CA, CRL (revocation list) | VERIFIED | `/etc/inc/certs.inc`, `openssl` |
| **Certificates Management** | WebGUI TLS certs, OpenVPN server/client certs, IPsec certs | VERIFIED | `/etc/inc/certs.inc`, `php85-openssl_x509_crl` |
| **SSH Management** | SSH server daemon, authorized keys, port remapping, SSHGuard | VERIFIED | `sshguard-2.5.1`, `/etc/sshd` |
| **Console & Shell** | Interactive console menu (`/etc/rc.initial`), php-sh shell sessions | VERIFIED | `/etc/rc.initial`, `/etc/phpshellsessions/*` |
| **Backup & Restore** | Complete `config.xml` backup, partial section backup, encryption | VERIFIED | `/etc/inc/config.lib.inc`, `/etc/rc.restore_config_backup` |
| **Auto Configuration Backup (ACB)** | Cloud encrypted configuration backup | VERIFIED | `/etc/inc/acb.inc` |
| **Cron Job Scheduler** | System crontab management, periodic scripts execution | VERIFIED | `/etc/inc/services.inc`, `cron` table in config.xml |
| **NTP Client & Server** | Time synchronization, pool selection, hardware clock drift | VERIFIED | `ntp-4.2.8p18`, `/usr/bin/nice -n20 adjkerntz` |
| **System Logging & Syslog** | Local circular logging, remote syslog forwarding, filter log | VERIFIED | `newsyslog`, `/etc/inc/syslog.inc` |
| **Package Management** | Offline and online package installations (`pkg-static`, repo sync) | VERIFIED | `pkg-2.8.4`, `pfSense-repoc`, `/etc/inc/pkg-utils.inc` |

---

## 2. Interfaces & Layer 2 Networking

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **Physical Ethernet** | NIC driver binding, autonegotiation, MTU, duplex settings | VERIFIED | `ifconfig`, FreeBSD network stack |
| **Interface Assignment** | WAN, LAN, OPTn customizable logical naming and mapping | VERIFIED | `/etc/inc/interfaces.inc`, `<interfaces>` XML |
| **Interface Groups** | Aggregate multiple interfaces for unified firewall rule policies | VERIFIED | `ifconfig group`, `/etc/inc/interfaces.inc` |
| **VLAN (802.1Q)** | Virtual LAN tagging over parent interfaces | VERIFIED | `ifconfig vlan`, `<vlans>` XML |
| **QinQ (802.1ad)** | Double VLAN tagging (Service Provider + Customer VLAN) | VERIFIED | `ifconfig vlan`, `<qinqs>` XML |
| **LAGG / LACP (802.3ad)** | Link aggregation (LACP, Failover, LoadBalance, RoundRobin) | VERIFIED | `ifconfig lagg`, `/etc/inc/interfaces.inc` |
| **Network Bridge** | Layer 2 software bridge spanning ports with STP/RSTP support | VERIFIED | `ifconfig bridge`, `<bridges>` XML |
| **PPPoE Client** | WAN connection encapsulation with PAP/CHAP authentication | VERIFIED | `mpd5-5.9_19`, `/etc/rc.pppoe-linkup` |
| **GRE & GIF Tunnels** | Generic Routing Encapsulation and generic IPv6-in-IPv4 tunnels | VERIFIED | `ifconfig gre`, `ifconfig gif` |
| **Virtual IP (VIP)** | IP Alias, CARP VIP, Proxy ARP, Other (non-routable tracking) | VERIFIED | `/etc/inc/interfaces.inc`, `<virtualip>` XML |
| **Wireless / AP Mode** | 802.11 Wi-Fi interface management and WPA client/authenticator | VERIFIED | `wpa_supplicant-2.12`, `wifi.pkg` |

---

## 3. Firewall & Packet Filtering

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **Stateful Filtering** | TCP/UDP/ICMP stateful packet filtering via Packet Filter (pf) | VERIFIED | `/dev/pf`, `/tmp/rules.debug`, `/etc/inc/filter.inc` |
| **Floating Rules** | Rules spanning multiple interfaces in any direction with match/pass | VERIFIED | `filter.inc`, pf `match` and `pass` directives |
| **Interface Rules** | Standard ingress firewall policies per assigned interface | VERIFIED | `filter.inc`, `<filter>` XML |
| **Rule Aliases** | Host, Network, Port, URL Table (dynamic file/URL), GeoIP | VERIFIED | `pfctl -t <table>`, `/etc/rc.update_urltables` |
| **Schedules** | Time-based firewall rule activation (daily, weekly, date ranges) | VERIFIED | `cron`, `filter.inc` time checks |
| **State Table Inspection** | Real-time state viewer, state search, specific state termination | VERIFIED | `pfctl -ss`, `pfctl -k`, `pftop-0.13` |
| **Bogon & Private IP Blocks** | Automated drop of unallocated IPv4/IPv6 and RFC1918 on WAN | VERIFIED | `/etc/rc.update_bogons.sh`, pf tables `bogons`, `bogonsv6` |

---

## 4. NAT (Network Address Translation)

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **Outbound NAT (SNAT)** | Automatic, Hybrid, or Manual outbound masquerade/NAT | VERIFIED | `nat` rules in pf syntax, `/etc/inc/filter.inc` |
| **Port Forward (DNAT)** | Inbound port forwarding with associated filter rule generation | VERIFIED | `rdr` rules in pf, `<nat><rule>` XML |
| **1:1 NAT (Bi-directional)** | Full IP-to-IP direct subnet and host bidirectional translation | VERIFIED | `binat` rules in pf, `<nat><onetoone>` XML |
| **NAT Reflection** | Hairpin NAT via NAT loopback or automatic split-DNS helper | VERIFIED | `<disablenatreflection>`, pf rdr reflection chains |
| **Static Port NAT** | Preserving source port without randomization (VoIP/Gaming) | VERIFIED | `static-port` flag in pf nat rule |

---

## 5. Routing & Multi-WAN

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **Static Routing** | Subnet destinations mapped to explicit gateway IPs/interfaces | VERIFIED | `route add`, `<staticroutes>` XML |
| **Gateway Management** | IPv4/IPv6 next-hops with latency/loss monitoring | VERIFIED | `dpinger`, `/etc/inc/gwlb.inc` |
| **Gateway Groups** | Multi-WAN failover (Tier 1 vs Tier 2) and Load Balancing | VERIFIED | pf `route-to` pool balance, `gwlb.inc` |
| **Policy Routing** | Directing packets through explicit gateways via firewall rules | VERIFIED | pf rule `route-to ($gw $if)` syntax |
| **Dynamic Routing (FRR)** | BGP, OSPFv2, OSPFv3, BFD, RIP | DOCUMENTED (EXTERNAL) | Available as `pfSense-pkg-frr` add-on package |

---

## 6. DHCP & IP Address Management

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **DHCPv4 Server** | Address pools, lease duration, DNS, NTP, gateway pushes | VERIFIED | `kea` / `isc-dhcpd`, `/etc/inc/services.inc` |
| **Static DHCP Mappings** | MAC-to-IP reservation, custom hostnames, specific boot options | VERIFIED | `<dhcpd><lan><staticmap>` XML |
| **DHCP Relay** | Forwarding client DHCP broadcast requests across subnets | VERIFIED | `dhcrelay`, `/etc/inc/services.inc` |
| **DHCPv6 & Prefix Del** | DHCPv6 address assignments, SLAAC, IA_PD delegation | VERIFIED | `radvd-2.20`, `dhcp6c`, `/etc/inc/parser_dhcpv6_leases.inc` |
| **Router Advertisements** | ICMPv6 RA daemon managing managed, assist, or stateless flags | VERIFIED | `radvd`, `<dhcpdv6><lan><ramode>` XML |

---

## 7. DNS & Name Resolution

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **DNS Resolver (Unbound)** | Recursive resolving DNS cache with DNSSEC, prefetching | VERIFIED | `unbound-1.26.1`, `/etc/inc/unbound.inc` |
| **DNS Forwarder (Dnsmasq)**| Lightweight forwarding resolver with DHCP lease integration | VERIFIED | `dnsmasq`, `/etc/inc/services.inc` |
| **Host Overrides** | Custom local DNS A, AAAA, and MX overrides | VERIFIED | `unbound.conf` `local-data`, `<unbound>` XML |
| **Domain Overrides** | Forwarding explicit domains to internal corporate nameservers | VERIFIED | `unbound.conf` `forward-zone`, `<unbound>` XML |
| **Dynamic DNS (DDNS)** | Automated public IP updates across 30+ DDNS providers | VERIFIED | `/etc/inc/dyndns.class`, `/etc/rc.dyndns.update` |

---

## 8. Virtual Private Networks (VPN)

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **IPsec (IKEv1 / IKEv2)** | Site-to-Site and Road Warrior IPsec VPN, EAP, PSK, X.509 | VERIFIED | `strongswan-6.1.0_1`, `/etc/inc/ipsec.inc` |
| **OpenVPN** | SSL/TLS Site-to-Site and Client-to-Gateway, TUN/TAP modes | VERIFIED | `openvpn-2.7.7`, `/etc/inc/openvpn.inc` |
| **WireGuard** | Modern high-speed cryptographic UDP tunnel | VERIFIED | `pfSense-pkg-WireGuard-0.2.13_4`, `wireguard-pfsense` |

---

## 9. Traffic Shaping & Quality of Service (QoS)

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **ALTQ Shaper** | HFSC, CBQ, PRIQ queuing disciplines on interfaces | VERIFIED | `/etc/inc/shaper.inc`, pf ALTQ directives |
| **Limiters (dummynet)** | Bandwidth capping and delay simulation via dummynet pipes/queues| VERIFIED | `dnctl`, `/etc/inc/shaper.inc`, `<dnshaper>` XML |
| **Fair Queueing (CoDel)** | Active Queue Management (FQ-CoDel) bufferbloat mitigation | VERIFIED | dummynet / ALTQ FQ-CoDel extensions |

---

## 10. Captive Portal

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **Web Redirection** | Intercepting unauthenticated HTTP/HTTPS traffic to login page | VERIFIED | `/etc/inc/captiveportal.inc`, pf anchor rules |
| **Authentication Backends**| Local users, external RADIUS, Voucher system | VERIFIED | `voucher-0.1_3`, `php85-pecl-radius` |
| **Session Control** | Inactivity timeout, hard timeout, bandwidth throttling per user | VERIFIED | `ipfw` / `pf` captive portal state tables |

---

## 11. High Availability (HA) & Clustering

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **CARP (Common Address Redundancy)** | Shared Virtual IP failover across master/backup nodes | VERIFIED | FreeBSD CARP protocol, `/etc/rc.carpmaster` |
| **pfsync** | Real-time state-table replication over dedicated sync interface | VERIFIED | `/dev/pfsync`, FreeBSD kernel |
| **XMLRPC Config Sync** | Automatic propagation of configuration XML changes to backup nodes | VERIFIED | `/etc/inc/xmlrpc_client.inc`, `XML_RPC2` |

---

## 12. Diagnostics & System Telemetry

| Feature | Description | ISO Verification Status | Technical Foundation |
| :--- | :--- | :--- | :--- |
| **Network Tools** | Ping, Traceroute, DNS lookup, ARP table, Route table | VERIFIED | Standard BSD binaries + PHP UI helpers |
| **Packet Capture** | Live interface packet inspection and `.pcap` export | VERIFIED | `tcpdump`, `/usr/sbin/tcpdump` |
| **Socket & Port Test** | TCP/UDP socket connectivity verification | VERIFIED | `nc` / `sockstat` |
| **RRD Monitoring** | Round Robin Database graphs for CPU, Memory, States, Traffic | VERIFIED | `rrdtool-1.9.0_1`, `php85-pecl-rrd` |
