# MITRANET — PACKAGE RUNTIME ARCHITECTURE

**Official Identity:**
- **Project**: MitraNet
- **Version**: 0.1.0-dev
- **Foundation**: MitraOS 1.0.0
- **Code OS**: Rinjani
- **Architecture**: amd64

## 1. Architectural Overview & Single Source of Truth

MitraNet runtime architecture operates across structured layers, establishing a unified hierarchy:

```text
  Web UI (public_html/ :80/:443)
        ↓
  REST API (api/REST/ :8080) / CLI / TUI
        ↓
  MitraNet Core & Configuration Engine (/conf/config.xml)
        ↓
  Network Services & Daemons (Unbound, Dnsmasq, DHCP, WireGuard, PF)
        ↓
  MitraOS 1.0.0 (Kernel / Sockets / Routing / Netlink / Conntrack)
```

### Configuration Ownership Principles
- **Single Source of Truth**: All configurations are owned and unified by `/conf/config.xml` managed by MitraNet Core.
- **No Rogue Writers**: Individual packages/services (e.g., Unbound, PF, DHCP) never modify configuration files directly. MitraNet Core generates runtime confs.
- **Reload Coordination**: Daemons are notified of configuration changes via `check_reload_status` IPC or dedicated signals.

## 2. Runtime Layers Summary

| Layer | Layer Title | Packages Count | Description |
|---|---|---|---|
| 0 | Layer 0: Boot & Package Bootstrap | 9 | Kernel, bootloader, microcode, pkg bootstrap |
| 1 | Layer 1: Base Runtime & Interpreters | 48 | Python 3.12, PHP 8.5, Tcl runtime engines |
| 2 | Layer 2: System Shared Libraries | 100 | Dynamic shared libraries, libcurl, OpenSSL, boost, ICU |
| 3 | Layer 3: Network & Kernel Dependencies | 5 | ARP proxy, radvd, rate shaper hooks |
| 4 | Layer 4: Wireless Subsystem | 2 | Hostapd, WPA Supplicant, 802.11 modules |
| 5 | Layer 5: Dynamic Routing & Switching | 1 | FRR, routing sockets, netlink interfaces |
| 6 | Layer 6: Firewall & NAT | 5 | PF packet filtering, NAT, miniupnpd |
| 7 | Layer 7: DHCP, DNS, & VPN Services | 17 | Unbound, Dnsmasq, ISC-DHCP, WireGuard, OpenVPN |
| 8 | Layer 8: Security & Intrusion Prevention | 2 | Suricata, SSHGuard, threat blacklist |
| 9 | Layer 9: Monitoring & Telemetry | 5 | BSNMP, RRDTool, telemetry collectors |
| 10 | Layer 10: MitraNet Core Foundation | 7 | Core daemons, system configuration synchronizer |
| 11 | Layer 11: Web Server & API Gateways | 3 | Nginx web server, reload status daemon, REST API |
| 12 | Layer 12: Support & Utilities | 0 | Administrative utilities and tools |
