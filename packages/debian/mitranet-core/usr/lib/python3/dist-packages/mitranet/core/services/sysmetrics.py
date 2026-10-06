"""
MitraNet System Metrics Collector.
Native Linux replacement for FreeBSD cpustats, rate, and qstats.
Reads directly from Linux /proc and /sys filesystem.
"""

import os
import typing

class SystemMetricsCollector:
    """Collects CPU, memory, and interface network statistics from Linux kernel."""

    @staticmethod
    def get_cpu_stats() -> typing.Dict[str, typing.Any]:
        """Reads /proc/stat to compute overall CPU times."""
        if not os.path.exists("/proc/stat"):
            return {"user": 0, "system": 0, "idle": 0}
        with open("/proc/stat", "r", encoding="utf-8") as f:
            for line in f:
                if line.startswith("cpu "):
                    parts = [int(p) for p in line.split()[1:]]
                    user, nice, system, idle = parts[0], parts[1], parts[2], parts[3]
                    return {
                        "user": user + nice,
                        "system": system,
                        "idle": idle,
                        "total": sum(parts)
                    }
        return {"user": 0, "system": 0, "idle": 0}

    @staticmethod
    def get_memory_stats() -> typing.Dict[str, int]:
        """Reads /proc/meminfo."""
        mem = {}
        if os.path.exists("/proc/meminfo"):
            with open("/proc/meminfo", "r", encoding="utf-8") as f:
                for line in f:
                    parts = line.split(":")
                    if len(parts) == 2:
                        key = parts[0].strip()
                        val_str = parts[1].strip().split()[0]
                        if val_str.isdigit():
                            mem[key] = int(val_str)
        return mem

    @staticmethod
    def get_interface_traffic(interface: str) -> typing.Dict[str, int]:
        """Reads /proc/net/dev to get RX/TX bytes for a specific interface."""
        if not os.path.exists("/proc/net/dev"):
            return {"rx_bytes": 0, "tx_bytes": 0}
        with open("/proc/net/dev", "r", encoding="utf-8") as f:
            for line in f:
                if ":" in line:
                    iface, stats = line.split(":", 1)
                    if iface.strip() == interface:
                        vals = stats.split()
                        return {
                            "rx_bytes": int(vals[0]),
                            "rx_packets": int(vals[1]),
                            "tx_bytes": int(vals[8]),
                            "tx_packets": int(vals[9])
                        }
        return {"rx_bytes": 0, "tx_bytes": 0}
