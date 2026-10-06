"""
MitraNet Table Expiry Service.
Native Linux replacement for FreeBSD expiretable.
Manages timed nftables sets or purges expired entries using nftables timeout syntax.
"""

import subprocess
import typing

class TableExpiryService:
    """Manages nftables dynamic set expiry."""

    def __init__(self, table_name: str = "inet mitranet"):
        self.table_name = table_name

    def create_expiring_set(self, set_name: str, timeout_seconds: int = 3600) -> str:
        """Generates nftables rule for creating a set with automatic entry timeout."""
        return (
            f"add set {self.table_name} {set_name} "
            f"{{ type ipv4_addr; flags timeout; timeout {timeout_seconds}s; }}"
        )

    def flush_set(self, set_name: str) -> bool:
        """Executes 'nft flush set <table_name> <set_name>'."""
        cmd = ["nft", "flush", "set"] + self.table_name.split() + [set_name]
        res = subprocess.run(cmd, capture_output=True, text=True, check=False)
        return res.returncode == 0
