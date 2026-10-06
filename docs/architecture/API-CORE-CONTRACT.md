# MITRANET — REST API & CORE SERVICE CONTRACT

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## Canonical Endpoint Contract

| Endpoint Group | HTTP Methods | Core Manager | Description |
|---|---|---|---|
| `/api/interfaces` | GET, POST, PUT, DELETE | `enterprise_network_manager.py` | Physical and virtual interfaces |
| `/api/addresses` | GET, POST, DELETE | `enterprise_network_manager.py` | IP address allocations |
| `/api/routes` | GET, POST, DELETE | `enterprise_network_manager.py` | Static and policy routes |
| `/api/vlans` | GET, POST, DELETE | `enterprise_network_manager.py` | 802.1Q VLAN definitions |
| `/api/bridges` | GET, POST, DELETE | `enterprise_network_manager.py` | L2 Bridge groups |
| `/api/bonds` | GET, POST, DELETE | `enterprise_network_manager.py` | LACP / Active-Backup bonds |
| `/api/firewall/*` | GET, POST, PUT, DELETE | `firewall_manager.py` | Rules, NAT, aliases, blacklists |
| `/api/vpn/*` | GET, POST, DELETE | `enterprise_network_manager.py` | WireGuard, OpenVPN, IPsec |
| `/api/platform/*` | GET | `mitranet_platform.py` | Hardware, telemetry, SFP, sensors |
| `/api/system/*` | GET, POST | `server.py` | Users, authentication, backups |
