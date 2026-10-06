"""
MitraNet Proxy ARP Service.
Native Linux replacement for choparp.
Configures Linux kernel in-tree proxy ARP or adds explicit proxy neighbor entries.
"""

import os
import subprocess
import typing

class ProxyARPService:
    """Configures proxy ARP natively on Linux interfaces."""

    @staticmethod
    def set_interface_proxy_arp(interface: str, enabled: bool) -> bool:
        """Sets /proc/sys/net/ipv4/conf/<interface>/proxy_arp."""
        path = f"/proc/sys/net/ipv4/conf/{interface}/proxy_arp"
        if not os.path.exists(path):
            return False
        val = "1\n" if enabled else "0\n"
        with open(path, "w", encoding="utf-8") as f:
            f.write(val)
        return True

    @staticmethod
    def add_proxy_neighbor(ip_address: str, interface: str) -> bool:
        """Executes 'ip neigh add proxy <ip> dev <dev>'."""
        cmd = ["ip", "neigh", "add", "proxy", ip_address, "dev", interface]
        res = subprocess.run(cmd, capture_output=True, text=True, check=False)
        return res.returncode == 0

    @staticmethod
    def del_proxy_neighbor(ip_address: str, interface: str) -> bool:
        """Executes 'ip neigh del proxy <ip> dev <dev>'."""
        cmd = ["ip", "neigh", "del", "proxy", ip_address, "dev", interface]
        res = subprocess.run(cmd, capture_output=True, text=True, check=False)
        return res.returncode == 0
