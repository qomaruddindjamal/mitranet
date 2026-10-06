# MITRANET — FIREWALL & NAT ARCHITECTURE & IMPLEMENTATION

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Firewall Pipeline
Managed authoritatively via `api/firewall_manager.py`:
- Ingress, Egress, and Forward packet filtering chains.
- Stateful connection tracking (`conntrack`).
- Real-time threat blacklist mitigation and SYN flood protection.
- Port aliases and structured zone grouping (WAN, LAN, DMZ, VPN).

## 2. NAT Subsystem
- **SNAT / Masquerade**: Outbound translation for LAN clients accessing the WAN.
- **DNAT / Port Forwarding**: Exposing internal server ports to external interfaces.
- **Hairpin NAT**: Enabling internal clients to access port-forwarded services via external IP.
