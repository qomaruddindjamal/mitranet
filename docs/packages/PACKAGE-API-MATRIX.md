# MITRANET — PACKAGE API MATRIX

**Official Identity:** MitraNet 0.1.0-dev (Code OS: Rinjani)

## 1. REST API Integration Mapping
- **Direct Feature Packages**: Mapped directly to dedicated `/api/*` endpoints (e.g. `wireguard-mitranet.pkg` -> `/api/vpn`, `nginx-*.pkg` -> HTTP Web Gateway, `dnsmasq-*.pkg` -> `/api/diagnostics/dhcp_leases`).
- **Runtime Dependency Packages**: Dynamic shared libraries supporting backend execution engines without individual REST routes.
- **System Utilities & Diagnostics**: Mapped to diagnostic endpoint suites (`/api/diagnostics/traceroute`, `/api/diagnostics/arp`, `/api/diagnostics/tcpdump`).
