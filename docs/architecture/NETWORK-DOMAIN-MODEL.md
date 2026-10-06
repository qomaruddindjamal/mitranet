# MITRANET — CANONICAL NETWORK DOMAIN MODEL

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## Domain Entity Model

All network resources in MitraNet conform to standard canonical domain entities:

| Entity | Key Attributes | Configuration Owner | Runtime Representation | API Resource |
|---|---|---|---|---|
| `Interface` | name, type, mac, mtu, state, speed | Core Config | `/sys/class/net/*` | `/api/interfaces` |
| `Address` | iface, ip, prefix, family, scope | Core Config | Netlink `RTM_NEWADDR` | `/api/addresses` |
| `Route` | dst, gateway, iface, metric, table | Core Config | Netlink `RTM_NEWROUTE` | `/api/routes` |
| `Vlan` | vlan_id, parent, mtu, state | Core Config | Netlink link type `vlan` | `/api/vlans` |
| `Bridge` | name, members, stp, aging_time | Core Config | Netlink link type `bridge` | `/api/bridges` |
| `Bond` | name, members, mode, lacp_rate | Core Config | Netlink link type `bond` | `/api/bonds` |
| `Zone` | name, interfaces, default_policy | Firewall Mgr | Ingress/Egress Chain Set | `/api/zones` |
| `FirewallRule` | id, zone, proto, src, dst, port, action | Firewall Mgr | Packet Filter Ruleset | `/api/firewall/rules` |
| `NatPolicy` | id, type (SNAT/DNAT), iface, target | Firewall Mgr | NAT Table / Conntrack | `/api/nat` |
| `QosPolicy` | iface, bandwidth, queues, scheduler | Core Config | `tc` / Queue Discs | `/api/qos` |
| `VpnTunnel` | type (WireGuard/OpenVPN), endpoint, keys | Network Mgr | `wg0` / `tun0` interface | `/api/vpn` |
| `DhcpService` | range_start, range_end, subnet, leases | Core Config | `dhcpd` / `dnsmasq` leases | `/api/dhcp` |
| `DnsService` | listen_addr, upstreams, domain_overrides | Core Config | `unbound.conf` / socket | `/api/dns` |
