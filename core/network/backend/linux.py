"""
Abstract and Concrete Linux Networking Backends.
Isolates all kernel access, rtnetlink, and /sys/class/net calls behind a clean interface.
"""

import json
import logging
import os
import subprocess
from typing import List, Dict, Any, Optional
from mitranet.core.network.exceptions import BackendExecutionError, InterfaceNotFoundError

logger = logging.getLogger("mitranet.network.backend")


class NetworkBackend:
    """Abstract interface for reading Linux kernel network device state."""

    def get_link_info(self) -> List[Dict[str, Any]]:
        raise NotImplementedError

    def get_addr_info(self) -> List[Dict[str, Any]]:
        raise NotImplementedError

    def get_carrier(self, iface_name: str) -> Optional[bool]:
        raise NotImplementedError

    def get_sys_statistics(self, iface_name: str) -> Optional[Dict[str, int]]:
        raise NotImplementedError


class LinuxNetworkBackend(NetworkBackend):
    """Production Linux Network Backend querying iproute2 and /sys/class/net."""

    def __init__(self, sys_class_net_path: str = "/sys/class/net"):
        self.sys_path = sys_class_net_path

    def _run_ip_json(self, command_args: List[str]) -> List[Dict[str, Any]]:
        cmd = ["ip", "-j"] + command_args
        try:
            res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, check=True)
            return json.loads(res.stdout) if res.stdout.strip() else []
        except FileNotFoundError:
            logger.error("ip utility (iproute2) not found in system PATH.")
            raise BackendExecutionError("Required Linux networking tool 'ip' (iproute2) is not installed.")
        except subprocess.CalledProcessError as e:
            logger.error("Error executing %s: %s", " ".join(cmd), e.stderr)
            raise BackendExecutionError(f"Linux kernel network query failed: {e.stderr.strip()}")
        except json.JSONDecodeError as e:
            logger.error("Malformed JSON received from %s: %s", " ".join(cmd), e)
            raise BackendExecutionError(f"Malformed JSON output from 'ip': {e}")

    def get_link_info(self) -> List[Dict[str, Any]]:
        return self._run_ip_json(["link", "show"])

    def get_addr_info(self) -> List[Dict[str, Any]]:
        return self._run_ip_json(["addr", "show"])

    def get_carrier(self, iface_name: str) -> Optional[bool]:
        carrier_path = os.path.join(self.sys_path, iface_name, "carrier")
        if not os.path.exists(carrier_path):
            return None
        try:
            with open(carrier_path, "r", encoding="utf-8") as f:
                val = f.read().strip()
                if val == "1":
                    return True
                elif val == "0":
                    return False
        except (OSError, IOError) as e:
            logger.debug("Carrier unavailable for %s: %s", iface_name, e)
        return None

    def get_sys_statistics(self, iface_name: str) -> Optional[Dict[str, int]]:
        stats_dir = os.path.join(self.sys_path, iface_name, "statistics")
        if not os.path.isdir(stats_dir):
            return None

        stats: Dict[str, int] = {}
        stat_fields = [
            "rx_bytes", "rx_packets", "rx_errors", "rx_dropped",
            "tx_bytes", "tx_packets", "tx_errors", "tx_dropped"
        ]
        for field in stat_fields:
            field_file = os.path.join(stats_dir, field)
            if os.path.exists(field_file):
                try:
                    with open(field_file, "r", encoding="utf-8") as f:
                        stats[field] = int(f.read().strip())
                except (ValueError, OSError):
                    stats[field] = 0
            else:
                stats[field] = 0
        return stats
