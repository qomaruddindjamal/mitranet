# MikroTik RouterOS to MitraNet Migration Matrix

## 1. Executive Summary
MikroTik RouterOS (RouterOS v7) is a widely deployed, Linux-kernel-derived proprietary network operating system celebrated for its flexible packet mangling, queueing disciplines (PCQ, HTB), Policy-based Equal Cost Multi-Path (ECMP), Per Connection Classifier (PCC), VRF, and carrier routing (BGP, OSPF, MPLS/VPLS, EVPN-VXLAN).

In MitraNet, RouterOS features represent **Priority 2 (Enhancement & Gap Elimination)**, enriching the baseline router capabilities beyond pfSense.

---

## 2. Comprehensive Feature Translation Matrix

| RouterOS Feature | RouterOS Technical Concept | Linux Equivalent Stack | MitraNet Architecture Module | Priority | Phase 0 Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Mangle Chains** | Pre/Postrouting mark packet, mark connection, mark routing | `nftables` meta mark, ct mark, set mark | `mitranet.network.firewall` | P1 | DESIGN ONLY |
| **PCC (Per Connection Classifier)** | Hashing 5-tuple fields (src, dst, port) divided by remainder | `nftables` jhash (`meta mark set jhash ...`) / conntrack marks | `mitranet.network.multiwan` | P1 | PROTOTYPE |
| **VRF (Virtual Routing and Forwarding)**| Routing tables isolation per tenant/interface | Linux VRF devices (`ip link add ... type vrf`) | `mitranet.network.vrf` | P2 | DESIGN ONLY |
| **BGP (Border Gateway Protocol)** | RouterOS BGPv4/v6 engine, route filters, communities | FRRouting (`frr` bgpd daemon) | `mitranet.network.routing` | P1 | DESIGN ONLY |
| **OSPFv2 / OSPFv3** | Dynamic interior link-state routing | FRRouting (`frr` ospfd / ospf6d daemon) | `mitranet.network.routing` | P1 | DESIGN ONLY |
| **BFD (Bidirectional Forwarding Detection)**| Sub-second path failure detection | FRRouting (`bfdd`) | `mitranet.network.routing` | P2 | DESIGN ONLY |
| **MPLS & LDP** | Label Distribution Protocol, MPLS forwarding | Linux Kernel MPLS FIB (`ip mpls`) + FRR `ldpd` | `mitranet.network.mpls` | P3 | DESIGN ONLY |
| **VXLAN / EVPN** | Layer 2 overlay over Layer 3 underlay | Linux VXLAN (`ip link add type vxlan`) + FRR EVPN | `mitranet.network.overlay` | P2 | DESIGN ONLY |
| **Queues & Simple Queue** | HTB hierarchy, bandwidth rate limiting | Linux `tc` (HTB qdisc + class + filter) | `mitranet.network.qos` | P1 | PROTOTYPE |
| **PCQ (Per Connection Queueing)** | Dynamic rate allocation per active host IP | Linux `tc-fq_codel` / `tc-cake` flow fairness | `mitranet.network.qos` | P2 | DESIGN ONLY |
| **FastPath / FastTrack** | Hardware bypass for established conntrack flows | Linux `nftables flowtable` (Netfilter Fastpath) | `mitranet.network.acceleration` | P1 | DESIGN ONLY |
| **Bridge VLAN Filtering** | L2 hardware offloaded or software VLAN tagged switch | Linux `bridge` with VLAN filtering enabled (`vlan_filtering 1`) | `mitranet.network.bridge` | P1 | PROTOTYPE |
| **PPPoE Server & Client**| Broadband access aggregator and subscriber termination | Linux `accel-ppp` (Carrier-grade) / `rp-pppoe` | `mitranet.services.pppoe` | P2 | DESIGN ONLY |
| **Hotspot Server** | Web-auth redirection, walled garden, RADIUS accounting | `nftables` captive redirect + `mitranet-portal` daemon | `mitranet.services.hotspot` | P3 | DESIGN ONLY |
| **IPsec (IKEv2 / Hardware crypto)**| IPsec transport and tunnel modes, crypto engine offload | Linux XFRM + strongSwan (`swanctl`) | `mitranet.services.vpn.ipsec` | P1 | DESIGN ONLY |
| **WireGuard** | WireGuard interface and peer configuration | Linux native kernel WireGuard module | `mitranet.services.vpn.wireguard`| P1 | PROTOTYPE |
| **Address Lists (Dynamic/Static)** | Grouping IPs for firewall matching, dynamic timeouts | `nftables` sets with timeout flag (`timeout 1h`) | `mitranet.network.firewall` | P1 | PROTOTYPE |
| **Raw Firewall Table** | Pre-conntrack packet dropping (DDoS defense, NOTRACK) | `nftables` chain prerouting raw (`type filter hook prerouting priority raw`) | `mitranet.network.firewall` | P2 | DESIGN ONLY |
| **RouterOS API / Winbox** | Binary API (8728) & Winbox GUI protocol | MitraNet REST / OpenAPI + gRPC + React WebGUI | `mitranet.api` | P1 | PROTOTYPE |
| **Safe Mode** | Automatic rollback on connection loss during reconfiguration | Transactional Commit-Confirm mechanism in MitraNet Engine | `mitranet.config.engine` | P1 | PROTOTYPE |
