# MITRANET — REST API ENDPOINTS SPECIFICATION

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Configuration & Transaction Management
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/config/running` | Fetch active running configuration schema |
| `GET` | `/api/config/candidate` | Fetch current uncommitted candidate configuration |
| `GET` | `/api/config/diff` | Calculate structural delta between running and candidate |
| `GET` | `/api/config/status` | Run schema validation checks and report status |
| `POST` | `/api/config/set` | Update candidate configuration key-value |
| `POST` | `/api/config/validate` | Trigger pre-apply semantic validation |
| `POST` | `/api/config/dry-run` | Simulate transaction apply without persisting |
| `POST` | `/api/config/apply` | Apply candidate changes with atomic rollback safety |
| `POST` | `/api/config/rollback` | Revert to previous known-good backup snapshot |

## 2. Telemetry & Core Subsystems
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/status` | Real-time system telemetry and metrics |
| `GET` | `/api/interfaces` | Auto-detected physical and virtual network interfaces |
| `GET` | `/api/firewall/*` | Firewall rules, NAT, aliases, and IP blacklists |
| `GET` | `/api/vpn/*` | WireGuard, OpenVPN, and StrongSwan status |
| `GET` | `/api/services/status`| Core network daemons health status |
