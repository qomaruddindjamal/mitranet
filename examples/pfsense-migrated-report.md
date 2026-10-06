# MitraNet Migration Report
- **Source**: pfSense (config.xml, v24.5)
- **Target**: MitraNet (JSON, schema v1.0)

## Summary
- **Migrated**: 8
- **Partial**: 0
- **Unsupported**: 1
- **Failed**: 0

## Migration Item Details
| Category | Feature | Status | Reason / Details |
| :--- | :--- | :--- | :--- |
| System | General Settings | **MIGRATED** | Hostname pfSense.home.arpa |
| System | FreeBSD powerd CPU governor | **UNSUPPORTED** | FreeBSD powerd is specific to BSD; Linux CPU governors (cpufreq) are managed via kernel. |
| Interfaces | Interface wan (em0) | **MIGRATED** | Mapped to Linux device 'eth0' |
| Interfaces | Interface lan (em1) | **MIGRATED** | Mapped to Linux device 'eth1' |
| Firewall | Rule 0100000101 | **MIGRATED** | ACCEPT on lan |
| Firewall | Rule 0100000102 | **MIGRATED** | ACCEPT on lan |
| NAT | Outbound NAT | **MIGRATED** | Configured nftables postrouting masquerade |
| DHCP | DHCPv4 Pool on lan | **MIGRATED** | 192.168.1.100 - 192.168.1.199 |
| DNS | Unbound DNS Resolver | **MIGRATED** | DNSSEC=enabled |

## Unsupported Items (Retained in Metadata)
- **FreeBSD powerd CPU governor**: FreeBSD powerd is specific to BSD; Linux CPU governors (cpufreq) are managed via kernel.