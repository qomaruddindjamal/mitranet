# MITRANET — VPN ARCHITECTURE & IMPLEMENTATION

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. Supported Technologies
- **WireGuard**: Kernel-native high-throughput peer-to-peer and site-to-site tunnels (`wg0`).
- **OpenVPN**: TLS-authenticated client-to-site remote access daemon.
- **IPsec / StrongSwan**: Enterprise policy-based IKEv2 site-to-site VPN.
- **Xray / VLESS**: Next-generation obfuscated traffic tunneling.

## 2. Status & Integration
- WireGuard, OpenVPN, and StrongSwan are monitored via `api/REST/server.py` (`/api/vpn`).
- Dedicated tunnel interfaces route securely into firewall zones (e.g. `VPN` zone).
