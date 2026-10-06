# Enterprise Network Operating Systems Feature Matrix

This matrix compares major enterprise and open-source NOS platforms to synthesize behavioral models for MitraNet.

| Feature Category | Feature Name | pfSense | RouterOS | Cisco IOS XE / NX-OS | Juniper Junos | Arista EOS | VyOS | SONiC | OpenNetLinux | MitraNet Target Implementation |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Firewall** | Stateful packet filtering | Yes (pf) | Yes (iptables/nft) | Yes (ZBFW / ACL) | Yes (Stateless/SRX) | Yes (ACL) | Yes (nftables) | Basic ACL | No | **nftables + conntrack** |
| **NAT** | SNAT, DNAT, 1:1, Hairpin | Yes | Yes | Yes | Yes | Yes (Limited) | Yes | Yes | No | **nftables nat** |
| **Routing** | BGPv4 / BGP-4 MP | Pkg (FRR)| Yes | Yes | Yes | Yes | Yes (FRR) | Yes (FRR) | No | **FRRouting (bgpd)** |
| **Routing** | OSPFv2 & OSPFv3 | Pkg (FRR)| Yes | Yes | Yes | Yes | Yes (FRR) | Yes (FRR) | No | **FRRouting (ospfd)** |
| **Routing** | Policy-Based Routing (PBR) | Yes (route-to)| Yes (Mangle) | Yes (Route-maps) | Yes (Filter-based) | Yes | Yes | Yes | No | **nftables mark + ip rule** |
| **Routing** | ECMP / Multipath | Yes (Pool) | Yes | Yes | Yes | Yes | Yes | Yes | No | **Linux Kernel ECMP FIB** |
| **MPLS** | MPLS / LDP / VPLS | No | Yes | Yes | Yes | Yes | Yes | Basic | No | **Linux mpls + FRR ldpd** |
| **Overlay** | EVPN-VXLAN | No | Yes | Yes | Yes | Yes | Yes | Yes | No | **Linux vxlan + FRR evpn** |
| **L2 Switching**| Bridge VLAN filtering | Basic | Yes | Yes | Yes | Yes | Yes | Yes | Yes | **Linux VLAN Bridge** |
| **L2 Switching**| Hardware ASIC offload | No | Yes (CRS/CCR)| Yes (ASIC) | Yes (ASIC) | Yes (ASIC) | No | Yes (SAI) | Yes (Switchdev)| **Switchdev + DSA drivers**|
| **VPN** | WireGuard | Yes | Yes | No | No | No | Yes | No | No | **Linux WireGuard (in-kernel)**|
| **VPN** | IPsec (IKEv2) | Yes | Yes | Yes | Yes | Yes | Yes | Basic | No | **strongSwan (charon/swanctl)**|
| **VPN** | OpenVPN | Yes | Yes | No | No | No | Yes | No | No | **OpenVPN 2.6+ daemon** |
| **QoS** | Bandwidth Queueing / Shaper | Yes (ALTQ)| Yes (HTB/PCQ) | Yes (MQC) | Yes (CoS) | Yes | Yes (tc) | Yes (PFC/ECN) | No | **tc (HTB, CAKE, FQ-CoDel)**|
| **Multi-WAN** | Gateway probing & Failover | Yes (dpinger)| Yes (Netwatch/PCC) | Yes (IP SLA) | Yes (RPM) | Yes | Yes (WLB) | No | No | **mitranet-gwmon + ip rule** |
| **Services** | DHCPv4/v6 Server | Yes (Kea) | Yes | Yes | Yes | Yes | Yes (Kea) | DHCP Relay | No | **Kea DHCP / dnsmasq** |
| **Services** | DNS Resolver & Cache | Yes (Unbound)| Yes | Basic | Basic | Basic | Yes (PowerDNS)| No | No | **Unbound DNS Resolver** |
| **Services** | PPPoE Server / Client | Client | Server/Client| Server/Client | Server/Client | No | Server/Client| No | No | **accel-ppp / pppd** |
| **HA** | High Availability Cluster | Yes (CARP/pfsync)| Yes (VRRP) | Yes (HSRP/GLBP) | Yes (VRRP) | Yes (VARP/VRRP)| Yes (VRRP/conntrack)| Yes (Dual-ToR)| No | **Keepalived + conntrackd** |
| **Config** | Transactional & Rollback | No (Immediate) | Yes (Safe mode) | Yes (Archive/Rollback)| Yes (commit-confirm)| Yes (commit-replace)| Yes (commit)| Yes (Config DB)| No | **MitraNet Commit-Confirm**|
| **Telemetry** | SFP Optical DOM Monitoring | No | Yes | Yes | Yes | Yes | Yes | Yes | Yes (ONLP) | **mitranet-platformd + ONLP**|
| **Management**| Web GUI | Yes (PHP) | Yes (WebFig) | Optional | Optional | CloudVision | Optional | Optional | No | **MitraNet WebGUI (React/FastAPI)**|
| **Management**| Hierarchical CLI | Menu only | Yes | Yes | Yes | Yes | Yes | Yes (click) | Bash only | **MitraNet CLI (Click/Prompt)**|
