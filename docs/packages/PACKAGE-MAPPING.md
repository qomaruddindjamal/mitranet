# MitraNet Functional Package Mapping Manifesto

## Executive Summary

A package count alone is not a valid coverage metric. One package may provide ten critical firewall functions, while several packages may implement overlapping utilities. In accordance with MitraNet Phase 2A non-negotiable rules, every source package is evaluated by its **functional capability**, mapped to its standard Debian/Linux native equivalent, and verified against MitraNet implementation architecture.

`
SOURCE PACKAGE / COMPONENT
        v
FUNCTIONALITY / ROLE
        v
MITRANET REQUIREMENT (Required / Optional / Replaced)
        v
DEBIAN / LINUX NATIVE EQUIVALENT
        v
MITRANET IMPLEMENTATION STRATEGY
        v
RUNTIME VALIDATION TARGET
        v
DECISION STATUS (PASS / DECIDED)
`

## Domain: Firewall & Packet Filtering

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| pf(4) kernel packet filter | **Required** | nftables (Linux Netfilter) | Native nftables rulesets & table management in core/engine | **PASS** |
| libpfctl (pf ioctl library) | **Required** | libnftnl / nftables cli | Native Python netlink/nftables bindings | **PASS** |
| pftop (state monitoring) | **Required** | conntrack-tools / iptstate / nft monitor | MitraNet connection tracker & state inspection CLI | **PASS** |
| filterlog (firewall log parser) | **Required** | systemd-journald / ulogd2 / nft log | MitraNet structured journal logging | **PASS** |
| filterdns (FQDN alias updater) | **Required** | dnsmasq ipset/nftset integration | MitraNet DNS-aware table resolver daemon | **PASS** |
| expiretable (table expiry daemon) | **Required** | nftables set timeout feature | In-kernel nftables stateful set timeouts | **PASS** |

## Domain: Routing & Interface Control

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| FreeBSD ifconfig / netgraph | **Required** | iproute2 (ip link, ip addr, ip route) | MitraNet Phase 1A-1F Network Engine | **PASS** |
| VLAN / 802.1Q tagging | **Required** | ip link type vlan | MitraNet Phase 1D VLAN Engine | **PASS** |
| Link Aggregation / LACP | **Required** | Linux bonding driver (mode 802.3ad) | MitraNet Phase 1D Bonding Engine | **PASS** |
| Linux Bridge / Layer 2 switching | **Required** | ip link type bridge | MitraNet Phase 1D Bridge Engine | **PASS** |
| VRF (Routing domains) | **Required** | ip link type vrf (Linux VRF Lite) | MitraNet Phase 1E VRF Engine | **PASS** |
| Static & Default Routing | **Required** | ip route / netlink RTM_NEWROUTE | MitraNet Phase 1C Routing Core | **PASS** |
| IGMP Proxy (Multicast) | **Optional** | igmpproxy (Debian package) | Debian standard igmpproxy daemon integration | **PASS** |
| dpinger (Gateway latency check) | **Required** | fping / iputils-ping / systemd-networkd | MitraNet gateway monitor healthcheck daemon | **PASS** |

## Domain: DHCP & Address Allocation

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| ISC DHCP 4.4 Server | **Required** | isc-dhcp-server / kea / dnsmasq | Debian native kea-dhcp4 / dnsmasq service | **PASS** |
| ISC DHCP 4.4 Client | **Required** | isc-dhcp-client / dhcpcd / systemd-networkd | Debian systemd-networkd / dhcpcd | **PASS** |
| ISC DHCP Relay | **Optional** | isc-dhcp-relay / kea-dhcp-ddns | Debian native isc-dhcp-relay | **PASS** |
| DHCPv6 Server & Client | **Required** | kea-dhcp6-server / radvd / wide-dhcpv6 | Debian kea-dhcp6 / radvd router advertisement | **PASS** |
| dhcpleases / dhcpleases6 | **Required** | dnsmasq lease file watcher / kea hooks | MitraNet DHCP lease parser & DNS sync service | **PASS** |
| choparp (Proxy ARP daemon) | **Optional** | ip neigh add proxy / parprouted | Linux in-tree kernel proxy ARP | **PASS** |

## Domain: DNS & Resolution

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| Unbound DNS Resolver | **Required** | unbound (Debian package) | Debian libunbound8 / unbound DNS resolver service | **PASS** |
| dnsmasq Forwarder | **Required** | dnsmasq (Debian package) | Debian dnsmasq caching forwarder | **PASS** |
| LDNS / Bind-tools | **Required** | bind9-dnsutils / ldnsutils | Debian bind9-dnsutils (dig, host, nslookup) | **PASS** |

## Domain: VPN & Secure Tunneling

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| WireGuard VPN (FreeBSD kmod) | **Required** | Linux in-tree wireguard + wireguard-tools | MitraNet native wireguard netlink/wg-quick integration | **PASS** |
| OpenVPN | **Required** | openvpn (Debian package) | Debian standard openvpn systemd service | **PASS** |
| IPsec / strongSwan | **Required** | strongswan (Debian package) | Debian strongSwan IKEv2 daemon & XFRM kernel integration | **PASS** |
| Xray-core (VLESS/VMess/Trojan) | **Required** | xray / v2ray-core (Linux amd64) | Native Linux amd64 binary & systemd service | **PASS** |
| MPD5 (PPTP/L2TP/PPPoE) | **Optional** | accel-ppp / pppd | Linux native accel-ppp daemon | **PASS** |

## Domain: Wireless & Connectivity

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| FreeBSD wlan / Hostapd | **Required** | hostapd + wpasupplicant + iw + wireless-regdb | Debian native hostapd AP daemon & nl80211 kernel drivers | **PASS** |
| Mobile Broadband Info | **Optional** | mobile-broadband-provider-info / ModemManager | Debian native mobile-broadband-provider-info | **PASS** |

## Domain: Virtualization & Hypervisor

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| bhyve Virtualization (kvm.pkg) | **Required** | Linux KVM + QEMU + libvirt | Linux kernel kvm_intel/kvm_amd + qemu-system-x86 | **PASS** |

## Domain: System Monitoring & Metrics

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| RRDtool (Graphs) | **Required** | rrdtool (Debian package) | Debian rrdtool + Prometheus/Grafana native telemetry | **PASS** |
| cpustats / rate / qstats | **Required** | sysstat / procps / ip -s | MitraNet system metrics collector (reads /proc & /sys) | **PASS** |
| Speedtest (Bandwidth) | **Required** | speedtest-cli (Debian package) | Debian speedtest-cli & official Ookla Linux package | **PASS** |
| iftop (Realtime traffic) | **Required** | iftop (Debian package) | Debian iftop network bandwidth monitor | **PASS** |
| smartmontools (Disk SMART) | **Required** | smartmontools (Debian package) | Debian smartmontools smartd service | **PASS** |
| ipmitool (Hardware IPMI) | **Optional** | ipmitool (Debian package) | Debian ipmitool utility | **PASS** |

## Domain: Configuration & Orchestration

| Source Component | MitraNet Requirement | Debian / Linux Equivalent | MitraNet Implementation Strategy | Status |
|---|---|---|---|---|
| pfSense config.xml | **Required** | MitraNet native config.json | MitraNet Native Schema & Transaction Engine | **PASS** |
| check_reload_status | **Required** | systemd / MitraNet event loop | MitraNet async event-driven transaction manager | **PASS** |
| minicron (Micro cron) | **Required** | systemd timers / cron | Debian systemd timer units | **PASS** |
