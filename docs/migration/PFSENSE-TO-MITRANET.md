# pfSense to MitraNet Migration Matrix

This matrix specifies the architectural translation from pfSense (FreeBSD-centric implementation) to MitraNet (Debian GNU/Linux kernel stack), strictly respecting functional equivalence without copying BSD-proprietary code.

| pfSense Feature | FreeBSD Implementation | Linux Equivalent Component | MitraNet Engine / Module | Migration Complexity | Priority | Phase 0 Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Stateful Firewall** | pf (`/dev/pf`, pfctl) | `nftables` + `conntrack` | `mitranet.network.firewall` | Medium | P1 (Core) | DESIGN ONLY |
| **Floating Rules** | pf match/pass rules | `nftables` priority chains (prerouting/postrouting) | `mitranet.network.firewall` | Medium | P1 | DESIGN ONLY |
| **Firewall Aliases** | pf tables (`pfctl -t`) | `nftables` named sets (`set`) & ipset | `mitranet.network.firewall` | Low | P1 | PROTOTYPE |
| **Outbound NAT (SNAT)** | pf `nat ... -> (...)` | `nftables` nat postrouting `masquerade` / `snat` | `mitranet.network.nat` | Low | P1 | DESIGN ONLY |
| **Port Forward (DNAT)** | pf `rdr ... -> ...` | `nftables` nat prerouting `dnat` | `mitranet.network.nat` | Low | P1 | DESIGN ONLY |
| **1:1 NAT (BINAT)** | pf `binat ... -> ...` | `nftables` dnat + snat paired rules | `mitranet.network.nat` | Medium | P1 | DESIGN ONLY |
| **Hairpin / NAT Reflection** | pf loopback rdr + nat | `nftables` hairpin prerouting/postrouting rules | `mitranet.network.nat` | Medium | P2 | DESIGN ONLY |
| **Static Routing** | FreeBSD route table | Linux Kernel FIB (`ip route`) | `mitranet.network.routing` | Low | P1 | PROTOTYPE |
| **Multi-WAN & Failover** | `dpinger` + pf `route-to` | `nftables` marks + `ip rule` (FIB) + `fping`/ping probe daemon | `mitranet.network.multiwan` | Medium | P1 | DESIGN ONLY |
| **Dynamic Routing** | `pfSense-pkg-frr` | FRRouting (`frr` - bgpd, ospfd, bfdd) | `mitranet.network.routing` | Medium | P2 | DESIGN ONLY |
| **Interface Management** | FreeBSD `ifconfig` | `iproute2` (`ip link`, `ip addr`) + `ethtool` | `mitranet.network.interfaces` | Low | P1 | PROTOTYPE |
| **VLAN (802.1Q)** | `ifconfig vlan create` | `ip link add link ... type vlan id ...` | `mitranet.network.interfaces` | Low | P1 | PROTOTYPE |
| **QinQ (802.1ad)** | `ifconfig vlan ... 802.1ad` | Nested 802.1ad/802.1q vlan subinterfaces | `mitranet.network.interfaces` | Medium | P2 | DESIGN ONLY |
| **Bonding / LACP** | FreeBSD `lagg` (lacp) | Linux `bonding` / `team` driver (`mode 4`) | `mitranet.network.interfaces` | Medium | P1 | DESIGN ONLY |
| **Network Bridge** | FreeBSD `ifconfig bridge` | Linux kernel bridge (`ip link add type bridge`) | `mitranet.network.interfaces` | Low | P1 | PROTOTYPE |
| **PPPoE Client** | `mpd5` / `ppp` | Linux `pppd` + `rp-pppoe` kernel module | `mitranet.network.interfaces` | Medium | P1 | DESIGN ONLY |
| **DHCPv4 Server** | Kea / ISC-DHCPd | Kea DHCPv4 (`kea-dhcp4-server`) / dnsmasq | `mitranet.services.dhcp` | Low | P1 | PROTOTYPE |
| **DHCPv6 & SLAAC** | `radvd` + Kea DHCPv6 | `kea-dhcp6-server` + `radvd` / `systemd-networkd` | `mitranet.services.dhcp` | Medium | P2 | DESIGN ONLY |
| **DNS Resolver** | `unbound` | `unbound` (Debian official package) | `mitranet.services.dns` | Low | P1 | PROTOTYPE |
| **IPsec VPN** | `strongswan` | strongSwan (`charon`, `swanctl`) | `mitranet.services.vpn.ipsec` | Medium | P1 | DESIGN ONLY |
| **OpenVPN** | `openvpn` | OpenVPN 2.6+ Linux package | `mitranet.services.vpn.openvpn` | Low | P1 | DESIGN ONLY |
| **WireGuard** | FreeBSD wg driver / Go | Linux in-kernel WireGuard (`wireguard-tools`) | `mitranet.services.vpn.wireguard`| Low | P1 | PROTOTYPE |
| **QoS / Traffic Shaping** | FreeBSD ALTQ / dummynet | Linux `tc` (Traffic Control) + `CAKE` / `FQ-CoDel` | `mitranet.network.qos` | High | P2 | DESIGN ONLY |
| **Captive Portal** | FreeBSD ipfw / pf tables | `nftables` captive chains + lightweight web daemon | `mitranet.services.portal` | High | P3 | DESIGN ONLY |
| **High Availability (VIP)** | FreeBSD CARP | Keepalived (`keepalived` VRRP v2/v3) | `mitranet.services.ha` | Medium | P2 | DESIGN ONLY |
| **State Synchronization** | FreeBSD `pfsync` | `conntrackd` (Linux connection tracking sync) | `mitranet.services.ha` | Medium | P2 | DESIGN ONLY |
| **Config Synchronization**| XMLRPC sync over HTTPS | Secure REST API / gRPC replication daemon | `mitranet.services.ha` | Medium | P2 | DESIGN ONLY |
| **System Diagnostics** | BSD tcpdump, sockstat | `tcpdump`, `ss`, `ip`, `traceroute`, `nft monitor` | `mitranet.tools.diagnostics` | Low | P1 | PROTOTYPE |
| **Hardware & Sensors** | FreeBSD `sysctl dev.*` | Linux `lm-sensors`, `smartmontools`, `ethtool` | `mitranet.core.hardware` | Low | P2 | DESIGN ONLY |

---

## Technical Translation Deep-Dive: Firewall Architecture

### pf (FreeBSD) vs nftables (MitraNet)

1. **State Tracking**:
   - *pfSense*: Relies on FreeBSD kernel `/dev/pf` state table entries with timeout tags.
   - *MitraNet*: Directly inspects and hooks Linux kernel `netfilter conntrack` table (`ct state established,related accept`).
2. **Rule Evaluation Flow**:
   - *pfSense*: "Last matching rule wins" unless marked with `quick`.
   - *MitraNet*: First matching terminal verdict wins in `nftables` (`accept`, `drop`, `reject`). The MitraNet compiler translates pf rule ordering into standard top-down nftables rules with strict index preservation.
3. **Table Aliases**:
   - *pfSense*: `<spamd> { 1.2.3.4, 5.6.7.8 }`
   - *MitraNet*: `set mitranet_alias_spamd { type ipv4_addr; elements = { 1.2.3.4, 5.6.7.8 } }`
