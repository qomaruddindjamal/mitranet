# MITRANET — FIREWALL & NAT ARCHITECTURE

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Packet Processing Pipeline

```text
PACKET INGRESS (Physical / Virtual Interface)
        ↓
PRE-ROUTING (DNAT / Port Forwarding / Blacklist Check)
        ↓
ZONE CLASSIFICATION (WAN / LAN / DMZ / VPN / MGMT)
        ↓
FORWARD FILTERING (Stateful connection matching & ACL rules)
        ↓
POST-ROUTING (SNAT / Masquerade translation)
        ↓
PACKET EGRESS
```

## 2. Manager Integration

- Fully controlled by `api/REST/firewall_manager.py`.
- Provides atomic rule generation, state verification, blacklist enforcement, and log tracking.
- Zero dual-engine conflicts: single authoritative manager.
