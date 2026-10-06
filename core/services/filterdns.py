"""
MitraNet FilterDNS Service.
Native Linux replacement for FreeBSD filterdns.
Periodically resolves FQDN aliases and updates nftables sets dynamically.
"""

import socket
import typing
import subprocess

class FilterDNSService:
    """Resolves DNS aliases and updates nftables named sets."""

    def __init__(self, table_name: str = "inet mitranet"):
        self.table_name = table_name

    def resolve_fqdn(self, hostname: str) -> typing.List[str]:
        """Resolves hostname to IPv4 and IPv6 addresses."""
        ips = []
        try:
            addr_info = socket.getaddrinfo(hostname, None)
            for item in addr_info:
                ip = item[4][0]
                if ip not in ips:
                    ips.append(ip)
        except (socket.gaierror, socket.herror):
            pass
        return sorted(ips)

    def generate_nft_set_elements(self, set_name: str, ips: typing.List[str]) -> str:
        """Generates nftables syntax to populate or update a set."""
        if not ips:
            return ""
        elements = ", ".join(ips)
        return f"add element {self.table_name} {set_name} {{ {elements} }}"
