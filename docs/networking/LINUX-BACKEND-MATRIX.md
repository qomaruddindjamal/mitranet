# Linux Backend Technology Matrix for MitraNet

This matrix evaluates and defines the specific Linux subsystem components selected for the MitraNet Debian core, specifying why each component was chosen and how it is applied.

| Networking Domain | Chosen Linux Component | Alternative Evaluated | Decision Rationale & MitraNet Application |
| :--- | :--- | :--- | :--- |
| **Packet Filtering & Firewall**| `nftables` + `conntrack` | `iptables`, `pf` on FreeBSD, `ebtables` | Modern successor to iptables; atomic transactions (`nft -f`); unified syntax for IPv4/IPv6; integrated sets and maps; native kernel conntrack tracking. |
| **Kernel Flow Acceleration** | Netfilter `flowtable` / Fastpath | DPDK, XDP/eBPF | Provides zero-overhead fastpath routing for established connections directly in Linux kernel, similar to RouterOS FastPath. |
| **Network Interface & IP Stack**| `pyroute2` / Linux Netlink | `ifconfig`, shell `ip` exec | Direct binary Netlink socket protocol communication. Guarantees structured error handling and atomic updates without parsing ad-hoc CLI stdout. |
| **802.1Q / Bridge Switching** | Linux `bridge` (`vlan_filtering 1`) | Open vSwitch (OVS) | Standard in-kernel bridge with modern per-VLAN filtering. Extremely lightweight, full hardware offload support on Switchdev hardware without OVS overhead. |
| **Link Aggregation** | Linux `bonding` (802.3ad LACP) | `teamd` / Open vSwitch | High stability, built directly into Linux mainline kernel; supports standard 802.3ad dynamic aggregation and active-backup modes. |
| **Dynamic Routing** | FRRouting (`frr` suite) | BIRD, Quagga | Industry standard; actively maintained by Linux Foundation; comprehensive support for BGP, OSPFv2, OSPFv3, BFD, and EVPN-VXLAN. |
| **DHCP Subsystem** | ISC Kea DHCP (`kea-dhcp4`, `kea-dhcp6`) | ISC DHCPd (deprecated), Dnsmasq | Modern, high-performance, modular, fully JSON-configurable DHCP server with REST hook APIs; official replacement recommended by ISC. |
| **DNS Subsystem** | Unbound DNS Resolver | BIND9, Dnsmasq | High-security validating recursive caching resolver with built-in DNSSEC; matches pfSense baseline; low memory footprint. |
| **VPN: High Speed** | Linux in-kernel `WireGuard` | OpenVPN, IPsec | Highest throughput, lowest latency, minimal attack surface (~4,000 LOC in Linux kernel); cryptographic key routing. |
| **VPN: Enterprise IPsec** | strongSwan (`charon`, `swanctl`) | Libreswan | Standard Linux IKEv1/IKEv2 daemon with complete X.509, EAP, and RFC-compliant IPsec interoperability. |
| **Traffic Control / QoS** | Linux `tc` (`cake`, `fq_codel`, `htb`) | ALTQ (BSD), Dummynet | CAKE and FQ-CoDel provide state-of-the-art bufferbloat mitigation with zero-knob flow fairness; HTB provides hierarchical bandwidth guarantees. |
| **High Availability & VRRP** | `keepalived` + `conntrackd` | CARP + pfsync (BSD) | Standard Linux VRRP implementation combined with conntrackd for sub-second failover and state table cluster synchronization. |
| **Hardware Platform Telemetry** | Linux `hwmon` + `ethtool` + `libonlp` | Vendor proprietary SDKs | Open, vendor-neutral hardware inspection; accesses standard Linux sysfs for standard PC/servers and ONLP C library for OCP whitebox switches. |
