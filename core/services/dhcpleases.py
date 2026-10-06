"""
MitraNet DHCP Leases Watcher.
Native Linux replacement for dhcpleases and dhcpleases6.
Parses standard Kea / dnsmasq / ISC DHCP leases files on Linux.
"""

import os
import re
import typing

class DHCPLeasesWatcher:
    """Parses DHCP leases files to track active host leases."""

    @staticmethod
    def parse_dnsmasq_leases(content: str) -> typing.List[typing.Dict[str, str]]:
        """
        Parses dnsmasq.leases format:
        <timestamp> <mac> <ip> <hostname> <client_id>
        """
        leases = []
        for line in content.splitlines():
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            parts = line.split()
            if len(parts) >= 4:
                leases.append({
                    "expiry": parts[0],
                    "mac": parts[1],
                    "ip": parts[2],
                    "hostname": parts[3] if parts[3] != "*" else "",
                })
        return leases

    @staticmethod
    def parse_isc_leases(content: str) -> typing.List[typing.Dict[str, str]]:
        """Parses ISC DHCPD lease format blocks."""
        leases = []
        lease_blocks = re.findall(r"lease\s+([0-9\.]+)\s*\{(.*?)\}", content, re.DOTALL)
        for ip, body in lease_blocks:
            mac_m = re.search(r"hardware\s+ethernet\s+([0-9a-fA-F:]+);", body)
            host_m = re.search(r'client-hostname\s+"([^"]+)";', body)
            ends_m = re.search(r"ends\s+\d+\s+([^;]+);", body)
            leases.append({
                "ip": ip,
                "mac": mac_m.group(1).lower() if mac_m else "",
                "hostname": host_m.group(1) if host_m else "",
                "expiry": ends_m.group(1).strip() if ends_m else "",
            })
        return leases
