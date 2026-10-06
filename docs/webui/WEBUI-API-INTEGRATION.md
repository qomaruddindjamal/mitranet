# MITRANET — WEB UI REST API INTEGRATION SPECIFICATION

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Modular API Consumer Matrix
| Web UI Module | Target API Endpoints | Operation Type | Backend Handler |
|---|---|---|---|
| `interfaces/` | `/api/interfaces`, `/api/interfaces/set`, `/api/tunnel/*` | Read / Mutate | `enterprise_network_manager.py` / `config_engine.py` |
| `firewall/` | `/api/firewall/*`, `/api/firewall/rules`, `/api/firewall/apply` | Read / Apply | `firewall_manager.py` |
| `routing/` | `/api/routing` | Read / Update | `enterprise_network_manager.py` |
| `qos/` | `/api/qos/apply`, `/api/optimize` | Apply / Tune | `server.py` |
| `vpn/` | `/api/vpn`, `/api/vpn/xray` | Status / Setup | `enterprise_network_manager.py` |
| `zones/` | `/api/enterprise/zones`, `/api/enterprise/zones/policy` | Read / Policy | `enterprise_network_manager.py` |
| `diagnostics/` | `/api/diagnostics/*`, `/api/dhcp/leases`, `/api/config/*` | Query / Backup | `server.py` & `config_engine.py` |
| `hardware/` | `/api/hardware/sensors`, `/api/sfp`, `/api/mitranet/platform` | Telemetry | `mitranet_platform.py` |
| `terminal/` | `/api/terminal/exec` | Admin Shell | `server.py` (Administrator only) |

## 2. API Request Abstraction
Client requests are standardized via `apiFetch(endpoint, options)` in `public_html/includes/common.js`.
