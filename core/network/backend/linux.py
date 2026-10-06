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

    def set_interface_up(self, iface_name: str) -> bool:
        raise NotImplementedError

    def set_interface_down(self, iface_name: str) -> bool:
        raise NotImplementedError

    def set_mtu(self, iface_name: str, mtu: int) -> bool:
        raise NotImplementedError

    def set_mac_address(self, iface_name: str, mac: str) -> bool:
        raise NotImplementedError

    def add_address(self, iface_name: str, cidr: str) -> bool:
        raise NotImplementedError

    def remove_address(self, iface_name: str, cidr: str) -> bool:
        raise NotImplementedError

    def get_routes(self, family: str = "inet", table: Optional[int] = 254) -> List[Dict[str, Any]]:
        raise NotImplementedError

    def add_route(
        self,
        destination: str,
        family: str = "inet",
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        metric: Optional[int] = None,
        table: Optional[int] = 254,
    ) -> bool:
        raise NotImplementedError

    def remove_route(
        self,
        destination: str,
        family: str = "inet",
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        table: Optional[int] = 254,
    ) -> bool:
        raise NotImplementedError

    def replace_route(
        self,
        destination: str,
        family: str = "inet",
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        metric: Optional[int] = None,
        table: Optional[int] = 254,
    ) -> bool:
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
            err = (e.stderr or "").strip()
            # If the FIB table does not exist yet in the kernel, iproute2 exits with code 2
            if "FIB table does not exist" in err:
                return []
            logger.error("Error executing %s: %s", " ".join(cmd), err)
            raise BackendExecutionError(f"Linux kernel network query failed: {err}")
        except json.JSONDecodeError as e:
            logger.error("Malformed JSON received from %s: %s", " ".join(cmd), e)
            raise BackendExecutionError(f"Malformed JSON output from 'ip': {e}")

    def _exec_ip_cmd(self, command_args: List[str], timeout_sec: int = 5) -> bool:
        """Executes iproute2 write command with shell=False, timeout, and structured error handling."""
        cmd = ["ip"] + command_args
        try:
            res = subprocess.run(
                cmd,
                shell=False,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True,
                timeout=timeout_sec,
                check=True
            )
            return True
        except FileNotFoundError:
            logger.error("ip utility (iproute2) not found in system PATH.")
            raise BackendExecutionError("Required Linux networking tool 'ip' (iproute2) is not installed.")
        except subprocess.TimeoutExpired:
            logger.error("Command timed out: %s", " ".join(cmd))
            raise BackendExecutionError(f"Linux networking command timed out: {' '.join(cmd)}")
        except subprocess.CalledProcessError as e:
            err_msg = e.stderr.strip() or f"exit code {e.returncode}"
            logger.error("Error executing %s: %s", " ".join(cmd), err_msg)
            raise BackendExecutionError(f"Linux kernel operation failed: {err_msg}")

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

    def set_interface_up(self, iface_name: str) -> bool:
        return self._exec_ip_cmd(["link", "set", "dev", iface_name, "up"])

    def set_interface_down(self, iface_name: str) -> bool:
        return self._exec_ip_cmd(["link", "set", "dev", iface_name, "down"])

    def set_mtu(self, iface_name: str, mtu: int) -> bool:
        return self._exec_ip_cmd(["link", "set", "dev", iface_name, "mtu", str(mtu)])

    def set_mac_address(self, iface_name: str, mac: str) -> bool:
        return self._exec_ip_cmd(["link", "set", "dev", iface_name, "address", mac])

    def add_address(self, iface_name: str, cidr: str) -> bool:
        return self._exec_ip_cmd(["address", "add", cidr, "dev", iface_name])

    def remove_address(self, iface_name: str, cidr: str) -> bool:
        return self._exec_ip_cmd(["address", "del", cidr, "dev", iface_name])

    def get_routes(self, family: str = "inet", table: Optional[int] = 254) -> List[Dict[str, Any]]:
        """Queries kernel routing table using ip -j route show with family and table filter."""
        args: List[str] = []
        if family == "inet6":
            args.append("-6")
        args.extend(["route", "show"])
        if table is not None:
            args.extend(["table", str(table)])
        return self._run_ip_json(args)

    def _build_route_args(
        self,
        action: str,
        destination: str,
        family: str = "inet",
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        metric: Optional[int] = None,
        table: Optional[int] = 254,
    ) -> List[str]:
        args: List[str] = []
        if family == "inet6":
            args.append("-6")
        args.extend(["route", action, destination])
        if gateway:
            args.extend(["via", gateway])
        if interface:
            args.extend(["dev", interface])
        if metric is not None:
            args.extend(["metric", str(metric)])
        if table is not None:
            args.extend(["table", str(table)])
        return args

    def add_route(
        self,
        destination: str,
        family: str = "inet",
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        metric: Optional[int] = None,
        table: Optional[int] = 254,
    ) -> bool:
        args = self._build_route_args("add", destination, family, gateway, interface, metric, table)
        return self._exec_ip_cmd(args)

    def remove_route(
        self,
        destination: str,
        family: str = "inet",
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        table: Optional[int] = 254,
    ) -> bool:
        args = self._build_route_args("del", destination, family, gateway, interface, None, table)
        return self._exec_ip_cmd(args)

    def replace_route(
        self,
        destination: str,
        family: str = "inet",
        gateway: Optional[str] = None,
        interface: Optional[str] = None,
        metric: Optional[int] = None,
        table: Optional[int] = 254,
    ) -> bool:
        args = self._build_route_args("replace", destination, family, gateway, interface, metric, table)
        return self._exec_ip_cmd(args)

