"""
MitraNet Status Reloader & Event Watcher.
Native Linux replacement for FreeBSD check_reload_status.
Watches reload event queues and triggers systemd services or transaction reloads.
"""

import os
import typing
import logging

logger = logging.getLogger("mitranet-status-reloader")

class StatusReloaderService:
    """Handles event triggers to reload specific network/firewall subsystems."""

    SUPPORTED_SUBSYSTEMS = {
        "filter": "Reload nftables firewall rules",
        "routing": "Reload kernel routing tables",
        "interfaces": "Reapply network interface configurations",
        "dns": "Reload DNS resolver daemon",
        "gateway": "Reload gateway latency monitor",
    }

    def __init__(self, run_dir: str = "/run/mitranet"):
        self.run_dir = run_dir

    def trigger_reload(self, subsystem: str) -> bool:
        """Records a reload trigger flag for a subsystem."""
        if subsystem not in self.SUPPORTED_SUBSYSTEMS:
            raise ValueError(f"Unknown reload subsystem: {subsystem}")
        os.makedirs(self.run_dir, exist_ok=True)
        flag_path = os.path.join(self.run_dir, f"reload_{subsystem}.flag")
        with open(flag_path, "w", encoding="utf-8") as f:
            f.write("1\n")
        logger.info(f"Triggered reload for subsystem: {subsystem}")
        return True

    def check_and_clear_trigger(self, subsystem: str) -> bool:
        """Checks if a reload trigger exists and clears it."""
        flag_path = os.path.join(self.run_dir, f"reload_{subsystem}.flag")
        if os.path.exists(flag_path):
            try:
                os.remove(flag_path)
            except OSError:
                pass
            return True
        return False
