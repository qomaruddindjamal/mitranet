# MitraNet Rinjani 1.0.2 — Firewall Rule Model Specification

## 1. Pydantic v2 Models

All firewall rules and table configurations are strongly typed using Pydantic v2.

### `FirewallRule` Attributes

| Field | Type | Default | Description |
|---|---|---|---|
| `id` | `str` | *required* | Unique identifier (alphanumeric + hyphen/underscore). |
| `enabled` | `bool` | `True` | Administrative state. |
| `family` | `FirewallFamily` | `inet` | Protocol family (`inet`, `ipv4`, `ipv6`). |
| `direction` | `FirewallDirection`| `in` | Chain target: `in` (input), `out` (output), `forward` (forward). |
| `interface` | `str` | `"any"` | Ingress network device (e.g., `enp0s3`, `veth0`). |
| `out_interface` | `Optional[str]` | `None` | Egress network device for forward/output chains. |
| `protocol` | `FirewallProtocol` | `any` | Protocol: `tcp`, `udp`, `tcp_udp`, `icmp`, `icmpv6`, etc. |
| `source` | `str` | `"any"` | Source IPv4/IPv6 host or CIDR. |
| `source_ports`| `List[str]` | `[]` | Source port list or ranges (e.g. `["1024-65535"]`). |
| `destination` | `str` | `"any"` | Destination IPv4/IPv6 host or CIDR. |
| `destination_ports`| `List[str]` | `[]` | Destination port list (e.g. `["80", "443"]`). |
| `states` | `List[FirewallConntrackState]` | `[]` | Conntrack states (`new`, `established`, `related`, `invalid`). |
| `action` | `FirewallAction` | `accept` | Verdict: `accept`, `drop`, `reject`. |
| `log` | `bool` | `False` | Kernel logging flag. |
| `log_prefix` | `Optional[str]` | `None` | Kernel syslog prefix (max 64 chars). |
| `counter` | `bool` | `True` | Maintain nftables packet and byte counter. |
| `priority` | `int` | `100` | Ordering priority (1–65535, lower is higher priority). |

---

## 2. Default Baseline Policy (`FirewallPolicy`)

- `input_default`: `drop`
- `forward_default`: `drop`
- `output_default`: `accept`
- `established_related_accept`: `True` (`ct state { established, related } accept`)
- `invalid_drop`: `True` (`ct state invalid drop`)
- `loopback_accept`: `True` (`iifname "lo" accept`)
- `anti_lockout_enabled`: `True` (Mandatory SSH access on management interfaces `enp0s3:22`)
